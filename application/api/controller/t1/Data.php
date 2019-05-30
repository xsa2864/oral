<?php
namespace app\api\controller\t1;

use think\Controller;
use think\facade\Cookie;
use think\helper\Hash;
use think\Db;
use think\Queue;
use think\Request;

class Data extends Verify
{   
    public function index()
    {
        $re_msg['code']  = 201;
        $re_msg['msg']   = '无效操作';
        echo json_encode($re_msg);
    }
    // 获取科室列表
    public function getHallList()
    {
    	$re_msg['code'] = 201;
        $re_msg['msg']  = "获取数据失败";  
        $re_msg['data'] = "";
        $query = input("query","");
        $where = array();
        if (!empty($query)) {
        	$where[] = ['HallName','like','%'.$query.'%'];
        }
        $result = db("hall")->field("HallNo as id,HallName,EnableFlag as status")
        			->where($where)
        			->select();
        if($result){
        	$re_msg['code'] = 200;
            $re_msg['msg']  = "获取数据成功";  
            $re_msg['data'] = $result;
        }
        echo json_encode($re_msg);
    }
    // 获取队列列表
    public function getQueueList()
    {
    	$re_msg['code'] = 201;
        $re_msg['msg']  = "获取数据失败";  
        $re_msg['data'] = "";
        $query = input("query","");
        $where = array();
        if (!empty($query)) {
        	$where[] = ['QueName','like','%'.$query.'%'];
        }
        $result = db("serque")->field("QueId as id,QueName,EnableFlag as status")
        			->where($where)
        			->select();
        if($result){
        	$re_msg['code'] = 200;
            $re_msg['msg']  = "获取数据成功";  
            $re_msg['data'] = $result;
        }
        echo json_encode($re_msg);
    }
    // 获取医生列表
    public function getDoctorList()
    {
    	$re_msg['code'] = 201;
        $re_msg['msg']  = "获取数据失败";  
        $re_msg['data'] = "";
        $query 		= input("query","");
        $staff_code = input("staff_code","");
        $where = array();
        if(!empty($query)) {
        	$where[] = ['QueName','like','%'.$query.'%'];
        }
        if(!empty($staff_code)){
        	$where[] = ['staff_code','=',$staff_code];
        }
        $result = db("z_doctor")->field("id,staff_code,QueName,status")
        			->where($where)
        			->select();
        if($result){
        	$re_msg['code'] = 200;
            $re_msg['msg']  = "获取数据成功";  
            $re_msg['data'] = $result;
        }
        echo json_encode($re_msg);
    }
    // 对接预约数据
    public function makeOrder()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "数据对接失败";  
        $re_msg['data'] = 0;
        $list = input("list",'');
        $result = json_decode($list,1);

        if($result){
            if(count($result)<=300){  
                $data = $result;              
                $rs = db("despeak")->insertAll($data);
                if($rs){
                    $re_msg['code'] = 200;
                    $re_msg['msg']  = "数据对接成功";  
                    $re_msg['data'] = $rs;
                }    
            }else{
                $re_msg['msg']  = "一次数据不要超过300条";  
            }
        }
        echo json_encode($re_msg);
    }
    // 平台数据同步
    public function updateMember()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "数据对接失败";  
        $re_msg['data'] = 0;
        $list = input("list",'');
        $result = json_decode($list,1);

        if($result){
            if(count($result)<=300){  
                $data = $result;              
                $rs = db("despeak")->insertAll($data);
                if($rs){
                    $re_msg['code'] = 200;
                    $re_msg['msg']  = "数据对接成功";  
                    $re_msg['data'] = $rs;
                }    
            }else{
                $re_msg['msg']  = "一次数据不要超过300条";  
            }
        }
        echo json_encode($re_msg);
    }

    // 对接his系统数据
    public function makeHisOrder()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "数据对接失败";  
        $re_msg['data'] = 0;

        $data['que_id']     = input("que_id",69);
        $quick              = input("quick",0);
        $data['doctor_id']  = input("doctor_id",0);
        // $data['manager_id'] = input("manager_id",0);
        $data['idcard'] = input("idcard",'');
        $data['prefix'] = input("prefix",'A');
        $data['code']   = input("code",'200');
        $data['name']   = input("name",'');
        $data['mobile'] = input("mobile",'');
        $data['sex']    = input("sex",'男');
        $data['birth']  = input("birth",'');
        $data['status'] = input("status",'');
        $data['order']  = input("order",'');
        $data['stime']  = input("stime",0);
        $data['etime']  = input("etime",0);
        // $is = Db::name("z_ticket")->where("manager_id",$data['manager_id'])->find();
        // if(!$is){
            $rel = new \app\admin\model\Relations;
            $result = Db::name("serque")->where("QueId",$data['que_id'])->find();
            if($result){      
                $arr = $rel->makeNumber($result,$data['que_id'],$data['doctor_id'],$quick);
                $data['pid']     = $arr['pid'];   
                $data['hall_id'] = $result['HallNo'];
                $data['title']   = $result['QueName'];
                $data['add_time']= time();
                $data['platform']= 'his';
                $rs = Db::name("z_ticket")->insert($data);
                if($rs){
                    $re_msg['code']     = 200;
                    $re_msg['msg']      = "数据对接成功";
                    // 更新前端数据
                    $ip = Db::name("z_terminal")->where("hall_id",$data['hall_id'])->column("ip");
                    if($ip){
                        $soc = new \app\admin\model\Socket;
                        foreach ($ip as $key => $value) {
                            $iprs = $soc->terminalSocke($value,['reload'=>1]);
                        }
                    }
                }
            }else{
                $re_msg['msg']     = "队列不存在";
            }
        // }else{
        //     $re_msg['msg']     = "数据已经对接过了";
        // }
        echo json_encode($re_msg);
    }
}