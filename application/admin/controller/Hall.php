<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;

class Hall extends Base
{
	// 部门数据列表
    public function listdata()
    {    	
    	$id = input("id",0);
        $flag = 0;
    	$where = array();
        if($this->userid!=1){
            $where['u.UnitId'] = $this->unitid;
            if($this->hallid){
                $where['h.HallNo'] = $this->hallid;
            }
        }else if ($id!=0) {
        	$where = array("u.UnitId"=>$id);
        }

        if($this->userid==1||$this->hallid==0){            
            $flag = 1;
        }
        
    	$list = db("hall")->alias("h")
                ->field('h.*,u.unitname')
                ->join("unit u","u.UnitId=h.UnitId",'LEFT')
                ->where($where)->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page);
    	$this->assign("list",$list);
        $this->assign("hall_id",$flag);
        return $this->fetch('listdata');
    }
    // 部门详细信息
    public function hallDetail(){
    	$id = input("id",0);
    	
    	$list = db("hall")->alias("h")->leftJoin("unit_region r","r.id=h.region_id")->where("h.HallNo",$id)->find();
    	$this->assign("list",$list);
    	$where = array();
        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
        }	
        $where['EnableFlag'] = 1;
    	$unit = db("unit")->where($where)->select();
    	$this->assign("unit",$unit);
        $this->assign("m_unitid",$this->unitid);
    	return $this->fetch('edithall');
    }
    // 保存信息
    public function saveHall(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';
	    $data['HallName']      = input('hallname');
	    $data['DispName']      = input('dispname','');
        $data['h_code']        = input('h_code','');
	    $data['UnitId'] 	   = input('UnitId');
	    $data['EnableFlag']    = input('EnableFlag');
	    $data['AlternateField1'] 	= input('synopsis');
	    $data['WorkTime1']     = input('stime1');
	    $data['WorkTime2']     = input('etime1');
	    $data['WorkTime3']     = input('stime2');
	    $data['WorkTime4']     = input('etime2');
	    $data['Despeak_Part']  = input('Despeak_Part');
	    $data['SerInterface']  = input('intfaces','');
        $data['title']         = input('title');
        $data['pic']           = input('pic');
        $data['status']        = input('status',0);
        $data['card']          = input('card',0);
        $data['is_doctor']     = input('is_doctor',0);
	    $hallno = input('hallno',0);

        $validate =  new \app\admin\validate\Hall;
        if (!$validate->check($data)) {
            $re_msg['msg'] = $validate->getError();
            echo json_encode($re_msg);exit;
        }

        if($data['h_code']){                
            if(strlen($data['h_code'])!=4){
                $re_msg['msg'] = '请填写4个字符唯一编码';
                echo json_encode($re_msg);exit;
            }   
            $awh[] = ["h_code",'=',$data['h_code']];
            $awh[] = ["HallNo",'<>',$hallno];
            $awh[] = ["UnitId",'=',$data['UnitId']];
            $a_rs = db("hall")->where($awh)->select();
            if($a_rs){
                $re_msg['msg'] = '唯一编码已经存在，请更换';
                echo json_encode($re_msg);exit;
            }
        }

        if($data['SerInterface']){                
            if(strlen($data['SerInterface'])!=4){
                $re_msg['msg'] = '请填写4个字符接口标识';
                echo json_encode($re_msg);exit;
            }   
            $awh[] = ["SerInterface",'=',$data['SerInterface']];
            $awh[] = ["HallNo",'<>',$hallno];
            $a_rs = db("hall")->where($awh)->select();
            if($a_rs){
                $re_msg['msg'] = '接口标识已经存在，请更换';
                echo json_encode($re_msg);exit;
            }
        }

	    $flag = 0;
	    if($hallno > 0){
	    	$flag = db("hall")->where("HallNo",$hallno)->update($data);
            if($flag!==false){
                $re_msg['success'] = 2;
                $re_msg['msg'] = '更新成功';
            }
	    }else{
	    	$data['InTime'] = date("Y-m-d H:i:s",time());
	    	$flag = db("hall")->insertGetId($data);
    	    if($flag){
    	    	$re_msg['success'] = 1;
            	$re_msg['msg'] = '保存成功';
                $def = new \app\admin\model\Defaults;
                $def->setTemp($data['UnitId'],$flag);
    	    }
        }
	    echo json_encode($re_msg);
    }
    // 删除数据
    public function hallDel(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
    	$result = db("hall")->where("HallNo",$id)->find();
    	if($result){
    		$flag = db("hall")->where("HallNo",$id)->delete();
    		if($flag){
    			$re_msg['success'] = 1;
       	 		$re_msg['msg'] = '删除成功';
                $def = new \app\admin\model\Defaults;
                $def->delTemp($id);
    		}
    	}else{
    		$re_msg['msg'] = '该数据已经不存在了';
    	}
    	echo json_encode($re_msg);
    }
}