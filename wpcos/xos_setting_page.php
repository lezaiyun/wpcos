<?php
/**
 *  插件设置页面
 * User: zdl25
 * Date: 2018/12/27
 * Time: 17:43
 */
function xos_setting_page() {
// 如果当前用户权限不足
if (!current_user_can('administrator')) {
	wp_die('Insufficient privileges!');
}

$xos_options = get_option('xos_options', True);
if (!empty($_POST)) {
    if($_POST['type'] == 'cos_info_set') {

        foreach ($xos_options as $k => $v) {
            if ($k =='no_local_file') {
                $xos_options[$k] = (isset($_POST[$k])) ? 'true' : 'false';
            } else {
	            $xos_options[$k] = (isset($_POST[$k])) ? trim(stripslashes($_POST[$k])) : '';
            }
        }
	    // 不管结果变没变，有提交则直接以提交的数据 更新xos_options
        update_option('xos_options', $xos_options);

        # 更新另外两个wp自带的上传相关属性的值
        # 替换 upload_path 的值
        $upload_path = trim(trim(stripslashes($_POST['upload_path'])), '/');
        update_option('upload_path', ($upload_path == '') ? ('wp-content/uploads') : ($upload_path));
        # 替换 upload_url_path 的值
        update_option('upload_url_path', trim(trim(stripslashes($_POST['upload_url_path'])), '/'));

?>
    <div class="updated"><p><strong>设置已保存！</strong></p></div>

<?php

    }
}

?>


<div class="wrap" style="margin: 10px;">
    <h2>WordPress COS（WPCOS）腾讯云COS存储设置</h2>
    <hr/>
    
        <p>WordPress COS（简称:WPCOS），基于腾讯云COS存储与WordPress实现静态资源到COS存储中。提高网站项目的访问速度，以及静态资源的安全存储功能。</p>
        <p>插件网站： <a href="https://www.laobuluo.com" target="_blank">老部落</a> / <a href="https://www.laobuluo.com/2186.html" target="_blank">WPCOS发布页面地址</a> / <a href="https://www.laobuluo.com/2196.html" target="_blank"> <font color="red">WPCOS安装详细教程</font></a></p>
        <p>优惠促销： <a href="https://www.laobuluo.com/tengxunyun/" target="_blank">最新腾讯云优惠汇总</a> / <a href="https://www.laobuluo.com/goto/qcloud-cos" target="_blank">腾讯云COS资源包优惠</a></p>
   
      <hr/>
    <form name="form1" method="post" action="<?php echo wp_nonce_url('./admin.php?page=' . XOS_BASEFOLDER . '/xos_actions.php'); ?>">
        <table class="form-table">
            <tr>
                <th>
                    <legend>存储桶名称</legend>
                </th>
                <td>
                    <input type="text" name="bucket" value="<?php echo esc_attr($xos_options['bucket']); ?>" size="50"
                           placeholder="BUCKET 比如：laobuluo-xxxxxx"/>

                    <p>1. 需要在腾讯云创建<code>bucket</code>存储桶。注意：填写"存储桶名称-对应ID"。</p>
                    <p>2. 示范： <code>laobuluo-xxxxxx</code></p>
                </td>
            </tr>
            <tr>
                <th>
                    <legend>存储桶所属地域</legend>
                </th>
                <td>
                    <input type="text" name="region" value="<?php echo esc_attr($xos_options['region']); ?>" size="50"
                           placeholder="存储桶 所属地域 比如：ap-shanghai"/>
                    <p>直接填写我们存储桶所属地区，示例：ap-shanghai</p>
                </td>
            </tr>
            <tr>
                <th>
                    <legend>APP ID 设置</legend>
                </th>
                <td>
                    <input type="text" name="app_id" value="<?php echo esc_attr($xos_options['app_id']); ?>" size="50"
                           placeholder="APP ID"/>

                    
                </td>
            </tr>
            <tr>
                <th>
                    <legend>secretID 设置</legend>
                </th>
                <td><input type="text" name="secret_id" value="<?php echo esc_attr($xos_options['secret_id']); ?>" size="50" placeholder="secretID"/></td>
            </tr>
            <tr>
                <th>
                    <legend>secretKey 设置</legend>
                </th>
                <td>
                    <input type="text" name="secret_key" value="<?php echo esc_attr($xos_options['secret_key']); ?>" size="50" placeholder="secretKey"/>
                    <p>登入 <a href="https://console.qcloud.com/cam/capi" target="_blank">API密钥管理</a> 可以看到 <code>APPID | SecretId | SecretKey</code>。如果没有设置的需要创建一组。点击 <code>新建密钥</code></p>
                </td>
            </tr>
            <tr>
                <th>
                    <legend>不在本地保存</legend>
                </th>
                <td>
                    <input type="checkbox"
                           name="no_local_file" <?php if (esc_attr($xos_options['no_local_file']) == 'true') {
						echo 'checked="TRUE"';
					}
					?> />

                    <p>如果不想同步在服务器中备份静态文件就 "勾选"。我个人喜欢只存储在腾讯云COS中，这样缓解服务器存储量。</p>
                </td>
            </tr>
            <tr>
                <th>
                    <legend>本地文件夹：</legend>
                </th>
                <td>
                    <input type="text" name="upload_path" value="<?php echo get_option('upload_path'); ?>" size="50"
                           placeholder="请输入本地文件夹目录"/>

                    <p>1. 静态文件在当前服务器的位置，例如： <code>wp-content/uploads</code> （不要用"/"开头和结尾），根目录输入<code>.</code>。</p>
                    <p>2. 示范：<code>wp-content/uploads</code></p>
                </td>
            </tr>
            <tr>
                <th>
                    <legend>COS远程地址：</legend>
                </th>
                <td>
                    <input type="text" name="upload_url_path" value="<?php echo get_option('upload_url_path'); ?>" size="50"
                           placeholder="请输入COS远程地址"/>

                    <p><b>设置注意事项：</b></p>

                    <p>1. 一般我们是以：<code>http://{cos域名}/{本地文件夹}</code>，同样不要用"/"结尾。</p>

                    <p>2. <code>{cos域名}</code> 是需要在设置的存储桶中查看的。"存储桶列表"，当前存储桶的"基础配置"的"访问域名"中。</p>

                    <p>3. 如果我们自定义域名的，<code>{cos域名}</code> 则需要用到我们自己自定义的域名。</p>
                    <p>4. 示范1： <code>https://laobuluo-xxxxxxx.cos.ap-shanghai.myqcloud.com/wp-content/uploads</code></p>
                    <p>5. 示范2： <code>https://cos.laobuluo.com/wp-content/uploads</code></p>
                </td>
            </tr>
            <tr>
                <th>
                    
                </th>
                <td><input type="submit" name="submit" value="保存WPCOS设置"/></td>

            </tr>
        </table>
        
        <input type="hidden" name="type" value="cos_info_set">
    </form>
</div>
<?php
}
?>