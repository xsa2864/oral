<?php
namespace app\api\controller\a1;

use think\Controller;
use think\Request;
use think\DB;
use think\facade\Cookie;

class Index extends Controller
{
    /**
     * 显示版本信息
     *
     * @return \think\Response
     */
    public function index()
    {
        return json(['Hospital'=>'预约平台','Version'=>'a1']);
    }

    public function getBase()
    {
        $unit_id = input("unit_id",0);
        if($unit_id){
            Cookie::set('unit_id',$unit_id,3600*24*30);
        }
        if(Cookie::has('unit_id')){
            $unit_id = Cookie::get('unit_id');
            $where['unitid'] = $unit_id;
        }else{
            $where['unitid'] = $unit_id;
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

        $data['attr1'] = isset($rs)?json_decode($rs['attr'],1):array();
        $data['attr2'] = isset($result)?json_decode($result['attr'],1):array();

        return json($data);
    }
}