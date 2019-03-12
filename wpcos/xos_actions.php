<?php
require_once 'xos_functions.php';
require_once 'sdk/cos-php-sdk-v5/vendor/autoload.php';

define( 'XOS_VERSION', '0.1' );
define( 'XOS_MINIMUM_WP_VERSION', '4.0' );  // 最早WP版本
define( 'XOS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );  // 插件路径
define('XOS_BASENAME', plugin_basename(__FILE__));
define('XOS_BASEFOLDER', plugin_basename(dirname(__FILE__)));

// 初始化选项
function xos_set_options() {
	$options = array(
		'bucket' => "",
		'region' => '',
		'app_id' => "",
		'secret_id' => "",
		'secret_key' => "",
		# 'no_remote_thumb' => "false",  # 不上传缩略图
		'no_local_file' => "false",  # 不在本地保留备份
	);
	if(!get_option('xos_options', False)){
		add_option('xos_options', $options, '', 'yes');
	};
}


/**
 * 删除本地文件
 * @param $file_path : 文件路径
 * @return bool
 */
function delete_local_file($file_path) {
	// var_dump($file_path);
	try {
		# 文件不存在
		if (!@file_exists($file_path)) {
			return TRUE;
		}
		# 删除文件
		if (!@unlink($file_path)) {
			return FALSE;
		}
		return TRUE;
	} catch (Exception $ex) {
		return FALSE;
	}
}


function client () {
	// 获取参数
	$xos_options = get_option('xos_options', True);
	// 设置连接：
	$cosClient = new Qcloud\Cos\Client(
		array(
			'region' => $xos_options['region'],
			'credentials'=> array(
				'secretId'    => $xos_options['secret_id'],
				'secretKey' => $xos_options['secret_key']
			)
		)
	);
	return $cosClient;
}


/**
 * 文件上传功能基础函数，被其它需要进行文件上传的模块调用
 * @param $key  : 远端需要的Key值[包含路径]
 * @param $file : 本地文件二进制数据流，需要读好数据传入进来。
 * @param array $opt  : 可选参数，暂时作为保留接口。
 *
 * @return bool  : 暂未想好如何与wp进行响应。
 */
function xos_file_upload($key, $file, $opt = array()) {
		$cosClient = client();

		try {
			// 判断该key是否存在。如果存在则跳过，不存在/或有异常，则执行上传文件
			$cosClient->headObject(array(
				'Bucket' => esc_attr(get_option('xos_options')['bucket']),
				'Key' => $key,
//		        'VersionId' => '111',
			));
		} catch (\Exception $e) {
			### 上传文件流
			try {
				$cosClient->Upload(
					esc_attr(get_option('xos_options')['bucket']),
					$key,
					$body = $file
				);
			} catch (\Exception $e) {
				return False;
			}
		}
//	}
}


/**对象存储对象删除功能基础函数，被其它需要进行删除的模块调用
 * @param $file : 文件名及路径
 *
 * @return mixed : 没啥好返回的，随便返回一个值。
 */
function xos_delete_remote_file($file) {
	$cosClient = client();

	//得到远程路径
	$del_file_path = str_replace(get_home_path(), '/', str_replace("\\", '/', $file));
	try {
		//删除文件
		$cosClient->deleteObject(array(
			//bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
			'Bucket' => esc_attr(get_option('xos_options')['bucket']),
			'Key' => $del_file_path
		));
	} catch (Exception $ex) {

	}
	// return $file;
}


/**
 * 删除附件（包括图片的原图）
 * @param $post_id
 */
function xos_delete_remote_attachment($post_id) {
	$meta = wp_get_attachment_metadata( $post_id );
	if (isset($meta['file'])) {
		$wp_uploads = wp_upload_dir();
		$file_path = $wp_uploads['basedir'] . '/' . $meta['file'];
		xos_delete_remote_file($file_path);
		
		if (isset($meta['sizes']) && count($meta['sizes']) > 0) {
			foreach ($meta['sizes'] as $val) {
				$size_file = dirname($file_path) . '/' . $val['file'];
				xos_delete_remote_file($size_file);
			}
		}
		
	}
}


/**
 * 上传附件（包括图片的原图）
 * @param $metadata
 * @return array()
 */
function xos_upload_attachments($metadata) {
	# 生成object在OSS中的存储路径
	if (get_option('upload_path') == '.') {
		//如果含有“./”则去除之
		$metadata['file'] = str_replace("./", '', $metadata['file']);
	}
	# 必须先替换\\, 因为get_home_path的输出格式为/
	$key = str_replace(get_home_path(), '', str_replace("\\", '/', $metadata['file']));;

	# 在本地的存储路径
	$file = fopen(get_home_path() . $key, 'rb');  //早期版本 $metadata['file'] 为相对路径

	# 设置可选参数
	$opt = array('Content-Type' => $metadata['type']);

	# 调用上传函数
	xos_file_upload('/' . $key, $file, $opt);

	# ( 删除主图文件如果在这里进行会导致无法生成缩略图，所以挪到下面处理缩略图的步骤进行 )如果不在本地保存，则删除本地文件
	#if (esc_attr(get_option('xos_options')['no_local_file']) == 'true') {
	#	delete_local_file(get_home_path() . $key);
	#}

	return $metadata;
}


/**
 * 上传图片的缩略图
 */
function xos_upload_thumbs($metadata) {
	# 上传所有缩略图
	if (isset($metadata['sizes']) && count($metadata['sizes']) > 0) {

		$xos_options = get_option('xos_options', True);

		# 若不上传缩略图则直接返回
		if (esc_attr($xos_options['no_remote_thumb']) == 'true') {
			return $metadata;
		}

		# 获取上传路径
		$wp_uploads = wp_upload_dir();
		//得到本地文件夹和远端文件夹
		$file_path = $wp_uploads['basedir'] . '/' . dirname($metadata['file']) . '/';
		if (get_option('upload_path') == '.') {
			$file_path = str_replace(get_home_path() . "./", '', str_replace("\\", '/', $file_path));
		} else {
			$file_path = str_replace("\\", '/', $file_path);
		}

		// 文件名可能相同，上传操作时会判断是否存在，如果存在则不会执行上传。
		foreach ($metadata['sizes'] as $val) {
			//生成object在COS中的存储路径
			$key = '/' . str_replace(get_home_path(), '', $file_path) . $val['file'];
			//生成本地存储路径
			$file = fopen($file_path . $val['file'], 'rb');
			//设置可选参数
			$opt = array('Content-Type' => $val['mime-type']);

			//执行上传操作
			xos_file_upload($key, $file, $opt);
			// 加if 因为会报警告$file文件流不存在，查看了代码在SDK里面已经处理掉了。
			# fclose($file);

			# 不保存本地文件则删除
			if (esc_attr($xos_options['no_local_file']) == 'true') {
				delete_local_file($file_path . $val['file']);
			}
		}
		// 删除主文件
		if (esc_attr($xos_options['no_local_file']) == 'true') {
	        delete_local_file($wp_uploads['basedir'] . '/' . $metadata['file']);
	    }
	}
	
	// $mf = fopen("/home/bae/app/wp-content/plugins/xos/file.log", "a");
	// # fwrite($mf, var_export($metadata, true));
	// fwrite($mf, $del_file_path);
	// fclose($mf);
	
	return $metadata;
}


// 在导航栏“设置”中添加条目
function xos_add_setting_page() {
//	add_options_page('WPCOS设置', 'WPCOS设置', 'manage_options', __FILE__, 'xos_setting_page');
	if (!function_exists('xos_setting_page')) {
		require_once 'xos_setting_page.php';
	}
	add_menu_page('WPCOS设置', 'WPCOS设置', 'administrator', __FILE__, 'xos_setting_page');
}
