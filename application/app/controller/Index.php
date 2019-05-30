<?php
namespace app\app\controller;

use think\Controller;
use think\Request;
use think\facade\Cookie;
use think\facade\Env;


class Index extends Common
{
    public function index()
    {
        $unitid = input("unitid",0);
        if($unitid){
            Cookie::set('unitid',$unitid,3600*24*30);
        }
        if(Cookie::has('unitid')){
            $unitid = Cookie::get('unitid');
            $where['unitid'] = $unitid;
        }else{
            $where['unitid'] = $unitid;
        }
        
        $where['status'] = 1;
        $where['form']   = 1;
        // 底部广告位
        $where['type'] = 2;
        $result = db("ads")->where($where)->order("id desc")->find();
        // 顶部广告位
        $where['type'] = 1;
        $rs = db("ads")->where($where)->order("id desc")->find();
        if(empty($rs)){
            $where['unitid'] = 0;
            $rs = db("ads")->where($where)->order("id desc")->find();
        }        

    	$list1 = isset($rs)?json_decode($rs['attr'],1):array();
    	$list2 = isset($result)?json_decode($result['attr'],1):array();
     
    	$this->assign("list1",$list1);
    	$this->assign("list2",$list2);
        return $this->fetch('index');
    }

    public function indexs()
    {
        return $this->fetch('indexVue');
    }
}
