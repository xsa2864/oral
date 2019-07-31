<?php
namespace app\app\controller;

use think\Controller;
use think\Facade\Request;
use think\Db;
use think\Cache;
use think\facade\Cookie;

class Hall extends Common
{
    // 单位选择列表
    public function selectList()
    {
        return $this->fetch("listVue");
    }
    // 队列选择列表
    public function getQueues()
    {
        return $this->fetch("queueVue");
    }
     // 医生选择列表
    public function getDoctors()
    {
        return $this->fetch("doctorVue");
    }
     // 预约页面
    public function getOrder()
    {
        return $this->fetch("markVue");
    }
    // 详情页面
    public function getOrderDetail()
    {
        return $this->fetch("resultVue");
    }
    // 查询页面
    public function searchDetail()
    {
        return $this->fetch("selectVue");
    }
    
	// 医院列表
    public function lists()
    {
        $unitid = input("unitid",0);
        if(Cookie::has('unitid') && $unitid==0){
            $unitid = Cookie::get('unitid');
        }
        $pageNum = 10;
        $where = array();
        if($unitid){
            $where['UnitId'] = $unitid;
            $list = db("hall")->where($where)->page(1,$pageNum)->select();
            if($list){
                $flag = 0;
                foreach ($list as $key => $value) {
                    if($flag){
                        $list[$key]['url'] = url('/app/hall/getDoctor',['id'=>$value['HallNo']]);
                    }else{
                        $list[$key]['url'] = url('/app/hall/getQueue',['id'=>$value['HallNo']]);
                    }
                }
            }
            $num = db('hall')->where($where)->count();
    	    $this->assign("Subtitle","预约科室列表");
            $more = $num>$pageNum?1:'没有更多数据了';
            $this->assign("more",$more);
            $this->assign("list",$list);
            return $this->fetch('list');
        }else{
            $where['EnableFlag'] = 1;
            $list = db("unit")->where($where)->page(1,$pageNum)->select();
            $num = db('unit')->where($where)->count();
            $this->assign("Subtitle","预约医院列表");
            $more = $num>$pageNum?1:'没有更多数据了';
            $this->assign("more",$more);
            $this->assign("list",$list);
            return $this->fetch('unitList');
        }
    }
    // 获取更多医院列表  
    public function more_list(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '没有更多数据了';

        $unitid = input("unitid",0);
        $where = array();
        if($unitid){
            $where['UnitId'] = $unitid;
        } 
        $where['EnableFlag'] = 1;
        $page = input("page",1);
        $pageNum = 10;
        if($unitid){
            $num = db('hall')->where($where)->count();
        }else{
            $num = db('unit')->where($where)->count();
        }
    	
    	$flag = $num - ($page-1)*$pageNum;
    	if($flag>0){
            if($unitid){
    		    $list = db("hall")->where($where)->page($page,$pageNum)->select();
                if($list){
                    $flag = 0;
                    foreach ($list as $key => $value) {
                        if($flag){
                            $list[$key]['url'] = url('/app/hall/getDoctor',['id'=>$value['HallNo']]);
                        }else{
                            $list[$key]['url'] = url('/app/hall/getQueue',['id'=>$value['HallNo']]);
                        }
                    }
                }
            }else{
                $list = db("unit")->where($where)->page($page,$pageNum)->select();
            }
    		if($list){
    			$re_msg['success'] = 1;
       			$re_msg['msg'] = $list;
       			$re_msg['page'] = $flag>$pageNum?($page+1):0;
    		}
    	}
    	echo json_encode($re_msg);
    }
    // 队列列表
    public function getQueue(){
        $id = input("id",0);
        $pageNum = 10;
        $where = array();
        $where[] = ["HallNo",'=',$id];
        $where[] = ["EnableFlag",'=',1];
        $list = db("serque")->where($where)->page(1,$pageNum)->select();
        if($list){
            $flag = 0;
            foreach ($list as $key => $value) {
                if($flag){
                    $list[$key]['url'] = url('/app/hall/getDoctor',['id'=>$value['HallNo']]);
                }else{
                    $list[$key]['url'] = url('/app/hall/getQueue',['id'=>$value['HallNo']]);
                }
            }
        }
        $num = db('serque')->where($where)->count();
        $more = $num>$pageNum?1:'没有更多数据了';
        $this->assign("more",$more);
        $this->assign("list",$list);
        $this->assign("HallNo",$id);
        $this->assign("Subtitle","就诊队列");
        return $this->fetch('queue');
    }
    // 医生列表
    public function getDoctor(){
    	$id = input("id",0);
    	$pageNum = 10;
        $where = array();
        $where[] = ["c.que_id",'=',$id];
        $where[] = ["d.status",'=',1];
    	$list = DB::name("z_doctor_class")->alias("c")
                ->field("d.*")
                ->rightJoin("z_doctor d","d.id=c.doctor_id")
                ->where($where)->page(1,$pageNum)->select();
    	$num = DB::name('z_doctor_class')
                ->alias("c")
                ->rightJoin("z_doctor d","d.id=c.doctor_id")
                ->where($where)
                ->count();
    	$more = $num>$pageNum?1:'没有更多数据了';
    	$this->assign("more",$more);
    	$this->assign("list",$list);
    	$this->assign("HallNo",$id);
    	$this->assign("Subtitle","就诊医生");
        return $this->fetch('doctor');
    }
    // 获取更多医院列表
    public function more_doctor(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '没有更多数据了';
        $id = input("HallNo",0);
    	$page = input("page",1);
    	$pageNum = 10;
        $where = array();
        $where[] = ["c.que_id",'=',$id];
        $where[] = ["d.status",'=',1];
    	// $num = db('serque')->where($where)->count();
        $num = DB::name('z_doctor_class')
                ->alias("c")
                ->rightJoin("z_doctor d","d.id=c.doctor_id")
                ->where($where)
                ->count();
    	$flag = $num - ($page-1)*$pageNum;
    	if($flag>0){
    		// $list = db("serque")->where($where)->page($page,$pageNum)->select();
            $list = DB::name("z_doctor_class")->alias("c")
                ->field("d.*")
                ->rightJoin("z_doctor d","d.id=c.doctor_id")
                ->where($where)->page(1,$pageNum)->select();
    		if($list){
    			$re_msg['success'] = 1;
       			$re_msg['msg'] = $list;
       			$re_msg['page'] = $flag>$pageNum?($page+1):0;
    		}
    	}
    	echo json_encode($re_msg);
    }
    // 预约
    public function getMark()
    {
        // $id         = input("id","");
        // $date       = input("date",date("Y-m-d",strtotime("+1 day",time())));
        // $list = db("z_doctor_class")->alias('dc')
        //         ->field("dc.*,s.QueName,d.QueName as name,d.WorkTime1,d.WorkTime2,d.WorkTime3,d.WorkTime4")
        //         ->leftJoin('serque s','s.QueId=dc.que_id')
        //         ->leftJoin('z_doctor d','dc.doctor_id=d.id')
        //         ->where("dc.id",$id)
        //         ->find();
        // $this->assign("date",'');
        // $this->assign("list",$list);

        // $cla = new \app\admin\model\ClassTime;
        // $data = $cla->checktime($list['doctor_id'],$list['que_id']);        

        // $this->assign("data",$data);
        // $this->assign("id",$id);
                
        $id = input("id",0);
        $did = input("did",0);
        $list = db("z_doctor")->alias('s')
                ->leftJoin('hall h','s.que_id = h.HallNo')
                ->leftJoin('unit u','u.UnitId = h.UnitId')
                ->where("s.id",$id)
                ->find();
        $work = new \own\Work();
        $result = $work->checktime($list);
        $where = array();
        if(Cookie::has('unitid')){
            $where[] = ['unitid','=',Cookie::get('unitid')];
        }else{
            $where[] = ['unitid','=',$this->unitid];
        }
        $if_name = db("config_fetch")->where($where)->value("if_name");
        
        $rs = array();
        if($did){
            $rs = db("despeak")->where("despeak_id",$did)->find();
        }
        $this->assign("drs",$rs);
        $this->assign("date",$result['data']);
    	$this->assign("list",$list);
        $this->assign("if_name",$if_name);

        $this->assign("idcard",cookie("idcard"));
        $this->assign("name",cookie("name"));
        $this->assign("mobile",cookie("mobile"));

    	$this->assign("Subtitle","填写预约信息");
        return $this->fetch('mark');
    }
    // 获取时间点
    public function getTime(){
    	$id = input("id",0);
    	$date = input("date",'');

        $re_msg['success'] = 0;
        $re_msg['msg'] = '查询失败';
        $data = array();
        if (Request::instance()->isPost())
        {
            $result = db("serque")->where("QueId",$id)->find();
            // 获取上班时间
            $work=new \own\Work();
            $data = $work->getCheckTimes($result,$date);
            
            if($result){
                $re_msg['success'] = 1;
                $re_msg['msg'] = $data;
            }
        }
        echo json_encode($re_msg);
    }
    // 保存预约
    public function markSave(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '预约失败';
        $despeak_id     = input("despeak_id",0);
        $data['name']   = input("name","");
    	$data['idcard'] = input("idcard","");
	    $data['mobile'] = input("mobile","");
	    $data['despeakDate'] = input("mktime",0);
        $radio1 = input("radio1","");
        $data['time_Part_S'] = '';
        $data['time_Part_O'] = '';
        if(!empty($radio1)){
            $ra = explode('-', $radio1);
            $data['time_Part_S'] = $ra[0].":00";
            $data['time_Part_O'] = $ra[1].":00";
            $data['despeakTime'] = strtotime($data['despeakDate']." ".$data['time_Part_O']);
        }
	    $data['unitId']    = input("unitId",0);
	    $data['queId']     = input("QueId",0);
	    $data['hallNo']    = input("HallNo",0);
	    $data['inTime']    = date("Y-m-d H:i:s",time());
	    $data['addtime']   = time();
        $data['platform']  = $this->get_platform();
        $config = db("config_fetch")->where("unitid",$data['unitId'])->find();
        if($config){    //判断是否黑名单
            if($config['blacklist_num']>0 && $config['blacklist_day']>0){
                $crs = db("despeak")->where("idcard",$data['idcard'])->limit($config['blacklist_num'])->order("despeak_id desc")->select();
                if($crs){
                    $falg = true;
                    $times = $crs[0]['addtime']+$config['blacklist_day']*3600*24;
                    if($times > time()){
                        foreach ($crs as $key => $value) {
                            if($value['status']>0){
                                $falg = false;
                            }
                        }
                    }else{
                        $falg = false;
                    }
                    if($falg){
                        $re_msg['msg'] = '该账户暂时不能预约';
                        echo json_encode($re_msg);exit;
                    }
                }
            }
        }
        if($data['despeakDate']==date("Y-m-d",time())){
            $m_time = strtotime($data['despeakDate'].' '.$data['time_Part_O']);
            if($m_time < time()){
                $re_msg['msg'] = '选择预约时间错误';
                echo json_encode($re_msg);exit;
            }
        }
        if(!empty($data['queId'])){
            $qrs = $this->makeNumber($data['queId'],$data['despeakDate'],$data['despeakTime']);
            $data['queName'] = $qrs['queName'];
            $data['noChar']  = $qrs['noChar'];
            $data['queNo']   = $qrs['queNo'];
        }
        if(!empty($data['hallNo'])){
            $data['hallName'] = db("hall")->where('HallNo',$data['hallNo'])->value("HallName");
        }
        $validate = new \app\app\validate\Token;
        if(!$validate->check($data)){
          $re_msg['msg'] = $validate->getError();
        }else{      
            cookie("idcard",$data['idcard']);
            cookie("name",$data['name']);
            cookie("mobile",$data['mobile']);
			$where['mobile'] = $data['mobile'];
			$where['idcard'] = $data['idcard'];
			$where['despeakDate'] = $data['despeakDate'];
			$where['time_Part_S'] = $data['time_Part_S'];
			$where['time_Part_O'] = $data['time_Part_O'];
            if($despeak_id){
                $us = db("despeak")->where("despeak_id",$despeak_id)->update($data);
                if($us){
                    $re_msg['success'] = 1;
                    $re_msg['msg'] = '重新预约成功';
                    $re_msg['id'] = $despeak_id;
                }
            }else{                
    			$result = db("despeak")->where($where)->select();	
    			if($result){
    				$re_msg['msg'] = '已经预约过';
    			}else{		
    			    $rs = db("despeak")->insertGetId($data);
    			    if($rs){
    			    	$re_msg['success'] = 1;
    		        	$re_msg['msg'] = '预约成功';
                        $re_msg['id'] = $rs;
                        // 短信推送
                        $sms = new \app\admin\model\SmsModel; 
                        $sms->remind_sms($data['unitId'],$data['mobile']);
    			    }
    			}
            }
		}
	    echo json_encode($re_msg);
    }
    // 生成排队好
    public function makeNumber($id=0,$time='',$etime=0){
        $arr = array();
        $result = db("serque")->where('QueId',$id)->find();
        if($result){            
            $queNo = 0;
            $where = array();
            $where[] = ['despeakDate','=',$time];
            $where[] = ['queId','=',$id];
            $where[] = ['status','=',1];
            if($result['QueForm']==1){//按人数排号
                $count = db("despeak")->where($where)->count();
                $queNo = $result['StarNo']+$count+1;
            }else if($result['QueForm']==2){//按时间排号
                $where[] = ['despeakTime','=',$etime];
                $count = db("despeak")->where($where)->count();
                $queNo = date('H',$etime-1000).sprintf("%02d",$count+1);
            }else if($result['QueForm']==3){

            }
            $arr['noChar']  = $result['NoChar'];
            $arr['queName'] = $result['QueName'];
            $arr['queNo']   = $queNo;
        }
        return $arr;
    }
    // 查询操作平台
    public function get_platform(){
        $platform = '4';
        $agent = strtolower($_SERVER['HTTP_USER_AGENT']);
        if(strpos($agent, 'windows nt')){
            $platform = 'windows';
        }else{
            $platform = 'APP';
        }
        // if(strpos($agent, 'mac os')){
        //     $platform = 'IOS';
        // }else if(strpos($agent, 'iphone')){
        //     $platform = 'iphone';
        // }else if(strpos($agent, 'android')){
        //     $platform = 'android';
        // }else if(strpos($agent, 'ipad')){
        //     $platform = 'ipad';
        // }
        return $platform;
    }
    // 显示预约结果
    public function markResult(){
        $id = input("id",0);
        $mobile = input('mobile','');
        $wh['d.despeak_id'] = $id;
        $wh['d.mobile'] = $mobile;
        $result = db("despeak")->alias("d")
                    ->field("d.*,h.HallName,s.QueName")
                    ->leftJoin("hall h","h.HallNo=d.hallNo")
                    ->leftJoin("serque s","s.QueId=d.queId")
                    ->where($wh)->find();
        $this->assign("result",$result);
        $this->assign("Subtitle","预约结果");
        return $this->fetch('result');
    }
    // 查询预约
    public function selectMark(){
        $status = input("status","");
        $this->assign("status",$status);
        $this->assign("idcard",cookie("idcard"));
        $this->assign("mobile",cookie("mobile"));
        $this->assign("Subtitle","预约信息");
        return $this->fetch('select');
    }
    // 查询预约内容
    public function getDataMark(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '查询失败';
        $idcard = input("idcard",'');
        $mobile = input("mobile",'');
        $status = input("status",'');
        if(Cookie::has('unitid')){
            $where['d.unitId'] = Cookie::get('unitid');
        }
        $where = array();
        if(!empty($idcard)){
            $where[] = ['d.idcard','=',$idcard];
            cookie("idcard",$idcard);
        }
        if(!empty($mobile)){
            $where[] = ['d.mobile','=',$mobile];
            cookie("mobile",$mobile);
        }
        if(!empty($where)){
            if(!empty($status)){
                $where[] = ['d.status','=',1];
                $where[] = ['d.despeakTime','>',time()];
            }
            
            $result = db("despeak")->alias("d")
                    ->field("d.*,h.HallName,s.QueName")
                    ->leftJoin("hall h","h.HallNo=d.hallNo")
                    ->leftJoin("serque s","s.QueId=d.queId")
                    ->order('d.despeak_id desc')
                    ->where($where)
                    ->where('d.addtime','>',strtotime("-3 month",time()))->select();  
            if($result){
                $re_msg['success']  = 1;
                $re_msg['msg']      = $result;
            } 
        }
        echo json_encode($re_msg);
    }
    // 取消预约
    public function cancelMark(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '取消失败';

        $id = input("id");
        $rs = db("despeak")->where("despeak_id",$id)->update(['status'=>0]);
        if($rs){
            $re_msg['success'] = 1;
            $re_msg['msg'] = '取消成功';
            // 短信推送
            $sms = new \app\admin\model\SmsModel; 
            $sms->cancel_sms($id);
        }
        echo json_encode($re_msg);
    }
    
}
