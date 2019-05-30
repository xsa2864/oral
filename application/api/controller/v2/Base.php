<?php
namespace app\api\controller\v2;

use think\Controller;
use think\Facade\Config;
use think\Facade\Cache;

/**
 * 判断请求是否正规
 */
class Base  extends Controller
{
	/**
	 * 验证码口令
	 */
	public function initialize()
	{
		$re_msg['code'] = 1;
        $re_msg['msg'] = 'OK';

		$token = input("token",'123:1548482375:4f2fe1744d88d9b54e180784041336b8');	
		$array = explode(':', $token);
	
		if(empty($token)){
			$re_msg['code'] = 0;
			$re_msg['msg'] = 'Token不能为空。';
		}else if($array[1]<time()){
			$re_msg['code'] = 0;
			$re_msg['msg'] = 'Token已经过去,请重新获取。';
		}else{
			$arr = Config::get("token");
			$str = $array[0].":".$arr[$array[0]].":".$array[1].":".$arr['secret'];
			if($array[2] != md5($str) && Cache::get('token') == md5($str)){
				$re_msg['code'] = 0;
				$re_msg['msg'] = 'Token无效,请重新获取。';
			}
		}
		if($re_msg['code']==0){
			echo json_encode($re_msg);exit;
		}
		
	}
}