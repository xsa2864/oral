<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;

class Operation extends Base
{
	public function index()
	{
		$wh['date'] = $date = input("date",date("Y-m",time()));
		$table_name = "op_log_".$date;		
		$result = Db::query("SHOW TABLES LIKE '%$table_name%'");
		$list = array();
		$page = '';
		if($result){
			$where = array();
			$stime = strtotime($date.'-01');
			$etime = strtotime("+1 month",$stime);
			// $where[] = ['add_time','>=',$stime];
			// $where[] = ['add_time','<',$etime];
			$list = db($table_name)->alias("o")
					->field("o.*,m.FullName,m.UserName")
					->leftJoin("manager m","m.UserId=o.manager_id")
					->where($where)
					->order("add_time desc")
					->paginate(20,false,[
						'query'    => $wh
					]);
			$page = $list->render();
		}
		$dates = array();
		for ($i=0; $i < 12; $i++) { 
			$dates[] = date("Y-m",strtotime("-$i month",time()));
		}
		$this->assign("dates",$dates);
		$this->assign("date",$date);
		$this->assign("page",$page);
		$this->assign("list",$list);
		return $this->fetch("log");
	}
	// 删除日志表
	public function delLog()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';

		$wh['date'] = $date = input("date",0);
		$time = strtotime($date);
		$agtime = strtotime("-3 month",time());
		if($agtime<$time){
        	$re_msg['msg'] = '不能删除近3个月内的数据';
		}else{
			$table_name = "t_op_log_".$date;		
			$result = Db::query("SHOW TABLES LIKE '%$table_name%'");
			if($result){
				Db::query("DROP TABLE `$table_name`");
			}
			$re_msg['success'] = 1;
        	$re_msg['msg'] = '删除成功';
		}
		echo json_encode($re_msg);
	}
}