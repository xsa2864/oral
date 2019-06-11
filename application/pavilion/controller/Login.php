<?php
namespace app\pavilion\controller;


use think\Controller;
use app\common\model\User as UserModel;
use think\facade\Cache;
use think\facade\Cookie;
use think\helper\Hash;
use think\Queue;
use think\Request;
use think\DB;

class Login extends Controller
{
    public function initialize()
    { 
        if (Cookie::has('user_info')) {
            // return $this->redirect('pavilion/index/index');
        }
    }

    /**
     * 显示登录表单
     * @return mixed
     */
    public function index()
    {              
        $ccd = new \app\api\model\CacheCode;
        $ip = $ccd->getCode();     
        $ter = DB::name("z_terminal")->alias("z")->field("z.seat_name,z.room_name,h.HallName")
                ->leftJoin("hall h","h.HallNo=z.hall_id")
                ->where("ip",$ip)->find();
        $info['username'] = Cookie::has('username')?Cookie::get("username"):'';
        // $info['password'] = Cookie::has('password')?Cookie::get("password"):'';
        $this->assign("info",$info);
        $this->assign("ter",$ter);
        return $this->fetch('login');
    }

    /**
     * 执行登录逻辑
     * @param Request $request
     * @return \think\response\Json
     */
    public function login()
    {
        $re_msg['success']  = 0;
        $re_msg['msg']      = '登录失败';

        $data['staff_code'] = input("username","");
        $data['password'] = input("password","");   
        Cookie::forever("username",$data['staff_code']);
        $sur = new \app\admin\model\Survey;
        $arr = $sur->isValid();
        if($arr['code']==201 || $arr['code']==202){
            $re_msg['msg']      = "软件还未授权，不能使用";
            echo json_encode($re_msg);
            exit;
        }

        $user = db("z_doctor")->alias("m")->where('m.staff_code',$data['staff_code'])->find();
        
        if($user){            
            if (md5($data['password']) != $user['password']){
                $re_msg['msg'] = '用户名或密码不正确!';
            }else if ($user['status'] !== 1){
                $re_msg['msg'] = '该用户已经被禁用!';
            }else{               
                $ccd = new \app\api\model\CacheCode;
                $ip = $ccd->getCode();
                $terminal_id = db("z_terminal")->field("id,z_type")->where("ip",$ip)->find();
                if($terminal_id){                    
                    $re_msg['success']  = 1;

                    $re_msg['data'] = $terminal_id['z_type'];
                    $num = $user['id'].time();
                    $login_token = md5($num);
                    $rs = db("z_doctor")->where("id",$user['id'])->update(['token'=>$login_token]);
                    Cookie::set('login_token',$login_token);
                }else{
                    $re_msg['success']  = 2;
                    $re_msg['msg'] = '该呼叫器还未注册,请注册完再登录!';
                }
            }
        }else{
            $re_msg['msg'] = '用户名或密码不正确!';
        }
        echo json_encode($re_msg);
    }

    // 注册
    public function register()
    {
        $ccd = new \app\api\model\CacheCode;
        $ip = $ccd->getCode();   
        cookie('user_info', null);
        $hall = DB::name("hall")->field("HallNo,HallName")->where("EnableFlag",1)->select();        
        $this->assign("hall",$hall);
        $ter = DB::name("z_terminal")->field("id,hall_id,room_name,seat_name,screen_code")->where("ip",$ip)->find();
        $this->assign("ter",$ter);
        return $this->fetch('register');
    }
    // 获取终端
    public function getTerminal(){
        $re_msg['code'] = 200;
        $re_msg['msg']  = "获取成功";  

        $hall_id    = input("hall_id",0);
        $list = db("z_terminal")->where("hall_id",$hall_id)->select();
        // $json       = cache("devices");
        // $arr        = json_decode($json,1);
        // $list       = array();
        // if($arr){
        //     foreach ($arr as $key => $value) {
        //         if($value['devices_area_id']==$hall_id){
        //             $list[]       = $value;
        //         }
        //     }
        // }

        $re_msg['data'] = $list;
        echo json_encode($re_msg);
    }
    // 保存配置
    public function setUp(){
        $re_msg['code'] = 201;
        $re_msg['msg']  = "注册失败";  
        $re_msg['data'] = '';
        $terminal_id      = input("terminal_id",0);
        // $ip      = request()->ip();
        $ccd = new \app\api\model\CacheCode;
        $ip = $ccd->getCode();        
        $result = DB::name("z_terminal")->where("id",$terminal_id)->find();
        if($result){
            $unset['ip'] = '';
            DB::name("z_terminal")->where("ip",$ip)->update($unset);
            $data['ip'] = $ip;
            $rs = DB::name("z_terminal")->where("id",$terminal_id)->update($data);
            if($rs!==false){
                $re_msg['code'] = 200;
                $re_msg['msg']  = "注册成功";
                $re_msg['data'] = $terminal_id;
            }
        }else{
            $re_msg['code'] = 201;
            $re_msg['msg']  = "管理端还未配置终端信息";  
        }
        if(!empty($re_msg['data'])){
            Cookie::forever('terminal_id', $re_msg['data']);
        }
        echo json_encode($re_msg);
    }    
}