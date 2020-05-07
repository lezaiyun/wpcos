<?php
function wpcos_setting_page() {
	if (!current_user_can('manage_options')) {
		wp_die('Insufficient privileges!');
	}
	$wpcos_options = get_option('wpcos_options');
	if ($wpcos_options && isset($_GET['_wpnonce']) && wp_verify_nonce($_GET['_wpnonce']) && !empty($_POST)) {
		if($_POST['type'] == 'cos_info_set') {
			foreach ($wpcos_options as $k => $v) {
				if ($k =='no_local_file') {
					$wpcos_options[$k] = isset($_POST[$k]);
				} elseif($k =='opt') {
					$wpcos_options[$k]['auto_rename'] = (isset($_POST['auto_rename'])) ? 1 : 0;
				} else {
					if ($k != 'cos_url_path') {
						$wpcos_options[$k] = (isset($_POST[$k])) ? sanitize_text_field(trim(stripslashes($_POST[$k]))) : '';
					}
				}
			}
			$wpcos_options = wpcos_set_img_process_handle($wpcos_options, $_POST);
			$wpcos_options = wpcos_set_thumbsize($wpcos_options, isset($_POST['disable_thumb']) );

			update_option('wpcos_options', $wpcos_options);
			update_option('upload_url_path', esc_url_raw(trim(trim(stripslashes($_POST['upload_url_path'])))));
			?>
            <div class="notice notice-success settings-error is-dismissible"><p><strong>WPCOS插件设置已保存。</strong></p></div>
			<?php
		}
		else if($_POST['type'] == 'cos_info_replace') {
			$wpcos_options = wpcos_legacy_data_replace();
		}
	}
	?>
    <style type="text/css">
        .wp-hidden{position: relative;display: inline-block;}
        .wp-hidden .eyes{padding:5px;position: absolute;right: 10px; top:0; color:#0071a1;}
    </style>
    <div class="wrap">
        <h1 class="wp-heading-inline">腾讯云COS对象存储插件(WPCOS)设置</h1> <a href="https://www.laobuluo.com/2186.html" target="_blank"class="page-title-action">插件介绍</a>
        <hr class="wp-header-end">

        <p>插件介绍：WPCOS插件，可实现WordPress静态文件分离至腾讯云对象存储COS，提高网站访问速度。</p>
        <p>快速导航： <a href="https://www.laobuluo.com/tengxunyun/" target="_blank"><font color="red">最新腾讯云优惠活动（云服务器/对象存储包）</font></a> / 站长QQ群： <a href="https://jq.qq.com/?_wv=1027&k=5gBE7Pt" target="_blank"> <font color="red">594467847</font></a>（交流建站和运营） / 公众号：QQ69377078（插件反馈）</p>

        <hr/>
        <table class="form-table">
            <form action="<?php echo wp_nonce_url('./admin.php?page=' . WPCOS_BASEFOLDER . '/wpcos_actions.php'); ?>" name="wpcosform" method="post">
                <tr>
                    <th scope="row">
                        空间名称
                    </th>
                    <td>
                        <input type="text" name="bucket" value="<?php echo esc_attr($wpcos_options['bucket']); ?>" size="40"
                               placeholder="BUCKET 比如：laobuluo-xxxxxx"/>

                        <p>需要在腾讯云创建<code>bucket</code>存储桶。注意：填写"存储桶名称-对应ID". 示范： <code>laobuluo-xxxxxx</code></p>

                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        所属地域
                    </th>

                    <td>
                        <input type="text" name="region" value="<?php echo esc_attr($wpcos_options['region']); ?>" size="40"
                               placeholder="存储桶 所属地域 比如：ap-shanghai"/>
                        <p>直接填写我们存储桶所属地区，示范：ap-shanghai</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        访问域名
                    </th>
                    <td>
                        <input type="text" name="upload_url_path" value="<?php echo esc_url(get_option('upload_url_path')); ?>" size="60"
                               placeholder="请输入COS远程地址/自定义目录"/>

                        <p><b>设置事项：</b></p>
                        <p>1. 一般我们是以：<code>http://{cos域名}</code>，不要用"<code>/</code>"结尾，支持自定义域名</p>
                        <p>2. 支持自定义COS目录，可实现<code>{cos域名}/自定义目录</code>格式</p>
                        <p>3. 示范1：<code>https://laojiang-xxxxx.cos.ap-shanghai.myqcloud.com</code></p>
                        <p>4. 示范2：<code>https://laojiang-xxxxx.cos.ap-shanghai.myqcloud.com/laobuluo</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        APPID 设置
                    </th>
                    <td>
                        <input type="text" name="app_id" value="<?php echo esc_attr($wpcos_options['app_id']); ?>" size="40"
                               placeholder="APP ID"/>


                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        SecretId 设置
                    </th>

                    <td>
                        <div class="wp-hidden">
                            <input type="password" name="secret_id" value="<?php echo esc_attr($wpcos_options['secret_id']); ?>" size="50" placeholder="secretID"/>
                            <div class="eyes">
                                <span class="dashicons dashicons-hidden"></span>
                            </div>
                        </div>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        SecretKey 设置
                    </th>

                    <td>
                        <div class="wp-hidden">
                            <input type="password" name="secret_key" value="<?php echo esc_attr($wpcos_options['secret_key']); ?>" size="50" placeholder="secretKey"/>
                            <div class="eyes">
                                <span class="dashicons dashicons-hidden"></span>
                            </div>
                        </div>
                        <p>登入 <a href="https://console.qcloud.com/cam/capi" target="_blank">API密钥管理</a> 可以看到 <code>APPID | SecretId | SecretKey</code>。如果没有设置的需要创建一组。点击 <code>新建密钥</code></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        自动重命名
                    </th>
                    <td>
                        <input type="checkbox"
                               name="auto_rename"
							<?php
							if ($wpcos_options['opt']['auto_rename']) {
								echo 'checked="TRUE"';
							}
							?>
                        />

                        <label>上传文件自动重命名，解决中文文件名或者重复文件名问题</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        不在本地保存
                    </th>
                    <td>
                        <input type="checkbox"
                               name="no_local_file"
							<?php
							if ($wpcos_options['no_local_file']) {
								echo 'checked="TRUE"';
							}
							?>
                        />

                        <label>禁止文件保存本地。建议勾选，本地不保存，减少服务器占用资源</label>
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        禁止缩略图
                    </th>
                    <td>
                        <input type="checkbox"
                               name="disable_thumb"
							<?php
							if (isset($wpcos_options['opt']['thumbsize'])) {
								echo 'checked="TRUE"';
							}
							?>
                        />
                        <label>仅生成和上传主图，禁止缩略图裁剪。</label>
                    </td>
                </tr>
                <tr>
                    <th scope="row">数据万象</th>
                    <td>
                        <fieldset>
                            <input type="checkbox" name="img_process_switch" onchange="checkboxOnclick(this)"
                                <?php
                                if( isset($wpcos_options['opt']['img_process']['switch']) &&
                                   $wpcos_options['opt']['img_process']['switch'] === 1){
                                    echo 'checked="TRUE"';
                                }
                                ?>
                            >
                            <label>开启数据万象</label>
                            <p class="clashid" style="display:
                            <?php
                                if( isset($wpcos_options['opt']['img_process']['switch']) &&
                                    $wpcos_options['opt']['img_process']['switch'] === 1){
                                    echo 'block';
                                } else {
                                    echo 'none';
                                }
                            ?>;">

                            <?php
                                if ( !isset($wpcos_options['opt']['img_process']['style_value'])
                                    or $wpcos_options['opt']['img_process']['style_value'] === 'imageMogr2/format/webp/interlace/1/quality/100'
                                    or $wpcos_options['opt']['img_process']['style_value'] === '' ) {
                                    echo '<label>
                                            <input name="img_process_style_choice" type="radio" value="0" checked="TRUE" > webp压缩图片
                                            </label><br/>
                                            <label>
                                            <input name="img_process_style_choice" type="radio" value="1">自定义规则
                                        </label><br/>
                                        <input style="min-width: 348px;"
                                        name="img_process_style_customize" type="text" id="rss_rule" placeholder="请填写自定义规则" 
                                        value="" disabled="disabled">';
                                } else {
	                                echo '<label>
                                            <input name="img_process_style_choice" type="radio" value="0" > webp压缩图片
                                            </label><br/>
                                            <label>
                                            <input name="img_process_style_choice" type="radio" value="1" checked="TRUE" >自定义规则
                                        </label><br/>
                                        <input style="min-width: 348px;"
                                        name="img_process_style_customize" type="text" id="rss_rule" placeholder="请填写自定义规则" 
                                        value="' . $wpcos_options['opt']['img_process']['style_value'] . '" >';
                                }
                            ?>
                            </p>
                            <p>支持数据万象编辑图片，压缩、转换格式、文字图片水印等。（ <a href="https://cloud.tencent.com/document/product/460/36540" target="_blank">官方文档</a> | <a href="https://www.laobuluo.com/3287.html" target="_blank">使用</a>）</p>
                        </fieldset>
                    </td>
                </tr>
                <tr>
                    <th>

                    </th>
                    <td><input type="submit" name="submit" value="保存设置" class="button button-primary" /></td>
                </tr>
                <input type="hidden" name="type" value="cos_info_set">
            </form>
        </table>
        <hr>
        <p><strong>替换说明：</strong></p>
        <p>1. 网站本地已有静态文件，需要在测试兼容WPCOS插件之后，将本地文件对应目录上传到COS目录中（可用COSBrowser工具）</p>
        <p>2. 初次使用对象存储插件，可以通过下面按钮一键快速替换网站内容中的原有图片地址更换为COS地址</p>
        <p>3. 如果是从其他对象存储或者外部存储替换WPCOS插件的，可用 <a href="https://www.laobuluo.com/2693.html" target="_blank">WPReplace</a> 插件替换。</p>
        <p>4. 建议不熟悉的朋友先备份网站和数据。</p>
        <table class="form-table">
            <form action="<?php echo wp_nonce_url('./admin.php?page=' . WPCOS_BASEFOLDER . '/wpcos_actions.php'); ?>" name="wpcosform2" method="post">


                <tr>
                    <th scope="row">
                        一键替换
                    </th>
                    <td>
                        <input type="hidden" name="type" value="cos_info_replace">
						<?php if(array_key_exists('wpcos_legacy_data_replace', $wpcos_options['opt']) && $wpcos_options['opt']['wpcos_legacy_data_replace'] == 1) {
							echo '<input type="submit" disabled name="submit" value="已替换" class="button" />';
						} else {
							echo '<input type="submit" name="submit" value="一键替换COS地址" class="button" />';
						}
						?>
                        <p>一键将本地静态文件URL替换成COS对象存储路径，不熟悉的朋友请先备份</p>
                    </td>
                </tr>
            </form>
        </table>

        <hr>
        <div style='text-align:center;line-height: 50px;'>
            <a href="https://www.laobuluo.com/" target="_blank">插件主页</a> | <a href="https://www.laobuluo.com/2186.html" target="_blank">插件发布页面</a> | <a href="https://jq.qq.com/?_wv=1027&k=5gBE7Pt" target="_blank">QQ群：594467847</a> | 公众号：QQ69377078（插件反馈）

        </div>
    </div>
    <script>


        function getElementsClass(classnames) {
            var classobj = new Array();
            var classint = 0;
            var tags = document.getElementsByTagName("*");
            for (var i in tags) {
                if (tags[i].nodeType == 1) {
                    if (tags[i].getAttribute("class") == classnames) {
                        classobj[classint] = tags[i];
                        classint++;
                    }
                }
            }
            return classobj;
        }

        var eyes = getElementsClass("eyes");

        for (var i = 0; i < eyes.length; i++) {

            (function(i) {
                eyes[i].onclick = function() {
                    var inpu = this.previousElementSibling;
                    if (inpu.type == "password") {
                        inpu.type = "text";
                        this.children[0].classList.replace("dashicons-hidden", "dashicons-visibility");

                    } else {

                        inpu.type = "password";
                        this.children[0].classList.replace("dashicons-visibility", "dashicons-hidden");

                    }
                }
            })(i);
        }

        var  clashid = getElementsClass("clashid");

        function checkboxOnclick(checkbox){
            if ( checkbox.checked){
                clashid[0].style.display='block'
            }else{
                clashid[0].style.display='none'
            }
        }

        var selectValue = null;
        var els = document.querySelectorAll("[name=img_process_style_choice]")
        var rule = document.querySelectorAll("[name=img_process_style_customize]")

        for (el of els) {
            el.addEventListener("click", function() {
                if (selectValue == this.value && selectValue) {
                    this.checked = ""
                    selectValue = null;
                } else {
                    selectValue = this.value;

                }
                if(selectValue=='1'){
                    rule[0].disabled= false

                }else{
                    rule[0].disabled= true
                }
            })
        }


    </script>
	<?php
}
?>