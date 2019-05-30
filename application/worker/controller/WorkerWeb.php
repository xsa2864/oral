<?php
namespace app\worker\controller;

use think\facade\Env;
use think\worker\Server;
use Workerman\Lib\Timer;
use Workerman\Worker;
use think\facade\Cache;

class WorkerWeb extends Server
{
    protected $socket = 'websocket://0.0.0.0:23452';
    protected $option 	= [
    	"name"=>"think",
    	"count"=>1
    ];
    public $max_request = 10;
    // 全局数组保存uid在线数据
    public $uidConnectionMap = array();
    // 全局数组保存设备在线数据
    public $uidDevicesMap = array();
    // 全局数组保存医生在线数据
    public $uidDoctorMap = array();
    // 记录最后一次广播的在线用户数
    public $last_online_count = 0;
    // 记录最后一次广播的在线页面数
    public $last_online_page_count = 0;
    // 心跳时间
    public $heartbeat_time = 55;

    /**
     * 收到信息
     * @param $connection
     * @param $data
     */
    public function onMessage($connection, $data)
    {  
        if($data){  
            $array = json_decode($data,1);
            switch($array['type']){
                case 'ping':
                    $connection->lastMessageTime = time();
                    return $connection->send('{"ping":"pong"}');
                case 'login':             
                    if(!isset($connection->uid))
                    {   
                        // 给connection临时设置一个lastMessageTime属性，用来记录上次收到消息的时间
                        $devices = isset($array['device'])?$array['device']:'devices';
                        $doctor_id = isset($array['doctor_id'])?$array['doctor_id']:'0';
                        $connection->uid = $devices=='z_admin'?'9.'.$array['devices_ip']:$array['devices_ip'];
                        $connection->lastMessageTime = time();
                        $this->uidConnectionMap[$connection->uid] = $connection;
                        $this->uidDevicesMap[$connection->uid] = $devices;
                        $this->uidDoctorMap[$connection->uid] = $doctor_id;
                        $this->saveCache($array['devices_ip'],1,$devices,$doctor_id);                       
                        return ;
                    }        
            }
        }
    }

    /**
     * 当连接建立时触发的回调函数
     * @param $connection
     */
    public function onConnect($connection)
    {
        
    }

    /**
     * 当连接断开时触发的回调函数
     * @param $connection
     */
    public function onClose($connection)
    {
        if(isset($connection->uid))
        {
            $this->saveCache($connection->uid,0,$this->uidDevicesMap[$connection->uid],$this->uidDoctorMap[$connection->uid]);
            // 连接断开时删除映射
            unset($this->uidConnectionMap[$connection->uid]);
        }
    }

    /**
     * 当客户端的连接上发生错误时触发
     * @param $connection
     * @param $code
     * @param $msg
     */
    public function onError($connection, $code, $msg)
    {
        echo "error $code $msg\n";
    }

    /**
     * 每个进程启动
     * @param $worker
     */
    public function onWorkerStart($worker)
    {
        // 监听一个http端口
        $inner_http_worker = new Worker('http://0.0.0.0:5679');
        $inner_http_worker->onMessage = function($http_connection, $buffer)
        {
            // 推送数据的url格式 type=publish&to=uid&content=xxxx           
            $json_data = $_POST ? $_POST : $_GET;
            $json = isset($json_data["json_data"])?$json_data["json_data"]:'';
            if($json){                
                $arr = json_decode($json,1);
                $to = $arr['to'];
                $buffer = $arr['content'];// $buffer = htmlspecialchars($arr['content']); 
                if($arr['type']=='publish'){
                    // 有指定uid则向uid所在socket组发送数据
                    if($to == 'all'){
                        // 否则向所有uid推送数据
                        $ret = $this->broadcast($buffer);
                    }else{
                        $ret = $this->sendMessageByUid($to, $buffer);
                    }    
                }
                // http接口返回，如果用户离线socket返回fail             
                if($ret){
                    $http_connection->send('ok');
                }else{
                    $http_connection->send('offline');
                }
            }           
            // 返回推送结果
            $http_connection->send('fail');
        };
        // 执行监听
        $inner_http_worker->listen();
        // 一个定时器，定时向所有uid推送当前uid在线数及在线页面数
        Timer::add(1, function(){            
            $time_now = time();
            foreach ($this->uidConnectionMap as $connection) {
                // 有可能该connection还没收到过消息，则lastMessageTime设置为当前时间
                if (empty($connection->lastMessageTime)) {
                    $connection->lastMessageTime = $time_now;
                    continue;
                }
                // 上次通讯时间间隔大于心跳间隔，则认为客户端已经下线，关闭连接
                if($time_now-$connection->lastMessageTime > $this->heartbeat_time){
                    $connection->close();
                }
            }
        });
    }
    // 向所有验证的用户推送数据
    public function broadcast($message)
    {
        $num = 0;
        foreach($this->uidConnectionMap as $connection)
        {
            $rs = $connection->send($message);
            if($rs){
                $num ++;
            }
        }
        return $num;
    }
    // 针对uid推送数据
    public function sendMessageByUid($uid, $message)
    {
        if(isset($this->uidConnectionMap[$uid]))
        {
            $connection = $this->uidConnectionMap[$uid];
            $connection->send($message);
            return true;
        }
        return false;
    }

    // 缓存连接地址信息
    public function saveCache($ip='',$status=1,$devices="devices",$doctor_id=0)
    {
        if(cache($devices))
        {
            echo 'ip >> '.$ip.' devices >> '.$devices." doctor_id >> ".$doctor_id." status >> ".$status."\n";
            $json = cache($devices);
            $arr = json_decode($json,1);
            if($devices=='devices'){                
                if(isset($arr[$ip])){
                    $arr[$ip]['devices_status'] = $status; 
                    if($status){
                        $arr[$ip]['devices_start_time'] = time();
                    }else{
                        $arr[$ip]['devices_end_time']   = time();
                    }    
                    cache($devices,json_encode($arr));
                }
            }else if($devices=='terminal'){                
                if(isset($arr[$doctor_id])){
                    $arr[$doctor_id]['devices_status'] = $status; 
                    if($status){
                        $arr[$doctor_id]['devices_start_time'] = time();
                    }else{
                        $arr[$doctor_id]['devices_end_time']   = time();
                    }    
                    cache($devices,json_encode($arr));          
                }
            }else if($devices=='z_admin'){
                if(isset($arr)){
                    foreach ($arr as $key => $value){
                        if(in_array($ip, $value)){                    
                            $arr[$key]['devices_status'] = $status; 
                            if($status){
                                $arr[$key]['devices_start_time'] = time();
                            }else{
                                $arr[$key]['devices_end_time']   = time();
                            }    
                            cache($devices,json_encode($arr));
                        }
                    }                
                }
            }
        }        
    }
}