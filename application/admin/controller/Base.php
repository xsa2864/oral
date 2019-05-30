<?php
namespace app\admin\controller;

use think\Controller;
use think\Loader;
use think\facade\Session;
use think\facade\Request;
use think\facade\View;
use think\facade\Config;
use think\facade\Env;


class Base extends Controller
{
    protected $current_action;
    public $user;
    public $unitid;
    public $userid;
    public function initialize()
    {
        $this->user = Session::get('user'); 
        $rules = Session::get('user.rules');      
        $this->unitid = Session::get('user.unitid');
        $this->userid = Session::get('user.UserId');
        $this->hallid = Session::get('user.hallid');

        $where[] = ['status','=',1] ;
        $where[] = ['ismenu','=',1] ;
        $admin_flag = 0;
        if($this->userid!=1){ 
            $where[] = ['id','in',explode(',', $rules)];
        }else{
            $admin_flag = 1;
        }
        $rs = db("auth_rule")->where($where)->order('sort asc')->select();
        $listmenu = $this->getc_Rule($rs);

        // 注册登录ip信息
        $ccd = new \app\api\model\CacheCode;
        $devices_ip = $ccd->getCode(); 
        // $devices_ip = request()->ip();
        $set = new \app\pavilion\model\Organize();
        $arr = $set->setAdmin($this->hallid,$devices_ip);

        View::share('user',$this->user);
        View::share('listmenu',$listmenu);
        View::share('admin_flag',$admin_flag);
        View::share('rules',explode(',', $rules));
        View::share('title',$this->userid==1?'超级管理员':Session::get('user.title'));
        View::share('devices_ip',$devices_ip);
        $sur = new \app\admin\model\Survey;
        View::share('validDate',$sur->getValidDate());
        if($this->hallid){
            $whm[] = ['m.hall_id','=',$this->hallid];
        }
        $whm[] = ['m.status','=',0];
        $whm[] = ['m.type','=',1];
        $msg_list = db("z_message")->alias("m")->field("m.id,d.QueName,m.content")->leftJoin("z_doctor d","d.id=m.doctor_id")->where($whm)->order("d.id","desc")->limit(10)->select();
        View::share('msg_list',$msg_list);

        $g = array();
        if($this->userid!=1 && $this->hallid){
            $g[] = ['hall_id','=',$this->hallid];
        }
        $g[] = ['status','=',0];
        $g[] = ['type','=',1];
        $msg_num = db("z_message")->field("count(*) as num")->where($g)->find();
        View::share('msg_num',$msg_num['num']);
        if($this->unitid){
            View::share('unitname',db("unit")->where("UnitId",$this->unitid)->value("unitname"));
        }

        if(!Session::get('user')){
            return $this->redirect('admin/login/index');
        }

        $rel = new \app\admin\model\Relations();
        $larr = $rel->getOnLine($this->hallid,$this->userid); 
        $ilist = '';
        if($arr){
            $keys = array_keys($larr);
            $lwh[] = ['d.id','in',$keys];
            $irs = db("z_doctor")->alias("d")
                    ->field("d.id,d.QueName as name")
                    ->where($lwh)->select();
            foreach ($irs as $key => $value) {
                $value['title'] = $larr[$value['id']];  
                $ilist[$key] = $value;
            }
        }
        View::share('ilist',$ilist);

        $auth = new \org\Auth();
        $this->current_action = request()->module().'/'.request()->controller().'/'.lcfirst(request()->action());
        $result = $auth->check($this->current_action,Session::get('user.UserId'));

        // 记录操作日志
        if (Request::instance()->isPost()){
            $log = new \app\admin\model\OperationLog;
            $log->writeLog($this->userid,input(),$this->current_action);
        }
       
        if(!$result){
            if (Request::instance()->isPost()){
                // echo json_encode(array('success'=>0,'msg'=>'没有权限'));
                // exit;
            }else{                
                if('admin/Main/index'!=$this->current_action){
                    return $this->redirect('admin/Main/index');
                }
            }
        }
    }
    // 递归
    public function getMenu($arr,$pid=0){
    	$data = array();
    	foreach ($arr as $key => $value) {
    		if($value['pid']==$pid){
    			$value['child'] = $this->getMenu($arr,$value['menu_id']);
    			$data[] = $value;
    		}
    	}
    	return $data;
    }
    // 规则递归
    public function getc_Rule($arr,$pid=0,$check=array()){
        $data = array();
        foreach ($arr as $key => $value) {
            if($value['pid']==$pid){
                $value['checked'] = in_array($value['id'], $check)?1:0;
                $value['child'] = $this->getc_Rule($arr,$value['id'],$check);
                $data[] = $value;
            }
        }
        return $data;
    }
    // 查询操作平台
    public function get_platform(){
        $platform = '4';
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($agent, 'windows nt')){
            $platform = 'windows';
        }else if(strpos($agent, 'mac os')){
            $platform = 'IOS';
        }else if(strpos($agent, 'iphone')){
            $platform = 'iphone';
        }else if(strpos($agent, 'android')){
            $platform = 'android';
        }else if(strpos($agent, 'ipad')){
            $platform = 'ipad';
        }
        return $platform;
    }

}