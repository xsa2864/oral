<?php
namespace app\api\controller\v2;

use think\Controller;
use think\Db;
/*
* ==呼叫器操作==
* 快捷键操作api
*/

class Quick extends Controller
{

    /*
     * 呼叫操作 
     * type 1=呼叫 2=重呼 3=过号 4=完成 5=停诊 6=保安
     * return Array
     */
    public function quickQueue()
    {
	    $re_msg['code'] = 201;
        $re_msg['msg']  = "操作失败";  
        $re_msg['data'] = [];  

        $ip         = input("ip",'');
        $type       = input("type",1);
        $is_login   = input("is_login",0);
        $staff_code = input("staff_code",0);
        $que_id     = input("que_id",0);
       
        if(empty($ip)){    
            // $re_msg['msg']  = "请填写呼叫器地址";     
            // return json($re_msg);
            $ccd = new \app\api\model\CacheCode;
            $ip = $ccd->getCode();  
        }

        $doctor_id  = 0;

        //获取在线医生
        if($is_login){
            $json       = cache("terminal");
            $arr        = json_decode($json,1);
            if(!empty($arr)){
                foreach ($arr as $key => $value) {
                    if($value['devices_ip']==$ip /*&& $value['devices_status']==1*/){
                        $que_id = explode(',', $value['devices_ads_id']);
                        $doctor_id = $key;
                    }
                }
            }
        }else{
            $rs = DB::name("z_doctor")->field("id,que_id")->where("staff_code",$staff_code)->find();
            $doctor_id  = $rs['id'];
            $que_id     = explode("|", $rs['que_id']);
            if(empty($que_id)){
                $re_msg['msg']  = "请先设置医生查看队列";  
                return $re_msg;
            }
        }

        // 呼叫器
        $ter = DB::name("z_terminal")->where("ip",$ip)->find();

        $result = array();
        if($doctor_id){        	
	        $push = new \app\api\model\PushMsg;
	        switch ($type) {
	        	case 1:
	        	case 2:
	        		$result = $push->getQueueInfoM($doctor_id,$que_id,$ter,$type);
	        		break;
	        	case 3:
	        	case 4:
	        		$status = $type==3?0:5;
	        		$result = $push->executeQueueM($doctor_id,$status);
	        		break;
	        	case 5:
	        		$result = $push->stopInfoM($ip);
	        		break;
	        	case 6:
	        		$result = $push->warningM($ter);
	        		break;
	        	default:
	        		$re_msg['msg']  = '缺少参数type';
	        		break;
	        }	        
	        if($result){
	        	if($result['success']==1){
		        	$re_msg['code'] = 200;
		        }
	        	$re_msg['msg']  = $result['msg'];
	        }
        }else{
        	$re_msg['msg']  = "医生不在线";  
        }
        return json($re_msg);
    }    
}