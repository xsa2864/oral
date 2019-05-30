<?php
namespace app\api\controller\t1;

use think\Controller;
use think\facade\Request;
use think\facade\View;
use think\facade\Env;
use think\facade\Config;

class Verify extends Controller
{
    public function initialize()
    {
        $re_msg['code'] = 201;
        $re_msg['msg'] = '验证失败。';
        $flag = false;
        $token = input("token",'');
        if($token){            
            $vtoken = md5(date("Y-m-d",time()));
            if($vtoken == $token){
                $flag = true;
                $re_msg['msg'] = '验证成功';
            }else{
                $re_msg['msg'] = '验证失败';
            }
        }
        if(!$flag){
            echo json_encode($re_msg);
            exit;
        }
    }
}