<?php
namespace app\api\controller\v1;

use think\Controller;
use think\Facade\Request;
use think\Db;
use think\facade\Cache;
use think\facade\Config;

class Feedback extends Controller
{

	public function initialize()
	{
		$secret = input("secret",md5(date("Y-m-d",time())));
		$v_secret = md5(date("Y-m-d",time()));
		if($secret != $v_secret){
			$re_msg['code'] = 201;
			$re_msg['msg'] = "secret验证不通过";
			echo json_encode($re_msg);
			exit;
		}
	}

	// 结果集
	public function results(){
		$operation 	= input("operation","");
		$ret 		= input("ret","");
		if($operation == "call"){
			$this->call_result($ret);
		}else if($operation == "evaluate"){
			$this->evaluate_result($ret);
		}
		$re_msg['code'] = 200;
		$re_msg['msg'] = "执行成功";
		echo json_encode($re_msg);
	}

	// 员工信息
	public function staffInfo()
	{
		$re_msg['code'] = 201;
		$re_msg['msg'] 	= "执行失败";
		$re_msg['ret'] 	= array();


		$staff_code		 = input("staff_code",'A0001');
		$room_name		 = input("room_name",'神经外科01诊室');
		$devices_ip		 = input("devices_ip",'192.168.0.169');
		$wait_number	 = input("wait_number",10);
		$wait_list		 = input("wait_list",array());
		$wait_list 		 =  [
			['ticket_no' => 'A0001','name' => '张里','status' => '0',"reservation" => 1],
			['ticket_no' => 'A0002','name' => '张成果','status' => '0',"reservation" => 0],
			['ticket_no' => 'A0003','name' => '陈丽水','status' => '0',"reservation" => 0],
			['ticket_no' => 'A0004','name' => '陈超','status' => '0',"reservation" => 0],
			['ticket_no' => 'A0005','name' => '张官','status' => '1',"reservation" => 1],
			['ticket_no' => 'A0006','name' => '刘时成','status' => '0',"reservation" => 0],
		];

		if($devices_ip){			
			$staff_info = db("serque")->field("QueName,pic,AlternateField1")->where("staff_code",$staff_code)->find();	
			// 指明给谁推送，为空表示向所有在线用户推送
	        $to_uid         		= $devices_ip;
	        $data['room_name']    	= $room_name;
	        $data['wait_number']    = $wait_number;
	        $data['wait_list']   	= $wait_list;
	        $data['staff_info']   	= $staff_info;

	        $content = json_encode($data);

	        // 推送的url地址，使用自己的服务器地址
	        $config = Config::get("app.socket_url");
	        $push_api_url = "http://127.0.0.1:".$config['port'];
	        $post_datas = array(
	           "type"       => "publish",
	           "content"    => $content,
	           "to"         => $to_uid, 
	        );
	        $post_data['json_data'] = json_encode($post_datas);
	        $ch = curl_init ();
	        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
	        curl_setopt ( $ch, CURLOPT_POST, 1 );
	        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
	        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
	        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
	        curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
	        $return = curl_exec ( $ch );
	        curl_close ( $ch );
	        
	        if($return=='ok'){
	        	$re_msg['code'] = 200;
				$re_msg['msg'] 	= "推送成功";
				$room = array();
		        if(cache("devices") && $devices_ip!=''){
		            $json = cache("devices");
		            $room = json_decode($json,1);
		            $room[$devices_ip]['room_name'] 	= $room_name;
		            $room[$devices_ip]['staff_name']  	= $staff_info['QueName'];
		            $room[$devices_ip]['staff_brief']	= $staff_info['AlternateField1'];
		        	cache("devices",json_encode($room));
		        }		        
	        }else if($return=='offline'){
	        	$re_msg['msg'] 	= "用户离线";
	        }else if($return=='fail'){
	        	$re_msg['msg'] 	= "推送失败";
	        }	
		}else{
			$re_msg['msg'] 	= "没有指定推送窗口ID";
		}
		echo json_encode($re_msg);
	}

	// 记录历史数据
    public function records($devices_ip="",$arr=array()){
        $array = cache("cache_list");
        if($devices_ip!="")
        {            
            $n = 0;
            if(!empty($array)){                
                krsort($array);    
                foreach ($array as $key => $value) {
                    if(!isset($value['devices_ip'])){
                        unset($array[$key]);
                    }else if($value['devices_ip'] == $devices_ip){
                        $n++;
                        if($n>4){
                            unset($array[$key]);
                        }
                    }
                }
            }
            $arr[$devices_ip]['devices_ip'] = $devices_ip;
            $arr[$devices_ip]['devices_send_time'] = time();
            $array[] = $arr[$devices_ip];
            cache("cache_list",$array);  
        }
    }

	// 用户信息
	public function userInfo()
	{
		$re_msg['code'] = 201;
		$re_msg['msg'] 	= "执行失败";
		$re_msg['ret'] 	= array();
		$card_number = input("card_number",0);
		$result = array();
		if($result){
			$re_msg['code'] = 200;
			$re_msg['msg'] 	= "执行成功";
			$re_msg['ret'] 	= $result;
		}
		echo json_encode($re_msg);
	}
}