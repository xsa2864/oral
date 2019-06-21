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
	public function broadcast($str,$hall_id=1,$screen_code=1,$type=0)
	{
		$data['CreateTime'] = date("Y-m-d H:i:s",time());
		if($type){
			$data['D_Text']		= $str;
		}else{			
			$data['S_Text']		= $str;
		}
		$data['AreaCode']	= $hall_id;
		$data['D_Address']	= $screen_code;
		$rs = Db::table('zx')->insert($data);
		return $rs;
	}
}