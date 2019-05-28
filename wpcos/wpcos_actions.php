<?php
require_once 'sdk/cos-php-sdk-v5/vendor/autoload.php';
define( 'WPCOS_VERSION', 0.2 );
define( 'WPCOS_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define('WPCOS_BASENAME', plugin_basename(__FILE__));
define('WPCOS_BASEFOLDER', plugin_basename(dirname(__FILE__)));
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
		}
	}
}
function wpcos_restore_options () {
	$wpcos_options = get_option('wpcos_options');
	$wpcos_options['cos_url_path'] = get_option('upload_url_path');
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
	try {
		$cosClient->headObject(array(
			'Bucket' => $wpcos_options['bucket'],
			'Key' => $key,
		));
		return True;
	} catch (\Exception $e) {
		return False;
	}
}
function wpcos_file_upload($key, $file_local_path, $opt = array(), $no_local_file = False) {
	$cosClient = wpcos_client();
	$wpcos_options = get_option('wpcos_options');
	try {
		$cosClient->Upload(
			$wpcos_options['bucket'],
			$key,
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
	$meta = wp_get_attachment_metadata( $post_id );
	if (isset($meta['file'])) {
		$attachment_key = $meta['file'];
		array_push($deleteObjects, array( 'Key' => $attachment_key, ));
	} else {
		$file = get_attached_file( $post_id );
		$attached_key = str_replace( wp_get_upload_dir()['basedir'] . '/', '', $file );
		$deleteObjects[] = array( 'Key' => $attached_key, );
	}
	if (isset($meta['sizes']) && count($meta['sizes']) > 0) {
		foreach ($meta['sizes'] as $val) {
			$attachment_thumbs_key = dirname($meta['file']) . '/' . $val['file'];
			$deleteObjects[] = array( 'Key' => $attachment_thumbs_key, );
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
function wpcos_upload_and_thumbs( $metadata, $attachment_id) {
	$wpcos_options = get_option('wpcos_options');
	$wp_uploads = wp_upload_dir();
	if (isset( $metadata['file'] )) {
		$attachment_key = '/' . $metadata['file'];
		$attachment_local_path = $wp_uploads['basedir'] . $attachment_key; 
	} else {
		$attachment_local_path = get_attached_file( $attachment_id );
		$attachment_key = str_replace( wp_get_upload_dir()['basedir'], '', $attachment_local_path );
	}
	$opt = array('Content-Type' => $metadata['type']);
	wpcos_file_upload($attachment_key, $attachment_local_path, $opt, $wpcos_options['no_local_file']);
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
function wpcos_add_setting_page() {
	if (!function_exists('wpcos_setting_page')) {
		require_once 'wpcos_setting_page.php';
	}
	add_menu_page('WPCOS设置', 'WPCOS设置', 'manage_options', __FILE__, 'wpcos_setting_page');
}
function wpcos_plugin_action_links($links, $file) {
	if ($file == plugin_basename(dirname(__FILE__) . '/wpcos.php')) {
		$links[] = '<a href="admin.php?page=' . WPCOS_BASEFOLDER . '/wpcos_actions.php">设置</a>';
	}
	return $links;
}
