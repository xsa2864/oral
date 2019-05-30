<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\Config;
use think\facade\Env;

class Card extends Base
{
	public function index()
	{
		$where = array();
        $list = db("n_card")->where($where)->paginate(20);
        $page = $list->render();
 		$this->assign("page",$page);
        $this->assign("list",$list);
		return $this->fetch('index');
	}
	// 卡片详细信息
    public function editCard(){
    	$id = input("id",0);    
        $where['id'] = $id;
    	$item = db("n_card")->where($where)->find();
    	$this->assign("item",$item);
    	return $this->fetch('editcard');
    }
    // 保存信息
    public function saveCard(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';

        $id 			    = input('id','');
        $data['code']       = input('code','');
        
        $result = db("n_card")->where("code",$data['code'])->find();
        if($result){
            $re_msg['msg'] = '已经存在该卡号，请更换';
            if($id>0){
                if($result['id']!=$id){
                    echo json_encode($re_msg);exit;
                }
            }else{                
                echo json_encode($re_msg);exit;
            }
        }
        if(empty($data['code'])){
            $re_msg['msg'] = '请填写卡号';
            echo json_encode($re_msg);exit;
        }

	    $flag = 0;
	    if($id > 0)
	    {
	    	$flag = db("n_card")->where("id",$id)->update($data);
            if($flag!==false){
                $re_msg['success'] = 2;
                $re_msg['msg'] = '更新成功';
            }
	    }else{            
	    	$data['add_time'] = time();
	    	$flag = db("n_card")->insert($data);
    	    if($flag){
    	    	$re_msg['success'] = 1;
            	$re_msg['msg'] = '添加成功';
    	    }
        }
	    echo json_encode($re_msg);
    }
    // 删除数据
    public function cardDel(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
    	$id = input("id",0);
    	$result = db("n_card")->where("id",$id)->find();
    	if($result){
    		$flag = db("n_card")->where("id",$id)->delete();
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