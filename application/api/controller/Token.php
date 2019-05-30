<?php
namespace app\api\controller;

use think\Controller;
use think\Facade\Config;
use think\Facade\Cache;
use think\Facade\Request;
use think\Db;

/**
假设用户仍以用户名"admin"，口令"hello"登录成功，系统可以知道：
该用户的id，例如，1230001；
该用户的口令，例如，"hello"；
Cookie过期时间，可由当前时间戳＋固定时长计算，例如，1461288165；
系统固定的一个随机字符串，例如，"secret"。
把上面4部分拼起来，得到：

"1230001:hello:1461288165:secret"
计算上述字符串的md5，得到："d9753...004d5"。

最后，按照用户id，过期时间和最终的hash值，拼接得到Cookie如下：

"1230001:1461288165:d9753...004d5"
当浏览器发送Cookie回服务器时，我们就可以按照下面的方式验证Cookie：

把Cookie分割成三部分，得到用户id，过期时间和hash值；
如果过期时间已到，直接丢弃；
根据用户id查找用户，得到用户口令；
按照生成Cookie时的算法计算md5，与Cookie自带的hash值对比。
 */
class Token extends Controller
{
    public static $expires = 3600*24*12;
    /**
     * 生成token
     */
    public function getToken()
    {    
        //参数验证
        $re_msg['code'] = 0;
        $re_msg['msg'] = 'OK';
        $arr = Config::get("token");
        $appid = input('appid','123');
        $appsecret = input('appsecret','123456');

        $ip = Request::instance()->ip();
        if(empty(Cache::get($ip))){
            Cache::set($ip,1,30);
        }else if(Cache::get($ip)<=5){
            Cache::inc($ip);
        }else{
            $re_msg['msg'] = '请勿频繁请求!';
            echo json_encode($re_msg);exit;
        }

        if(isset($arr[$appid])){
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
            $re_msg['msg'] = 'appid参数值有误!';
        }
        echo json_encode($re_msg);
    }
    // 判断是否存在
    public function is_no(){
        $log = new \app\api\model\Log;
        $log->save_log('123');
    }
}