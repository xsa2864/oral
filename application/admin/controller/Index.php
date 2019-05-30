<?php
namespace app\admin\controller;

use think\Controller;
use think\facade\Cookie;
use think\facade\Session;
use think\facade\Env;

class Index extends Controller
{
    public function index()
    {
        return $this->redirect('admin/Login/index');
    }
    /**
     * 清除模版缓存 不删除 temp目录
     */
    public function celarTemp() {
        $my_files = (array)glob(Env::get("RUNTIME_PATH") .'temp/*.php');
        array_map(function($v){ if(file_exists($v)) @unlink($v); }, $my_files);
    }

    /**
     * 退出
     */
    public function logout()
    {
        Session::delete('remind');
        Session::delete('user');
        echo json_encode(array('success'=>1,'msg'=>'退出成功'));
    }
}


