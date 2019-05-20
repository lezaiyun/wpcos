<?php
/**
Plugin Name: WordPress COS（WPCOS）
Plugin URI: https://www.laobuluo.com/2186.html
Description: WordPress同步附件内容远程至腾讯云COS云存储中，实现网站数据与静态资源分离，提高网站加载速度。站长互助QQ群： <a href="https://jq.qq.com/?_wv=1027&k=5gBE7Pt" target="_blank"> <font color="red">594467847</font></a>
Version: 0.2
Author: 老部落（By:zdl25）
Author URI: https://www.laobuluo.com
*/

require_once 'wpcos_actions.php';

# 插件 activation 函数当一个插件在 WordPress 中”activated(启用)”时被触发。
register_activation_hook(__FILE__, 'wpcos_set_options');
# register_deactivation_hook(__FILE__, 'wpcos_restore_options');  # 禁用时触发钩子

add_action('upgrader_process_complete', 'wpcos_upgrade_options');  # 插件升级完成时执行

# 避免上传插件/主题被同步到对象存储
if (substr_count($_SERVER['REQUEST_URI'], '/update.php') <= 0) {
	add_filter('wp_generate_attachment_metadata', 'wpcos_upload_and_thumbs');
}

# 删除文件时触发删除远端文件，该删除会默认删除缩略图
add_action('delete_attachment', 'wpcos_delete_remote_attachment');

# 添加插件设置菜单
add_action('admin_menu', 'wpcos_add_setting_page');
