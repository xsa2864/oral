<?php
namespace app\api\controller\v2;

use think\Controller;
use think\Request;
use think\Validate;
use think\DB;
use think\facade\Cookie;

class Call extends Controller
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
			'staff_code'  	=> 'require',
			'interface_code'=> 'require',
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
}