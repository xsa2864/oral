<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\facade\Env;

class Template extends Base
{
	public function index()
	{	
		$where = array();
		if($this->userid!=1){
			$where[] = ['hall_id','=',$this->hallid];
		}
        $list = db("config_print")->alias("p")->field("p.*,h.HallName")
        	->leftJoin("hall h","h.HallNo=p.hall_id")
        	->where($where)->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page);
        $this->assign("list",$list);
		return $this->fetch('index');
	}
	// 卡片详细信息
    public function editTemp(){
    	$id = input("id",0);    
        $where['id'] = $id;
    	$item = db("config_print")->where($where)->find();
    	$wh = array();
    	if($this->userid!=1){
            if($this->hallid){
                $wh['HallNo'] = $this->hallid;
            }
        }
    	$hall = db("hall")->where($wh)->select();
		$this->assign("hall",$hall);
    	$this->assign("item",$item);
    	return $this->fetch('edittemp');
    }
    // 保存信息
    public function saveTemp(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';

        $id 			     = input('id',0);
        $data['title']       = input('title','');
        $data['status']      = input('status',0);
        $data['temp']        = input('temp','');
        $data['hall_id']  	 = input('hall_id',0);
       
        if(empty($data['temp'])){
            $re_msg['msg'] = '模板内容不能为空';
            echo json_encode($re_msg);exit;
        }

	    if($id > 0)
	    {
	    	$flag = db("config_print")->where("id",$id)->update($data);
            if($flag!==false){
                $re_msg['success'] = 2;
                $re_msg['msg'] = '更新成功';
            }
	    }else{            	    	
	    	$data['add_time'] = time();
	    	$flag = db("config_print")->insert($data);
    	    if($flag){
    	    	$re_msg['success'] = 1;
            	$re_msg['msg'] = '添加成功';
    	    }
        }
	    echo json_encode($re_msg);
    }
    // 删除数据
    public function delTemp(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
        if($id==1){
            $re_msg['msg'] = 'ID为1 默认模板不能删除';
            echo json_encode($re_msg);
            exit;
        }
    	$result = db("config_print")->where("id",$id)->find();
    	if($result){
    		$flag = db("config_print")->where("id",$id)->delete();
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