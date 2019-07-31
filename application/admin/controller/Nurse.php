<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;

class Nurse extends Base
{
	public function index()
	{
		$where = array();
		if($this->userid!=1){
			if($this->user['hallid']){
				$where[] = ['t.hall_id','=',$this->hallid];
			}
			$where[] = ['t.unit_id','=',$this->unitid];
		}
		// $where[] = ['t.status','>=',1];
		// $where[] = ['t.status','<=',2];
		$where[] = ['t.add_time','>',strtotime(date("Y-m-d",time()))];
		$list = db("z_ticket")->alias("t")
				->field("t.*")
				->where($where)
				->order("t.add_time asc,t.status desc,t.sort desc,t.pid asc")
				->select();
		$rel = new \app\admin\model\Relations;
		$result = $rel->listTicket($list);
		
		$hall = db("hall")->where('HallNo',$this->hallid)->find();
		$this->assign("hall",$hall);
		$this->assign("result",$result);
		return $this->fetch("index");
	}
	// 获取已呼人员
	public function getQueOver(){
		$id 	= input("id",0);
		$status = input("status",0);
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = $status==5?"未查询到已呼人员":($status==0?"未查询到过号人员":'未查询到等待人员');
		$where = array();
		$where[] = ['que_id','=',$id];
		if($status==1){
			$where[] = ['status','in',[1,2]];
		}else{
			$where[] = ['status','=',$status];
		}
		$where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
		if($this->userid!=1){
			$where[] = ['unit_id','=',$this->unitid];
		}
 		$result = db("z_ticket")->where($where)->order("sort desc,pid asc")->select();

		if($result){
			$re_msg['success'] = 1;
			$re_msg['msg'] 	   = "查询成功";
			$re_msg['data']	   = $result;
		}
		echo json_encode($re_msg);
	}
	// 复诊
	public function restore(){
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "完成或者过号的患者才能复诊";
		$id 	= input("id",0);
		$where = array();
		$where[] = ['id','=',$id];
		$where[] = ['status','in',[0,5]];
		if($this->userid!=1){
			$where[] = ['unit_id','=',$this->unitid];
		}
 		$result = db("z_ticket")->where($where)->find();
		if($result){
			$rs = db("z_ticket")->where("id",$id)->update(['status'=>1]);
			if($rs!==false){				
				$re_msg['success'] = 1;
				$re_msg['msg'] 	   = "复诊成功";
			}
		}
		echo json_encode($re_msg);
	}
	// 获取用户信息
	public function getInfo(){
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "未查询到数据";
		$id 	= input("id",0);
		$type 	= input("type",'');
		$result = db("z_ticket")->where("id",$id)->find();
		$data 	= '<select name="doctor_id"><option value="">没有医生</option>';
		if($type=='doctor'){
			$wh = array();
			$wh[] = ['que_id','like','%|'.$result['que_id'].'|%'];
			$drs = db("z_doctor")->where($wh)->select();
			$doc = new \app\admin\model\ClassTime;
			$drs = $doc->workDoctor($result['que_id']);			
			if($drs){
				$data = '<select name="doctor_id"><option value="">请选择医生</option>';
				foreach ($drs as $key => $value) {
					$select = '';
					if($value['id']==$result['doctor_id']){
						$select = 'selected';
					}
					$data .= "<option value='".$value['id']."' ".$select.">".$value['QueName']."</option>";
				}
				$data .= '</select>';
			}
		}
		if($result){
			$re_msg['success'] = 1;
			$re_msg['msg'] 	   = $result['title'].$result['prefix'].$result['code'].$result['name'];
			$re_msg['data']	   = $data;
		}
		echo json_encode($re_msg);
	}
	// 变更顺序
	public function changeNum(){
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "变更失败!";
		$id = input("id",0);
		$number = input("number",0);
		if($number!=0){			
			$rel = new \app\admin\model\Relations;
			$rs = $rel->changeNumber($id,$number);
			if($rs['success']){
				$re_msg['success'] = 1;				
			}
			$re_msg['msg'] 	   = $rs['msg'];
		}else{
			$re_msg['msg'] 	   = "变更位置不能为0";
		}
		echo json_encode($re_msg);
	}
	// 变更医生
	public function changeDoctor(){
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "变更失败";
		$id 		= input("id",0);
		$doctor_id 	= input("doctor_id",0);
		if($doctor_id > 0){			
			$data['doctor_id'] = $doctor_id;
			$rs = db("z_ticket")->where("id",$id)->update($data);
			if($rs){
				$re_msg['success'] = 1;		
				$re_msg['msg'] 	   = "变更成功";		
			}
		}else{
			$re_msg['msg'] 	   = "请选择有效医生";
		}
		echo json_encode($re_msg);
	}
	// 刷卡取号
	public function payCard()
	{
		$where = array();
		if($this->userid!=1){
			$where['UnitId'] = $this->unitid;
			if($this->user['hallid']){
				$where['HallNo'] = $this->hallid;
			}
		}
		$list  = db("serque")->where($where)->select();
		$this->assign("list",$list);		
		$devices_ip = request()->ip();
		$this->assign("ip",$devices_ip);
		return $this->fetch("payCard");
	}

	// 取预约号
	public function getTicket()
	{
		$where = array();
		if($this->userid!=1){
			if($this->user['hallid']){
				$where['HallNo'] = $this->hallid;
			}
			$where[] = ['UnitId','=',$this->unitid];
		}
		$list  = db("serque")->where($where)->select();
		$this->assign("list",$list);
		$devices_ip = request()->ip();
		$this->assign("ip",$devices_ip);
		return $this->fetch("getTicket");
	}
		/*
	 * 预约取号
	 */
	public function makeSure()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "没有有效预约信息";
        // $this->updateNewData();
		$idcard = input("online_idcard",0);
		$where[] = ['d.idcard|d.mobile','like','%'.$idcard.'%'];
		$where[] = ['d.status','=',1];
		$where[] = ['d.despeakTime','>',strtotime(date("Y-m-d",time()))];
		if($this->userid!=1){
			$where[] = ['unitId','=',$this->unitid];
		}
 		$result = db("despeak")
					->alias('d')
					->field('d.*')
					->where($where)
					->order('d.despeakTime', 'asc')
					->select();
		if($result){
			$re_msg['code'] = 200;
        	$re_msg['msg']  = "预约信息";
        	$data = Array();
        	foreach ($result as $key => $value) {
        		$item = array();
	        	$rel = new \app\admin\model\Relations;
				$item = $rel->checkValid($value['despeak_id']);  
				$value['item'] = $item;
				$data[] = $value;
        	}
			$re_msg['data']  = $data;
		}
		echo json_encode($re_msg);
	}
	// 打印票号
	public function produceTicket()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "票号生成失败";
        $re_msg['data'] = array();
        $ticket_id = input("ticket_id",'');
        if($ticket_id){
        	$hall_id = $this->hallid;
        	$n = 0;
        	foreach ($ticket_id as $key => $value) {
        		$item = array();
	        	$rel = new \app\admin\model\Relations;
				$item = $rel->orderTicket($value,$hall_id);  
				if($item['success']==1){
					$re_msg['code'] = 200;
					$re_msg['msg']  = $item['msg'];
					$prt = new \app\admin\model\PrintOut;
        			$text = $prt->makeText($item['data'],$hall_id);		
					$re_msg['data'][] = $text;
					$n++;
				}else if($n==0){
					$re_msg['msg']  = $item['msg'];
				}
        	}
        }else{
        	$re_msg['msg']  = "请选择打印号票";
        }
        echo json_encode($re_msg);
	}
	// 获取信息
	public function showUser(){
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "未查询到数据";
		$onlyid = input("onlyid",0);
		$result = db("interface_waitman")->where("OnlyId",$onlyid)->find();
		if($result){
			$re_msg['success'] = 1;
			$re_msg['msg'] 	   = "查询到数据";
			$re_msg['data']	   = $result;
		}
		echo json_encode($re_msg);
	}
	// 生成票号
	public function mkTicket()
	{
	    $birth		 	= input("brot",0);
	    $data['QueId'] 	= input("QueId",0);
	    $data['quick'] 	= input("quick",0);
		$data['idcard'] = input("onlyid","");
	    $data['name'] 	= input("name","");
	    $data['tips1']  = input("role","");
	    $data['sex'] 	= input("sex","男"); 
	    $data['birth'] 	= strtotime($birth); 
	    $is_info 		= input("is_info",0);
	    $data['tips2'] 	= "护士站";

	    $rel = new \app\admin\model\Relations;
	    if($is_info){
	    	$re_msg = $rel->makeTickets($data);
	    }else{
	    	if(empty($data['name'])){
		    	$re_msg['success'] = 0;
				$re_msg['msg'] 	   = "请输入正确的人员信息";
				echo json_encode($re_msg);exit;
		    }else{
	    		$re_msg = $rel->makeTicket($data);
		    }
	    }
	    if($re_msg['success']==1){
	    	$prt = new \app\admin\model\PrintOut;
        	$text = $prt->makeText($re_msg['data'],$re_msg['data']['hall_id']);	
        	$re_msg['data'] = $text;
	    } 
	    echo json_encode($re_msg);
	}
	
	// 删除票号
	public function delTicket(){
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "该患者不能删除";

		$id = input("id",0);
		$result = db("z_ticket")->where("id",$id)->find();
		if($result){
			if($result['status']!=2){				
				$rs = db("z_ticket")->where("id",$id)->update(['status'=>-1]);
				$re_msg['success'] = 1;
				$re_msg['msg'] 	   = "删除成功";
			}
		}
		echo json_encode($re_msg);
	}
	// 查询结果
	public function selectInfo(){
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "没有查询到数据";

		$condition = input("condition",0);
		$where = array();
		if($this->userid!=1){
			if($this->user['hallid']){
				$where[] = ['hall_id','=',$this->hallid];
			}
		}
		$where[] = ['name|idcard|code','like','%'.$condition.'%'];
		$where[] = ['status','>=',0];
		$where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
		$result = db("z_ticket")->where($where)->select();
		if($result){			
			$re_msg['success'] = 1;
			$re_msg['msg'] 	   = "查询成功";
			$re_msg['data']    = $result;
		}
		echo json_encode($re_msg);
	}
}