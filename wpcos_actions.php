<?php
require_once 'sdk/cos-php-sdk-v5/vendor/autoload.php';
define( 'WPCOS_VERSION', 1.5 );
define( 'WPCOS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'WPCOS_BASENAME', plugin_basename(__FILE__) );
define( 'WPCOS_BASEFOLDER', plugin_basename(dirname(__FILE__)) );
function wpcos_set_options() {
    $options = array(
	    'version' => WPCOS_VERSION,
	    'bucket' => "",
		'region' => "",
		'app_id' => "",
		'secret_id' => "",
		'secret_key' => "",
		'no_local_file' => False,
	    'cos_url_path' => '',
	    'opt' => array(
	    	'auto_rename' => 0,
	    ),
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
function wpcos_upgrade_options($plugin){
	if ($plugin == 'wpcos') {
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
		} else {
			$wpcos_options = get_option('wpcos_options');
			if (!array_key_exists('opt', $wpcos_options)) {
				$wpcos_options['opt'] = array(
					'auto_rename' => 0,
				);
				update_option('wpcos_options', $wpcos_options);
			}
		}
	}
}
function wpcos_restore_options () {
	$wpcos_options = get_option('wpcos_options');
	$wpcos_options['cos_url_path'] = get_option('upload_url_path');
	if (!array_key_exists('opt', $wpcos_options)) {
		$wpcos_options['opt'] = array(
			'auto_rename' => 0,
		);
	}
	update_option('wpcos_options', $wpcos_options);
	update_option('upload_url_path', '');
}
function wpcos_delete_local_file($file_path) {
	try {
		if (!@file_exists($file_path)) {
			return TRUE;
		}
		if (!@unlink($file_path)) {
			return FALSE;
		}
		return TRUE;
	} catch (Exception $ex) {
		return FALSE;
	}
}
function wpcos_client () {
	$wpcos_options = get_option('wpcos_options', True);
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
function wpcos_remote_file_exists ($key) {
	$cosClient = wpcos_client();
	$wpcos_options = get_option('wpcos_options');
    $upload_url_path = get_option('upload_url_path');
	try {
		$cosClient->headObject(array(
			'Bucket' => $wpcos_options['bucket'],
			'Key' => wpcos_key_handle($key, $upload_url_path),
		));
		return True;
	} catch (\Exception $e) {
		return False;
	}
}
function wpcos_file_upload($key, $file_local_path, $opt = array(), $no_local_file = False) {
	$cosClient = wpcos_client();
	$wpcos_options = get_option('wpcos_options');
    $upload_url_path = get_option('upload_url_path');
	try {
		$cosClient->Upload(
			$wpcos_options['bucket'],
            wpcos_key_handle($key, $upload_url_path),
			$body = fopen($file_local_path, 'rb')
		);
		if ($no_local_file) {
			wpcos_delete_local_file($file_local_path);
		}
	} catch (\Exception $e) {
		return False;
	}
}
function wpcos_delete_remote_attachment($post_id) {
	$deleteObjects = array();
	$meta = wp_get_attachment_metadata( $post_id );  // 以下获取的key都不以/开头, 但该sdk方法必须非/开头
    $upload_url_path = get_option('upload_url_path');
	if (isset($meta['file'])) {
		$attachment_key = '/' . $meta['file'];
		array_push($deleteObjects, array( 'Key' => ltrim(wpcos_key_handle($attachment_key, $upload_url_path), '/'), ));
	} else {
		$file = get_attached_file( $post_id );
		if ($file) {
			$attached_key = '/' . str_replace( wp_get_upload_dir()['basedir'] . '/', '', $file );
			$deleteObjects[] = array( 'Key' => ltrim(wpcos_key_handle($attached_key, $upload_url_path), '/'), );
		}
	}
	if (isset($meta['sizes']) && count($meta['sizes']) > 0) {
		foreach ($meta['sizes'] as $val) {
			$attachment_thumbs_key = '/' . dirname($meta['file']) . '/' . $val['file'];
			$deleteObjects[] = array( 'Key' => ltrim(wpcos_key_handle($attachment_thumbs_key, $upload_url_path), '/'), );
		}
	}
	if ( !empty( $deleteObjects ) ) {
		$cosClient = wpcos_client();
		try {
			$cosClient->deleteObjects(array(
				'Bucket' => esc_attr(get_option('wpcos_options')['bucket']),
				'Objects' => $deleteObjects,
			));
		} catch (Exception $ex) {

		}
	}
}
function wpcos_upload_and_thumbs( $metadata ) {
	$wpcos_options = get_option('wpcos_options');
	$wp_uploads = wp_upload_dir();
	if (isset( $metadata['file'] )) {
		$attachment_key = '/' . $metadata['file'];
		$attachment_local_path = $wp_uploads['basedir'] . $attachment_key;
		$opt = array('Content-Type' => $metadata['type']);
		wpcos_file_upload($attachment_key, $attachment_local_path, $opt, $wpcos_options['no_local_file']);
	}
	if (isset($metadata['sizes']) && count($metadata['sizes']) > 0) {
		foreach ($metadata['sizes'] as $val) {
			$attachment_thumbs_key = '/' . dirname($metadata['file']) . '/' . $val['file'];
			$attachment_thumbs_local_path = $wp_uploads['basedir'] . $attachment_thumbs_key;
			$opt = array('Content-Type' => $val['mime-type']);
			wpcos_file_upload($attachment_thumbs_key, $attachment_thumbs_local_path, $opt, $wpcos_options['no_local_file']);
		}
	}
	return $metadata;
}
function wpcos_upload_attachments ($upload) {
	$mime_types       = get_allowed_mime_types();
	$image_mime_types = array(
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
function wpcos_unique_filename( $filename ) {
	$ext = '.' . pathinfo( $filename, PATHINFO_EXTENSION );
	$number = '';
    $upload_url_path = get_option('upload_url_path');
	while ( wpcos_remote_file_exists( wpcos_key_handle(wp_get_upload_dir()['subdir'] . "/$filename", $upload_url_path)) ) {
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

function wpcos_key_handle($key, $upload_url_path){
    # 参数2 为了减少option的获取次数
    $url_parse = wp_parse_url($upload_url_path);
    # 约定url不要以/结尾，减少判断条件
    if (array_key_exists('path', $url_parse)) {
        $key = $url_parse['path'] . $key;
    }
    return $key;
}

function wpcos_sanitize_file_name( $filename ){
	$wpcos_options = get_option('wpcos_options');
	if ($wpcos_options['opt']['auto_rename']) {
		return date("YmdHis") . "" . mt_rand(100, 999) . "." . pathinfo($filename, PATHINFO_EXTENSION);
	} else {
		return $filename;
	}
}

function wpcos_add_setting_page() {
	if (!function_exists('wpcos_setting_page')) {
		require_once 'wpcos_setting_page.php';
	}
	add_options_page('WPCOS设置', 'WPCOS设置', 'manage_options', __FILE__, 'wpcos_setting_page');
}
function wpcos_plugin_action_links($links, $file) {
	if ($file == plugin_basename(dirname(__FILE__) . '/wpcos.php')) {
		$links[] = '<a href="admin.php?page=' . WPCOS_BASEFOLDER . '/wpcos_actions.php">设置</a>';
	}
	return $links;
}

function wpcos_set_thumbsize($set_thumb){
	$wpcos_options = get_option('wpcos_options');
	if($set_thumb) {
		$wpcos_options['opt']['thumbsize'] = array(
			'thumbnail_size_w' => get_option('thumbnail_size_w'),
			'thumbnail_size_h' => get_option('thumbnail_size_h'),
			'medium_size_w'    => get_option('medium_size_w'),
			'medium_size_h'    => get_option('medium_size_h'),
			'large_size_w'     => get_option('large_size_w'),
			'large_size_h'     => get_option('large_size_h'),
			'medium_large_size_w' => get_option('medium_large_size_w'),
			'medium_large_size_h' => get_option('medium_large_size_h'),
		);
		update_option('thumbnail_size_w', 0);
		update_option('thumbnail_size_h', 0);
		update_option('medium_size_w', 0);
		update_option('medium_size_h', 0);
		update_option('large_size_w', 0);
		update_option('large_size_h', 0);
		update_option('medium_large_size_w', 0);
		update_option('medium_large_size_h', 0);
		update_option('wpcos_options', $wpcos_options);
	} else {
		if(isset($wpcos_options['opt']['thumbsize'])) {
			update_option('thumbnail_size_w', $wpcos_options['opt']['thumbsize']['thumbnail_size_w']);
			update_option('thumbnail_size_h', $wpcos_options['opt']['thumbsize']['thumbnail_size_h']);
			update_option('medium_size_w', $wpcos_options['opt']['thumbsize']['medium_size_w']);
			update_option('medium_size_h', $wpcos_options['opt']['thumbsize']['medium_size_h']);
			update_option('large_size_w', $wpcos_options['opt']['thumbsize']['large_size_w']);
			update_option('large_size_h', $wpcos_options['opt']['thumbsize']['large_size_h']);
			update_option('medium_large_size_w', $wpcos_options['opt']['thumbsize']['medium_large_size_w']);
			update_option('medium_large_size_h', $wpcos_options['opt']['thumbsize']['medium_large_size_h']);
			unset($wpcos_options['opt']['thumbsize']);
			update_option('wpcos_options', $wpcos_options);
		}
	}
}
function wpcos_legacy_data_replace() {
	global $wpdb;

	$wpcos_options = get_option('wpcos_options');
	$originalContent = home_url('/wp-content/uploads');
	$newContent = get_option('upload_url_path');

	# 文章内容文字/字符替换
	$result = $wpdb->query(
		"UPDATE {$wpdb->prefix}posts SET `post_content` = REPLACE( `post_content`, '{$originalContent}', '{$newContent}');"
	);

	$wpcos_options['opt']['wpcos_legacy_data_replace'] = 1;
	update_option('wpcos_options', $wpcos_options);
	return $wpcos_options;
}