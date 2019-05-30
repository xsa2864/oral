<?php 
namespace app\admin\model;

use think\Model;
use think\Db;
use think\facade\Env;

class Voice extends Model
{
	/*
	 * 发送语音播报
	 */
	public function broadcast($str,$addr_id=1)
	{
		$data['CreateTime'] = date("Y-m-d H:i:s",time());
		// $data['AreaCode']	= $addr_id;
		$data['S_Text']		= $str;
		$data['D_Address']	= 1;
		$data['S_SendMark']	= 1;
		$rs = Db::connect('db_voice_config')->table('zx')->insert($data);
		return $rs;
	}
}