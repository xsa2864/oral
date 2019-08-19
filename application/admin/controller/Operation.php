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
			if($this->userid!=1){
				$where[] = ['o.unit_id','=',$this->unitid];
			}
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
	// his日志
	public function hisLog()
	{
		$wh['date'] = $date = input("date",date("Y-m",time()));
		$type = input("type",0);
		$where = array();
		if($type){
			$wh['type'] = $type;
			$where[] = ['o.type','=',$type];
		}

		$table_name = "his_log_".$date;		
		$result = Db::query("SHOW TABLES LIKE '%$table_name%'");
		$list = array();
		$page = '';
		if($result){
			$stime = strtotime($date.'-01');
			$etime = strtotime("+1 month",$stime);
			$where[] = ['o.add_time','>=',$stime];
			$where[] = ['o.add_time','<',$etime];
			$list = Db::name($table_name)->alias("o")
					->field("o.*")
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
		$this->assign("type",$type);
		$this->assign("page",$page);
		$this->assign("list",$list);
		return $this->fetch("hislog");
	}
	// 删除his日志表
	public function delHisLog()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';

		$wh['date'] = $date = input("date",0);
		$time = strtotime($date);
		$agtime = strtotime("-3 month",time());
		if($agtime<$time){
        	$re_msg['msg'] = '不能删除近3个月内的数据';
		}else{
			$table_name = "t_his_log_".$date;		
			$result = Db::query("SHOW TABLES LIKE '%$table_name%'");
			if($result){
				Db::query("DROP TABLE `$table_name`");
			}
			$re_msg['success'] = 1;
        	$re_msg['msg'] = '删除成功';
		}
		echo json_encode($re_msg);
	}
	// 操作表数据
	public function opLog()
	{
		return $this->fetch("oplog");
	}

	public function delDespeak()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';

		$date1 = input("date1",0);
		$date2 = input("date2",0);

		$where = [];
		if($date1){
			$where[] = ['addtime','>=',strtotime($date1)];
		}
		if($date2){
			$where[] = ['addtime','<',strtotime($date1)];
		}
		$rs = Db::name("despeak")->where($where)->delete();
		if($rs){
			$re_msg['success'] = 1;
        	$re_msg['msg'] = '删除成功';
		}
		
		return json($re_msg);
	}

	public function delTicket()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';

		$date1 = input("date1",0);
		$date2 = input("date2",0);

		$where = [];
		if($date1){
			$where[] = ['add_time','>=',strtotime($date1)];
		}
		if($date2){
			$where[] = ['add_time','<',strtotime($date1)];
		}
		if($this->userid!=1){
			$where[] = ['unit_id','=',$this->unitid];			
		}
		$rs = Db::name("z_ticket")->where($where)->delete();
		if($rs){
			$re_msg['success'] = 1;
        	$re_msg['msg'] = '删除成功';
		}		
		return json($re_msg);
	}

	public function delOpLogs()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';

		$date1 = input("date1",0);
		$date2 = input("date2",0);

		$sql = "SHOW TABLES LIKE '%op_log%'";
		$tab = Db::query($sql);
		$tables = [];
		$qtab = [];
		foreach ($tab as $key => $value) {
			$tables[] = $value['Tables_in_qqs (%op_log%)'];
		}

		$time = strtotime($date1);
		$da1 = date("Y-m",$time);		
		$da2 = date("Y-m",strtotime($date2));
		for ($i=1; $i <= 12; $i++) { 
			if(in_array('t_op_log_'.$da1, $tables)){
				$qtab[] = 't_op_log_'.$da1;
			}
			if($da1==$da2){
				break;
			}
			$da1 = date("Y-m",strtotime("+$i month",$time));	
			if($i==12){
				$re_msg['msg'] = '时间跨度不要超过12个月';
				return $re_msg;
			}
		}

		$where = [];
		if($date1){
			$where[] = ['add_time','>=',strtotime($date1)];
		}else{			
        	$re_msg['msg'] = '请输入开始时间';
			return $re_msg;
		}
		if($date2){
			$where[] = ['add_time','<',strtotime($date2)];
		}else{			
        	$re_msg['msg'] = '请输入结束时间';
			return $re_msg;
		}
		foreach ($qtab as $key => $val) {		
			$rss = 	Db::table($val)->where($where)->select();
			if($rss){				
				$rs = Db::table($val)->where($where)->delete();
				if($rs){
					$re_msg['success'] = 1;
		        	$re_msg['msg'] = '删除成功';
				}
			}else{
				$re_msg['success'] = 1;
		        $re_msg['msg'] = '删除成功';
			}
		}
				
		return json($re_msg);
	}

	public function delHisLogs()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';

		$date1 = input("date1",0);
		$date2 = input("date2",0);

		$sql = "SHOW TABLES LIKE '%his_log%'";
		$tab = Db::query($sql);
		$tables = [];
		$qtab = [];
		foreach ($tab as $key => $value) {
			$tables[] = $value['Tables_in_qqs (%his_log%)'];
		}

		$time = strtotime($date1);
		$da1 = date("Y-m",$time);		
		$da2 = date("Y-m",strtotime($date2));
		for ($i=1; $i <= 12; $i++) { 
			if(in_array('t_his_log_'.$da1, $tables)){
				$qtab[] = 't_his_log_'.$da1;
			}
			if($da1==$da2){
				break;
			}
			$da1 = date("Y-m",strtotime("+$i month",$time));	
			if($i==12){
				$re_msg['msg'] = '时间跨度不要超过12个月';
				return $re_msg;
			}
		}

		$where = [];
		if($date1){
			$where[] = ['add_time','>=',strtotime($date1)];
		}else{			
        	$re_msg['msg'] = '请输入开始时间';
			return $re_msg;
		}
		if($date2){
			$where[] = ['add_time','<',strtotime($date2)];
		}else{			
        	$re_msg['msg'] = '请输入结束时间';
			return $re_msg;
		}
		foreach ($qtab as $key => $val) {			
			$rss = 	Db::table($val)->where($where)->select();
			if($rss){				
				$rs = Db::table($val)->where($where)->delete();
				if($rs){
					$re_msg['success'] = 1;
		        	$re_msg['msg'] = '删除成功';
				}
			}else{
				$re_msg['success'] = 1;
		        $re_msg['msg'] = '删除成功';
			}	
		}
		return json($re_msg);
	}
}