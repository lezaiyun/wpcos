WordPress腾讯云COS对象存储插件（WPCOS）
----------------------------

WordPress COS（简称:WPCOS），基于腾讯云COS存储与WordPress实现静态资源到COS存储中。提高网站项目的访问速度，以及静态资源的安全存储功能。

--------------------

**WPCOS插件特点**

1、自动同步将WordPress静态文件，比如图片等上传到腾讯云COS存储中，在WP网站中删除图片会自动也删除COS存储文件；

2、可以设置本地与COS同步备份，或者本地不保存，仅存储到COS中；

3、对于腾讯云COS存储地区问题，不会因为增加机房而需要更新插件，我们直接输入所属地区即可。

4、重构WPCOS插件，实现编辑图片、自动重命名重复图片、目录固定设置。

**WPCOS插件安装**

1、插件下载地址

    A - GitHub：https://github.com/laobuluo/wpcos
    B - 在Wordpress插件后台直接搜索WPCOS即可下载。

2、安装插件

将插件WPCOS文件夹解压后上传到"wp-content\plugins"目录，然后再网站后台启动插件。

3、插件设置

插件启动之后我们可以在WordPress后台左侧菜单看到"WPCOS设置"，点击设置。

![请输入图片描述][4]

这里我们根据从腾讯云COS获取的API信息和存储桶信息填写。

具体教程可以参考：[https://www.laobuluo.com/2196.html][6]

WPCOS插件更新地址：[https://www.laobuluo.com/2186.html][7]

**WPCOS更新进度**

2019.3.11 - WPCOS调试和发布文档的整理。因为考虑到后续还会完善功能，暂定0.1版本。

= 2019年5月 重构WPCOS插件 =
1. 重写wpcos，替换原先不合理的钩子及流程，简化工作流程，提高效率
2. 优化了使用体验，去除本地文件夹设置，简化操作
3. 处理旧版本对于新版本升级兼容的处理，并增加版本概念，便于后续升级
4. 删除附件使用批量删除接口，减少请求次数
5. 增加对非图片格式附件上传的兼容
6. 已存在附件时自动重命名
7. 官方插件中心合规性修改
8. 插件列表项中添加设置链接，方便用户配置
9. 使用wordpress图片编辑功能生成的图片保存并同步支持

**WPCOS支持网站**

老蒋部落：[https://www.itbulu.com][8]

欢迎广大网友分享插件与提出建议，我们将给予支持网友的网站、博客整理到这里。

  [2]: https://github.com/laobuluo/wpcos
  [4]: https://raw.githubusercontent.com/laobuluo/wpcos/master/wpcos-1.jpg
  [6]: https://www.laobuluo.com/2196.html
  [7]: https://www.laobuluo.com/2186.html
  [8]: https://www.itbulu.com/