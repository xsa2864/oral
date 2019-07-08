<?php
namespace app\api\controller\v2;

use think\Controller;
use think\facade\Request;
use think\facade\View;
use think\facade\Env;
use think\facade\Config;

class Base extends Controller
{
    public $user;
    public $ter;
    public function initialize()
    {
        $re_msg['code'] = 201;
        $re_msg['msg'] = '登录失败。';
        $flag = false;
        $ip = input("ip",'');
        $token = input("token",'2:1562751346:ba5e010d5880093a0c2be34991c74d15');
        if(empty($ip)){            
            $ccd = new \app\api\model\CacheCode;
            $ip = $ccd->getCode();  
        }
        if($token){            
            $user_arr = explode(":", $token);
            if($user_arr[1] > time())
            {
                $rs = db("z_doctor")->where("id",$user_arr[0])->find();
                $secret = 'think';
                $str = $user_arr[0].":".$rs['staff_code'].":".$user_arr[1].":".$secret;
                $c_sign = md5($str);
                if($c_sign==$user_arr[2]){
                    if($rs['token']==$c_sign){                    
                        $ter = array();
                        if($ip){
                            $ter = db("z_terminal")->where("ip",$ip)->find();
                            if($ter){                                
                                $this->ter = $ter;
                                $this->user = $rs;
                                $flag = true;
                            }else{
                                $re_msg['msg'] = '还未绑定呼叫器';
                            }
                        }else{
                            $re_msg['msg'] = '缺少参数ip或者地址。';
                        }
                    }else{
                        $re_msg['msg'] = '账号已经在其它地方登录,请重新登录账号。';
                    }
                }
            }else{
                $re_msg['msg'] = '登录已过期，请重新登录。';
            }        
        }
        if(!$flag){
            echo json_encode($re_msg);
            exit;
        }
    }
}