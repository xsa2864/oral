<?php
namespace app\admin\model;

use think\DB;
use think\Model;

class Defaults extends Model
{
	public function setTemp($unit_id=0,$hall_id=0)
	{
		// 添加语音和显示默认数据
		$wh[] = ['unitid','=',0]; 
		$wh[] = ['hall_id','=',0]; 
		$wh[] = ['form','=',0]; 
		$rs = DB::name("ads")
				->field("unitid,hall_id,type,horizontal,title,attr,addtime")
				->where($wh)
				->select();
		if($rs){
			$datas = array();
			foreach ($rs as $key => $val) {
				$val['unitid'] = $unit_id;
				$val['hall_id'] = $hall_id;
				$val['addtime'] = time();
				$datas[] = $val;
			}
			Db::name('ads')->insertAll($datas);
		}

		// 添加语音和显示默认数据
		$where[] = ['unit_id','=',0]; 
		$where[] = ['hall_id','=',0]; 
		$result = DB::name("z_voice")
				->field("unit_id,hall_id,type,screen_type,number,title,rule,add_time")
				->where($where)
				->select();
		if($result){
			$data = array();
			foreach ($result as $key => $value) {
				$value['unit_id'] = $unit_id;
				$value['hall_id'] = $hall_id;
				$value['add_time'] = time();
				$data[] = $value;
			}
			Db::name('z_voice')->insertAll($data);
		}		

	}
	// 删除相关数据
	public function delTemp($hall_id=0)
	{
		$where[] = ['hall_id','=',$hall_id];
		DB::name("ads")->where($where)->delete();
		DB::name("z_voice")->where($where)->delete();
	}
}