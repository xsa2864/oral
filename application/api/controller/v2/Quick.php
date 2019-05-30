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
        $re_msg['data'] = '';  

        $type = input("type",1);

        $ip = input("ip",'');
        if(empty($ip)){            
            $ccd = new \app\api\model\CacheCode;
            $ip = $ccd->getCode();  
        }

        $doctor_id  = 0;
        $que_id     = array();
        $hall_id    = 0;
        $room_name  = '';

        //获取在线医生
        $json       = cache("terminal");
        $arr        = json_decode($json,1);
        if(!empty($arr)){
            foreach ($arr as $key => $value) {
                if($value['devices_ip']==$ip && $value['devices_status']==1){
                    $que_id = explode(',', $value['devices_ads_id']);
                    $doctor_id = $key;
                }
            }
        }
        // 呼叫器
        $ter = DB::name("z_terminal")->where("ip",$ip)->find();
        if($ter){
        	$hall_id 	= $ter['hall_id'];
        }
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