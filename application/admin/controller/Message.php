<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;

class Message extends Base
{
	public function listData()
	{	
		$where = array();
		if($this->userid!=1){
			if($this->hallid){
				$where[] = ['m.hall_id','=',$this->hallid];
			}
		}
		$list = db("z_message")->alias("m")
				->field("m.*,h.HallName,d.QueName")
				->leftJoin("hall h","h.HallNo=m.hall_id")
				->leftJoin("z_doctor d","d.id=m.doctor_id")
				->where($where)->order("m.status asc,m.add_time desc")
				->paginate(20);
		$page = $list->render();
 		$this->assign("page",$page);
		$this->assign("list",$list);
		return $this->fetch("listData");
	}

	// 发送消息页面
	public function editMsg(){
		$id = input("id",0);
		$mid = input("mid",0);
		$where = array();		
		if($id){
			$where[] = ['id','=',$id];
		}
		$doctor = db("z_doctor")->where($where)->select();
		$this->assign("doctor",$doctor);
		$this->assign("mid",$mid);
		return $this->fetch("editMsg");
	}

	//执行发送消息
	public function sendMsg(){
		$re_msg['success'] 		= 0;
		$re_msg['msg'] 	   		= "发送失败";
		$mid 					= input("mid",0);
		$data['doctor_id'] 		= input("doctor_id",0);
		$data['title'] 			= input("title","");
		$data['content'] 		= input("content","");
		$data['hall_id'] 		= $this->hallid;
		$data['add_time'] 		= time();
		$res['devices_type']	= 'message';
		$res['devices_content'] = $data['content'];
 
		if(empty($data['doctor_id'])){
			$re_msg['msg'] 	   = "请选择医生";
			echo json_encode($re_msg);exit;
		}
		
		if(empty($data['content'])){
			$re_msg['msg'] 	   = "内容不能为空";
			echo json_encode($re_msg);exit;
		}
		$socket = new \app\admin\model\Socket;
		$arr = $socket->changeSocke($data['doctor_id'],'terminal',$res);
		if($arr['code']==200){
			$rs = db("z_message")->insert($data);
			if($rs){
				$re_msg['success'] = 1;
				$re_msg['msg'] 	   = "发送成功";
				Db::name("z_message")->where("id",$mid)->update(['status'=>1]);
			}			
		}else{
			$re_msg['msg'] 	   = "该医生不在线";
			$rel = new \app\pavilion\model\Organize;
			$rel->setOffLine($data['doctor_id'],0,'terminal');
		}
		echo json_encode($re_msg);
	}

	// 确认已读消息
	public function readMsg()
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "确认已读失败";
		$id = input("id",0);
		$data['status'] = 1;
		$rs = db("z_message")->where("id",$id)->data($data)->update();
		if($rs!==false){			
			$re_msg['success'] = 1;
			$re_msg['msg'] 	   = "确认已读";
		}
		echo json_encode($re_msg);
	}

	// 删除消息
	public function delMsg()
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "删除失败";
		$id = input("id",0);
		$rs = db("z_message")->where("id",$id)->delete();
		if($rs!==false){			
			$re_msg['success'] = 1;
			$re_msg['msg'] 	   = "删除成功";
		}
		echo json_encode($re_msg);
	}

	// 删除消息
	public function delMsgs()
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "删除失败";
		$id = input("id",0);
		if($id==1){
			$where[] = ['status',"=",1];
			$rs = Db::name('z_message')->where($where)->delete();
		}else{
			$rs = Db::name('z_message')->delete(true);
		}
		if($rs){			
			$re_msg['success'] = 1;
			$re_msg['msg'] 	   = "删除成功";
		}
		echo json_encode($re_msg);
	}
}