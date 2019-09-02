<?php
namespace app\api\model;

use think\Model;
use think\DB;
use think\facade\Env;

/*==呼叫器操作==*/

class PushMsg extends Model
{
    // 过号 完成就诊
    public function executeQueueM($doctor_id=0,$status=2)
    {
        $re_msg['success'] = 0;
        $re_msg['msg']   = "请先呼叫患者";  
        $re_msg['data']  = [];  

        $where = array();
        $where[] = ['status','=',2];
        $where[] = ['doctor_id','=',$doctor_id];
        $where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
        $result = DB::name("z_ticket")->where($where)->find();
        if($result){
            $rs = $this->updateStatusM($doctor_id,$result['id'],$status);
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg']   = '操作完成'; 
                $data['id']      = $result['id'];
                $data['que_id']  = $result['que_id'];
                $data['title']   = $result['title'];
                $data['code']    = $result['prefix'].$result['code'];
                $data['name']    = $result['name'];
                $re_msg['data']  = $data;  
            }
        }
        return $re_msg;
    }

    /*
     * 更新状态
     * return boolean
     */
    public function updateStatusM($doctor_id=0,$id=0,$status=0){
        $data['doctor_id']  = $doctor_id;
        if($status==2){
            $data['call_time']  = time();
        }else{
            $data['over_time']  = time();
        }        
        $data['status']     = $status;        
        $rs = DB::name('z_ticket')->where("id",$id)->update($data);
        return $rs;
    }

    /*
     * 呼叫操作 
     * flag是否触发 0=否 1=是
     * return Array
     */
    public function getQueueInfoM($doctor_id=0,$que_id=array(),$ter=[],$flag=0){
        $re_msg['success'] = 0;
        $re_msg['msg']  = "呼叫失败";  
        $re_msg['data'] = '';  

        $where = array();
        $data = array();
        $where[] = ['que_id','in',$que_id];
        $where[] = ['status','>=',1];
        $where[] = ['status','<=',2];
        $where[] = ['doctor_id','in',[0,$doctor_id]];
        $where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
        $result = DB::name("z_ticket")->field("count(*) as num")->where($where)->find();
        $ticket = DB::name("z_ticket")->where($where)->order("status desc,sort desc,pid asc")->find();
        $data['number'] = $result['num'];
        if($ticket){
            $data['que_id']= $ticket['que_id'];
            $data['id']    = $ticket['id'];
            $data['title'] = $ticket['title'];
            $data['code']  = $ticket['prefix'].$ticket['code'];
            $data['name']  = $ticket['name'];
            if($flag == 1){
                if($ticket['status'] != 2){
                    $this->updateStatusM($doctor_id,$ticket['id'],2); 
                    $ticket['status'] = 2;
                    $ticket['doctor_id'] = $doctor_id;
                }
                // $re_msg = $this->apiPublish($ticket['que_id'],$hall_id,$room_name,$doctor_id);
            }            
            if($ticket['status'] == 2){
                $re_msg = $this->apiPublish($ticket,$ter);
            }else{
                $re_msg['msg']  = "请先呼叫患者";
            }
        }else{
            $sent = new \app\api\model\PushCache;
            $sent->unsetScreenInfo($ter);
            $re_msg['success'] = 0;
            $re_msg['msg']  = "暂时没有患者";          
        }
        $re_msg['data'] = $data; 
        return $re_msg;
    }

    // 呼叫保安
    public function warningM($terminal=[],$doctor_id=0)
    {
        // 获取推送目标        
        $re_msg['success'] = 0;
        $re_msg['msg']  = "呼叫失败"; 

        $org = new \app\api\model\Organize;
        $hall_ip = $org->getLargeIp($terminal['hall_id']);
        $str = Db::name("config_fetch")->where("unitid",$terminal['unit_id'])->value("warning");
        $name = Db::name("z_doctor")->where("id",$doctor_id)->value("QueName");
        $data['warning'] = $str.' '.$terminal['room_name'].' '.($name?$name."医生处":'');
        if(!empty($hall_ip)){            
            $soc = new \app\api\model\Socket;
            foreach ($hall_ip as $key => $value) {
                $arr = $soc->terminalSocke($value,$data);
                if($arr['success']==1){
                    $re_msg['success'] = 1;
                    $re_msg['msg']  = "呼叫成功"; 
                }
            }        
            $Voice = new \app\api\model\Voice;
            $Voice->broadcast($data['warning'],$terminal['hall_id']);    
        }else{
            $re_msg['msg']  = "设备不在线"; 
        }
        return $re_msg;
    }

    // 停诊
    public function stopInfoM($ip='')
    {
        $devices_ip = db("z_terminal")->where("ip",$ip)->value('devices_ip');
        $data['stop'] = '停诊';
        $soc = new \app\api\model\Socket;
        $re_msg = $soc->terminalSocke($devices_ip,$data);
        return $re_msg;
    }

    /*
     * 推送服务
     * return Array
     */
    public function publishM($id=0,$hall_id=0,$room_name='',$doctor_id=0)
    {
        $data = array();
        $where = array();
        $where[] = ['t.id','=',$id];
        $result = db("z_ticket")->alias("t")
                ->field("t.*,s.QueName as qname")
                ->leftJoin("serque s","s.QueId=t.que_id")
                ->where($where)->find();      
        $rule = '';
        $devices_ip = '';
        if($result){           
            // 医生信息
            $doctor = db("z_doctor")->where("id",$doctor_id)->find();
            if($doctor){
                $data['staff_info'] = $doctor;
            }
            $wait = array();
            $wait[] = ['que_id','=',$result['que_id']];
            $wait[] = ['doctor_id','in',[0,$doctor_id]];
            $wait[] = ['status','>=',1];
            $wait[] = ['status','<=',2];
            $wait[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
            $wait_list = db("z_ticket")->field("prefix,code,name,status,order,title")->order("status desc,sort desc,pid asc")->where($wait)->select();
            if($wait_list){
                $ccd = new \app\api\model\CacheCode;
                $ip = $ccd->getCode();  
                $terminal = db("z_terminal")->field("devices_ip,room_name,seat_name,title")->where("ip",$ip)->find();
                $data['wait_list'] = $wait_list;
                $data['wait_number'] = count($wait_list)-1;

                $sent = new \app\api\model\Sentence;
                $h_now = '';
                $h_title = '';
                $items = '';
                foreach ($wait_list as $key => $value) {
                    $value['room_name'] = $terminal['room_name'];
                    $value['QueName']   = $terminal['title'];
                    $value['seat_name'] = $terminal['seat_name'];
                    //诊室屏内容
                    $data['house_list'][$key]['title'] = $sent->houseString($value,$hall_id);
                    $data['house_list'][$key]['status'] = $value['status'];
                    //综合显示屏内容
                    if($value['status']==2){
                        $h_now = $sent->houseString($value,$hall_id,1);
                        // 语音推送
                        $v_str = $sent->houseString($value,$hall_id,1,1);
                        if($v_str){                            
                            $Voice = new \app\admin\model\Voice;
                            $Voice->broadcast($v_str['str'],$v_str['addr_id']);        
                        }
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
                $queue['que_name']  = $result['qname'];
                $queue['name']      = $doctor['QueName'];
                $queue['room_name'] = $room_name;
                $queue['que_id']    = $result['que_id'];
                $queue['hall_id']   = $hall_id;
                $sent->hallList($queue);
            }
            // 过号1小时内信息
            $wh = array();
            $wh[] = ['status','=',0];
            $wh[] = ['add_time','>',strtotime(date("Y-m-d",time()-3600))];
            $false_str = db("z_ticket")->field("prefix,code,name,status,order")->order("add_time desc")->where($wh)->select();
            $f_title = '';
            foreach ($false_str as $keys => $val) {
                if($f_title!=''){
                    $f_title .= '、';
                }
                $f_title .= $sent->houseStr($val);
            }
            $data['hall_false'] = $f_title;
            $data['devices_name'] = $room_name;
            // 获取推送目标
            $arr_ip = array();

            $org = new \app\api\model\Organize;
            $hall_ip = $org->getLargeIp($hall_id);

            $arr_ip = $hall_ip;
            $arr_ip[] = $terminal['devices_ip']; 
            $soc = new \app\api\model\Socket;            
            foreach ($arr_ip as $key => $value) {
                $a = $soc->terminalSocke($value,$data);
            }  
            return $a;         
        }      
    }

    /*
     * api push 推送消息
     *
     */
    public function apiPublish($ticket=[],$terminal=[])
    {
        $re_msg['success']  = 0;
        $re_msg['msg']      = '执行失败';
        $re_msg['data']     = '';         
       
        if($ticket){
            $queue_id = $ticket['que_id'];
            
            // 缓存信息
            $push = new \app\api\model\PushCache;
            $num = $push->setCacheInfo($terminal,$ticket);  

            // 获取推送目标
            $arr_ip = array();
            $org = new \app\api\model\Organize;
            $hall_ip = $org->getLargeIp($terminal['hall_id']);

            $arr_ip = $hall_ip;
            if(isset($terminal['devices_ip'])){
                $arr_ip[] = $terminal['devices_ip']; 
            }
            $soc = new \app\api\model\Socket;
            foreach ($arr_ip as $key => $value) {
                $a = $soc->terminalSocke($value,['call'=>1]);
                if($a['success']==1){
                    $re_msg['success'] = 1;
                    $re_msg['msg'] = $a['msg'];
                }
            }                    

            $data['title']  = $ticket['title'];
            $data['code']   = $ticket['prefix'].$ticket['code'];
            $data['name']   = $ticket['name']; 
            $data['status'] = $ticket['status'];
            $data['number'] = $num; 
            
            $re_msg['data'] = $data; 
        }else{
            $re_msg['msg'] = '还没有患者';
        }       
        return $re_msg;
    }
    /*
     * 手术室推送服务
     * return Array
     */
    public function publishO($result,$arr,$doctor_id=0)
    {
        $data = array();    
        $hall_id = $result['hall_id'];
        $arr['room_name'] = $result['room_name'];
        // 医生信息
        $doctor = db("z_doctor")->where("id",$doctor_id)->find();
        if($doctor){
            $data['staff_info'] = $doctor;
        }            
            
        $sent       = new \app\pavilion\model\Sentence;
        //综合显示屏内容
        $content = $sent->houseString($arr,$hall_id,1);
        $data['house_list']['title'] = $content;

        // 语音推送
        $v_str = $sent->houseString($arr,$hall_id,1,1);
        if($v_str){                            
            $Voice = new \app\admin\model\Voice;
            $Voice->broadcast($v_str['str'],$v_str['addr_id']);        
        }
        $items          = array();
        $items['name']  = $sent->pregName($arr['name']);
           
        // 手术屏显示内容
        $queue['now']       = $content;
        $queue['title']     = $arr['status_name'];
        $queue['items']     = $items;
        $queue['que_name']  = $result['room_name'];
        $queue['que_id']    = $doctor_id;
        $queue['name']      = $doctor['QueName'];
        $queue['room_name'] = $result['room_name'];
        $queue['hall_id']   = $hall_id;
        $sent->hallList($queue);
           
        $data['devices_name'] = $result['room_name'];
        // 获取推送目标
        $org = new \app\pavilion\model\Organize;
        $hall_ip = $org->getLargeIp($hall_id);
    
        $soc = new \app\admin\model\Socket;
        $rs = array();
        $rs_msg['success'] = 0;
        $rs_msg['msg'] = '推送失败';
        foreach ($hall_ip as $key => $value) {
            $rs = $soc->terminalSocke($value,$data);
            if($rs['success']==1){
                $rs_msg['success'] = 1;
                $rs_msg['msg'] = '推送成功';
            }    
        }           
        return $rs_msg; 
    }
}