=== WPCOS腾讯云对象存储COS ===

Contributors: laobuluo
Donate link: https://www.laobuluo.com/donate/
Tags:腾讯云COS,腾讯云对象存储,腾讯云wordpress,腾讯云存储分离,腾讯云存储
Requires at least: 4.5.0
Tested up to: 5.4
Stable tag: 1.5.3
Requires PHP: 5.6
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

== Description ==

<strong>WordPress COS（简称:WPCOS），基于腾讯云COS存储与WordPress实现静态资源到COS存储中。提高网站项目的访问速度，以及静态资源的安全存储功能。</strong>

<strong>主要功能：</strong>

* 1、自动同步将WordPress静态文件，比如图片等上传到腾讯云COS存储中，在WP网站中删除图片会自动也删除COS存储文件；
* 2、可以设置本地与COS同步备份，或者本地不保存，仅存储到COS中.
* 3、腾讯云COS对象存储可以使用自带免费远程连接，也可以自定义域名，支持HTTPS。
* 4、WPCOS插件更多详细介绍和安装：<a href="https://www.laobuluo.com/2186.html" target="_blank" >https://www.laobuluo.com/2186.html</a>

<strong>支持网站平台：</strong>

* 1. 老蒋部落 <a href="https://www.itbulu.com" target="_blank" >https://www.itbulu.com</a>


== Installation ==

* 1、把wpcos文件夹上传到/wp-content/plugins/目录下<br />
* 2、在后台插件列表中激活wpcos<br />
* 3、在《wpcos设置》菜单中输入腾讯云COS对象存储相关参数信息<br />
* 4、设置可以参考：https://www.laobuluo.com/2196.html

== Frequently Asked Questions ==

* 1.当发现插件出错时，开启调试获取错误信息。
* 2.我们可以选择备份对象存储或者本地同时备份。
* 3.如果已有网站使用WPCOS，插件调试没有问题之后，需要将原有本地静态资源上传到COS中，然后修改数据库原有固定静态文件链接路径。、
* 4.插件是基于腾讯云COS对象存储SDK设计的，需要将对象存储升级至V5版本，早期V4版本兼容不好。

== Screenshots ==

1. screenshot-1.png

== Changelog ==

= 1.1 =
* 1. 重写wpcos，替换原先不合理的钩子及流程，简化工作流程，提高效率
* 2. 优化了使用体验，去除本地文件夹设置，简化操作
* 3. 处理旧版本对于新版本升级兼容的处理，并增加版本概念，便于后续升级
* 4. 删除附件使用批量删除接口，减少请求次数
* 5. 增加对非图片格式附件上传的兼容
* 6. 已存在附件时自动重命名
* 7. 官方插件中心合规性修改
* 8. 插件列表项中添加设置链接，方便用户配置
* 9. 使用wordpress图片编辑功能生成的图片保存并同步支持

= 1.2 =
* 1. 在最新WordPress5.3正式版兼容支持
* 2. 修改最新版本WP图片处理流程方式

= 1.3 =
* 1. 修复媒体库删除附件不同步删除问题
* 2. 添加随机附件命名功能
* 3. 新增用户可自定义COS对象存储目录功能，不限制文件放置根目录

= 1.4 =
* 1. 新增禁止缩略图功能

= 1.5 =
* 1. 基于WP官方样式调整插件样式，更简洁
* 2. 新增一键替换原来静态文件路径功能
* 3. 重新完善插件文档说明，更易懂
* 4. 优化禁止缩略图的逻辑，禁止系统缩略图裁剪，但是不禁止主题自带需要的缩略图

= 1.5.1 =
* 1. 修复一键替换按钮函数错误

= 1.5.2 =
* 1. 测试兼容WordPress 5.4

= 1.5.3 =
* 1. 优化前端样式，密钥可视及隐藏

== Upgrade Notice ==
* 