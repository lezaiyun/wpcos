WordPress COS（简称:WPCOS），基于腾讯云COS存储与WordPress实现静态资源到COS存储中。提高网站项目的访问速度，以及静态资源的安全存储功能。

WPCOS插件特点

1、自动同步将WordPress静态文件，比如图片等上传到腾讯云COS存储中，在WP网站中删除图片会自动也删除COS存储文件；

2、可以设置本地与COS同步备份，或者本地不保存，仅存储到COS中（这一点在多个网友版本中均有错误，我们加以完善）；

3、对于腾讯云COS存储地区问题，不会因为增加机房而需要更新插件，我们直接输入所属地区即可。

WPCOS插件安装

1、插件下载地址

A - GitHub：https://github.com/laobuluo/wpcos

B - 备用镜像地址：https://download.laobuluo.com/wordpress/wpcos.zip

2、安装插件

将插件WPCOS文件夹解压后上传到"wp-content\plugins"目录，然后再网站后台启动插件。

3、插件设置

插件启动之后我们可以在WordPress后台左侧菜单看到"WPCOS设置"，点击设置。

请输入图片描述

这里我们根据从腾讯云COS获取的API信息和存储桶信息填写。

请输入图片描述

具体教程可以参考：https://www.laobuluo.com/2196.html

WPCOS插件更新地址：https://www.laobuluo.com/2186.html

WPCOS更新进度

2019.3.11 - WPCOS调试和发布文档的整理。因为考虑到后续还会完善功能，暂定0.1版本。