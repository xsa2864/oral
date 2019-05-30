<?php

namespace app\api\controller\v1;

use think\Controller;
use think\Facade\Request;
use think\Db;
use app\api\controller\Base;

class Nvapi extends Base
{
	public $DevType;
	public $DevCode;
	public $conn;

	public function initialize()
	{
		$re_msg['code'] = 200;		
		$this->DevType = input("DevType","002");	//当前设备的类型 
		$this->DevCode = input("DevCode","002");		//当前设备编号
		if(empty($this->DevType) || empty($this->DevCode)){
			$re_msg['code'] = 400;
			$re_msg['msg'] = '请求参数有误';
		}else{
			// $where['devNum'] = $this->DevCode;
			// $rs = db("dbs")->where($where)->find();
			// if($rs){
			// 	$config = "mysql://".$rs['account'].":".$rs['password']."@".$rs['ip'].":3307/qqs#utf8";
			// 	$this->conn = Db::connect($config);
			// 	if(!$this->conn){
			// 		$re_msg['code'] = 201;
			// 		$re_msg['msg'] = '数据库连接失败';
			// 	}
			// }else{
			// 	$re_msg['code'] = 201;
			// 	$re_msg['msg'] = '设备不存在';
			// }			
		}
		if($re_msg['code']!=200){
			echo json_encode($re_msg);
			exit;
		}
	}

	// 增加票号
	public function add_ticket(){
		$re_msg['code'] = 201;
		$re_msg['msg'] 	= "请求失败";

		$data['secret'] 		= md5(date("Y-m-d",time()));
		$data['operation'] 		= 'add_ticket';
		$data['DevType'] 		= input("DevType","");
		$data['DevCode'] 		= input("DevCode","");
		$data['queueId'] 		= input("queueId","");
		$data['isPrint'] 		= input("isPrint","");
		$data['isAppointment'] 	= input("isAppointment","");
		$data['guideCode'] 		= input("guideCode","");

		$json = json_encode($data)."\n";
		$client = stream_socket_client('tcp://127.0.0.1:2345');
		fwrite($client, $json);

		if(true){
			$re_msg['code'] = 200;
			$re_msg['msg'] = "执行成功";
			$re_msg['ret'] = fread($client, 2026);
		}
		echo json_encode($re_msg);
	}

	//  呼叫票号 
	public function call_ticket(){
		$re_msg['code'] = 201;
		$re_msg['msg'] 	= "请求失败";

		$data['secret'] 	= md5(date("Y-m-d",time()));
		$data['operation'] 	= 'call_ticket';
		$data['DevType'] 	= input("DevType","");
		$data['DevCode'] 	= input("DevCode","");
		$data['callerAddr'] = input("callerAddr",""); 	//呼叫器地址
		$data['guideCode'] 	= input("guideCode",""); 	//选呼的票号
		$json = json_encode($data)."\n";
		$client = stream_socket_client('tcp://127.0.0.1:2345');
		fwrite($client, $json);

		if(true){
			$re_msg['code'] = 200;
			$re_msg['msg'] = "执行成功";
			$re_msg['ret'] = fread($client, 8192);
		}
		echo json_encode($re_msg);
	}

	// 查询数据信息
	public function select_area(){
		$re_msg['code'] = 201;
		$re_msg['msg'] 	= "请求失败";

		$data['secret'] 		= md5(date("Y-m-d",time()));
		$data['operation'] 		= 'select_area';
		$data['areaCode'] 		= input("areaCode","");

		$json = json_encode($data)."\n";
		$client = stream_socket_client('tcp://127.0.0.1:2345');
		fwrite($client, $json);

		if(true){
			$re_msg['code'] = 200;
			$re_msg['msg'] = "执行成功";
			$re_msg['ret'] = fread($client, 2026);
		}
		echo json_encode($re_msg);
	}
	
	// // 增加票号 
	// public function addTicket(){
	// 	$re_msg['code'] = 201;
	// 	$re_msg['msg'] = "请求失败";
	// 	$queueId = input("queueId","5");				//队列 ID 
	// 	$isPrint = input("isPrint","0");				//是否打印 
	// 	$isAppointment = input("isAppointment","1");	//是否预约 
	// 	$guideCode = input("guideCode","D0005");			//票号 

	// 	$data['TicketNo'] = $guideCode;
	// 	$data['ServiceNo'] = $queueId;
	// 	$data['PrintTime'] = date("Y-m-d H:i:s",time());
	// 	$data['QueueTime'] = date("Y-m-d H:i:s",time());

	// 	$date = date("Y-m-d",time());
	// 	$sql = "select * from ticket where TicketNo='".$guideCode."' and DATE(PrintTime)='".$date."' limit 1";
	// 	$result = $this->conn->query($sql);
	// 	if($result){
	// 		$re_msg['msg'] = "票号已存在";
	// 	}else{			
	// 		$rs = $this->conn->table("ticket")->insert($data);	
	// 		if($rs){
	// 			$re['queueId'] 	 = $queueId;
	// 			$re['guideCode'] = $guideCode;
	// 			$re['waitCount'] = 2;
	// 			$re_msg['code'] = 200;
	// 			$re_msg['msg'] = "执行成功";
	// 			$re_msg['ret'] = $re;
	// 		}
	// 	}
	// 	echo json_encode($re_msg);
	// }
}