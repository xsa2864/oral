<?php
namespace app\api\controller\t1;


use think\Controller;
use think\facade\Cookie;
use think\helper\Hash;
use think\Queue;
use think\Request;

class Login extends Controller
{   
    public function index()
    {
        $re_msg['code']  = 201;
        $re_msg['msg']   = '无效操作';
        echo json_encode($re_msg);
    }
    /**
     * 执行登录逻辑
     * @param Request $request
     * @return \think\response\Json
     */
    public function login()
    {
        $re_msg['code']  = 201;
        $re_msg['msg']      = '登录失败';

        $data['staff_code'] = input("username","");
        $data['password']   = input("password","");        

        $user = db("z_doctor")->alias("m")->where('m.staff_code',$data['staff_code'])->find();
       
        if($user){            
            if (md5($data['password']) != $user['password']){
                $re_msg['msg'] = '用户名或密码不正确!';
            }else if ($user['status'] !== 1){
                $re_msg['msg'] = '该用户已经被禁用!';
            }else{               
                $ip       = request()->ip();
                $terminal_id = db("z_terminal")->where("ip",$ip)->value("id");
                if($terminal_id){                    
                    $re_msg['code']     = 200;
                    $re_msg['msg']      = '登录成功';
                    $secret = 'think';
                    $time = time()+3600*24*7;
                    $str = $user['id'].":".$user['staff_code'].":".$time.":".$secret;
                    $sign = md5($str);
                    $login_token = $user['id'].":".$time.":".$sign;
                    $rs = db("z_doctor")->where("id",$user['id'])->update(['token'=>$sign]);
                    $re_msg['token']  = $login_token;
                }else{
                    $re_msg['code']  = 202;
                    $re_msg['msg']   = '该呼叫器还未注册,请注册完再登录!';
                }
            }
        }else{
            $re_msg['msg'] = '用户名或密码不正确!';
        }
        echo json_encode($re_msg);
    }

    // 获取终端
    public function getTerminal(){
        $re_msg['code'] = 200;
        $re_msg['msg']  = "获取成功";  

        $hall_id    = input("hall_id",0);
        $list = db("z_terminal")->where("hall_id",$hall_id)->select();
        $re_msg['data'] = $list;
        echo json_encode($re_msg);
    }
    // 保存配置
    public function setUp(){
        $re_msg['code'] = 201;
        $re_msg['msg']  = "注册失败";  
        $re_msg['data'] = '';
        $terminal_id      = input("terminal_id",0);
        $devices_ip       = input("ip",'');

        $result = db("z_terminal")->where("id",$terminal_id)->find();
        if($result){
            $data['ip'] = $devices_ip;
            $rs = db("z_terminal")->where("id",$terminal_id)->data($data)->update();
            if($rs!==false){
                $re_msg['code'] = 200;
                $re_msg['msg']  = "注册成功";
                $re_msg['data'] = $terminal_id;
            }
        }else{
            $re_msg['msg']  = "管理端还未配置终端信息";  
        }        
        echo json_encode($re_msg);
    }
}