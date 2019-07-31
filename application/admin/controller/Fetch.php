<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;

class Fetch extends Base
{
	// 取号配置
    public function index(){
    	$wh = array();        
        $unit_id = input('unit_id',1);
        if($this->userid!=1){
            $wh[] = ['unitid','=',$this->unitid];
        }        
        $unit = db("unit")->where($wh)->select();
        $list = db("config_fetch")->where($wh)->find();
        $this->assign("list",$list);
        $this->assign("unit",$unit);
        $this->assign("unit_id",$unit_id);
    	return $this->fetch('index');
    }

    // 保存配置信息
    public function save(){
    	$re_msg['success'] = 0;
    	$re_msg['msg'] = '更新失败';
        $data['fetch_area']     = input("fetch_area",0);
        $data['fetch_number']   = input("fetch_number",3);
        $data['next']           = input("next",0);
        $data['today']          = input("today",0);
        $data['prefix']         = input("prefix",0);
        $data['if_name']        = input("if_name",0);
        $data['half_day']       = input("half_day",0);
        $data['fetchTime']      = input("fetchTime",0);
        $data['degree']         = input("degree","");
        $data['des_day']        = input("des_day",0);
        $data['blacklist_num']  = input("blacklist_num",0);
        $data['blacklist_day']  = input("blacklist_day",0);

        $data['yuyue_num']      = input("yuyue_num",0);
        $data['gs_time']        = input("gs_time",0);
        $data['ge_time']        = input("ge_time",0);
        $data['as_time']        = input("as_time",0);
        $data['ae_time']        = input("ae_time",0);
        $data['warning']        = input("warning","");
        $unitid                 = $this->unitid;
        $id                     = input("id",0);
        $rs = 0;
        $where[] = ['unitid','=',$unitid];
        $where[] = ['id','=',$id];
    	$result = db("config_fetch")->where($where)->find();
    	if($result){
    		$rs = db("config_fetch")->where('id',$id)->update($data);
        	if($rs!==false){
        		$re_msg['success'] = 1;
        		$re_msg['msg'] = '更新成功';
        	}
    	}else{
    		$data['unitid'] = $unitid;
    		$rs = db("config_fetch")->data($data)->insert();
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '添加成功';
            }
    	}
    	echo json_encode($re_msg);
    }
}