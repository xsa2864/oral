<?php 
namespace app\admin\model;

use think\Model;
use think\facade\Env;
use think\facade\Config;

class Socket extends Model
{
    // 呼叫soket
    public function changeSocke($id='',$type='terminal',$data='')
    {       
        $re_msg['code'] = 201;
        $re_msg['msg']  = "执行失败";      

        $org = new \app\api\model\Organize();
        $devices_ip = $org->getConnIp($id,$type);   
        $devices_ip = $type=='z_admin'?'9.'.$devices_ip:$devices_ip;
      
        $content = json_encode($data);
        // 推送的url地址，使用自己的服务器地址
        $config = Config::get("app.socket_url");
        $push_api_url = "http://127.0.0.1:".$config['port'];

        $post_datas = array(
           "type"       => 'publish',
           "content"    => $content,
           "to"         => $devices_ip, 
        );

        $post_data['json_data'] = json_encode($post_datas);

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec ( $ch );
        curl_close ( $ch );

        if($return=='ok'){
            $re_msg['code'] = 200;
            $re_msg['msg']  = "推送成功";            
        }else if($return=='offline'){
            $re_msg['msg']  = "该设备编号离线";
        }else if($return=='fail'){
            $re_msg['msg']  = "推送失败";
        }
        return $re_msg;
    }

    /* 
     * 根据IP推送消息
     * return Array
     */
    public function terminalSocke($devices_ip='',$data='')
    {       
        $re_msg['success'] = 0;
        $re_msg['msg']  = "执行失败";      

        $content = json_encode($data);
        // 推送的url地址，使用自己的服务器地址
        $config = Config::get("app.socket_url");
        $push_api_url = "http://127.0.0.1:".$config['port'];

        $post_datas = array(
           "type"       => 'publish',
           "content"    => $content,
           "to"         => $devices_ip, 
        );
        $post_data['json_data'] = json_encode($post_datas);

        $ch = curl_init ();
        curl_setopt ( $ch, CURLOPT_URL, $push_api_url );
        curl_setopt ( $ch, CURLOPT_POST, 1 );
        curl_setopt ( $ch, CURLOPT_HEADER, 0 );
        curl_setopt ( $ch, CURLOPT_RETURNTRANSFER, 1 );
        curl_setopt ( $ch, CURLOPT_POSTFIELDS, $post_data );
        curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        $return = curl_exec ( $ch );
        curl_close ( $ch );
        
        if($return=='ok'){
            $re_msg['success'] = 1;
            $re_msg['msg']  = "推送成功";   
             // 记录缓存 
            $this->writeCacheM($devices_ip,$data);     
        }else if($return=='offline'){
            $re_msg['msg']  = "该设备编号离线";
        }else if($return=='fail'){
            $re_msg['msg']  = "推送失败";
        }
        return $re_msg;
    }

    /*
     * 记录缓存
     */
    public function writeCacheM($devices_ip='',$data=array())
    {
        $json = cache("devices");
        $arr  = json_decode($json,1);
        if(isset($arr[$devices_ip])){  
            if(isset($data['devices_name'])){
                $arr[$devices_ip]['devices_name'] = $data['devices_name'];
            }              
            if(isset($data['staff_info']['QueName'])){
                $arr[$devices_ip]['devices_user_name'] = $data['staff_info']['QueName'];
            }
            if(isset($data['staff_info']['AlternateField1'])){
                $arr[$devices_ip]['devices_user_brief'] = $data['staff_info']['AlternateField1'];
            }
            cache("devices",json_encode($arr));  
        }              
    }

    /*
     * 记录历史数据
     */
    public function recordsM($devices_code="",$arr=array()){
        $array = cache("cache_list");
        if($devices_code!="")
        {            
            $n = 0;
            if(!empty($array)){                
                krsort($array);    
                foreach ($array as $key => $value) {
                    if(!isset($value['devices_code'])){
                        unset($array[$key]);
                    }else if($value['devices_code']==$devices_code){
                        $n++;
                        if($n>4){
                            unset($array[$key]);
                        }
                    }
                }
            }
            $arr[$devices_code]['devices_code'] = $devices_code;
            $arr[$devices_code]['devices_send_time'] = time();
            $array[] = $arr[$devices_code];
            cache("cache_list",$array);  
        }
    }
}