<?php
/**
 * Created by PhpStorm.
 * User: zdl25
 * Date: 2019/1/8
 * Time: 11:22
 */

require 'vendor/autoload.php';

$options = array(
	'bucket' => "virgo-10066963",
	'region' => 'ap-shanghai',
	'app_id' => "1251113458",
	'secret_id' => "AKID480vppwXbhFmFhRIuMkT5gBTrPGNNURn",
	'secret_key' => "NZdsYPBsOvKclw1RpHlbDiGj3FJIf1FQ",
	'nothumb' => "false", // 是否上传缩略图
	'nolocalsaving' => "false", // 是否保留本地备份
	'upload_url_path' => "https://virgo-10066963.cos.ap-shanghai.myqcloud.com", // URL前缀
	'basedir' => 'E:\web\wordpress',
	'upload_dir' => '\wp-content\uploads',
);

$cosClient = new Qcloud\Cos\Client(array
	(
		'region' => $options['region'],
		'credentials'=> array(
			'secretId'    => $options['secret_id'],
			'secretKey' => $options['secret_key']
		)
	)
);


// 若初始化 Client 时未填写 appId，则 bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
$bucket = $options['bucket'];
$files = array();


function get_all_uploads($dir) {
	if (!is_dir($dir)) return false;

	global $files;

	$handle = opendir($dir);

	if ($handle) {
		while (($fl = readdir($handle)) !== false) {
			$temp = $dir. DIRECTORY_SEPARATOR. $fl;
			if (is_dir($temp) && $fl!='.' && $fl != '..') {
				get_all_uploads($temp);
			}else{
				if ($fl!='.' && $fl != '..') {
					$files[] = $temp;
				}
			}
		}
		closedir($handle);
	}
//	return str_replace($dir, '', $files);
	return $files;
}




function xos_file_upload($options) {
	$result = str_replace($options['basedir'], '', get_all_uploads($options['basedir'].$options['upload_dir']));

	$cosClient = new Qcloud\Cos\Client(array
		(
			'region' => $options['region'],
			'credentials'=> array(
				'secretId'    => $options['secret_id'],
				'secretKey' => $options['secret_key']
			)
		)
	);


	foreach ($result as $k=>$v){

		try {
			$result = $cosClient->headObject(array(
				'Bucket' => $options['bucket'],
				'Key' => str_replace('\\', '/', $v),
//		'VersionId' => '111',
			));
			echo($result);
		} catch (\Exception $e) {
			### 上传文件流
			try {
				$result = $cosClient->Upload(
					$options['bucket'],
					str_replace('\\', '/', $v),
					$body = fopen($options['basedir'] . $v, 'rb')
				);
				print_r($result);
			} catch (\Exception $e) {
				echo($e);
			}
		}

	}
}


//try {
//	$result = $cosClient->headObject(array(
//		'Bucket' => $options['bucket'],
//		'Key' => 'wp-content/3.png',
////		'Key' => str_replace('\\', '/', $v),
////		'VersionId' => '111',
//	));
//	echo($result);
//} catch (\Exception $e) {
//	### 上传文件流
//	try {
//		$file = fopen('wp-content/3.png', 'rb');
//		var_dump($file);
////		$result = $cosClient->Upload(
////			$options['bucket'],
////			$file,
////			$body = $file
////		);
//		fclose($file);
////		print_r($result);
//	} catch (\Exception $e) {
//		echo($e);
//	}
//}

// 删除 COS 对象
$result = $cosClient->deleteObject(array(
	//bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
//	'Bucket' => 'testbucket-125000000',
	'Bucket' => $options['bucket'],
	'Key' => '/00.gif',
));
var_dump($result);
