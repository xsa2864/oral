<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;

class Voice extends Base
{
	// 语音配置界面
	public function setVoice()
	{
        $whu  = array();
        $wh  = array();
		$where = array();
        if($this->userid!=1){
            $whu[]      = ['UnitId','=',$this->unitid];
            if($this->hallid){
                $wh[]       = ['HallNo','=',$this->hallid];
                $where[]    = ['s.hall_id','=',$this->hallid];
            }            
        }        
        $where[]    = ['s.type','=',1];
        $list = db("z_voice")->alias("s")
                ->field("s.*,u.HallName,m.FullName,q.QueName")
                ->leftJoin("hall u","u.HallNo=s.hall_id")
                ->leftJoin("serque q","q.QueId=s.que_id")
                ->leftJoin("manager m","m.UserId=s.manager_id")
                ->where($where)
                ->order("s.unit_id asc")
                ->paginate(20);  
        $page = $list->render();
        $this->assign("page",$page);
        $this->assign("list",$list);

		$hall  = db("hall")->where($wh)->select();
		$this->assign("hall",$hall);

        $unit  = db("unit")->where($whu)->select();
        $this->assign("unit",$unit);

        $this->assign("user_id",$this->userid);
		return $this->fetch("setVoice");
	}
    // 获取队列
    public function getSerque()
    {
        $re_msg['success'] = 0;
        $re_msg['msg']     = '获取失败';
        $id = input('id',0);
        $result = db("serque")->field("QueId,QueName")->where("HallNo",$id)->select();
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '获取成功';
            $re_msg['data']    = $result;
        }
        echo json_encode($re_msg);
    }
	//编辑模板
    public function voiceTemSave(){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '操作失败';
        $id                 = input("id",0);
        if($this->userid==1){
            $data['unit_id']    = input("unit_id",0);
        }else{
            $data['unit_id']    = input("unit_id",$this->unitid);
        }
        $data['hall_id']    = input("hall_id",$this->hallid);
        $data['que_id']     = input("que_id","");
        $data['title']      = input("title","");
        $data['rule']    	= input("rule","");
        $data['addr']    	= input("addr","");
        $data['screen_type']       = input("screen_type",0);
        $data['number']     = input("number","");
        $data['code']    	= input("code","");
        $data['type']       = input("type",0);
        // if(empty($data['que_id'])){
        //     $re_msg['msg']     = '请选择队列';
        //     echo json_encode($re_msg);exit;
        // }
        if(empty($data['title'])){
            $re_msg['msg']     = '名称不能为空';
            echo json_encode($re_msg);exit;
        }
        if(empty($data['rule'])){
            $re_msg['msg']     = '内容不能为空';
            echo json_encode($re_msg);exit;
        }
        if($id){
            $rs = db("z_voice")->where("id",$id)->update($data);
            if($rs!==false){
                $re_msg['success'] = 1;
                $re_msg['msg']     = '更新成功';
            }
        }else{
            $data['manager_id'] = $this->userid;
            $data['add_time'] = time();
            $rs = db("z_voice")->insertGetId($data);
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg']     = '保存成功';
            }
        }
        echo json_encode($re_msg);
    }
	// 获取模板信息
    public function getTempInfo(){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '查询失败';
        $re_msg['data']    = '';
        $id = input("id",0);
        $where = array();
        $where[] = ['id','=',$id];
        if($this->userid!=1){
            $where[] = ['unit_id','=',$this->unitid];
        }
        $result = db("z_voice")->where($where)->find();
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '查询成功';
            $re_msg['data']    = $result;
        }
        echo json_encode($re_msg);
    }
	// 删除
	public function delVoice()
	{
		$re_msg['success'] = 0;
        $re_msg['msg']     = '删除失败';
        $id = input("id",0);
        $where = array();
        $where[] = ['id','=',$id];
        if($this->userid!=1){
            $where[] = ['unit_id','=',$this->unitid];
        }
        $rs = db("z_voice")->where($where)->delete();
        if($rs){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '删除成功';
        }
        echo json_encode($re_msg);
	}
}