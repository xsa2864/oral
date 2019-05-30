<?php 
namespace app\pavilion\model;

use think\Model;
use think\facade\Env;

class Organize extends Model
{	
    // 显示屏 默认变量数据
    public function setDefault($ip='',$devices_type=0){
        $json       = cache("devices");
        $arr        = json_decode($json,1);
        if(!isset($arr[$ip])){            
            $arr[$ip]['devices_ip']              = $ip;
            $arr[$ip]['devices_code']            = '';    
            $arr[$ip]['devices_type']            = $devices_type;//0=诊室屏  1=综合屏
            $arr[$ip]['devices_area_id']         = 0;//区域ID
            $arr[$ip]['devices_area_name']       = '';//区域名称
            $arr[$ip]['devices_user_name']       = "";//使用者名称
            $arr[$ip]['devices_user_type']       = '';//医师资格
            $arr[$ip]['devices_user_brief']      = "";//使用者简介
            $arr[$ip]['devices_name']            = $ip;
            $arr[$ip]['devices_width']           = 0;
            $arr[$ip]['devices_height']          = 0;
            $arr[$ip]['devices_ads_id']          = 0;//广告位ID
            $arr[$ip]['devices_content']         = "";
            $arr[$ip]['devices_first_time']      = time();
            $arr[$ip]['devices_start_time']      = 0;
            $arr[$ip]['devices_end_time']        = 0;
            $arr[$ip]['devices_send_time']       = 0;
            $arr[$ip]['devices_status']          = 0;
            $arr[$ip]['devices_video']           = 0;
            $arr[$ip]['devices_image']           = 0;
            $arr[$ip]['devices_font_color']      = 0;
            $arr[$ip]['devices_font_size']       = 0;
            $arr[$ip]['devices_background_color']= "";  
            $arr[$ip]['devices_tips']            = "";
            $arr[$ip]['devices_field']           = ["1","2","3","4","5","6"];  
            $arr[$ip]['queue_title']             = ''; 
            $arr[$ip]['queue_list']              = '';
            $arr[$ip]['queue_wait_num']          = 0;
            cache("devices",json_encode($arr));    
        }
        $ijson       = cache("terminal");
        $iarr        = json_decode($ijson,1);
        if(isset($iarr)){
            foreach ($iarr as $key => $value) {
                if(in_array($ip, $value)){                    
                    unset($iarr[$key]);
                }
            }        
            cache("terminal",json_encode($iarr));             
        }
        return $arr[$ip];
    }

    /*
     * 呼叫器终端 默认数据
     * 医生 绑定 ip
     */
    public function setTerminal($id=0,$ip='',$data='',$que_id=''){
        $json       = cache("terminal");
        $arr        = json_decode($json,1);

        if(!isset($arr[$id]) || 1){
            $arr[$id]['devices_ip']              = $ip;
            $arr[$id]['devices_type']            = 0;
            $arr[$id]['devices_area_id']         = 0;//队列id/名称
            $arr[$id]['devices_area_name']       = '';//区域名称
            $arr[$id]['devices_user_name']       = "呼叫器终端";//使用者名称
            $arr[$id]['devices_user_brief']      = "";//使用者简介
            if(!empty($data)){
                $arr[$id]['devices_name']        = $data;
            }
            if(!empty($que_id)){
                $arr[$id]['devices_ads_id']      = $que_id;//队列id
            }
            $arr[$id]['devices_content']         = "";
            $arr[$id]['devices_start_time']      = 0;
            $arr[$id]['devices_end_time']        = 0;
            $arr[$id]['devices_send_time']       = 0;
            $arr[$id]['devices_status']          = 0;
            cache("terminal",json_encode($arr));     
        }
        return $arr[$id];
    }    

    /*
     * 后台站点 默认数据
     * 区域编号 id  绑定 ip
     */
    public function setAdmin($id=0,$ip=''){
        $json       = cache("z_admin");
        $arr        = json_decode($json,1);

        if(!isset($arr[$id]) || 1){
            $arr[$id]['devices_ip']              = $ip;
            $arr[$id]['devices_type']            = 0;
            $arr[$id]['devices_area_id']         = 0;//区域ID
            $arr[$id]['devices_area_name']       = '';//区域名称
            $arr[$id]['devices_user_name']       = "后台站点";//使用者名称
            $arr[$id]['devices_user_brief']      = "";//使用者简介
            $arr[$id]['devices_name']            = "";
            $arr[$id]['devices_ads_id']          = 0;//广告位ID
            $arr[$id]['devices_content']         = "";
            $arr[$id]['devices_start_time']      = 0;
            $arr[$id]['devices_end_time']        = 0;
            $arr[$id]['devices_send_time']       = 0;
            $arr[$id]['devices_status']          = 0;
            cache("z_admin",json_encode($arr));     
        }
        return $arr[$id];
    }

    /*
     * 修改医生是否在线 0=离线 1=在线
     */
    public function setOffLine($id=0,$status=0,$type='terminal')
    {
        $json = cache($type);
        $arr  = json_decode($json,1);
        if(isset($arr[$id])){
            $arr[$id]['devices_status'] = $status;
            cache($type,json_encode($arr));   
        }
    }

    // 获取ip
    public function getConnIp($id=0,$type='terminal'){
        $json       = cache($type);
        $arr        = json_decode($json,1);
        $ip         = isset($arr[$id]['devices_ip'])?$arr[$id]['devices_ip']:'';
        return $ip;
    }

    //获取当前在线医生
    public function getOnLine()
    {
        $line = array();
        $json       = cache("terminal");
        $arr        = json_decode($json,1);
        if(!empty($arr)){
            foreach ($arr as $key => $value) {
                if($value['devices_status']==1){
                    $line[$key] = $value['devices_ip'];
                }
            }
        }
        return $line;
    } 
    // 获取综合显示屏IP
    public function getLargeIp($hall_id=''){
        $json       = cache("devices");
        $arr        = json_decode($json,1);
        $ip = array();
        foreach ($arr as $key => $value) {
            if($value['devices_area_id'] == $hall_id && $value['devices_type']==1 && $value['devices_status']==1){
                $ip[] = $value['devices_ip'];
            }
        }
        return $ip;
    }
    // 替换变量
    public function getContent($id,$result){
        $content = db("sms_temp")->where('id',$id)->value("content");
        if(!empty($content)){            
            $content = str_replace("[name]",$result['name']?$result['name']:'',$content);
            $content = str_replace("[date]",$result['despeakDate'].' '.$result['time_Part_S'].'~'.$result['time_Part_O'],$content);
            $content = str_replace("[code]",($result['noChar']?$result['noChar']:'').($result['queNo']?$result['queNo']:''),$content);
            $content = str_replace("[hall]",$result['hallName']?$result['hallName']:'',$content);
            $content = str_replace("[doctor]",$result['queName']?$result['queName']:'',$content);
        }
        return $content;
    }
}