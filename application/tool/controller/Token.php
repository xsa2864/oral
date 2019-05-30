<?php
namespace app\tool\controller;


use think\Controller;
use think\Request;
use think\Db;

class Token extends Controller
{
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
        if($arr['code']==200){
            $data['Machine_code']       = $mac;
            $data['Available_time']     = $day;
            $data['Registration_code']  = $arr['data'];
            $data['Registration_time']  = time();
            $rs = Db::table("zkitsoft_key")->insert($data);
        }
        echo json_encode($arr);
    }
}