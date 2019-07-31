<?php
namespace app\api\controller\t1;

use think\Controller;
use think\facade\Request;
use think\facade\View;
use think\facade\Env;
use app\api\controller\t1\Base;

class Terminal extends Base
{
    public function index()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "无效操作";
        return json($re_msg);
    }

    // 指定诊室屏呼叫显示
    public function appointCall()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "呼叫失败";  

        $room_name  = $this->ter['room_name'];
        $list       = input("list",'');
        $wait_list  = json_decode($list);
        
        $data['staff_info']     = $this->user;
        $data['devices_name']   = $room_name;
        $data['wait_list']      = $wait_list;
        $data['wait_number']    = count($wait_list)-1;

        // 获取推送目标
        $arr_ip = array();
        $org = new \app\pavilion\model\Organize;
        $hall_ip = $org->getLargeIp($this->ter['hall_id']);
        $arr_ip = $hall_ip;
        $arr_ip[] = $this->ter['devices_ip']; 

        $soc = new \app\admin\model\Socket;
        foreach ($arr_ip as $key => $value) {
            $result = $soc->terminalSocke($value,$data); 
            if($result['success']){
                $re_msg['code'] = 200;
                $re_msg['msg']  = "呼叫成功";  
            }   
        }    
        return json($re_msg);
    }
    // 停诊
    public function stopInfo()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "呼叫失败";  

        $data['stop'] = '停诊';     
        $soc = new \app\admin\model\Socket;
        $result = $soc->terminalSocke($this->ter['devices_ip'],$data);
        if($result['success']){
            $re_msg['code'] = 200;
            $re_msg['msg']  = "呼叫成功";  
        }   
        echo json_encode($re_msg);
    }
    // 指定综合屏呼叫显示
    public function appointLargeCall()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "呼叫失败";  

        $room_name  = $this->ter['room_name'];
        $que_id     = input("que_id",0);
        $list       = input("list",'');
        $wait_list  = json_decode($list,1);
        
        $data['staff_info']     = $this->user;
        $data['devices_name']   = $room_name;
        $data['wait_list']      = $wait_list;
        $data['wait_number']    = count($wait_list)-1;
        $hall_id = $this->ter['hall_id'];
        $que_name = '';
        $result = db("serque")->where("QueId",$que_id)->find();
        if($result){
            $que_name = $result['QueName'];
        }

        $h_now = '';
        $h_title = '';
        $items = '';
        $sent = new \app\pavilion\model\Sentence;
        if($wait_list)
        {
            foreach ($wait_list as $key => $value) {
                //综合显示屏内容
                if($value['status']==2){
                    $h_now = $sent->houseString($value,$hall_id,1);
                    $value['name'] = $sent->pregName($value['name']);
                    $items = $value;                                    
                }else{
                    if($key<=2){
                        if($h_title!=''){
                            $h_title .= '、';
                        }
                        $h_title .= $sent->houseStr($value);
                    }
                }
            }
            // 综合屏显示内容
            $queue['now']       = $h_now;
            $queue['items']     = $items;
            $queue['title']     = $h_title;
            $queue['que_name']  = $que_name;
            $queue['que_id']    = $que_id;
            $queue['name']      = $this->user['QueName'];
            $queue['room_name'] = $room_name;
            $queue['hall_id']   = $hall_id;
            $sent->hallList($queue);
        }

        // 获取推送目标
        $arr_ip = array();
        $org = new \app\pavilion\model\Organize;
        $hall_ip = $org->getLargeIp($this->ter['hall_id']);
        $arr_ip = $hall_ip;

        $soc = new \app\admin\model\Socket;
        foreach ($arr_ip as $key => $value) {
            $result = $soc->terminalSocke($value,$data); 
            if($result['success']){
                $re_msg['code'] = 200;
                $re_msg['msg']  = "呼叫成功";  
            }   
        }    
        echo json_encode($re_msg);
    }
    // 呼叫保安
    public function warning()
    {
        // 获取推送目标        
        $re_msg['code'] = 201;
        $re_msg['msg']  = "呼叫失败"; 
        $hall_id    = $this->ter['hall_id'];
        $room_name  = $this->ter['room_name'];

        $org = new \app\pavilion\model\Organize;
        $hall_ip = $org->getLargeIp($hall_id);
        $str = db("config_fetch")->where("unitid",$this->unitid)->value("warning");
        $data['warning'] = $str.$room_name;
        if(!empty($hall_ip)){            
            $soc = new \app\admin\model\Socket;
            foreach ($hall_ip as $key => $value) {
                $arr = $soc->terminalSocke($value,$data);
                if($arr['success']==1){ 
                    $re_msg['code'] = 200;
                    $re_msg['msg']  = "呼叫成功"; 
                }
            }        
        }else{
            $re_msg['msg']  = "设备不在线"; 
        }
        echo json_encode($re_msg);
    }
    // 获取队列列表信息
    public function getQueue()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "获取成功";  
        $list = db("serque")->field("QueId as id,code,QueName")->select();
        $re_msg['data'] = $list;  
        return json($re_msg);
    }
    // 设置语言播放
    public function setVoice()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "播放失败";  

        $list       = input("list",'');
        $wait_list  = json_decode($list,1);        
        $hall_id = $this->ter['hall_id'];
        if($wait_list)
        {
            $sent = new \app\pavilion\model\Sentence;
            // foreach ($wait_list as $key => $value) {
                // 语音推送
                $v_str = $sent->houseString($wait_list[0],$hall_id,1,1);
                if($v_str){                            
                    $Voice = new \app\admin\model\Voice;
                    $rs = $Voice->broadcast($v_str['str'],$v_str['addr_id']); 
                    if($rs){
                        $re_msg['code'] = 200;
                        $re_msg['msg']  = "播放成功";  
                    }       
                }   
            // }
        }
        echo json_encode($re_msg);
    }

}