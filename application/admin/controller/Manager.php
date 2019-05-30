<?php
namespace app\admin\controller;

use think\View;
use think\Controller;

class Manager extends Base
{
    public function index()
    {            	
    	$user = db("manager")
    			->alias("m")
    			->field("m.*,u.unitname")
    			->leftJoin("unit u","u.UnitId=m.unitid")
    			->where("UserId",$this->userid)->find();
    	$this->assign("user",$user);
        return $this->fetch('manager');
    }

    // 保存管理员信息
    public function managerSave(){
    	$re_msg['success'] = 0;
    	$re_msg['msg'] = "更新失败";

    	$data['FullName'] = input("FullName",'');
		$data['BodNo'] = input("BodNo",'');
		$data['Sex'] = input("Sex",'女');
		$password = input("password",'');
		$password2 = input("password2",'');
		if(empty($data['FullName'])){
			$re_msg['msg'] = "姓名不能为空！";
				echo json_encode($re_msg);
				exit;
		}
		if(empty($data['BodNo'])){
			$re_msg['msg'] = "手机号不能为空！";
				echo json_encode($re_msg);
				exit;
		}
		if(!empty($password)){
			if($password == $password2){
				$data['password'] = md5($password);
			}else{
				$re_msg['msg'] = "两次输入的密码不一样！";
				echo json_encode($re_msg);
				exit;
			}
		}

		$rs = db("manager")->where("UserId",$this->userid)->update($data);
		if($rs!==false){
			$re_msg['success'] = 1;
    		$re_msg['msg'] = "更新成功";
		}
		echo json_encode($re_msg);
    }
}