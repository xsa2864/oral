<?php 
namespace app\api\model;

use think\Model;
use think\Db;
use think\facade\Env;

class Voice extends Model
{
	/*
	 * 发送语音播报
	 */
	public function broadcast($str,$hall_id=1,$screen_code=1,$type=0,$que_id=0)
	{
		$data['CreateTime'] = date("Y-m-d H:i:s",time());
		$addr = 0;
		if($type){
			$data['D_Text']		= $str;
		}else{			
			$data['S_Text']		= $str;
			if($que_id){
				$addr = 0;
			}else{
				$addr = Db::name("hall")->where("HallNo",$hall_id)->value("voice_addr");
			}
		}
		$data['AreaCode']	= $addr?$addr:0;
		$data['D_Address']	= $screen_code;
		$rs = Db::table('zx')->insert($data);
		return $rs;
	}
}