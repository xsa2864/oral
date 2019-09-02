<?php 
namespace app\api\model;

use think\Model;
use think\Db;
use think\facade\Env;

class Voice extends Model
{
	/*
	 * 发送语音播报
	 * type 0=文本 1=语音 screen_code=屏地址
	 */
	public function broadcast($str,$hall_id=0,$screen_code=1,$type=0,$que_id=0)
	{
		$addr = 0;
		if($type){
			$data['D_Text']		= $str;
		}else{			
			$data['S_Text']		= $str.','.$str;			
		}
		$data['CreateTime'] = date("Y-m-d H:i:s",time());
		$data['D_Address']	= $screen_code;
		$hall_addr = Db::name("hall")->where("HallNo",$hall_id)->value("voice_addr");
		if($hall_addr){
			$data['AreaCode']	= $hall_addr;
			$rs = Db::table('zx')->insert($data);
		}
		$que_addr = Db::name("serque")->where("QueId",$que_id)->value("voice_addr");
		if($que_addr){
			$data['AreaCode']	= $que_addr;
			$rs = Db::table('zx')->insert($data);
		}
	}
}