<?php
namespace app\admin\controller;


use app\tool\Tool;
use think\Controller;
use app\common\model\User as UserModel;
use think\facade\Cookie;
use think\facade\Session;
use think\helper\Hash;
use think\Queue;
use think\Request;

class Login extends Controller
{
    public function initialize()
    {
        if(!request()->isPost() || lcfirst(request()->action())!=''){            
            if (Session::has('user')) {
                return $this->redirect('admin/nurse/index');
            }
        }
    }

    /**
     * 显示登录表单
     * @return mixed
     */
    public function index()
    {
        $info = Session::get('user');
        if($info){
            $this->redirect('admin/nurse/index');
        }
        $sur = new \app\admin\model\Survey;
        $this->assign('validDate',$sur->getValidDate());
        return $this->fetch('login');
    }

    /**
     * 执行登录逻辑
     * @param Request $request
     * @return \think\response\Json
     * @throws \think\db\exception\DataNotFoundException
     * @throws \think\db\exception\ModelNotFoundException
     * @throws \think\exception\DbException
     */
    public function login(Request $request)
    {
        $re_msg['success']  = 0;
        $re_msg['msg']      = '登录失败';
        $data = $request->post();
        // $validate = $this->validate($data, "app\\admin\\validate\\Login");
        $username = input("username",'');
        // if(!captcha_check($data['captcha'])){
        //     return Tool::showError('验证码错误', ['token' => $request->token()]);
        // };
        $validate = new \app\admin\validate\Login;
        if (!$validate->check($data)) {
            $re_msg['msg']      = $validate->getError();
            echo json_encode($re_msg);
            exit;
        }
        $sur = new \app\admin\model\Survey;
        $arr = $sur->isValid();
        if($arr['code']==201 || $arr['code']==202){
            $re_msg['msg']      = $arr['msg'].'，再登录';
            echo json_encode($re_msg);
            exit;
        }

        $user = db("manager")->alias("m")
                ->field("m.*,g.title,g.rules")
                ->join("auth_group_access ga","ga.uid=m.UserId")
                ->join("auth_group g","g.id=ga.group_id")
                ->where('m.UserName',$username)->find();
        if (null === $user)
        {
            $re_msg['msg']      = '用户名或密码不正确';
        }
        else if (md5($data['password']) != $user['password'])
        {
            $re_msg['msg']      = '用户名或密码不正确';
        }
        else if ($user['status'] !== 1)
        {
            $re_msg['msg']      = '该用户已经被禁用！';
        }else{
            Session::set('user', $user);
            $re_msg['success']  = 1;
            $re_msg['msg']      = '登录成功';
        }
        echo json_encode($re_msg);
    }

    // 激活页面
    public function getGenuine()
    {
        $flag = input("flag",0);
        $where[] = ['EnableFlag','=',1];
        $item = db("unit")->where($where)->find();
        $mac = '';
        $sur = new \app\admin\model\Survey;
        $mac = $sur->getMac();
        $this->assign("item",$item);
        $this->assign("flag",$flag);
        $this->assign("mac",$mac);
        return $this->fetch('genuine');
    }
    public function activation()
    {
        $sur = new \app\admin\model\Survey;
        $arr = $sur->isValid();
        if($arr['code']==203){
            if(Session::has("remind")){
                $arr['code'] = 200;
            }else{
                session("remind",1);
            }            
        }
        echo json_encode($arr);
    }
    // 激活
    public function activationDo()
    {   
        $code = input("code","");
        $flag = input("flag",false);
        $sur = new \app\admin\model\Survey;
        $arr = $sur->activation($code,$flag);
        echo json_encode($arr);
    }
    // 生成激活号码页面
    public function getToken()
    {
        return $this->fetch('token');
    }

    public function makeToken()
    {
        $mac = input("mac","");
        $day = input("day",30);
        $sur = new \app\admin\model\Survey;
        $arr = $sur->getToken($mac,$day);
        echo json_encode($arr);
    }
    // 显示激活信息
    public function showinfo()
    {
        $where[] = ['EnableFlag','=',1];
        $item = db("unit")->where($where)->find();
        $mac = '';
        $sur = new \app\admin\model\Survey;
        $validDate = $sur->getValidDate();

        $sur = new \app\admin\model\Survey;
        $mac = $sur->getMac();
        $this->assign("item",$item);
        $this->assign("mac",$mac);
        $this->assign("validDate",$validDate);
        return $this->fetch('showinfo');
    }
}