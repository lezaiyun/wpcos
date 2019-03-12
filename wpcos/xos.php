<?php
/**
Plugin Name: WordPress COS（WPCOS）
Plugin URI: https://www.laobuluo.com/2186.html
Description: WordPress同步附件内容远程至腾讯云COS云存储中，实现网站数据与静态资源分离，提高网站加载速度。
Version: 0.1
Author: 老部落（By:zdl25）
Author URI: https://www.laobuluo.com
*/

require_once 'xos_actions.php';

# 插件 activation 函数当一个插件在 WordPress 中”activated(启用)”时被触发。
# $file — (string)(必须) — 主插件文件的路径。 $function — (string)(必须) — 当插件启用时要执行的函数。
register_activation_hook(__FILE__, 'xos_set_options');

# 避免上传插件/主题被同步到对象存储
if (substr_count($_SERVER['REQUEST_URI'], '/update.php') <= 0) {
	add_filter('wp_handle_upload', 'xos_upload_attachments');
	add_filter('wp_generate_attachment_metadata', 'xos_upload_thumbs');
}

# 删除文件时触发删除远端文件，该删除会默认删除缩略图
# add_filter('wp_delete_file', 'xos_delete_remote_file');
add_action('delete_attachment', 'xos_delete_remote_attachment');

# 添加插件设置菜单
add_action('admin_menu', 'xos_add_setting_page');
