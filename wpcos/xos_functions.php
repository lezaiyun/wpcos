<?php
/**
 * Created by PhpStorm.
 * User: zdl25
 * Date: 2019/1/9
 * Time: 14:46
 */
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