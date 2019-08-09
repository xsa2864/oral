<?php
namespace app\pavilion\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\View;
use think\facade\Env;
use think\facade\Config;


class Fetch extends Controller
{
	public function index()
	{		
		$hall_id = cookie("hall_id")?cookie("hall_id"):0;
		$where[] = ['HallNo','=',$hall_id];
		$hwere[] = ['EnableFlag','=',1];
		$queue = Db::name("serque")->field("QueId,QueName,ClassesTime")->where($where)->select();
		$rel = new \app\admin\model\ClassTime;
		$q_list = $rel->queueValid($queue);
		
		$hall = Db::name("hall")->where('HallNo',$hall_id)->find();
		$list = Db::name("hall")->field("HallNo,HallName")->where('EnableFlag',"1")->select();
		$this->assign("hall_id",$hall_id);
		$this->assign("list",$list);
		$this->assign("hall",$hall);
		$this->assign("queue",$q_list);
		// $devices_ip = request()->ip();
		$ccd = new \app\api\model\CacheCode;
        $devices_ip = $ccd->getCode();  
		$this->assign("ip",$devices_ip);
		return $this->fetch("index");
	}
	// 刷卡取号
	public function card()
	{
		$hall_id = cookie("hall_id")?cookie("hall_id"):0;
		$where[] = ['HallNo','=',$hall_id];
		$hwere[] = ['EnableFlag','=',1];
		$queue = Db::name("serque")->field("QueId,QueName,ClassesTime")->where($where)->select();
		$rel = new \app\admin\model\ClassTime;
		$q_list = $rel->queueValid($queue);
		
		$hall = Db::name("hall")->where('HallNo',$hall_id)->find();
		$list = Db::name("hall")->field("HallNo,HallName")->where('EnableFlag',"1")->select();
		$this->assign("hall_id",$hall_id);
		$this->assign("list",$list);
		$this->assign("hall",$hall);
		$this->assign("queue",$q_list);		
		// $devices_ip = request()->ip();
		$ccd = new \app\api\model\CacheCode;
        $devices_ip = $ccd->getCode();  
		$this->assign("ip",$devices_ip);
		return $this->fetch("card");
	}

	public function showCard()
	{
		return $this->fetch('hall'); //vueCard
	}
	public function vueCard()
	{
		return $this->fetch('vueCard'); //vueCard
	}
	// 保存区域
	public function setHall()
	{
		$hall_id = input("hall_id",0);
		Cookie::forever('hall_id',$hall_id);
		echo 1;
	}

	// 获取用户信息
	public function getInfo()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "未查询到数据";
		$idcard = input("idcard",0);
		$where[] = ['CardId','=',$idcard];
		$item = Db::name("interface_waitman")->where($where)->find();
		if($item){
			$re_msg['code'] = 200;
        	$re_msg['msg']  = "询到数据";
			$re_msg['data'] = $item;
		}
		echo json_encode($re_msg);
	}
	// 生成票号
	public function makeTicket()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "票号生成失败";
        $que_id = input("que_id",0);
        $idcard = input("idcard",0);
        $hall_id = input("hall_id",0);
		$arrs['QueId']  = $que_id;
		$arrs['quick']  = 0;
		$flag = Db::name("hall")->where('HallNo',$hall_id)->value("card");
		if($flag){
			// 有用户信息生成票号
	        $where[] = ['CardId','=',$idcard];
			$item = Db::name("interface_waitman")->where($where)->find();
			if($item){			
				$arrs['idcard']	= $idcard;
				$arrs['name']	= $item['Name'];
				$arrs['tips1']	= $item['Role'];
				$arrs['tips2']	= $item['Origin'];
				$arrs['sex']	= $item['Sex'];
				$arrs['birth']	= $item['Brot'];
		        $rel = new \app\admin\model\Relations;
		        $result = $rel->makeTicket($arrs);
		        if($result['success']==1){
		        	// 更新前端数据
		        	$ip = Db::name("z_terminal")->where("hall_id",$hall_id)->column("ip");
		        	if($ip){
		        		$soc = new \app\admin\model\Socket;
		        		foreach ($ip as $key => $value) {
		        			$iprs = $soc->terminalSocke($value,['reload'=>1]);
		        		}
		        	}
		        	$re_msg['code'] = 200;
		        	$re_msg['msg']  = $result['msg'];
		        	$prt = new \app\admin\model\PrintOut;
        			$text = $prt->makeText($result['data'],$hall_id);	
		        	$re_msg['data'] = $text;
		        }else{
		        	$re_msg['msg']  = $result['msg'];
		        }
			}else{
				$re_msg['msg']  = "未查询到用户信息";
			}
		}else{		
			// 无用户信息生成票号
			$arrs['QueId']  = $que_id;
			$arrs['quick']  = 0;
			$rel = new \app\admin\model\Relations;
		    $result = $rel->makeTickets($arrs);
		    if($result['success']==1){
		    	// 更新前端数据
		        $ip = Db::name("z_terminal")->where("hall_id",$hall_id)->column("ip");
		        if($ip){
		        	$soc = new \app\admin\model\Socket;
		        	foreach ($ip as $key => $value) {
		        		$iprs = $soc->terminalSocke($value,['reload'=>1]);
		        	}
		        }
		    	$re_msg['code'] = 200;
		    	$re_msg['msg']  = $result['msg'];
		    	$prt = new \app\admin\model\PrintOut;
        		$text = $prt->makeText($result['data'],$hall_id);	
		    	$re_msg['data'] = $text;
		    }else{
		    	$re_msg['msg']  = $result['msg'];
		    }
        	$re_msg['time']	   = time();
		}
        echo json_encode($re_msg);
	}

	/*
	 * 刷卡取号
	 */
	public function makeCard()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "没有有效预约信息";
        $hall_id = cookie("hall_id")?cookie("hall_id"):0;
        $unit_id = cookie("unit_id")?cookie("unit_id"):0;
		$idcard = input("online_idcard",0);
		$que_id = input("que_id",0);
		$flag = true;

		$config = Db::name("config_fetch")->where("unitid",$unit_id)->lock(true)->find();
		$limit = $config['fetch_number'];

        $where[] = ['over_time','>=',strtotime(date("Y-m-d",time()))];
        $where[] = ['idcard','=',$idcard];

        $count = Db::name("z_ticket")->where($where)->count();
        if($count>=$limit && $limit>0 && false){
        	$re_msg['msg']  = "此卡取号超出限制次数";
        	echo json_encode($re_msg);exit;
        }

		if(empty($config['prefix'])){
			$wheres[] = ['code','=',$idcard];
			$result = Db::name("n_card")->where($wheres)->find();
			if(!$result){
				$flag = false;
				$re_msg['msg']  = "后台还没有这张卡信息,无效操作";
			}
		}else{
			$prs = strpos($idcard,$config['prefix']);
			if($prs===false){
				$flag = false;
				$re_msg['msg']  = "无效卡";
			}
		}
		if($flag){
			Db::startTrans();			
			$rs = 0;
			$over_time = strtotime(date("Y-m-d",time()));   
        	$rel = new \app\admin\model\Relations;
        	$que_arr = Db::table("t_serque")->where("QueId",$que_id)->find();
        	$arr = $rel->makeNumber($que_arr,$que_id);
		    $data['code']  	 	= $arr['code'];
		    $data['pid']  	 	= $arr['pid'];	    	
		    $data['prefix']  	= $que_arr['NoChar'];
        	$data['que_id'] 	= $que_id;
        	$data['title'] 		= Db::table("t_serque")->where("QueId",$que_id)->value("QueName");
			$data['idcard'] 	= $idcard;
			$data['over_time']  = strtotime(date("Y-m-d",time()));
			$data['add_time']   = time();
			$lwh[] = ['que_id',"=",$data['que_id']];
			$lwh[] = ['code',"=",$data['code']];
			$lwh[] = ['add_time','=',$data['add_time']];
			$lrs = Db::table("t_z_ticket")->where($lwh)->lock(true)->find();
        	$rs = Db::table("t_z_ticket")->insert($data);
        	if($rs){
        		$re_msg['code'] = 200;
        		$re_msg['msg']  = "取号成功";
        		$wh[] = ['que_id','=',$que_id];
        		$wh[] = ['status','=',1];
        		$wh[] = ['over_time','=',$over_time];
        		$num = Db::table("t_z_ticket")->where($wh)->count();
        		$data['add_time']   = date("Y-m-d H:i",time());
        		$data['num'] = $num;
        		$prt = new \app\admin\model\PrintOut;
        		$text = $prt->makeText($data,$hall_id);
        		$re_msg['data'] = $text;

        		// 更新前端数据
		        $ip = Db::name("z_terminal")->where("hall_id",$hall_id)->column("ip");
		        if($ip){
		        	$soc = new \app\admin\model\Socket;
		        	foreach ($ip as $key => $value) {
		        		$iprs = $soc->terminalSocke($value,['reload'=>1]);
		        	}
		        }
        	}
        	if($rs && !$lrs){
        		Db::commit();
        	}else{
        		$re_msg['code'] = 201;
        		$re_msg['msg']  = "请重新取号";
        		$re_msg['data'] = '';
	        	Db::rollback();	        	
	        }
		}
		echo json_encode($re_msg);
	}
	/*
	 * 预约取号
	 */
	public function makeSure()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "没有有效预约信息";
        $this->updateNewData();
		$idcard = input("online_idcard",0);
		if(strlen($idcard)==18){
			$where[] = ['d.idcard','=',$idcard];
		}else if(strlen($idcard)==11){
			$where[] = ['d.mobile','=',$idcard];
		}else{
			$where[] = ['d.despeak_id','=',0];
		}
		$where[] = ['d.status','=',1];
		$where[] = ['d.despeakTime','>',strtotime(date("Y-m-d",time()))];
		$result = db("despeak")
					->alias('d')
					->field('d.*')
					->where($where)
					->order('d.despeakTime', 'asc')
					->select();
		if($result){
			$re_msg['code'] = 200;
        	$re_msg['msg']  = "预约信息";
        	$data = Array();
        	foreach ($result as $key => $value) {
        		$item = array();
	        	$rel = new \app\admin\model\Relations;
				$item = $rel->checkValid($value['despeak_id']);  
				$value['item'] = $item;
				$data[] = $value;
        	}
			$re_msg['data']  = $data;
		}
		echo json_encode($re_msg);
	}
	// 打印票号
	public function produceTicket()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "票号生成失败";
        $re_msg['data'] = array();
        $ticket_id = input("ticket_id",'');
        if($ticket_id){
        	$hall_id = cookie("hall_id");
        	$n = 0;
        	foreach ($ticket_id as $key => $value) {
        		$item = array();
	        	$rel = new \app\admin\model\Relations;
				$item = $rel->orderTicket($value,$hall_id);  
				if($item['success']==1){
					$re_msg['code'] = 200;
					$re_msg['msg']  = $item['msg'];
					$prt = new \app\admin\model\PrintOut;
        			$text = $prt->makeText($item['data'],$hall_id);		
					$re_msg['data'][] = $text;
					$n++;
				}else if($n==0){
					$re_msg['msg']  = $item['msg'];
				}
        	}
        }else{
        	$re_msg['msg']  = "请选择打印号票";
        }
        echo json_encode($re_msg);
	}
	//生成预约号
	public function orderTicket()
	{
		$re_msg['code'] = 201;
        $re_msg['msg']  = "票号生成失败";
        $id = input("id",0);
        $hall_id = cookie("hall_id");
		$rel = new \app\admin\model\Relations;
		$item = $rel->checkValid($id);
		if($item['success']==1){
			$data = $item['data'];
			if($item['data']['hallNo']==$hall_id){				
				$arrs['QueId']  = $data['queId'];
				$arrs['quick']  = 2;
				$arrs['order']	= 1;
				$arrs['doctor_id']	= $data['doctor_id'];
				$arrs['idcard']	= $data['idcard'];
				$arrs['name']	= $data['name'];
				$arrs['stime']	= $data['despeakTime'];
				$arrs['etime']	= strtotime($data['despeakDate'].' '.$data['time_Part_O']);
				$arrs['sex']	= '';
				$arrs['birth']	= '';
				$result = $rel->makeTicket($arrs);
				if($result['success']==1){
					$re_msg['code'] = 200;
					$re_msg['msg']  = $result['msg'];
					$re_msg['data'] = $result['data'];
					$d['status'] = 2;
					db("despeak")->where("despeak_id",$data['despeak_id'])->data($d)->update();
				}else{
					$re_msg['msg']  = $result['msg'];
				}
			}else{
				$re_msg['msg']  = "该预约不能在本区域取号,请前往对应区域取号";
			}
		}else{
			$re_msg['msg']  = $item['msg'];
		}
        echo json_encode($re_msg);
	}

	public function updateNewData()
	{
		if(!Cookie::has("curl_token"))
		{
			$url = 'http://114.116.81.59/api/token/getToken';
			$param = [
				'appid'=>'secret',
				'appsecret'=>'xsa2864'
			];
			$json = $this->requestPost($url, $param);
			$arr = json_decode($json,1);
			if($arr['code']==200){
				Cookie::set('curl_token',$arr['data']['token'],$arr['data']['expires_in']);
			}
		}else{
			$max_id = cache("max_id")?cache("max_id"):0;
			$url = 'http://114.116.81.59/api/v1/appointment/getDespeak';
			$param = [
				'token'=>cookie("curl_token"),
				'max_id'=>$max_id
			];
			$json = $this->requestPost($url, $param);
			$arr = json_decode($json,1);
			if($arr['code']==200){
				$data = array();
				$datas = array();
				$max_id = 0;
				foreach ($arr['data'] as $key => $value) {
            		$data['mobile'] 		= $value['mobile'];
            		$data['name'] 			= $value['name'];
            		$data['idcard'] 		= $value['idcard'];
            		$data['noChar'] 		= $value['noChar'];
            		$data['queNo'] 			= $value['queNo'];
            		$data['despeakDate'] 	= $value['despeakDate'];
            		$data['despeakTime'] 	= $value['despeakTime'];
            		$data['time_Part_S'] 	= $value['time_Part_S'];
            		$data['time_Part_O'] 	= $value['time_Part_O'];
            		$data['unitId'] 		= $value['unitId'];
            		$data['unitName'] 		= $value['unitName'];
            		$data['hallNo'] 		= $value['hallNo'];
            		$data['hallName'] 		= $value['hallName'];
            		$data['queId'] 			= $value['queId'];
            		$data['doctor_id'] 		= $value['doctor_id'];
            		$data['queName'] 		= $value['queName'];
            		$data['remark'] 		= $value['remark'];
            		$data['platform'] 		= $value['platform'];
            		$data['inTime'] 		= $value['inTime'];
            		$data['status'] 		= $value['status'];
            		$data['addtime'] 		= $value['addtime'];
            		$datas[] = $data;
            		$max_id = $max_id<$value['despeak_id']?$value['despeak_id']:$max_id;
				}
				cache("max_id",$max_id);
				$rs = Db::name("despeak")->insertAll($datas);
				// echo $rs;
			}else{
				// echo $arr['msg'];
			}
		}
	}
	/**
     * 模拟post进行url请求
     * @param string $url
     * @param string $param
     */
    public function requestPost($url = '', $param = '') {
        if (empty($url) || empty($param)) {
            return false;
        }
        
        $postUrl = $url;
        $curlPost = $param;
        $ch = curl_init();//初始化curl
        curl_setopt($ch, CURLOPT_URL,$postUrl);//抓取指定网页
        curl_setopt($ch, CURLOPT_HEADER, 0);//设置header
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);//要求结果为字符串且输出到屏幕上
        curl_setopt($ch, CURLOPT_POST, 1);//post提交方式
        curl_setopt($ch, CURLOPT_POSTFIELDS, $curlPost);
        $data = curl_exec($ch);//运行curl
        curl_close($ch);
        
        return $data;
    }
}