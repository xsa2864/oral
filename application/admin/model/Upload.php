<?php 
namespace app\admin\model;

use think\Model;
use think\facade\Env;
use think\facade\Config;

class Upload extends Model
{
	// 保存base64图片
	public function base64Up($image='')
	{
		$path_name = '';
		preg_match('/^(data:\s*image\/(\w+);base64,)/', $image, $result);
		if($result){
			$type = $result[2];			
			$file_path = 'public/uploads/'.date('Ymd',time())."/";
			$full_path = Env::get('root_path').$file_path;
			if(!file_exists($full_path)){
				mkdir($full_path,0777,true);
			}
			$file_name = time().'.'.$type;
			$path = $full_path.$file_name;
			if(file_put_contents($file, base64_decode(str_replace($result[1], '', $image)))){
				$path_name = $file_path.$file_name;
			}
		}	
		return $path_name;
	}
}