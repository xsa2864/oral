<?php 
namespace app\admin\model;

use think\Model;
use think\Db;
use think\facade\Env;

class PrintOut extends Model
{
	// 生成打印内容
	public function makeText($arr,$hall_id=0){
		$tm[] = ['hall_id','=',$hall_id];
		$tm[] = ['status','=',1];
		$temp = Db::name("config_print")->where($tm)->value("temp");
		if(empty($temp)){
			$temp = Db::name("config_print")->where("hall_id",0)->order("id desc")->value("temp");
		}
		if($temp)
		{
			$d_name = "";
			if(isset($arr['[doctor_id]'])){
				$d_name = Db::name("z_doctor")->where("id",$arr['[doctor_id]'])->value("QueName");
			}
			$temp = str_replace("[doctor]",$d_name,$temp);
			$from = '';
			if(isset($arr['[order]'])){
				$from = $arr['[order]']==1?'预约':'现场';
			}
			$temp = str_replace("[order]",$from,$temp);
			$temp = str_replace("[name]",isset($arr['name'])?$arr['name']:'',$temp);
			$temp = str_replace("[room]",isset($arr['room'])?$arr['room']:'',$temp);
			$temp = str_replace("[queue]",isset($arr['title'])?$arr['title']:'',$temp);
            $temp = str_replace("[code]",isset($arr['code'])?$arr['prefix'].$arr['code']:'',$temp);
            $temp = str_replace("[idcard]",isset($arr['idcard'])?$arr['idcard']:'',$temp);
            $temp = str_replace("[number]",isset($arr['num'])?$arr['num']:'',$temp);
            $temp = str_replace("[time]",isset($arr['add_time'])?$arr['add_time']:'',$temp);
		}
        return $temp;
	}
}