<?php
namespace app\pavilion\model;

use think\DB;
use think\Model;
use think\facade\Env;
use think\facade\Cache;


class PushCache extends Model
{
	/*
	 * 缓存诊室屏和综合屏信息
	 * ip=编号 doctor_id=医生ID queue_id=队列ID hall_id=区域ID
	 */
	public function setCacheInfo($ip=0,$doctor_id=0,$queue_id=0,$hall_id=0)
	{
		$num = 0;
		$terminal = DB::name("z_terminal")->where("ip",$ip)->find();
		$doctor_info = DB::name("z_doctor")->field("QueName,type,pic,AlternateField1")->where("id",$doctor_id)->find();
		$wait = array();
	    $wait[] = ['que_id','=',$queue_id];
	    $wait[] = ['doctor_id','in',[0,$doctor_id]];
	    $wait[] = ['status','>=',0];
	    $wait[] = ['status','<=',2];
	    $wait[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
	    $wait_list = DB::name("z_ticket")->field("prefix,code,name,status,order,title")->order("status desc,sort desc,pid asc")->where($wait)->select();

        if(!empty($terminal['devices_ip'])){            
            $arr = $this->setRoomScreenInfo($hall_id,$doctor_info,$terminal,$wait_list);
            $num = $arr['queue_wait_list'];
        }
        $this->setHallScreenInfo($hall_id,$doctor_info,$terminal,$wait_list);
        $terminal['number'] = $num;
        return $terminal;
	}
	/*
	 *缓存诊室屏信息
	 * hall_id=区域ID doctor_info=医生信息 terminal=呼叫器信息 wait_list=等待列表
	 */
	public function setRoomScreenInfo($hall_id=0,$doctor_info=[],$terminal=[],$wait_list=[])
	{
		if(!empty($terminal)){
			$cache = Cache::get("roomScreen");
			$arr   = json_decode($cache,1);
			$ip    = $terminal['devices_ip'];	//显示屏ip
			$arr[$ip]['room_name'] 	= $terminal['room_name'];
			// 医师信息
			if($doctor_info){
				$arr[$ip]['user_name'] 	= $doctor_info['QueName'];
				$arr[$ip]['user_type']  = $doctor_info['type'];
				$arr[$ip]['user_pic'] 	= $doctor_info['pic'];
				$arr[$ip]['user_brief'] = $doctor_info['AlternateField1'];
			}
			// 队列信息
			if($wait_list){
	        	$sent = new \app\pavilion\model\Sentence;
	        	$num = 0;
	        	$queue_list = [];
	        	$arr[$ip]['queue_title'] 		= '';
	        	foreach ($wait_list as $key => $value) {         
	        		$value['room_name'] = $terminal['room_name'];
	                $value['QueName']   = $terminal['title'];
	                $value['seat_name'] = $terminal['seat_name'];   	
	                if($value['status']==2){
	                	$arr[$ip]['queue_title'] = $sent->houseString($value,0,0,3);
	                }else if($value['status']==1){
	        			$queue_list[] = $sent->houseString($value,$hall_id);
	        			$num ++;
	                }
	        	}
	        	$arr[$ip]['queue_list'] 		= $queue_list;
				$arr[$ip]['queue_wait_list'] 	= $num;
	        }
        	$time = strtotime(date("Y-m-d",time())." 23:59:59") - time();
			Cache::set("roomScreen",json_encode($arr),$time);
		}
		return $arr[$ip];
	}

	// 缓存综合屏信息
	public function setHallScreenInfo($hall_id=0,$doctor_info=[],$terminal=[],$wait_list=[])
	{
		$cache = Cache::get("hallScreen");
		$arr   = json_decode($cache,1);

		// 队列信息
		if($wait_list){
            $sent = new \app\pavilion\model\Sentence;
            $n 			= 0;
            $p 			= 0;
            $h_now 		= '';
            $wait 		= '';
            $pass 		= '';
            $list 		= [];
            $list['doctor_name'] 	= $doctor_info['QueName'];
            $list['seat_name'] 		= $terminal['seat_name'];    
            $list['room_name'] 		= $terminal['room_name'];        

            $Voice = new \app\admin\model\Voice;
            foreach ($wait_list as $key => $value) {
                $value['room_name'] = $terminal['room_name'];
                $value['QueName']   = $terminal['title'];
                $value['seat_name'] = $terminal['seat_name'];
                //综合显示屏内容
                if($value['status']==2){
                    $h_now = $sent->houseString($value,$hall_id,1);
                    // 语音推送
                    $v_str = $sent->houseString($value,$hall_id,1,1);                
                    if($v_str){                            
                        $Voice->broadcast($v_str['str'],$v_str['addr_id']);        
                    }
               
                    $list['user_name']  = $sent->pregName($value['name']);
           	 		$list['queue_name'] = $value['title'];   
           	 		$list['code'] 		= $value['prefix'].$value['code'];                          
                }else if($value['status']==1){
                    if($n < 2){
                        if($wait!=''){
                            $wait .= '、';
                        }
                        $wait .= $sent->houseStr($value);
                    }
                    $n ++;
                }else if($value['status']==0){
                    if($p <= 5){
                        if($pass!=''){
                            $pass .= '、';
                        }
                        $pass .= $sent->houseStr($value);
                    }
                    $p ++;
                }
            }
            $list['wait'] = $wait;     
            $arr[$hall_id]['title']					 = $h_now; 
            $arr[$hall_id]['wait']					 = $wait; 
            $arr[$hall_id]['pass']					 = $pass; 
			$arr[$hall_id]['list'][$terminal['id']]  = $list;
        }	        
        $time = strtotime(date("Y-m-d",time())." 23:59:59") - time();
		Cache::set("hallScreen",json_encode($arr),$time);
		return $arr[$hall_id];
	}

	// 获取显示屏信息
	public function getScreenInfo($ip='',$device='roomScreen')
	{
		$cache = Cache::get($device);
		$arr   = json_decode($cache,1);
		if($device=='hallScreen'||$device=='operScreen'){
			$data = [];
			if(isset($arr[$ip])){
				$arr[$ip]['list'] = array_values($arr[$ip]['list']);
				$data = $arr[$ip];
			}
		}else{
			$data = isset($arr[$ip]) ? $arr[$ip] : [];
		}
		return $data;
	}

	// 缓存手术室信息
	public function operScreen($ter=[],$arrs=[],$doctor_name='')
	{
		$cache = Cache::get("operScreen");
		$arr   = json_decode($cache,1);

        $hall_id 			= $ter['hall_id'];
        $arrs['room_name'] 	= $ter['room_name'];
        
        $sent       = new \app\pavilion\model\Sentence;
        //综合显示屏内容
        $content = $sent->houseString($arrs,$hall_id,1);
        $arr[$hall_id]['title'] = $content;
        $arr[$hall_id]['pass'] = '';
        // 语音推送
        $v_str = $sent->houseString($arrs,$hall_id,1,1);
        if($v_str){                            
            $Voice = new \app\admin\model\Voice;
            $Voice->broadcast($v_str['str'],$v_str['addr_id']);        
        }
           
        // 手术屏显示内容
        $queue['name']     			= $sent->pregName($arrs['name']);
        $queue['doctor_name']     	= $doctor_name;
        $queue['status_name']  		= $arrs['status_name'];
        $queue['room_name'] 		= $ter['room_name'];        
        $arr[$hall_id]['list'][$ter['hall_id']] = $queue;
        $time = strtotime(date("Y-m-d",time())." 23:59:59") - time();
        Cache::set("operScreen",json_encode($arr),$time);
		return $arr[$hall_id];
	}
}