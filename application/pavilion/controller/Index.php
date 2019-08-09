<?php
namespace app\pavilion\controller;

use think\Controller;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\View;
use think\Db;
use think\facade\Env;
use think\facade\Config;


class Index extends Controller
{
    public $user;
    public $doctor_id;
    public $ter;
    public $terminal_id;
    public function initialize()
    {
        $flag = false;
        if(Cookie::has('login_token')){
            $login_token = Cookie::get('login_token');
            $rs = db("z_doctor")->where("token",$login_token)->find();
            if($rs){
                // $ip         = request()->ip();
                $ccd = new \app\api\model\CacheCode;
                $ip = $ccd->getCode();  
                $this->ter  = db("z_terminal")->where("ip",$ip)->find();
                $this->terminal_id  = $this->ter['id'];
                $this->user         = $rs;
                $this->doctor_id    = $rs['id'];
                $flag = true;
            }else{
                cookie('queue_id', null);
                cookie('login_token', null);
                if(request()->isPost()){
                    $re_msg['code'] = 204;
                    $re_msg['msg'] = '账号已经在其它地方登录,请重新登录账号。';
                    echo json_encode($re_msg);exit;
                }
            }   
        }
        if(!$flag){
            return $this->redirect('pavilion/login/index');
        }
    }
   
    public function index()
    {
        // 注册登录ip信息
        // $devices_ip = request()->ip();
        $ccd = new \app\api\model\CacheCode;
        $devices_ip = $ccd->getCode();
        $set = new \app\pavilion\model\Organize();
        $arr = $set->setTerminal($this->doctor_id,$devices_ip);

        $result = db("z_doctor")->where("id",$this->doctor_id)->find();
        $list   = array();
        $arr_id    = array();
        $where  = array();
        $mk_arr = array();
        if($result){
            $arr_id = array_filter(explode('|', $result['que_id']));
            $mk_arr = explode(',', $result['mk_que_id']);
        }
        $flag = Cookie::has('queue_id')?1:0;       
        if(!$flag){            
            $where[] = ['QueId','in',$arr_id];
            $list = db("serque")->field("QueId,QueName")->where($where)->select();
            if(count($list)==1){
                $arr = $set->setTerminal($this->doctor_id,$devices_ip,$list[0]['QueName'],$list[0]['QueId']); 
                if($arr['devices_ads_id']){
                    Cookie::set('queue_id', $arr['devices_ads_id']);
                    $flag = 1;    
                }
            }
        }

        // $item = db("z_terminal")->where("id",$this->terminal_id)->find();
        $me = array();
        $me[] = ["doctor_id",'=',$this->doctor_id];
        $me[] = ["status",'=',0];
        $me[] = ["type",'=',0];
        $msg = db("z_message")->where($me)->order("add_time desc")->find();

        $this->assign("devices_ip",$devices_ip);
        $this->assign("msg",$msg);
        $this->assign("flag",$flag);
        // $this->assign("item",$item);
        $this->assign("arr",$arr);
        $this->assign("list",$list);
        $this->assign("mk_arr",$mk_arr);
        $this->assign("doctor",$result);
        return $this->fetch("index");
    }    

    // 发送消息
    public function sendMsg(){
        $re_msg['code'] = 201;
        $re_msg['msg']  = "发送失败"; 
        $data['content'] = input("content",'');
        if(!empty($data['content'])){
            // 注册登录ip信息
            $socket = new \app\admin\model\Socket;
            $arr = $socket->changeSocke($this->ter['hall_id'],'z_admin',['devices_type'=>'message']);
            
            if($arr['code']==200){
                $data['unit_id']    = $this->ter['unit_id'];
                $data['hall_id']    = $this->ter['hall_id'];
                $data['doctor_id']  = $this->doctor_id;
                $data['type']       = 1;
                $data['add_time']   = time();
                $rs = db("z_message")->insert($data);
                if($rs){
                    $re_msg['code'] = 200;
                    $re_msg['msg']  = "发送成功";     
                }                
            }else{
                $re_msg['msg']  = '护士台不在线';                   
            } 
        }else{
            $re_msg['msg']  = "内容不能为空"; 
        }
        echo json_encode($re_msg);
    }
    // 确认已读消息
    public function readMsg()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']     = "没有未读的消息";

        $wh[] = ['doctor_id','=',$this->doctor_id];
        $wh[] = ['status','=',0];
        $wh[] = ['type','=',0];
        $result = db("z_message")->where($wh)->order("add_time desc")->find();

        if($result){            
            $data['status'] = 1;
            $rs = db("z_message")->where("id",$result['id'])->data($data)->update();
            $msg = db("z_message")->where($wh)->order("add_time desc")->value("content");
            if($msg){                
                $re_msg['code'] = 200;
                $re_msg['msg']     = $msg;
            }
        }
        echo json_encode($re_msg);
    }
    // 选中队列
    public function selectQuene()
    {
        $re_msg['code'] = 200;
        $re_msg['msg']  = "设置成功"; 
        $id = input("id",0);
        Cookie::set('queue_id', $id);
        if($id){
            $id_arr = explode(',', $id);
            $wh[] = ['QueId','in',$id_arr];
            $result = db("serque")->field("GROUP_CONCAT(QueName) as que_name")->where($wh)->find();
            
            if($result){
                $devices_ip = $this->ter['ip'];
                $set = new \app\pavilion\model\Organize();
                $set->setTerminal($this->doctor_id,$devices_ip,$result['que_name'],$id);                
            }
        }
        // echo json_encode($re_msg);
        return json($re_msg);
    }
    // 获取队列信息
    public function getQueueInfo(){
        $re_msg['code'] = 201;
        $re_msg['msg']  = "获取失败";  
        $re_msg['data'] = '';  
        $doctor_id  = $this->doctor_id;
        $hall_id    = $this->ter['hall_id'];
        $room_name  = $this->ter['room_name'];
        $que_id     = explode(',', cookie('queue_id'));
        $flag       = input("flag",0);  //1=呼叫  2=重呼

        $terminal = [];
        $ccd = new \app\api\model\CacheCode;
        $ip = $ccd->getCode();  

        $where = array();
        $where[] = ['que_id','in',$que_id];
        $where[] = ['status','>=',1];
        $where[] = ['status','<=',2];
        $where[] = ['doctor_id','in',[0,$doctor_id]];
        $where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
        $ticket = DB::name("z_ticket")->where($where)->order("status desc,sort desc,pid asc")->find();
        if($ticket){
            if($flag==2){         
                if($ticket['status']!=2){                
                    $re_msg['msg']  = "请先呼叫患者";  
                    echo json_encode($re_msg);
                    exit;            
                }
            }
            if($ticket['status']!=2 && $flag==1){
                // 呼叫的用户更新信息
                $update = new \app\api\model\PushMsg;
                $update->updateStatusM($doctor_id,$ticket['id'],2); 
                $ticket['status'] = 2;
                $ticket['doctor_id'] = $doctor_id;
            }

            // 缓存信息
            $push = new \app\api\model\PushCache;
            $wait_num = $push->getWaitNum($ticket['que_id'],$doctor_id);  

            if($flag){                
                $push->setCacheInfo('',$ticket,$ip);   
                // 获取推送目标
                $arr_ip = array();
                $org = new \app\api\model\Organize;
                $hall_ip = $org->getLargeIp($hall_id);

                $arr_ip = $hall_ip;
                if(isset($this->ter['devices_ip'])){
                    $arr_ip[] = $this->ter['devices_ip']; 
                }
                $soc = new \app\api\model\Socket;
                foreach ($arr_ip as $key => $value) {
                    $a = $soc->terminalSocke($value,['call'=>1]);
                }        
            }

            $data['title']  = $ticket['title'];
            $data['code']   = $ticket['prefix'].$ticket['code'];
            $data['name']   = $ticket['name']; 
            $data['status'] = $ticket['status'];
            $data['number'] = $wait_num; 
            $re_msg['code'] = 200;
            $re_msg['msg']  = '执行成功';
            $re_msg['data'] = $data; 
        }else{
            $sent = new \app\api\model\PushCache;
            $sent->unsetScreenInfo('',$ip);
            $re_msg['msg'] = '暂时没有患者';
        }       

        echo json_encode($re_msg);
    }
    // 指定呼叫
    public function appointCall(){
        $re_msg['code'] = 201;
        $re_msg['msg']  = "呼叫失败";  
        $re_msg['data'] = '';  
        $doctor_id  = $this->doctor_id;
        $hall_id    = $this->ter['hall_id'];
        $room_name  = $this->ter['room_name'];
        $que_id     = explode(',', cookie('queue_id'));
        $ticked_id  = input("ticked_id",0);
        $flag       = input("flag",2);  //1=呼叫  2=重呼

        $quick  = new \app\api\model\PushMsg;
        $where = array();
        $where[] = ['status','=',2];
        $where[] = ['doctor_id','=',$doctor_id];
        $where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
        $res = Db::name("z_ticket")->where($where)->find();        
        if($res){
            $re_msg['msg']  = "请先完成目前患者的检查,然后再操作！";  
            echo json_encode($re_msg);exit;
        }else{
            $quick->updateStatusM($doctor_id,$ticked_id,2);
            // 缓存信息
            $ccd = new \app\api\model\CacheCode;
            $ip = $ccd->getCode();  
            $ticket = Db::name("z_ticket")->where("id",$ticked_id)->find();
            $push = new \app\api\model\PushCache;
            $num = $push->setCacheInfo('',$ticket,$ip);   

            $data['title']  = $ticket['title'];
            $data['code']   = $ticket['prefix'].$ticket['code'];
            $data['name']   = $ticket['name']; 
            $data['number'] = $num; 
            $re_msg['code'] = 200;
            $re_msg['msg'] = '执行成功';
            $re_msg['data'] = $data; 
        }       

        // 获取推送目标
        $arr_ip = array();
        $org = new \app\api\model\Organize;
        $hall_ip = $org->getLargeIp($hall_id);
        $arr_ip = $hall_ip;
        if(isset($this->ter['devices_ip'])){
            $arr_ip[] = $this->ter['devices_ip']; 
        }
        $soc = new \app\api\model\Socket;
        foreach ($arr_ip as $key => $value) {
            $a = $soc->terminalSocke($value,['call'=>1]);
        }     
        echo json_encode($re_msg);
    }
    // 停诊
    public function stopInfo()
    {
        $re_msg['code'] = 201;        
        // $ip = request()->ip();
        $ccd = new \app\api\model\CacheCode;
        $ip = $ccd->getCode();  
        $quick = new \app\api\model\PushMsg;
        $result = $quick->stopInfoM($ip);
        if($result['success']==1){
            $re_msg['code'] = 200;
        }
        $re_msg['msg'] = $result['msg'];
        echo json_encode($re_msg);
    }
    // 根据队列获取医生
    public function getSerque()
    {
        $re_msg['code'] = 201;   
        $where[] = ['HallNo','=',$this->ter['hall_id']];
        $result = db("serque")->field("QueId as id,QueName")->where($where)->select();
        if($result){
            $re_msg['code'] = 200;   
            $re_msg['data'] = $result;   
        }
        echo json_encode($re_msg);
    }
    // 转移患者
    public function moveTicket()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "转移失败";
        $queue_id = input("queue_id",0);
        $where[] = ['doctor_id','=',$this->doctor_id];
        $where[] = ['status','=',2];
        $where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
        $rs = db("z_ticket")->where($where)->find();
        $result = 0;
        if($rs){   
            $sh[] = ['que_id','=',$queue_id];
            $sh[] = ['hall_id','=',$rs['hall_id']];
            $sh[] = ['add_time','>',time()-7200];
            $pid = db("z_ticket")->field("max(pid) as pid")->where($sh)->find();
            $data['pid']        = $pid['pid']?($pid['pid']+10000):10000;
            $data['doctor_id']  = 0;
            $data['title']      = db("serque")->where('QueId',$queue_id)->value("QueName");
            $data['que_id']     = $queue_id;
            $data['status']     = 1;
            $result = db("z_ticket")->where("id",$rs['id'])->data($data)->update();
            if($result){
                $re_msg['code'] = 200;
                $re_msg['msg']  = "转移成功";
            }
        }
        echo json_encode($re_msg);
    }
    // 过号 完成就诊
    public function executeQueue()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "请先呼叫患者";  
        $doctor_id  = $this->doctor_id;
        $status     = input("status",2);
      
        $quick = new \app\api\model\PushMsg;
        $result = $quick->executeQueueM($doctor_id,$status);
        if($result['success']==1){
            $next = DB::name("config_fetch")->where("unitid",$this->ter['unit_id'])->value("next");
            if($next){
                $re_msg['code'] = 208;
            }else{
                $re_msg['code'] = 200;
            }
        }
        $re_msg['msg']  = $result['msg']; 
        echo json_encode($re_msg);
    }
    
    // 获取信息
    public function getListInfo()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "未查询到数据";  
        $status     = input("status",0);
        $doctor_id  = $this->doctor_id;
        $where = array();
        $where[] = ['status','=',$status];
        if($status==1){
            $que_id     = explode(',', cookie('queue_id'));
            $where[] = ['que_id','in',$que_id];
            $where[] = ['doctor_id','in',[0,$doctor_id]];
        }else{
            $where[] = ['doctor_id','=',$doctor_id];
        }
        $where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
        $result = db("z_ticket")->field("id,prefix,code,name")->where($where)->order(["over_time"=>"desc","pid"=>"asc"])->select();
        if($result){
            $re_msg['code'] = 200;
            $re_msg['msg']  = "查询到数据";  
            $re_msg['data'] = $result;
        }
        echo json_encode($re_msg);
    }
   
    // 呼叫保安
    public function warning()
    {
        // 获取推送目标        
        $re_msg['code'] = 201;
        $re_msg['msg']  = "呼叫失败"; 

        $pus = new \app\api\model\PushMsg;
        $result = $pus->warningM($this->ter);
        if($result['success']==1){
            $re_msg['code'] = 200;
            $re_msg['msg']  = $result['msg']; 
        }else{
            $re_msg['msg']  = $result['msg']; 
        }

        echo json_encode($re_msg);
    }

    // 手术室
    public function operation()
    {
        // 注册登录ip信息
        $devices_ip = request()->ip();
        $set = new \app\api\model\Organize();
        $arr = $set->setTerminal($this->doctor_id,$devices_ip);

        $result = db("z_doctor")->where("id",$this->doctor_id)->find();
        $list   = array();
        $arr    = array();
        $where  = array();
        $mk_arr = array();
        if($result){
            $arr = array_filter(explode('|', $result['que_id']));
            $mk_arr = explode(',', $result['mk_que_id']);
        }
        $flag = Cookie::has('queue_id')?1:0;       
        if(!$flag){            
            $where[] = ['QueId','in',$arr];
            $list = db("serque")->field("QueId,QueName")->where($where)->select();
            if(count($list)==1){
                $arrs = $set->setTerminal($this->doctor_id,$devices_ip,$list[0]['QueName'],$list[0]['QueId']); 
                if($arrs['devices_ads_id']){
                    Cookie::set('queue_id', $arrs['devices_ads_id']);
                    $flag = 1;    
                }
            }
        }
        $item = db("z_terminal")->where("id",$this->terminal_id)->find();
        $me = array();
        $me[] = ["doctor_id",'=',$this->doctor_id];
        $me[] = ["status",'=',0];
        $me[] = ["type",'=',0];
        $msg = db("z_message")->where($me)->order("add_time desc")->find();

        $list = Config::get("app.status_name");
        $this->assign("list",$list);
        $this->assign("devices_ip",$devices_ip);
        $this->assign("msg",$msg);
        $this->assign("flag",$flag);
        $this->assign("item",$item);
        $this->assign("list",$list);
        $this->assign("mk_arr",$mk_arr);
        $this->assign("doctor",$result);
        return $this->fetch("operation");
    }

    // 手术室
    public function operations()
    {
        return $this->fetch("operations");
    }

    // 手术室显示
    public function operationCall()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "呼叫失败"; 
        
        $id = input("id",0);
        $name = input("name","");
        $status = input("status",1);
        $arr['name']        = $name;
        $arr['status_name'] = Config::get("app.status_name.".$status);
        $arr['order']       = 0;

        if($id>0){
            $rs = Db::name("z_operation")->where("id",$id)->find();
            if($rs){
                $data['status'] = $rs['status']==1?2:3;
                $data['over_time'] = $data['status']==3?time():0;
                $arr['name']        = $rs['sufferer'];
                $arr['status_name'] = Config::get("app.status_name.".$data['status']);
            }
        }

        $push = new \app\api\model\PushCache;
        $result = $push->operScreen($this->ter,$arr,$this->user['QueName']);
        if($result){
            $re_msg['code'] = 200;
            $re_msg['msg']  = ''; 
            if($id>0){
                Db::name("z_operation")->where('id',$id)->update($data);
            }else{                
                $data['doctor_id']  = $this->doctor_id;
                $data['name']       = $this->user['QueName'];
                $data['sufferer']   = $name;
                $data['status']     = $status;
                $data['add_time']   = time();
                Db::name("z_operation")->insert($data);
            }            
        }

        // 获取推送目标
        $org = new \app\api\model\Organize;
        $hall_ip = $org->getLargeIp($this->ter['hall_id']);
    
        $soc = new \app\admin\model\Socket;    
        foreach ($hall_ip as $key => $value) {
            $rs = $soc->terminalSocke($value,['call'=>1]);    
            if($rs['success']==1){
                $re_msg['msg']  = $rs['msg']; 
            }       
        }  
        echo json_encode($re_msg);
    }
    // 获取今日手术信息
    public function getDayInfo()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "未查询到数据";  
        $status     = input("status",0);
        $doctor_id  = $this->doctor_id;
        $where = array();
        if($status==3){
            $where[] = ['status','=',$status];
        }else{
            $where[] = ['status','<>',3];
        }
        $where[] = ['doctor_id','=',$this->doctor_id];
        $where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
        $result = Db::name("z_operation")->field("*,from_unixtime(over_time,'%H:%i:%S') as otime")->where($where)->order("status desc,over_time desc")->select();
        if($result){
            $re_msg['code'] = 200;
            $re_msg['msg']  = "查询到数据";  
            $re_msg['data'] = $result;
        }
        echo json_encode($re_msg);
    }
    // 获取拼接语句
    public function getContent()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "获取数据失败";  
        $status             = input("status",1);
        $arr['name']        = input("name",'');
        $arr['order']       = 0;
        $arr['status_name'] = Config::get("app.status_name.".$status);
        $sent       = new \app\api\model\Sentence;
        //综合显示屏内容
        $content = $sent->houseString($arr,$this->ter['hall_id'],1);
        if($content){
            $re_msg['code']     = 200;
            $re_msg['msg']      = "获取数据成功";  
            $re_msg['data']  = $content;  
        }
        echo json_encode($re_msg);
    }
}