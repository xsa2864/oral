<?php
namespace app\api\controller\v2;

use think\Controller;
use think\Request;
use think\DB;
use think\facade\Cookie;

class Index extends Controller
{
    /**
     * 显示版本信息
     *
     * @return \think\Response
     */
    public function index()
    {
        return json(['Hospital'=>'口腔医院','Version'=>'v3']);
    }

    // 获取基础信息
    public function showBase()
    {
        $horizontal = input("horizontal",0);
    	$ip = Cookie::get("devices_ip");
    	if(!$ip){
    		// $ip = Request()->ip();
            $ccd = new \app\api\model\CacheCode;
            $ip = $ccd->getCode();  
            Cookie::forever("devices_ip",$ip);
            $set = new \app\api\model\Organize;
            $set->setDefault($ip);
    	}

    	$cache = cache("devices");    	
    	$arr = json_decode($cache,1);    
    	
    	$ads_img 	= '';
    	$img_list 	= '';
    	$tip 		= '请保持现场秩序与安静，耐心等候叫号！';
    	if(isset($arr[$ip])){
            $hall_id = $arr[$ip]['devices_area_id'];            
            $tip    = $arr[$ip]['devices_tips'];
        }else{
            $hall_id = 0;
        }
    	//顶部广告图片	
	    $where = array();
	    $where[] = ['hall_id','=',$hall_id];
	    $where[] = ['type','=',1];
        $where[] = ['form','=',0];
        $where[] = ['horizontal','=',$horizontal];        
	    $ads = DB::name("ads")->where($where)->find();
	    if($ads){
	        $attr = json_decode($ads['attr'],1);
	        $ads_img = $attr[0]['img'];
	    }
	    // 轮播图
	    $cre = array();
	    $cre[] = ['hall_id','=',$hall_id];
	    $cre[] = ['type','=',2];
        $cre[] = ['form','=',0];
	    $cads = DB::name("ads")->where($cre)->find();

	    $img_list = 0;
	    if($cads){
	        $img_list = json_decode($cads['attr'],1);
	    }
    	
    	$data['ads_img'] 	= $ads_img;
    	$data['tip'] 		= $tip;
    	$data['img_list'] 	= $img_list;
		$data['ip'] = $ip;
    	return json($data);
    }

    // 获取呼叫信息
    public function showInfo(){
    	$ip = Cookie::get("devices_ip");
    	$push   = new \app\api\model\PushCache;
        $data['item']    = $push->getScreenInfo($ip);  
    	return json($data);
    }

    // 修改通信編號
    public function changeCode()
    {
    	$devices_ip = input("devices_ip",'');
        $devices_type = input("devices_type",0);
        if(empty($devices_ip)){
            $ccd = new \app\api\model\CacheCode;
            $devices_ip = $ccd->getCode();  
        }
        Cookie::forever("devices_ip",$devices_ip);
        $set = new \app\api\model\Organize;
        $set->setDefault($devices_ip,$devices_type);
        return json("设置成功". $devices_ip,200);       
    }

    public function showBases()
    {
        $horizontal = input("horizontal",0);
    	$ip = Cookie::get("devices_ip");
    	if(!$ip){
            $ccd = new \app\api\model\CacheCode;
            $ip = $ccd->getCode();  
    		Cookie::forever("devices_ip",$ip);
            $set = new \app\api\model\Organize;
            $set->setDefault($ip,1);
    	}
    	$cache = cache("devices");    	
    	$arr = json_decode($cache,1);    

        $top_logo = '';
        $top_text = '';
        $img 	  = '';
    	$tip 		= '请保持现场秩序与安静，耐心等候叫号！';
    	$field = [];
    	if(isset($arr[$ip])){
    		$hall_id = $arr[$ip]['devices_area_id'];
            foreach ($arr[$ip]['devices_field'] as $key => $value) {
                $field['f'.$value] = 1;
            }
            $tip = $arr[$ip]['devices_tips'];
 		}else{
            $hall_id = 0;
        }
        // 综合屏顶部展示
        $wh = array();
        $wh[] = ["hall_id",'=',$hall_id];
        $wh[] = ["type",'=','4'];
        $wh[] = ["status",'=','1'];
        $wh[] = ['horizontal','=',$horizontal];
        $ads = DB::name("ads")->where($wh)->find();
        if($ads){
            $arr_log = json_decode($ads['attr'],1);
            $top_logo = isset($arr_log[0]['img'])?$arr_log[0]['img']:'';
            $top_text = $ads['title'];
        }
        //综合屏图片展示
        $wh = array();
        $whs[] = ["hall_id",'=',$hall_id];
        $whs[] = ["type",'=',3];
        $whs[] = ["status",'=','1'];
        $wh[] = ['horizontal','=',$horizontal];
        $attr = DB::name("ads")->where($whs)->value("attr");
        if($attr){
            $img = json_decode($attr,1);
        }

 		$data['top_logo']	= $top_logo;
 		$data['top_text']	= $top_text;
 		$data['img']		= $img;
 		$data['ip']			= $ip;
 		$data['tip']		= $tip;
 		$data['field']		= $field;
 		return json($data);
    }
    // 获取区域呼叫信息
    public function showHallInfo(){
    	$type  = input("type",1);
    	$ip    = Cookie::get("devices_ip");
    	$cache = cache("devices");    	
    	$arr   = json_decode($cache,1); 
    	$hall_id = 0;
    	$item  = [];
    	$num   = 0;
		if(isset($arr[$ip])){
    		$hall_id = $arr[$ip]['devices_area_id'];
    		$push   = new \app\api\model\PushCache;
    		if($type==1){
        		$item   = $push->getScreenInfo($hall_id,'hallScreen'); 
    		}else{
    			$item   = $push->getScreenInfo($hall_id,'operScreen'); 
    		}
        	if(isset($item['list'])) {
        		$num = count($item['list']);
        	}
    	}
        $data['item'] = $item;
        $data['num']  = $num;
    	return json($data);
    }
    
    // 获取排班表信息
    public function getClassTime()
    {
        $where[] = ['class','<>',0];
        $result = db("z_doctor_class")->alias("dc")->field("dc.*,s.QueName,dr.QueName as name,dr.type")
            ->rightJoin("z_doctor dr","dr.id=dc.doctor_id")
            ->leftJoin("serque s","s.QueId=dc.que_id")
            ->order("dc.que_id asc")
            ->where($where)->select();
        $list = array();
        if($result){            
            $cla = new \app\admin\model\ClassTime;
            foreach ($result as $key => $value) {
                $arr = $cla->arrangeClass($value['class']);
                $value['detail'] = $arr;
                $list[$value['que_id']]['title'] = $value['QueName'];
                $list[$value['que_id']]['data'][] = $value;
            }
        }
        
        $config = db("z_temp_config")->where("id","1")->find();
        $data["list"] = array_values($list);
        $data["config"] = $config;
        return json($data);
    }
}