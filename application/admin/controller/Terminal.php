<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\facade\Request;
use think\Db;
use think\facade\Env;

class Terminal extends Base
{
	public function listData()
	{
		$where = array();
		if($this->userid!=1){
            if($this->hallid){
                $where[] = ['hall_id','=',$this->hallid];
            }
            $where[] = ['unit_id','=',$this->unitid];
        }
		$list = db("z_terminal")->where($where)->order("hall_id asc")->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page); 		
		$this->assign("list",$list);
		return $this->fetch("list");
	}

	public function editInfo()
	{
		$id = input("id",0);
		// db("z_terminal")->alias("t")->field("t.*,h.HallName")
		// 		->leftJoin("hall h","h.HallNo=t.hall_id")
		// 		->where("t.id",$id)
		// 		->find();
		$where = array();
		if($this->userid!=1){
            if($this->hallid){
                $where['HallNo'] = $this->hallid;
            }
            $where[] = ['UnitId','=',$this->unitid];
        }
		$hall = db("hall")->where($where)->select();
		$list = db("z_terminal")->where("id",$id)->find();
		$this->assign("list",$list);
		$this->assign("hall",$hall);
		return $this->fetch("editInfo");
	}
	// 获取终端
    public function getTerminal(){
        $re_msg['success'] 	= 1;
        $re_msg['msg']  	= "获取成功";  

        $hall_id    = input("hall_id",0);
        $json       = cache("devices");
        $arr        = json_decode($json,1);
        $list       = array();
        if($arr){
            foreach ($arr as $key => $value) {
                if($value['devices_area_id']==$hall_id && $value['devices_type']==0){
                    $list[]       = $value;
                }
            }
        }
        $re_msg['data'] = $list;
        echo json_encode($re_msg);
    }
	public function saveInfo()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '新增失败';
        if (Request::isPost()){
	        $arr['hall_id']    	= input("hall_id",'');
            $unit_id = Db::name("hall")->where("HallNo",$arr['hall_id'])->value("UnitId");
            $arr['unit_id']     = $unit_id;
            $arr['hall_name']   = input("hall_name",'');
            $arr['is_screen']  	= input("is_screen",0);
            $arr['screen_code'] = input("screen_code",0);
            $arr['title']    	= input("title",'');
            $arr['room_name'] 	= input("room_name",'');
            $arr['seat_name']  	= input("seat_name",'');
            $arr['ip']      	= input("ip",'');
            $arr['devices_ip']     = input("devices_ip",'');
            $arr['devices_name']   = input("devices_name",'');
            $arr['z_type']         = input("z_type",1);
            $id = input("id",0);
            if(empty($arr['hall_id'])){
                $re_msg['msg'] = '请选择区域';
                echo json_encode($re_msg);exit;
            }         

            // $uwh[] = ["code",'=',$arr['code']];
            // $u_rs = db("z_terminal")->where($uwh)->select();
            // if($u_rs && !$id){
            //     $re_msg['msg'] = '呼叫器编号已经存在，请更换';
            //     echo json_encode($re_msg);exit;
            // }
            
            if($id){
                $flag = db("z_terminal")->where('id',$id)->update($arr);
                if($flag!==false){
                    $re_msg['success'] = 1;
                    $re_msg['msg'] = '更新成功';
                }else{
                    $re_msg['msg'] = '更新失败';
                }
            }else{
                $flag = db("z_terminal")->insert($arr);
                if($flag){
                    $re_msg['success'] = 2;
                    $re_msg['msg'] = '新增成功';
                }
            }    
        }
        echo json_encode($re_msg);
	}
	public function delInfo()
	{
		$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $id = input("id",0);
        $arr = explode(',', $id);
        $where[] = ['id','in',$arr];
        $result = db("z_terminal")->where($where)->select();
        if($result){
            $flag = db("z_terminal")->delete($arr);
            if($flag){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '删除成功';
            }
        }else{
            $re_msg['msg'] = '该数据已经不存在了';
        }
        echo json_encode($re_msg);
	}
}