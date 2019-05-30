<?php
namespace app\tool\controller;

use think\Controller;
use think\facade\Cache;
use think\facade\Cookie;

class Base extends Controller
{
    public $token;
    /**
     * @desc 
     * @throws Exception
     */
    public function initialize()
    {
        if(Cookie::has("token")){
            $this->token = cookie("token");
        }else{
            $url = "http://114.116.81.59/api/token/gettoken";
            $data['appid']      = '123';
            $data['appsecret']  = '123456';
            $ch = curl_init();
            curl_setopt($ch, CURLOPT_URL, $url);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            // post数据
            curl_setopt($ch, CURLOPT_POST, 1);
            // post的变量
            curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
            curl_setopt($ch, CURLOPT_HEADER, 0);
            //执行请求
            $output = curl_exec($ch);
            $arr = json_decode($output,1);
            if($arr['code'] == 1){
                Cookie::set('token',$arr['data']['token'],3600*24*6);
                $this->token = $arr['data']['token'];
            }
        }
    }

}