<?php 
namespace app\api\model;

use think\Model;
use think\Db;
use think\facade\Env;

class Status extends Model
{
	/*
	 * 预约状态
	 */
	public function despeak($arr)
	{
		$re_msg['msg'] = '预约中-时间已到';
		if($arr['status']==2){
			$re_msg['msg'] = '完成';
		}else if($arr['status']==3){
			$re_msg['msg'] = '已过期';
		}else if($arr['status']==0){
			$re_msg['msg'] = '已取消';
		}else{
			$stime = strtotime($arr['despeakDate'].' '.$arr['time_Part_S']);
			$etime = strtotime($arr['despeakDate'].' '.$arr['time_Part_O']);
			if($etime < time()){
				$data['status'] = 3;
				DB::name("despeak")->where("despeak_id",$arr['despeak_id'])->update($data);
				$re_msg['msg'] =  '已过期';
			}else if($stime > time()){
				$re_msg['msg'] = '预约中-时间还未到';
			}
		}
		return $re_msg;
	}
}