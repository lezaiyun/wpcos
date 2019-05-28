<?php
require_once 'sdk/cos-php-sdk-v5/vendor/autoload.php';


define( 'WPCOS_VERSION', 0.2 );  // 插件数据版本
define( 'WPCOS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );  // 插件路径
define('WPCOS_BASENAME', plugin_basename(__FILE__));
define('WPCOS_BASEFOLDER', plugin_basename(dirname(__FILE__)));

// 初始化选项
function wpcos_set_options() {
    $options = array(
	    'version' => WPCOS_VERSION,  # 0.2版本新增，用于以后当有数据结构升级时初始化数据
	    'bucket' => "",
		'region' => "",
		'app_id' => "",
		'secret_id' => "",
		'secret_key' => "",
		'no_local_file' => False,  # 不在本地保留备份
	    'cos_url_path' => '',
	);
	$wpcos_options = get_option('wpcos_options');
	if(!$wpcos_options){
		if (get_option('xos_options')) {
			wpcos_upgrade_options('wpcos');
		} else {
			add_option('wpcos_options', $options, '', 'yes');
		}
	};

	if ( isset($wpcos_options['cos_url_path']) && $wpcos_options['cos_url_path'] != '' ) {
		update_option('upload_url_path', $wpcos_options['cos_url_path']);
	}
}


/**
 *  插件升级时，数据更新函数
 *  @param string $plugin Path to the plugin file relative to the plugins directory.
 */
function wpcos_upgrade_options($plugin){
	if ($plugin == 'wpcos') {
		// 0.1版本升级时需要处理的部分
		$xos_options = get_option('xos_options');
		if($xos_options){
			$wpcos_options = array();
			if ($xos_options['no_local_file'] == 'false'){
				$xos_options['no_local_file'] = False;
			} else {
				$xos_options['no_local_file'] = True;
			}
			foreach ($xos_options as $k => $v) {
				$wpcos_options[$k] = $v;
			}
			$wpcos_options['version'] = WPCOS_VERSION;
			add_option('wpcos_options', $wpcos_options, '', 'yes');
			delete_option('xos_options');
		}
	}
}


function wpcos_restore_options () {
	$wpcos_options = get_option('wpcos_options');
	$wpcos_options['cos_url_path'] = get_option('upload_url_path');
	update_option('wpcos_options', $wpcos_options);
	update_option('upload_url_path', '');
}


/**
 * 删除本地文件
 * @param $file_path : 文件路径
 * @return bool
 */
function wpcos_delete_local_file($file_path) {
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


function wpcos_client () {
	// 获取参数
	$wpcos_options = get_option('wpcos_options', True);
	// 设置连接：
	$cosClient = new Qcloud\Cos\Client(
		array(
			'region' => $wpcos_options['region'],
			'credentials'=> array(
				'secretId'    => $wpcos_options['secret_id'],
				'secretKey' => $wpcos_options['secret_key']
			)
		)
	);
	return $cosClient;
}


/**
 * 判断cos中是否存在同名对象
 * @param $key  : 远端需要的Key值[包含路径]
 * @return bool  : 存在为True, 不存在为False.
 */
function wpcos_remote_file_exists ($key) {
	$cosClient = wpcos_client();
	$wpcos_options = get_option('wpcos_options');

	try {
		// 判断该key是否存在。如果存在则跳过，不存在/或有异常，则执行上传文件
		$cosClient->headObject(array(
			'Bucket' => $wpcos_options['bucket'],
			'Key' => $key,
		));
		return True;
	} catch (\Exception $e) {
		return False;
	}

}


/**
 * 文件上传功能基础函数，被其它需要进行文件上传的模块调用
 * @param $key  : 远端需要的Key值[包含路径]
 * @param $file_local_path : 文件在本地的路径。
 * @param array $opt  : 可选参数，暂时作为保留接口。
 * @param bool $no_local_file : 如果为真，则不在本地保留附件
 *
 * @return bool  : 暂未想好如何与wp进行响应。

*/
function wpcos_file_upload($key, $file_local_path, $opt = array(), $no_local_file = False) {
	$cosClient = wpcos_client();
	$wpcos_options = get_option('wpcos_options');

//	try {
//		// 判断该key是否存在。如果存在则跳过，不存在/或有异常，则执行上传文件
//		$cosClient->headObject(array(
//			'Bucket' => $wpcos_options['bucket'],
//			'Key' => $key,
//		));
//	} catch (\Exception $e) {
//		# 原先将上传安排在这里
//	}
	### 上传文件流
	  # 由于增加了独立文件名钩子对cos中同名文件的判断，避免同名文件的存在，因此这里直接覆盖上传。
	try {
		$cosClient->Upload(
			$wpcos_options['bucket'],
			$key,
			$body = fopen($file_local_path, 'rb')
		);
		// 如果上传成功，且不再本地保存，在此删除本地文件
		if ($no_local_file) {
			wpcos_delete_local_file($file_local_path);
		}
	} catch (\Exception $e) {
		return False;
	}
}


/**
 * 删除远程附件（包括图片的原图）
 * @param $post_id
 */
function wpcos_delete_remote_attachment($post_id) {
	// 获取要删除的对象Key的数组
	$deleteObjects = array();
	$meta = wp_get_attachment_metadata( $post_id );

	if (isset($meta['file'])) {
		$attachment_key = $meta['file'];
		array_push($deleteObjects, array( 'Key' => $attachment_key, ));
	} else {
		$file = get_attached_file( $post_id );
		$attached_key = str_replace( wp_get_upload_dir()['basedir'] . '/', '', $file );  # 不能以/开头
		$deleteObjects[] = array( 'Key' => $attached_key, );
	}

	if (isset($meta['sizes']) && count($meta['sizes']) > 0) {
		foreach ($meta['sizes'] as $val) {
			$attachment_thumbs_key = dirname($meta['file']) . '/' . $val['file'];
			$deleteObjects[] = array( 'Key' => $attachment_thumbs_key, );
		}
	}

	if ( !empty( $deleteObjects ) ) {
		// 执行删除远程对象
		$cosClient = wpcos_client();
		try {
			//删除文件
			$cosClient->deleteObjects(array(
				'Bucket' => esc_attr(get_option('wpcos_options')['bucket']),
				'Objects' => $deleteObjects,  # 经验证，这里的Key不能以/开头，否则将会删除失败
			));
		} catch (Exception $ex) {

		}
	}
}


/**
 * 上传图片及缩略图
 * @param $metadata: 附件元数据
 * @return array $metadata: 附件元数据
 * 官方的钩子文档上写了可以添加 $attachment_id 参数，但实际测试过程中部分wp接收到不存在的参数时会报错，上传失败，返回报错为“HTTP错误”
 */
function wpcos_upload_and_thumbs( $metadata ) {
	$wpcos_options = get_option('wpcos_options');
	$wp_uploads = wp_upload_dir();  # 获取上传路径

	if (isset( $metadata['file'] )) {
		# 1.先上传主图
		// wp_upload_path['base_dir'] + metadata['file']
		$attachment_key = '/' . $metadata['file'];  // 远程key路径
		$attachment_local_path = $wp_uploads['basedir'] . $attachment_key;  # 在本地的存储路径
		$opt = array('Content-Type' => $metadata['type']);  # 设置可选参数
		wpcos_file_upload($attachment_key, $attachment_local_path, $opt, $wpcos_options['no_local_file']);  # 调用上传函数
	}

//		$f = fopen(wp_upload_dir()['path'] . '/x.txt', 'w');
//		fwrite($f, var_export(array('k' => $attachment_key, 'loacl_path' => $attachment_local_path, 'opt' => $opt, 'id' => $attachment_id, 'meta' => $metadata), true));
//		fclose($f);

	# 如果存在缩略图则上传缩略图
	if (isset($metadata['sizes']) && count($metadata['sizes']) > 0) {

		// 文件名可能相同，上传操作时会判断是否存在，如果存在则不会执行上传。
		foreach ($metadata['sizes'] as $val) {
			$attachment_thumbs_key = '/' . dirname($metadata['file']) . '/' . $val['file'];  // 生成object在COS key
			$attachment_thumbs_local_path = $wp_uploads['basedir'] . $attachment_thumbs_key;  // 本地存储路径
			$opt = array('Content-Type' => $val['mime-type']);  //设置可选参数
			wpcos_file_upload($attachment_thumbs_key, $attachment_thumbs_local_path, $opt, $wpcos_options['no_local_file']);  //调用上传函数
			// 如果上传成功，且不再本地保存，在此删除本地文件
//				# 必须在这里处理是因为wp_handle_upload中不能删除文件，否则生成不了缩略图，且
//				# 必须使用wp_handle_upload处理附件上传，否则非图片格式附件的metadata中不一定含有'file'，造成无法上传。
//			if ($wpcos_options['no_local_file']) {
//				wpcos_delete_local_file( $attachment_thumbs_local_path );
//			}
		}
	}

	return $metadata;
}

/**
 * @param array  $upload {
 *     Array of upload data.
 *
 *     @type string $file Filename of the newly-uploaded file.
 *     @type string $url  URL of the uploaded file.
 *     @type string $type File type.
 * @return array  $upload
 */
function wpcos_upload_attachments ($upload) {
	$mime_types       = get_allowed_mime_types();
	$image_mime_types = array(
		// Image formats.
		$mime_types['jpg|jpeg|jpe'],
		$mime_types['gif'],
		$mime_types['png'],
		$mime_types['bmp'],
		$mime_types['tiff|tif'],
		$mime_types['ico'],
	);
	if ( ! in_array( $upload['type'], $image_mime_types ) ) {
		$key        = str_replace( wp_upload_dir()['basedir'], '', $upload['file'] );
		$local_path = $upload['file'];
		wpcos_file_upload( $key, $local_path, array( 'Content-Type' => $upload['type'] ), get_option('wpcos_options')['no_local_file'] );
	}

	return $upload;
}


/**
 * Filters the result when generating a unique file name.
 *
 * @since 4.5.0
 *
 * @param string        $filename                 Unique file name.
 * @param string        $ext                      File extension, eg. ".png".
 *
 * @return string New filename, if given wasn't unique.
 */
function wpcos_unique_filename( $filename, $ext ) {
	if ( !$ext ) {
		$ext = '.' . pathinfo( $filename, PATHINFO_EXTENSION );
	}

	$number = '';
	while ( wpcos_remote_file_exists( wp_get_upload_dir()['subdir'] . "/$filename") ) {
		$new_number = (int) $number + 1;
		if ( '' == "$number$ext" ) {
			$filename = "$filename-" . $new_number;
		} else {
			$filename = str_replace( array( "-$number$ext", "$number$ext" ), '-' . $new_number . $ext, $filename );
		}
		$number = $new_number;
	}

	return $filename;
}


// 在导航栏“设置”中添加条目
function wpcos_add_setting_page() {
	if (!function_exists('wpcos_setting_page')) {
		require_once 'wpcos_setting_page.php';
	}
	add_menu_page('WPCOS设置', 'WPCOS设置', 'manage_options', __FILE__, 'wpcos_setting_page');
}

// 在插件列表页添加设置按钮
function wpcos_plugin_action_links($links, $file) {
	if ($file == plugin_basename(dirname(__FILE__) . '/wpcos.php')) {
		$links[] = '<a href="admin.php?page=' . WPCOS_BASEFOLDER . '/wpcos_actions.php">设置</a>';
	}
	return $links;
}
