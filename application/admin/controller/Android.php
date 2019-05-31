<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;
use think\facade\Config;

class Android extends Base
{
	// 取号配置
    public function index(){
        $wh  = array();
        $whu = array();
        $where = array();
        if($this->userid!=1){
            $whu[]      = ['UnitId','=',$this->unitid];
            // $where[]    = ['s.unit_id','=',$this->unitid];
            if($this->hallid){
                $wh[]       = ['HallNo','=',$this->hallid];
                $where[]    = ['s.hall_id','=',$this->hallid];
            }   
        }        
        $where[]    = ['s.type','=',0];
        $list = db("z_voice")->alias("s")
                ->field("s.*,u.HallName,m.FullName,q.QueName")
                ->leftJoin("hall u","u.HallNo=s.hall_id")
                ->leftJoin("serque q","q.QueId=s.que_id")
                ->leftJoin("manager m","m.UserId=s.manager_id")
                ->where($where)
                ->order("s.unit_id asc")
                ->paginate(20);  
        $page = $list->render();
        $this->assign("page",$page);
        $this->assign("list",$list);

        $hall  = db("hall")->where($wh)->select();
        $this->assign("hall",$hall);
        
        $unit  = db("unit")->where($whu)->select();
        $this->assign("unit",$unit);

    	return $this->fetch('index');
    }
    // 获取区域
    public function getHall()
    {
        $re_msg['success'] = 0;
        $re_msg['msg'] = '获取失败';
        $unit_id = input("unit_id",0);
        $where[] = ['UnitId','=',$unit_id];
        $where[] = ['EnableFlag','=',1];
        $list = db("hall")->field("HallNo,HallName")->where($where)->select();
        if($list){
            $re_msg['success'] = 1;
            $re_msg['msg'] = '获取成功';
            $re_msg['data'] = $list;
        }
        echo json_encode($re_msg);
    }
    // 保存配置信息
    public function save(){
    	$re_msg['success'] = 0;
    	$re_msg['msg'] = '更新失败';
        $data['screen_num']     = input("screen_num",0);
        $data['screen_rule']    = input("screen_rule",'');
        $data['view_id']        = input("view_id",0);
    	$rs = 0;
    	$result = db("config_android")->where('unitid',$this->unitid)->find();
    	if($result){
    		$rs = db("config_android")->where('unitid',$this->unitid)->update($data);
    	}else{
    		$data['unitid'] = $this->unitid;
    		$rs = db("config_android")->data($data)->insert();
    	}
    	if($rs){
    		$re_msg['success'] = 1;
    		$re_msg['msg'] = '更新成功';
    	}
    	echo json_encode($re_msg);
    }

    // 显示屏信息
    public function info(){
    	$json = cache("room_code");
    	$list = json_decode($json,1);
        $this->assign("list",$list);
        $this->assign("title"," 显示屏在线信息");
    	return $this->fetch('info');
    }

    // 信息发布
    public function infoSend(){
        $json = cache("devices");
        $list  = json_decode($json,1);
        if($list){
            ksort($list);
        }       
        $clist = cache("cache_list");
        if($clist){
            krsort($clist);
        }      
        // 背景图片
        $where['status']    = 1;
        $result = db("ads")->where($where)->select();
        $this->assign("img_list",$result);
        $this->assign("list",$list);
        $this->assign("clist",$clist);
        $this->assign("title"," 显示屏在线信息");
        return $this->fetch('infoSend');
    }

    //显示屏数据
    public function showInfo(){
    	$json = cache("devices");
        $arr = json_decode($json,1);
        if(!empty($arr)){            
            $volume = array();
            $harr = array();
            foreach ($arr as $key => $value) {
                if($this->hallid!=0 && $this->userid!=1){
                    if($this->hallid==$value['devices_area_id']){
                        $volume[$key] = $value['devices_area_id'];
                        $harr[$key] = $value;
                    }
                }else{
                    $volume[$key] = $value['devices_area_id'];
                    $harr = $arr;
                }
            }
            array_multisort($volume,$harr);
            // print_r(json_decode($json,1));exit;
        }
    	echo json_encode($harr);
    }

    // 切换显示
    public function change()
    {
    	$re_msg['code'] = 201;
		$re_msg['msg'] 	= "推送没有成功";

    	$code = input("code",0);
    	$type = input("type",1);
    	$url  = input("url","");

    	$json = cache("room_code");
	    $arr  = json_decode($json,1);
	    if($type==2){
	    	$arr[$code]['video'] = 2;
	    	$arr[$code]['url'] 	 = $url;
	    }else if($type==1){
	    	$arr[$code]['video'] = 1;
	    	$arr[$code]['ad_id'] 	 = $url;
	    }else if($type==3){
	    	$arr[$code]['url'] 	 = $url;
	    }else if($type==4){
	    	$arr[$code]['ad_id'] 	 = $url;
	    }

	    cache("room_code",json_encode($arr));

    	if($type==2){
    		if(!empty($url)){
    			if(strpos($url,'http') ===false){
    				$config = Config::get("app.socket_url");
    				$file = explode("\\", Env::get("ROOT_PATH"));
    				$file = array_filter($file);
    				$url = "http://".$config['host'].'/'.$file[count($file)-1]."/public/uploads/video/".$url;
    			}
    			$this->changeSocke('publish_video',$code,$url);    			
    		}
    	}else if($type==1){
    		$img_list = '';
	        $ads_list = Db::connect('db_config2')->table("t_ads")->where("id",$url)->find();
	        if($ads_list){
	            $img_list = $ads_list['attr'];            
	        }
    		$this->changeSocke('publish_img',$code,$img_list);
    	}	
    	
    	echo json_encode($re_msg);
    }
    // 推送
    public function publish()
    {
        $devices_ip                         = input("devices_ip",array('-1'));
        $data['devices_font_color']         = input("devices_font_color","");
        $data['devices_background_color']   = input("devices_background_color","");
        $data['devices_content']            = input("devices_content","");
        $data['devices_image']              = input("devices_image","");
        $data['devices_font_size']          = input("devices_font_size",0);
        $data['devices_ads_id']             = input("devices_ads_id",0);
        $data['devices_video']              = input("devices_video","");
        $data['devices_type']               = input("devices_type","admin");

        if($data['devices_video']){
            if(strpos($data['devices_video'],'http') ===false){
                $config = Config::get("app.socket_url");
                $file = explode("\\", Env::get("ROOT_PATH"));
                $file = array_filter($file);
                $url = "http://".$config['host'].'/'.$file[count($file)-1]."/public/uploads/video/".$data['devices_video'];
                $data['devices_video'] = $url;
                $data['devices_ads_id'] = 0;
            }        
        }else if($data['devices_ads_id']){
            $img_list = '';
            $ads_list = Db::connect('db_config2')->table("t_ads")->where("id",$data['devices_ads_id'])->find();
            if($ads_list){
                $img_list = $ads_list['attr'];            
            }
            $data['devices_image'] = $img_list;
        }   

        if(in_array(0, $devices_ip)){
            $this->changeSocke(0,$data);
        }else{
            $return = 'fail';
            foreach ($devices_ip as $key => $value) {
                $rs = $this->changeSockeAll($value,$data);
                if($rs=='ok'){
                    $return = 'ok';
                    $this->log_devices($value,$data);  
                }
            }

            if($return=='ok'){
                $re_msg['code'] = 200;
                $re_msg['msg']  = "推送成功";
            }else if($return=='offline'){
                $re_msg['msg']  = "用户离线";
            }else if($return=='fail'){
                $re_msg['msg']  = "推送失败";
            }
            echo json_encode($re_msg);  
        }
    }
    // 历史推送
    public function sendAgain(){
        $re_msg['code'] = 201;
        $re_msg['msg']  = "执行失败";      

        $array  = cache("cache_list");
        $key    = input("key","");

        if(!empty($array)){
            if(isset($array[$key])){
                $this->changeSocke($array[$key]['devices_ip'],$array[$key]);
            }
        }
        echo json_encode($re_msg);
    }

    // 呼叫soket
    public function changeSocke($devices_ip='',$data,$type='publish')
    {		
    	$re_msg['code'] = 201;
		$re_msg['msg'] 	= "执行失败";		

	    $content = json_encode($data);
	    // 推送的url地址，使用自己的服务器地址
	    $config = Config::get("app.socket_url");
	    $push_api_url = "http://".$config['host'].":".$config['port'];

	    $post_datas = array(
	       "type"       => $type,
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
			$re_msg['msg'] 	= "推送成功";
            $this->log_devices($devices_ip,$data);  
	    }else if($return=='offline'){
	    	$re_msg['msg'] 	= "用户离线";
	    }else if($return=='fail'){
	    	$re_msg['msg'] 	= "推送失败";
	    }
	    echo json_encode($re_msg);	
    }
    // 多个呼叫soket
    public function changeSockeAll($devices_ip='',$data,$type='publish')
    {         
        $content = json_encode($data);
        // 推送的url地址，使用自己的服务器地址
        $config = Config::get("app.socket_url");
        $push_api_url = "http://".$config['host'].":".$config['port'];

        $post_datas = array(
           "type"       => $type,
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
        
        return $return;
    }
    // 保存推送过的内容
    public function log_devices($devices_ip,$data){
        $json = cache("devices");
        $arr  = json_decode($json,1);
        if(isset($data['devices_font_color'])){
            $arr[$devices_ip]['devices_font_color'] = $data['devices_font_color'];
        }
        if(isset($data['devices_background_color'])){
            $arr[$devices_ip]['devices_background_color'] = $data['devices_background_color'];
        }
        if(isset($data['devices_font_size'])){
            $arr[$devices_ip]['devices_font_size'] = $data['devices_font_size'];
        }
        if(isset($data['devices_image'])){
            $arr[$devices_ip]['devices_image']    = $data['devices_image'];
        }
        if(isset($data['devices_content'])){
            $arr[$devices_ip]['devices_content']  = $data['devices_content'];
        }
        if(isset($data['devices_ads_id'])){
            $arr[$devices_ip]['devices_ads_id']   = $data['devices_ads_id'];
        }
        if(isset($data['devices_video'])){
            $arr[$devices_ip]['devices_video']    = $data['devices_video'];
        }
        cache("devices",json_encode($arr));
    }
    // 显示屏信息two
    public function infoTwo(){
        // $json = cache("devices");
        // $arr = json_decode($json,1);
        // $ijson       = cache("terminal");
        // $iarr        = json_decode($ijson,1);
        // $zjson       = cache("z_admin");
        // $zarr        = json_decode($zjson,1);
        // echo '<pre>';
        // // print_r($arr);
        // echo '======terminal========';
        // print_r($iarr);
        // echo '======z_admin========';
        // print_r($zarr);
        // exit;
        $this->assign("title"," 显示屏在线信息");
        return $this->fetch('infoTwo');
    }
    // 编辑页面
    public function editInfo(){
        $devices_ip = input("ip",0);
        $json = cache("devices");
        $arr  = json_decode($json,1);
        $hall = db("hall")->where("EnableFlag",1)->select();
        $this->assign("hall",$hall);
        $this->assign("list",isset($arr[$devices_ip])?$arr[$devices_ip]:array());
        $this->assign("devices_ip",$devices_ip);
        $this->assign("title"," 编辑信息");
        return $this->fetch('editInfo');
    }
    // 保存设备信息
    public function saveDeviceInfo(){
        $re_msg['success'] = 1;
        $re_msg['msg'] = '更新成功';
        $devices_ip         = input("devices_ip",0);
        $devices_type       = input("devices_type",0);
        $devices_code       = input("devices_code",'');
        $devices_name       = input("devices_name",'');
        $devices_area_id    = input("devices_area_id",0);
        $devices_tips       = input("devices_tips",'');
        $devices_field      = input("info",'');
        $json = cache("devices");
        $arr  = json_decode($json,1);
        if(isset($arr[$devices_ip])){
            $arr[$devices_ip]['devices_type'] = $devices_type;
            $arr[$devices_ip]['devices_code'] = $devices_code;
            $arr[$devices_ip]['devices_name'] = $devices_name;
            $arr[$devices_ip]['devices_tips'] = $devices_tips;
            $arr[$devices_ip]['devices_area_id'] = $devices_area_id;
            $arr[$devices_ip]['devices_field'] = $devices_field;
            $name = db("hall")->where("HallNo",$devices_area_id)->value("HallName");
            $arr[$devices_ip]['devices_area_name'] = $name;
            cache("devices",json_encode($arr));  
        }
        echo json_encode($re_msg);    
    }
    // 切换显示
    public function changeTwo()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "推送没有成功";

        $code = input("code",0);
        $text = input("text","");
        $type = input("type",0);
        
        $json = cache("room_code");
        $arr  = json_decode($json,1);        
        if($type){
            $arr[$code]['bground']   = $text;
        }else{
            $arr[$code]['text_str']  = $text; 
            $this->changeSocke('publish_text',$code,'',$text);    
        }
        cache("room_code",json_encode($arr));  
        echo json_encode($re_msg);
    }

    // 标记名称
    public function addTitle()
    {
        $code  = input("code",0);
        $title_name = input('title_name','');
        if($code){
            $json = cache("room_code");
            $arr  = json_decode($json,1);
            $arr[$code]['title_name']   = $title_name;
            cache("room_code",json_encode($arr));  
        }
    }

    // 删除设备
    public function deviceDel()
    {
        $type = input("type",0);    //清空
        $list = input("list",'');    //清空
        $code = input("code",'');
        if($type){
            // cache("devices",NULL);  
            $json = cache("devices");
            $arr  = json_decode($json,1);
            $arrs = explode(',', $list);
            foreach ($arrs as $key => $value) {
                unset($arr[$value]);
            }
            cache("devices",json_encode($arr));  
        }else{
            $json = cache("devices");
            $arr  = json_decode($json,1);
            unset($arr[$code]);
            cache("devices",json_encode($arr));  
        }
        return 1;
    }
    // 刷新设备
    public function deviceUp()
    {
        $type = input("type",0);    //0=刷新全部  1=指定刷新
        $list = input("list",'');    //列表
        if($type){            
            $arrs = explode(',', $list);
            $soc = new \app\admin\model\Socket;
            foreach ($arrs as $key => $value) {
                $a = $soc->terminalSocke($value,['reload'=>'1']);
            }       
        }else{
            $a = $soc->terminalSocke('all',['reload'=>'1']);
        }
        return 1;
    }
}