<?php

require 'vendor/autoload.php';

$cosClient = new Qcloud\Cos\Client(array(
    'region' => 'ap-shanghai', #地域，如ap-guangzhou,ap-beijing-1
    'credentials' => array(
        'secretId' => 'AKID480vppwXbhFmFhRIuMkT5gBTrPGNNURn',
        'secretKey' => 'NZdsYPBsOvKclw1RpHlbDiGj3FJIf1FQ',
    ),
));

// 若初始化 Client 时未填写 appId，则 bucket 的命名规则为{name}-{appid} ，此处填写的存储桶名称必须为此格式
$bucket = 'virgo-10066963';
$key = 'a.txt';
$local_path = "E:/a.txt";

# 上传文件
## putObject(上传接口，最大支持上传5G文件)
### 上传内存中的字符串
// try {
    // $result = $cosClient->putObject(array(
        // 'Bucket' => $bucket,
        // 'Key' => $key,
        // 'Body' => fopen($local_path, 'rb')
    // ));
    // print_r($result);
    // # 可以直接通过$result读出返回结果
    // echo ($result['ETag']);
// } catch (\Exception $e) {
    // echo($e);
// }


/*列出文件及复制对象的代码块*/
// try {
    // $prefix = ''; // 列出对象的前缀
    // $marker = ''; // 上次列出对象的断点
    // while (true) {
        // $result = $cosClient->listObjects(array(
            // 'Bucket' => $bucket,
            // 'Marker' => $marker,
            // 'MaxKeys' => 1000 // 设置单次查询打印的最大数量，最大为1000
        // ));
        // foreach ($result['Contents'] as $rt) {
            // // 打印key
            // echo($rt['Key'] . "<br />");
			// // print_r($rt);
			// // echo "<br />";
			
			// if ($rt['Size'] > 0) {
				// // 执行复制
				// try {
					// $result = $cosClient->copyObject(array(
						// 'Bucket' => $bucket, //格式：BucketName-APPID
						// 'Key' => str_replace('wp-content/uploads/', '/',$rt['Key']),
						// 'CopySource' => 'virgo-10066963.cos.ap-shanghai.myqcloud.com/' . $rt['Key'],
					// )); 
					// // 请求成功
					// print_r($result);
				// } catch (\Exception $e) {
					// // 请求失败
					// echo($e);
				// }
			// }
			
        // }
        // $marker = $result['NextMarker']; // 设置新的断点
        // if (!$result['IsTruncated']) {
            // break; // 判断是否已经查询完
        // }
    // }
// } catch (\Exception $e) {
    // echo($e);
// }

$deleteObjects = array(
	array(
		'Key' => '/2019/05/016f72db5ce3081d5c392c02ec72f5f2-1.gif',
	),
	array(
		'Key' => '/2019/05/016f72db5ce3081d5c392c02ec72f5f2-3.gif',
	),

);

# 删除多个object
## deleteObjects
try {
    $result = $cosClient->deleteObjects(array(
        'Bucket' => $bucket,
        'Objects' => array(
            array(
                'Key' => '2019/05/016f72db5ce3081d5c392c02ec72f5f2.gif',
				),
			array(
				'Key' => '2019/05/016f72db5ce3081d5c392c02ec72f5f2-3.gif',
				),
		)
	)
	);
    print_r($result);
} catch (\Exception $e) {
    echo($e);
}

