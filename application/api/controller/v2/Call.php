<?php
namespace app\api\controller\v2;

use think\Controller;
use think\Request;
use think\Validate;
use think\DB;
use think\facade\Cookie;
use app\api\controller\v2\Base;

class Call extends Base
{
	// 医生工号、呯叫器ID、当前号码与姓名、三个等候的号码与姓名、
	public function operCall()
	{
		$data['staff_code'] = $staff_code	= input('staff_code','');
		$data['interface_code'] = $code 	= input('interface_code','');
		$data['ip'] 		= $ip 			= input('ip','');
		$data['list'] 		= $list 		= input('list','');
		//[{"prefix":"w","code":"1002","name":"\u6797\u5c0f\u5170","status":"2","order":"0","title":"\u5916\u79d1"},{"prefix":"w","code":"1003","name":"\u9648\u519b","status":"1","order":"0","title":"\u5916\u79d1"},{"prefix":"w","code":"1004","name":"\u674e\u56db","status":"1","order":"0","title":"\u5916\u79d1"}]

		$validate = Validate::make([
			'ip'  			=> 'require',
			'list'  		=> 'require',
		]);
		$result = $validate->check($data);
		$re_msg['code'] = 201;
		$re_msg['msg'] = '呼叫器端还未绑定！';
		if(!$result) {
		    $re_msg['msg'] =  $validate->getError();
		}else{			
			$list = json_decode($list,1);
			if(is_array($list)){				
				$terminal = DB::name("z_terminal")->where("ip",$ip)->find();			
				if($terminal){
					// $list = empty($list)?'':json_decode($list,1);
					$push = new \app\api\model\PushCache;
			        $push->setCacheInfos($code,$terminal,$list);       
			               	
				    // 获取推送目标
				    $arr_ip = array();
				    if(isset($terminal['hall_id'])){
				     	$org = new \app\api\model\Organize;
				     	$hall_ip = $org->getLargeIp($terminal['hall_id']);
				    	$arr_ip = $hall_ip;		        	
				    }
				    if(isset($terminal['devices_ip'])){
				        $arr_ip[] = $terminal['devices_ip']; 
				    }
				    if(!empty($arr_ip)){		    	
					    $soc = new \app\api\model\Socket;    
					    foreach ($arr_ip as $key => $value) {
					        $rs = $soc->terminalSocke($value,['call'=>1]);    
					        if($rs['success']==1){
					        	$re_msg['code'] = 200; 
					        }
					        $re_msg['msg']  = $rs['msg'];
					    }  	        
				    }else{
				    	$re_msg['msg'] = '没有推送信息目标';
				    }
				}
			}else{
				$re_msg['msg'] = 'list数据格式不对';
			}
		}
        return json($re_msg);
	}

	// 设置语言播放
    public function setVoice()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "播放语音失败";  

        $list       = input("list",'');
        $wait_list  = json_decode($list,1);        
        $ip       	= input("ip",'');
        $que_id 	= input("que_id",0);
        if($wait_list)
        {
			$ter = DB::name("z_terminal")->where("ip",$ip)->find();	
			if($ter){				
	            $sent = new \app\api\model\Sentence;
	            $Voice = new \app\api\model\Voice;
	            foreach ($wait_list as $key => $value) {
	                // 语音推送
	                $v_str = $sent->houseString($value,$ter['hall_id'],1,1);
	                if($v_str){                            
	                    $rs = $Voice->broadcast($v_str['str'],$ter['hall_id'],$ter['screen_code'],0,$que_id);
	                    if($rs){
	                        $re_msg['code'] = 200;
	                        $re_msg['msg']  = "播放语音成功";  
	                    }else{
	                    	$re_msg['msg']  = $rs['msg'];  
	                    }      
	                }   
	            }
			}else{
				$re_msg['msg']  = "呼叫器端还未绑定！";  
			}
        }
        return json($re_msg);
    }
}