<?php
namespace app\api\controller\v2;

use think\Request;
use app\api\controller\Send;
use app\api\controller\Oauth;
use think\facade\Cache;
use think\Db;

class Token extends Controller
{
    public static $expires = 3600*24*12;
    /**
     * 生成token
     */
    public function getToken()
    {    
        //参数验证
        $re_msg['code'] = 201;
        $re_msg['msg'] = 'fail';

        $staff_code = input('staff_code');
        $password 	= input('password');

        // $ip = Request::instance()->ip();
        // if(empty(Cache::get($ip))){
        //     Cache::set($ip,1,30);
        // }else if(Cache::get($ip)<=5){
        //     Cache::inc($ip);
        // }else{
        //     $re_msg['msg'] = '请勿频繁请求!';
        //     echo json_encode($re_msg);exit;
        // }
        $where[] = ['staff_code','=',$staff_code];
        $pwd = DB::name("z_doctor")->where($where)->value("password");
        if($pwd == md5($password)){
            if($appsecret == $arr[$appid]){                
                $time = time()+self::$expires;
                $str = $appid.":".$arr[$appid].":".$time.":".$arr['secret'];
                $md5Str = md5($str);
                $cookie = "$appid:$time:$md5Str";  
                Cache::set('token',$md5Str,self::$expires);
                $data['token']      = $cookie;
                $data['expires_in'] = $time;
                $re_msg['code']     = 1;
                $re_msg['data']     = $data;
            }else{
                $re_msg['msg'] = 'appsecret参数值有误!';
            }
        }else{
            $re_msg['msg'] = '账号或者密码错误!';
        }
        return json($re_msg);
    }
    // 判断是否存在
    public function is_no(){
        $log = new \app\api\model\Log;
        $log->save_log('123');
    }
}