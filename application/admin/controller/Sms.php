<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Config;
use alisms\SendSms;
use think\Loader;

class Sms extends Base
{
	// 短信配置
    public function smsConfig()
    {        
        $where = array();
        if($this->userid!=1){
            $where[] = ['s.unitid','=',$this->unitid];
        }
    	$list = db("sms_config")->alias("s")
                ->field("s.*,u.unitname")
                ->leftJoin("unit u","u.UnitId=s.unitid")
                ->where($where)
                ->select();
    	$this->assign("list",$list);
        $this->assign("userid",$this->userid);
        return $this->fetch('smsconfig');
    }
    // 短信配额
    public function smsAdd(){    
        $id = input("id",0);
        $unit = db("unit")->field("UnitId,unitname")->where("UnitId",$id)->find();
        $this->assign("unit",$unit);
        return $this->fetch('smsAdd');
    }
    // 添加短信数量
    public function smsAddNum(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '增加失败';

        $unit_id    = input("unit_id",'');
        $number     = input("number",0);
        $note       = input("note",'');
        if(empty($unit_id)){
            $re_msg['msg'] = '请选择增加短信数量的单位';
            echo json_encode($re_msg);exit;
        }
        if(empty($number)){
            $re_msg['msg'] = '增加数量不能为0';
            echo json_encode($re_msg);exit;
        }
        $rs = db("sms_config")->where("unitid",$unit_id)->setInc("number",$number);
        if($rs){
            $re_msg['success']  = 1;
            $re_msg['msg']      = '增加成功';
            $sms = new \app\admin\model\SmsModel;
            $lrs = $sms->smsAddLog($this->userid,$unit_id,$number,$note);
        }
        echo json_encode($re_msg);
    }
    // 短信配置编辑
    public function smsEdit(){
    	$id = input('id',-1);
    	$where  = array();
        $uwh    = array();
        $swh    = array();
    	if($this->userid!=1){
            $uwh[]   = ['UnitId','=',$this->unitid];
            $swh[]   = ['unit_id','=',$this->unitid];
            $where[] = ['unitid','=',$this->unitid];
        }

        $where[] = ['id','=',$id];
        
        $result = db("sms_config")->where($where)->find();  
        $this->assign("result",$result);

        $list = db("sms_temp")->where($swh)->select();
        $this->assign("list",$list);

    	$unit = db("unit")->where($uwh)->select();
    	$this->assign("unit",$unit);

        $this->assign("userid",$this->userid);
    	return $this->fetch('smsEdit');
    }
    // 保存配置
    public function smsSave(){
    	$re_msg['success'] = 0;
        $re_msg['msg'] = '添加失败';

	    $data['unitid']      = input('unitid',0);
	    $data['status']         = input('status',0);
        $data['is_mark']        = input('is_mark',0);
        $data['mark_ok']        = input('mark_ok',0);
        $data['mark_cancel']    = input('mark_cancel',0);
	    $id = input('id',0);
        if(empty($data['unitid'])){
            $re_msg['msg'] = '请选择单位';
            echo json_encode($re_msg);exit;
        }

	    $flag = 0;
	    if($id > 0){
	    	$flag = db("sms_config")->where("id",$id)->update($data);
	    	if($flag!==false){
		    	$re_msg['success'] = 1;
	        	$re_msg['msg'] = '更新成功';
		    }else{
		    	$re_msg['msg'] = '更新失败';
		    }
	    }else{
            $data['number']      = input('number',0);
            $rs = db("sms_config")->where('unitid',$data['unitid'])->find();
            if(!$rs){
                $flag = db("sms_config")->insert($data);
            }	    	
		    if($flag){
		    	$re_msg['success'] = 2;
	        	$re_msg['msg'] = '添加成功';
                // 添加日志
                $sms = new \app\admin\model\SmsModel;
                $lrs = $sms->smsAddLog($this->userid,$data['unitid'],$data['number'],'初始化数据');
		    }else{
                $re_msg['msg'] = '该单位配置信息已经存在';
            }
	    }
	    echo json_encode($re_msg);
    }
    // 删除配置
    public function smsDel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $id = input("id",0);
        if($this->userid==1){            
            $rs = db("sms_config")->where("id",$id)->delete();
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '删除成功';
            }
        }else{
            $re_msg['msg'] = '您没有权限删除';
        }
        echo json_encode($re_msg);
    }
    // 短信日志
    public function smsAddLog(){
        $wh['unitid'] = $unitid = input("unitid","");
        $wh['content'] = $content = input("content","");
        $wh['stime']  = $stime  = input("stime","");
        $wh['etime']  = $etime  = input("etime","");

        $where = array();
        if(!empty($unitid)){
            $where[] = ['s.unit_id','=',$unitid]; 
        }       
        if(!empty($content)){
            $where[] = ['s.note','like','%'.$content.'%']; 
        }
        if(!empty($stime)){
            $where[] = ['s.add_time','>=',strtotime($stime)]; 
        }
        if(!empty($etime)){
            $where[] = ['s.add_time','<',strtotime("+1 day",strtotime($etime))]; 
        }
        $list = db("sms_add_log")->alias("s")
                ->field("s.*,u.unitname,m.FullName")
                ->leftJoin("unit u","u.UnitId=s.unit_id")
                ->leftJoin("manager m","m.UserId=s.manager_id")
                ->where($where)
                ->order("add_time desc")
                ->paginate(20);
        $page = $list->render();
        $this->assign("page",$page);
        $this->assign("list",$list);
        
        $unit = db("unit")->select();
        $this->assign("unit",$unit);
        $this->assign("wh",$wh);

        return $this->fetch('smsAddLog');
    }
    // 短信日志
    public function smsLog(){
    	$wh['mobile'] = $mobile = input("mobile",'');
    	$wh['unitid'] = $unitid = input("unitid","");
        $wh['content'] = $content = input("content","");
        $wh['stime']  = $stime  = input("stime","");
        $wh['etime']  = $etime  = input("etime","");

    	$where = array();
        $whs = array();
        if(!empty($unitid)){
            $where[] = ['s.unitid','=',$unitid]; 
        }            
        if($this->userid!=1){
            $whs[] = ['UnitId','=',$this->unitid];
            $where[] = ['s.unitid','=',$this->unitid]; 
        }
        
        if(!empty($mobile)){
            $where[] = ['s.mobile','=',$mobile]; 
        }
        if(!empty($content)){
            $where[] = ['s.content','like','%'.$content.'%']; 
        }
        if(!empty($stime)){
            $where[] = ['s.addtime','>=',strtotime($stime)]; 
        }
        if(!empty($etime)){
            $where[] = ['s.addtime','<',strtotime("+1 day",strtotime($etime))]; 
        }
    	$list = db("sms_log")->alias("s")
    			->field("s.*,u.unitname,m.FullName")
    			->leftJoin("unit u","u.UnitId=s.unitid")
                ->leftJoin("manager m","m.UserId=s.manager_id")
    			->where($where)
    			->order("addtime desc")
    			->paginate(20);
    	$page = $list->render();
 		$this->assign("page",$page);
    	$this->assign("list",$list);
        
    	$unit = db("unit")->where($whs)->select();
    	$this->assign("unit",$unit);
    	$this->assign("wh",$wh);

        return $this->fetch('smsLog');
    }
    // 批量发送短信
    public function sendBatchSms(){
        $wh    = array();
        $where = array();
        if($this->userid!=1){
            $wh[]       = ['UnitId','=',$this->unitid];
            $where[]    = ['unit_id','=',$this->unitid];
        }      
        $unit = db("unit")->where($wh)->select();
        $temp = db("sms_temp")->where($where)->select();
        $this->assign("unit",$unit);
        $this->assign("temp",$temp);
        return $this->fetch('sendBatchSms');
    }
    // 执行批量发送
    public function sendSms(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '发送失败';

        $data['mobile']  = input("mobile",'');
        $data['content'] = trim(input("content",""));
        $data['type']    = input("type",1);
        $data['group_id']= input("group_id",0);

        $validate =  new \app\admin\validate\Vsms;
        // $validate = Loader::validate('Vsms');
        if(!$validate->check($data)){
            $re_msg['msg'] = $validate->getError();
            echo json_encode($re_msg);
            exit;
        }
        if($this->userid!=1){
            $number = count(explode(',', trim($data['mobile'])));
            $config = db("sms_config")->where("unitid",$this->unitid)->find();
            if(($config['number']-$config['used'])<$number){
                $re_msg['msg'] = '短信条数不够,不能发送';
                echo json_encode($re_msg);exit;
            }
        }

        if(!empty($data['mobile']) && !empty($data['content'])){
            $mob = array();
            $temp = array();
            $sign = array();
            $where = array();
            if($data['type']==1){
                $where[] = ['mobile','in',$data['mobile']];
                $result = db("despeak")->field("mobile,name,despeakDate as date")->where($where)->order("name desc")->group("mobile")->select();
            }else{
                if($data['group_id']){
                    // 管理员
                    $where[] = ['BodNo','in',$data['mobile']];
                    $result = db("manager")->field("BodNo as mobile,FullName as name,InTime as date")->where($where)->group("mobile")->select();
                }else{
                    // 医生
                    $where[] = ['mobile','in',$data['mobile']];
                    $result = db("serque")->field("mobile,QueName as name,InTime as date")->where($where)->group("mobile")->select();
                }   
            }
            // $mobile = explode(',', trim($data['mobile']));
            // foreach (array_unique($mobile) as $key => $value) {
            //     if(preg_match("/^1[345678]{1}\d{9}$/",$value)){
            //         $mob[] = $value;
            //         $temp[] = $data['content'];
            //         $sign[] = $data['signs'];
            //     }
            // }
            $smsp =  new \app\admin\model\SmsModel;
            foreach ($result as $key => $value) {
                if(preg_match("/^1[345678]{1}\d{9}$/",$value['mobile'])){
                    $str = '';
                    $mob[]  = $value['mobile'];
                    $temp[] = $smsp->replaceContent($data['content'],$value);
                    $sign[] = '中科易达';
                }
            }

            if(count($mob)>0){     
                $templateParam  = $temp;        
                $templateCode   = 'SMS_137780004';
                $signName       = $sign;
                $fs = $this->writeSms($mob,$templateParam,$templateCode,'中科易达');
                $sms = new SendSms();
                $rs = $sms::sendBatchSms($mob,$signName,$templateCode,$templateParam);
                $rs = json_encode($rs);
                $result = json_decode($rs,1);
                if($result['Message'] == 'OK'){
                    $re_msg['success'] = 1;
                    $re_msg['msg'] = '发送成功';
                    $this->updateSms($mob,$templateCode);
                }else{
                    $re_msg['msg'] = $result['Message'];
                }
            }else{
                $re_msg['msg'] = '手机号码错误';
            }
        }
        echo json_encode($re_msg);
    }
    // 发送短信写到日志
    public function writeSms($mobile=array(),$content='',$temp='',$sign=''){
        $rs = 0;
        if(!empty($mobile)){
            $data = array();
            foreach ($mobile as $key => $value) {
                $data[$key]['mobile']  = $value;
                $data[$key]['temp']    = $temp;
                $data[$key]['sign']    = $sign;
                $data[$key]['content'] = $content[$key];
                $data[$key]['unitid']  = $this->unitid;
                $data[$key]['manager_id']  = $this->userid;
                $data[$key]['addtime'] = time();
            }
            $rs = db("sms_log")->insertAll($data);
        }
        return $rs;
    }
    // 更新短信状态
    public function updateSms($mobile=array(),$temp=''){
        $rs = 0;
        $wh = array();

        if(!empty($mobile)){            
            foreach ($mobile as $key => $value) {
                $wh['temp'] = $temp;
                $wh['mobile'] = $value;
                $wh['status'] = 0;
                $result = db("sms_log")->field("max(id) as id,unitid,status")->where($wh)->find();
                if($result['status']==0){                    
                    $rs = db("sms_log")->where("id",$result['id'])->update(['status'=>1]);
                    if($rs){
                        $rss = db('sms_config')->where('unitid', $result['unitid'])->setInc('used');
                    }
                }
            }
        }
        return $rs;
    }
    // 重新发送短信
    public function reSend(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '发送失败';

        $id = input("id",0);
        $where['id'] = $id;
        $where['status'] = 0;
        $rs = db("sms_log")->where($where)->find();

        if($rs){
            // 短信推送
            $csms = new \app\admin\model\SmsModel; 
            $number = $csms->checkSmsNum($rs['unitid']);
            if($number<=0){
                $re_msg['msg'] = '可用短信数量不够,无法发送';
                echo json_encode($re_msg);exit;
            }
            $sms = new SendSms();
            $templateParam = array("code"=>$rs['content']);
            $m = $sms::sendSms($rs['mobile'],$rs['sign'],$rs['temp'],$templateParam);
            $js = json_encode($m);
            $arr = json_decode($js,1);
            if($arr['Message']=='OK'){
                $rss = db('sms_config')->where('unitid', $rs['unitid'])->setInc('used');
                $rrs = db("sms_log")->where("id",$id)->update(['status'=>1,'error'=>'']);
            }else{
                db("sms_log")->where("id",$id)->update(['error'=>$arr['Message']]);
                $rrs = 0;
            }
            $re_msg['msg'] = $arr['Message'];
        }
        if($rrs){
            $re_msg['success'] = 1;
        }
        echo json_encode($re_msg);
    }
    // 单个发送短信
    public function send(){ 
        $sms = Loader::model('SmsModel');
        $mobile = input("mobile",'17095989213');       
        $sms->remind_sms(3,$mobile);
        exit;
        //获取对象，如果上面没有引入命名空间，可以这样实例化：$sms = new \alisms\SendSms()
        $sms = new SendSms();
        //$mobile为手机号
        $signName = '福州总院';
        $templateCode = 'SMS_137780004';

        //模板参数，自定义了随机数，你可以在这里保存在缓存或者cookie等设置有效期以便逻辑发送后用户使用后的逻辑处理
        $code = mt_rand();
        $templateParam = array("code"=>$code);
        $m = $sms::sendSms($mobile,$signName,$templateCode,$templateParam);
        
        // $m = array(
        //     "Message" => 'OK' ,
        //     "RequestId" => '5CEF9808-8C24-4700-B74D-2A9DAF0096A8' ,
        //     "BizId" => '496524036029272812^0',
        //     "Code" => 'OK' );
        //类中有说明，默认返回的数组格式，如果需要json，在自行修改类，或者在这里将$m转换后在输出
        $data = array();
        $rs = 0;
        $js = json_encode($m);
        $arr = json_decode($js,1);
        if($arr['Message']=="OK"){
            $data['mobile']  = $mobile;
            $data['temp']    = $templateCode;
            $data['sign']    = $signName;
            $data['content'] = $code;
            $data['unitid']  = $this->unitid;
            $data['addtime'] = time();
            $rs = db("sms_log")->insert($data);
        }
        echo $rs;
    }

    //添加模板
    public function template(){
        $id = input('id',-1);
        $where = array();
        $wh    = array();
        if($this->userid!=1){
            $wh[]       = ['UnitId','=',$this->unitid];
            $where[]    = ['unit_id','=',$this->unitid];
        }        
        $list = db("sms_temp")->alias("s")
                ->field("s.*,u.unitname,m.FullName")
                ->leftJoin("unit u","u.UnitId=s.unit_id")
                ->leftJoin("manager m","m.UserId=s.manager_id")
                ->where($where)
                ->order("unit_id asc")
                ->paginate(20);  
        $page = $list->render();
        $this->assign("page",$page);
        $this->assign("list",$list);

        $unit = db("unit")->where($wh)->select();
        $this->assign("unit",$unit);
        $this->assign("userid",$this->userid);
        return $this->fetch("smsTemp");
    }
    //编辑模板
    public function smsTemSave(){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '操作失败';
        $id                 = input("id",0);
        $data['unit_id']    = input("unit_id","");
        $data['title']      = input("title","");
        $data['content']    = input("content","");
        if(empty($data['unit_id'])){
            $re_msg['msg']     = '请选择单位';
            echo json_encode($re_msg);exit;
        }
        if(empty($data['title'])){
            $re_msg['msg']     = '名称不能为空';
            echo json_encode($re_msg);exit;
        }
        if(empty($data['content'])){
            $re_msg['msg']     = '内容不能为空';
            echo json_encode($re_msg);exit;
        }
        if($id){
            $rs = db("sms_temp")->where("id",$id)->update($data);
            if($rs!==false){
                $re_msg['success'] = 1;
                $re_msg['msg']     = '更新成功';
            }
        }else{
            $data['manager_id'] = $this->userid;
            $data['add_time'] = time();
            $rs = db("sms_temp")->insertGetId($data);
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg']     = '保存成功';
            }
        }
        echo json_encode($re_msg);
    }
    // 获取模板信息
    public function getTempInfo(){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '查询失败';
        $re_msg['data']    = '';
        $id = input("id",0);
        $where = array();
        $where[] = ['id','=',$id];
        if($this->userid!=1){
            $where[] = ['unit_id','=',$this->unitid];
        }
        $result = db("sms_temp")->where($where)->find();
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '查询成功';
            $re_msg['data']    = $result;
        }
        echo json_encode($re_msg);
    }
    // 删除模板
    public function templateDel(){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '删除失败';
        $id = input("id",0);
        $where = array();
        $where[] = ['id','=',$id];
        if($this->userid!=1){
            $where[] = ['unit_id','=',$this->unitid];
        }
        $rs = db("sms_temp")->where($where)->delete();
        if($rs){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '删除成功';
        }
        echo json_encode($re_msg);
    }
    // 查询列表
    public function getListMobile(){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '查询没有结果';
        $sign  = input("sign",1);
        $unit_id  = input("unit_id",0);
        $hall_id  = input("hall_id",0);
        if(empty($unit_id)){
            $re_msg['msg']     = '请选择单位';
            echo json_encode($re_msg);exit;
        }
        $where = array();
        if($sign==1){
            $stime = input("stime",0);
            $etime = input("etime",0);
            $where[] = ['d.unitId','=',$unit_id];
            if(!empty($hall_id)){
                $where[] = ['d.hallNo','=',$hall_id];
            }
            if(!empty($stime)){
                $where[] = ['d.addtime','>=',strtotime($stime)];
            }
            if(!empty($etime)){
                $where[] = ['d.addtime','<',strtotime("+1 day",strtotime($etime))];
            }
            $result = db("despeak")->alias("d")
                    ->field('d.despeak_id,d.mobile,d.name,h.HallName')
                    ->leftJoin("hall h","h.HallNo=d.hallNo")
                    ->group("mobile")
                    ->where($where)->select();

        }else{
            $group_id  = input("group_id",0);                
            if(!empty($group_id)){
                $where[] = ['m.unitid','=',$unit_id];
                if(!empty($group_id)){
                    $where[] = ['m.yygroup_id','=',$group_id];
                }
                $result = db("manager")->alias("m")
                        ->field('m.UserId,m.BodNo as mobile,m.FullName,y.DispName as HallName')
                        ->leftJoin("yygroup y","y.id=m.yygroup_id")
                        ->where($where)->select();
            }else{              
                $where[] = ['s.UnitId','=',$unit_id];  
                if(!empty($hall_id)){
                    $where[] = ['s.HallNo','=',$hall_id];
                }
                $result = db("serque")->alias("s")
                            ->field('s.QueId,s.mobile,s.QueName as FullName,h.HallName')
                            ->leftJoin("hall h","h.HallNo=s.HallNo")
                            ->where($where)->select();
            }
        }

        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '查询成功';
            $re_msg['data']     = $result;
        }
        echo json_encode($re_msg);

    }
    // 查询科室
    public function getHall(){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '查询没有结果';
        $id  = input("id",0);
        $where = array();
        $wheres = array();
        $where[] = ['UnitId','=',$id];
        $wheres[] = ['unitId','=',$id];
        if($this->userid!=1){
            if($this->unitid!=$id){
                $where[] = ['UnitId','=',-1];
                $wheres[] = ['unitId','=',-1];
            }
        }
        $result = db("hall")->where($where)->select();        
        $results = db("yygroup")->where($wheres)->select();
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '查询成功';
            $re_msg['data']     = $result;
            $re_msg['datas']     = $results;
        }
        echo json_encode($re_msg);
    }
}