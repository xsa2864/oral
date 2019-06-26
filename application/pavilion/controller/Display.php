<?php
namespace app\pavilion\controller;


use think\Controller;
use app\common\model\User as UserModel;
use think\facade\Cookie;
use think\facade\Cache;
use think\helper\Hash;
use think\Queue;
use think\facade\Request;

class Display extends Controller
{   
    /*
     * transverse = 横 and vertical = 竖
     * 2019-05-30
     */
    // 诊室横屏  
    public function roomTone()
    {
        return $this->fetch("roomTone");
    }
    // 诊室竖屏 
    public function roomVone()
    {
        return $this->fetch("roomVone");
    }
    // 大厅横屏
    public function hallTone()
    {
        return $this->fetch("hallTone");
    }
     // 大厅横屏
    public function largeVues2()
    {
        return $this->fetch("largeVues2");
    }
    // 大厅竖屏
    public function hallVone()
    {
        return $this->fetch("hallVone");
    }
    // 手术横屏
    public function operationTone()
    {
        return $this->fetch("operationTone");
    }
    // 手术竖屏
    public function operationVone()
    {
        return $this->fetch("operationVone");
    }


	public function index()
	{
        if(Cookie::has('devices_ip')){
            $devices_ip = cookie("devices_ip");
        }else{
            // $devices_ip = request()->ip();
            $ccd = new \app\api\model\CacheCode;
            $devices_ip = $ccd->getCode();  
        }
        $set = new \app\pavilion\model\Organize;
        $arr = $set->setDefault($devices_ip);

        $zh = array();
        $zh[] = ['type','=',0];
        $zh[] = ['screen_type','=',0];
        $zh[] = ['hall_id','in',[0,$arr['devices_area_id']]];
        $rule = db("z_voice")->where($zh)->order("hall_id desc")->find();

        $ads_img = '';
        $where = array();
        $where[] = ['hall_id','=',$arr['devices_area_id']];
        $where[] = ['type','=',1];
        $ads = db("ads")->where($where)->find();
        if($ads){
            $attr = json_decode($ads['attr'],1);
            $ads_img = $attr[0]['img'];
        }
        $cre = array();
        $cre[] = ['hall_id','=',$arr['devices_area_id']];
        $cre[] = ['type','=',2];
        $cads = db("ads")->where($cre)->find();
        $img_list = 0;
        if($cads){
            $img_list = json_decode($cads['attr'],1);
        }
        
        $this->assign("doctor",$arr);
        $this->assign("ads_img",$ads_img);
        $this->assign("img_list",$img_list);
        $this->assign("devices_video",'');
        $this->assign("rule",$rule);
		$this->assign("devices_ip",$devices_ip);
        // return $this->fetch("index");
		// return $this->fetch("indexthree");
        return $this->fetch("indextwo");
	}
    
    // 修改通信編號
    public function changeCode()
    {
        if(Request::isPost()){
            $devices_ip = input("devices_ip",0);
            cookie("devices_ip",$devices_ip);
            return true;
        }
        return false;
    }

    // 综合显示屏
    public function large(){
        if(Cookie::has('devices_ip')){
            $devices_ip = cookie("devices_ip");
        }else{
            // $devices_ip = request()->ip();
            $ccd = new \app\api\model\CacheCode;
            $devices_ip = $ccd->getCode();  
        }
        $set = new \app\pavilion\model\Organize;
        $arr = $set->setDefault($devices_ip);
       
        $hall_id    = 0;
        $tips       = '';
        if(cache("devices")){
            $json = cache("devices");
            $arr = json_decode($json,1);
            if(isset($arr[$devices_ip])){
                $hall_id = $arr[$devices_ip]['devices_area_id'];
                $tips    = $arr[$devices_ip]['devices_tips'];
                $queue = cache("queue");
                $arrs = json_decode($queue,1);
                $hour = date("H",time());
                if($hour<10){
                    unset($arrs[$hall_id]);
                    cache("queue",json_encode($arrs));
                }
            }
        }
        // 综合屏顶部展示
        $top_logo = '';
        $top_text = '';
        $wh = array();
        $wh[] = ["hall_id",'=',$hall_id];
        $wh[] = ["type",'=',4];
        $wh[] = ["status",'=',1];
        $ads = db("ads")->where($wh)->find();
        if($ads){
            $arr_log = json_decode($ads['attr'],1);
            $top_logo = isset($arr_log[0]['img'])?$arr_log[0]['img']:'';
            $top_text = $ads['title'];
        }
        $this->assign("top_logo",$top_logo);
        $this->assign("top_text",$top_text);
        //综合屏图片展示
        $where = array();
        $where[] = ["hall_id",'=',$hall_id];
        $where[] = ["type",'=',3];
        $where[] = ["status",'=',1];
        $attr = db("ads")->where($where)->value("attr");
        $arr = json_decode($attr,1);
        
        $this->assign("img",$arr);

        $this->assign("tips",$tips);
        $this->assign("hall_id",$hall_id);
        $this->assign("devices_ip",$devices_ip);
        return $this->fetch("large");
    }
    // 综合显示屏数据
    public function showHallQueue()
    {
        $hall_id =  input("hall_id",0);
        $json = '';
        if(cache("queue")){
            $json = cache("queue");
            $arrs = json_decode($json,1);
            $jsons       = cache("devices");
            $arr         = json_decode($jsons,1);

            // $devices_ip  = request()->ip();
            $ccd = new \app\api\model\CacheCode;
            $devices_ip = $ccd->getCode();  
            $field = array();
            if(isset($arr[$devices_ip])){    
                // 显示字段
                $arrs["$hall_id"]['field'] = $arr[$devices_ip]['devices_field'];
            }
            $arrs["$hall_id"]['list'] = isset($arrs["$hall_id"]['list'])?array_values($arrs["$hall_id"]['list']):array();
            $arrs["$hall_id"]['number'] = count($arrs["$hall_id"]['list']);
            $json = json_encode($arrs["$hall_id"]);
        }
        echo $json;
    }

    public function test()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "执行失败";   
        $type = input("type","");
        if($type=='ping'){
            $re_msg['code'] = 200;
            $re_msg['msg']  = "pong";   
        }
        echo json_encode($re_msg);
    }

    // 手术显示屏
    public function operation(){
        if(Cookie::has('devices_ip')){
            $devices_ip = cookie("devices_ip");
        }else{
            // $devices_ip = request()->ip();
            $ccd = new \app\api\model\CacheCode;
            $devices_ip = $ccd->getCode();  
        }
        $set = new \app\pavilion\model\Organize;
        $arr = $set->setDefault($devices_ip);
        $hall_id    = 0;
        $tips       = '';
        if(cache("devices")){
            $json = cache("devices");
            $arr = json_decode($json,1);
            if(isset($arr[$devices_ip])){
                $hall_id = $arr[$devices_ip]['devices_area_id'];
                $tips    = $arr[$devices_ip]['devices_tips'];
                $queue = cache("queue");
                $arrs = json_decode($queue,1);
                unset($arrs[$hall_id]);
                cache("queue",json_encode($arrs));
            }
        }
        // 综合屏顶部展示
        $top_logo = '';
        $top_text = '';
        $wh = array();
        $wh[] = ["hall_id",'=',$hall_id];
        $wh[] = ["type",'=',4];
        $wh[] = ["status",'=',1];
        $ads = db("ads")->where($wh)->find();
        if($ads){
            $arr_log = json_decode($ads['attr'],1);
            $top_logo = $arr_log[0]['img'];
            $top_text = $ads['title'];
        }
        $this->assign("top_logo",$top_logo);
        $this->assign("top_text",$top_text);
        //综合屏图片展示
        $where = array();
        $where[] = ["hall_id",'=',$hall_id];
        $where[] = ["type",'=',3];
        $where[] = ["status",'=',1];
        $attr = db("ads")->where($where)->value("attr");
        $arr = json_decode($attr,1);
        
        $this->assign("img",$arr);

        $this->assign("tips",$tips);
        $this->assign("hall_id",$hall_id);
        $this->assign("devices_ip",$devices_ip);
        return $this->fetch("operation");
    }

	/**
     * 退出
     */
    public function logout()
    {
        cookie('user_info',null);
        cookie('queue_id', null);
        cookie('login_token', null);
        echo json_encode(array('success'=>1,'msg'=>'退出成功'));
    }
}