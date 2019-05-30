<?php 
namespace app\admin\model;

use think\Model;
use think\Db;
use think\facade\Env;
use think\facade\Config;

class Survey extends Model
{
	public function getValidDate()
	{
		$day = 0;
		if(Config::has('activation.active')){      
            $time = config("activation.active.time");
			$day = round(($time-time())/3600/24);
        }
		return $day;
	}
	/*
	 * 监测授权时间
	 */
	public function isValid()
	{
		$re_msg['code']  = 201;
        $re_msg['msg']   = '还未激活';

        if(!Config::has('activation.active')){      
            $re_msg['code']  = 202;
            $re_msg['msg']      = '可以免费激活';
        }else{
            $time = config("activation.active.time");
            $vtime = $time-3600*24*30;
            if($time < time()){
                $re_msg['msg']      = '本软件已过使用期限，请您报告给单位主管部门，联系供应商进行重新授权！';
            }else if($vtime < time()){
            	$re_msg['code']  = 203;
                $day = round(($time-time())/3600/24);
                $re_msg['msg']      = '本软件还剩'.$day.'天过期，请您报告给单位主管部门，联系供应商进行重新授权！';
            }else{
            	$re_msg['code']  = 200;
            	$re_msg['msg']      = '已经激活';
            }
        }
		return $re_msg;
	}

	// 激活
    public function activation($token='',$flag=false)
    {
        $re_msg['code']  = 201;
        $re_msg['msg']      = '激活失败';

		$filename = Env::get("CONFIG_PATH")."activation.php";        

        if($flag){
        	if(!Config::has('activation.active')){  
	            $time = time()+45*24*3600;
	            $arr = ['active'=>[
	                'time'=>$time,
	                'code'=>[]
	            ]];           
	            $rs = file_put_contents($filename, "<?php \r\n return " . var_export($arr, true) . ";");
	            if($rs){	            	
		            $re_msg['code']  = 200;
		            $re_msg['msg']   = '试用激活成功';
	            }else{
	            	$re_msg['msg']   = '试用激活失败';
	            }
	        }else{
	        	$re_msg['msg']   = '试用资格已经试用过';
	        }
	        if($token==''){
	        	return $re_msg;
	        }
        }

        if($token){
            $str = base64_decode($token);
            $array = explode('|', $str);
            if(count($array)!=3){
            	$re_msg['msg']      = '激活码无效';
            }else{            	
	            $mac = $this->getMac();

	            $sign   = $array[0];
	            $day    = $array[1];
	            $time   = $array[2];
	            $script = 'zkyd';
	            $code = array();     

	            $str = $mac.$day.$script.$time;
	            $activation  = config("activation.");
	            $code = config("activation.active.code")?config("activation.active.code"):[];
	            if(!in_array($sign, $code))
	            {
	                if(md5($str)===$sign)
	                {
	                    // $activation['active']['time'] = isset($activation['active'])?$activation['active']['time']+$day*3600*24:time()+$day*3600*24;
	                    $activation['active']['time'] = time()+$day*3600*24;
	                    array_push($code, $sign);
	                    $activation['active']['code'] = $code;
	                    $filename = Env::get("CONFIG_PATH")."activation.php";
	                    $rs = file_put_contents($filename, "<?php \r\n return " . var_export($activation, true) . ";");
	                    if($rs){
	                        $re_msg['code']  = 200;
	                        $re_msg['msg']   = '激活码成功';
	                    }
	                }else{
	                    $re_msg['msg']  = '激活码无效';
	                }
	            }else{
	                $re_msg['msg']  = '激活码使用过了';
	            }
            }
        }else{
            $re_msg['msg']      = '激活码不能为空';
        }
        
        return $re_msg;
    }
    // 获取激活码
    public function getToken($mac='',$day=30)
    {
    	$re_msg['code']  = 201;
        $re_msg['msg']      = '获取激活码失败';

        if($mac==''){
        	$re_msg['msg']      = 'MAC地址不能为空';
        }else{        	
	        $script = 'zkyd';
	        $time   = time();
	        $str = $mac.$day.$script.$time;
	        $sign = md5($str).'|'.$day.'|'.$time;
	        if($sign){
	        	$re_msg['code']  = 200;
	       	 	$re_msg['msg']   = '获取激活码成功';
	       	 	$re_msg['data']  = base64_encode($sign);
	        }
        }
        return $re_msg;
    }
    public function getMac()
    {
    	$mac = '';
    	@exec("ipconfig /all",$array); 
	    foreach ($array as $key => $value )
	    {
	        if (preg_match("/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i", $value, $arr ) )
	        {
	            $mac = $arr[0];
	            break;
	        }
	    }
    	$str = str_replace("-","",$mac);
    	return $str;
    }
}