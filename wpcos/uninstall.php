<?php
if(!defined('WP_UNINSTALL_PLUGIN')){
	exit();
}
if (get_option('xos_options')) {
	delete_option( 'xos_options' );
}
delete_option('wpcos_options');
update_option('upload_url_path', '');
