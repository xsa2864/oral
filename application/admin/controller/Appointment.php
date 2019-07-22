<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\facade\Request;
use think\Db;
use think\facade\Session;
use PHPExcel_IOFactory;
use PHPExcel;
use think\facade\Env;

class Appointment extends Base
{
    public function defaults()
    {
        return $this->fetch('login/defaults');
    }
    public function listdata()
    {
        return $this->fetch('listdata');
    }
    // 获取下级用户id列表
    public function getNextUid() {
        $lists = '';
        $gwh[] = ['uid','=',$this->userid];
        $pid = db("auth_group_access")->where($gwh)->value("group_id");
        if($pid){  
            $lwh = array();
            $lwh[] = ['ag.pid','like','%|'.$pid.'|%'];
            $lwh[] = ['m.unitid','=',$this->unitid];

            $list = db("manager")->alias("m")
                    ->leftJoin("auth_group_access ga","ga.uid=m.UserId")
                    ->leftJoin("auth_group ag","ag.id=ga.group_id")
                    ->where($lwh)
                    ->value("group_concat(m.UserId) as lists");
        }
        if($list){
            $lists = explode(',', $list);
        }else{
            $lists = [0];
        }
        return $lists;
    }
    // 预约数据
    public function signdata(){
        $wh['idcards']  = $idcards    = input('idcards');
        $wh['mobile']   = $mobile     = input('mobile');
        $wh['hallname'] = $hallname   = input('hallname');
        $wh['quename']  = $quename    = input('quename');
        $wh['name']     = $name       = input('name','');
        $wh['stime']    = $stime      = input('stime',date("Y-m-d",time()));
        $wh['etime']    = $etime      = input('etime',date("Y-m-d",time()));
        $wh['status']   = $status     = input('status',1);
        $wh['remark']   = $remark     = input('remark','');

        $where = array();
        $hwh   = array();
        $swh   = array();
        if($this->userid!=1)
        {
            $where[] = ['d.unitId','=',$this->unitid];
            $hwh[]   = ['UnitId','=',$this->unitid];
            $swh[]   = ['UnitId','=',$this->unitid];
            if($this->user['yygroup_id']){                
                $lists = $this->getNextUid();
                $where[] = ['d.manager_id','in',$lists];            
            }
        }

        if(!empty($mobile)){
            $where[] = ['d.mobile','like','%'.$mobile.'%'];
        }
        if(!empty($idcards)){
            $where[] = ['d.idcard','like','%'.$idcards.'%'];
        }
        if(!empty($remark)){
            $where[] = ['d.remark','like','%'.$remark.'%'];
        }
        if(!empty($hallname)){
            $where[] = ['h.HallName','like','%'.trim($hallname).'%'];
        }
        if(!empty($quename)){
            $where[] = ['s.QueName','like','%'.trim($quename).'%'];
        }
        $stime = date("Y-m-d",strtotime($stime));
        $etime = date("Y-m-d",strtotime("+1 day",strtotime($etime)));

        if (!empty($stime) && !empty($etime)) {
            // $where[] = ['d.despeakTime','between time',[strtotime($stime),strtotime($etime)]];
            $where[] = ['d.despeakTime','>=',strtotime($stime)];
            $where[] = ['d.despeakTime','<',strtotime($etime)];
        }else if(!empty($stime)){
            $where[] = ['d.despeakTime','>=',strtotime($stime)];
        }else if (!empty($etime)) {
           $where[] = ['d.despeakTime','<',strtotime($etime)];
        } 
        if($status!=''){            
            if($status==3){
                $where[] = ['d.despeakTime','<',time()];
                $where[] = ['d.status','=',1];
            }else if($status==1){
                $where[] = ['d.despeakTime','>',time()];
                $where[] = ['d.status','=',$status];
            }else{
                $where[] = ['d.status','=',$status];
            }
        }
        
        $result = db("despeak")->alias("d")
                    ->field("d.*,h.HallName,s.QueName,mr.FullName,g.DispName")
                    ->leftJoin("hall h","h.HallNo=d.hallNo")
                    ->leftJoin("serque s","s.QueId=d.queId")
                    ->leftJoin("manager mr","mr.UserId=d.manager_id")
                    ->leftJoin("yygroup g","g.id=mr.yygroup_id")
                    ->order("d.addtime desc")
                    ->where($where)
                    ->paginate(20,false,[
                        'type'     => '\page\Page',
                        'var_page' => 'page',
                        'query'    => $wh,
                    ]);
        $page = $result->render();

        $this->assign("wh",$wh);
        $this->assign("page",$page);
        $this->assign("list",$result);
        $list_hall = db("hall")->where($hwh)->select();
        $this->assign("list_hall",$list_hall);
        $list_d = db("serque")->where($swh)->select();
        $this->assign("list_d",$list_d);
        return $this->fetch('signdata');
    }
    // 取消预约
    public function cancel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '取消失败';
        if (Request::instance()->isPost())
        {
            $id = input("id",0); 
            $data['status'] = 0;
            $result = db("despeak")->where("despeak_id",$id)->update($data);
            if($result){
                $re_msg['success'] = 1;
                $re_msg['msg'] = "取消成功";
            }
        }
        echo json_encode($re_msg);
    }
    // 查询数据
    public function getdata(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '查询失败';
        if (Request::instance()->isPost())
        {
            $result = $this->getDespeakData(input()); 
            if($result['success'] == 1){
                $re_msg['success'] = 1;
                $re_msg['msg'] = $result['msg'];
            }else{
                $re_msg['msg'] = $result['msg'];
            
            }
        }
        echo json_encode($re_msg);
    }
    //获取数据列表
    public function getDespeakData($arr=[]){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '未查询到数据';
        $idcards    = isset($arr['idcards'])?$arr['idcards']:'';
        $mobile     = isset($arr['bodnums'])?$arr['bodnums']:'';
        $hallname   = isset($arr['hallname'])?$arr['hallname']:'';
        $quename    = isset($arr['quename'])?$arr['quename']:'';
        $stime      = isset($arr['stime'])?$arr['stime']:'';
        $etime      = isset($arr['etime'])?$arr['etime']:'';
        $vagues     = isset($arr['vagues'])?$arr['vagues']:'';

        $where = array();
        if($this->userid!=1){
            $where[] = ['d.unitId','=',$this->unitid];
        }
        if(!empty($mobile)){
            $where[] = ['d.mobile','like','%'.$mobile.'%'];
        }
        if(!empty($idcards)){
            $where[] = ['d.idcard','like','%'.$idcards.'%'];
        }
        if(!empty($hallname)){
            $where[] = ['h.HallName','like','%'.trim($hallname).'%'];
        }
        if(!empty($quename)){
            $where[] = ['s.QueName','like','%'.trim($quename).'%'];
        }
        if(!empty($stime)){
            $where[] = ['d.despeakTime','>=',strtotime($stime)];
        }elseif (!empty($etime)) {
           $where[] = ['d.despeakTime','<',strtotime($etime)];
        }elseif (!empty($stime) && !empty($etime)) {
            $where[] = ['d.despeakTime','between time',[strtotime($stime),strtotime($etime)]];
        }

        $result = db("despeak")->alias("d")
                ->field("d.*,h.HallName,s.QueName,mr.FullName,g.DispName")
                ->leftJoin("hall h","h.HallNo=d.hallNo")
                ->leftJoin("serque s","s.QueId=d.queId")
                ->leftJoin("manager mr","mr.UserId=d.manager_id")
                ->leftJoin("yygroup g","g.id=mr.yygroup_id")
                ->order("d.addtime desc")
                ->where($where)->select();
        
        if($result){
            foreach ($result as $key => $value) {
                $s_name = '预约中';
                if($value['status']==1){
                    if($value['despeakTime']<time()){
                        $s_name = '已过期';
                    }
                }else{
                    $s_name = $value['status']==0?'取消预约':($value['status']==2?'已完成':'已过期');
                }
                $result[$key]['status'] = $s_name;

                $platform = '电话';
                if(empty($value['FullName'])){
                    if($value['platform']=='windows'){
                        $platform = '官网';
                    }else{
                        $platform = 'APP';
                    }
                }   
                $result[$key]['platform'] = $platform;
            }
            $re_msg['success'] = 1;
            $re_msg['msg'] = $result;
        }
        return $re_msg;
    }
    //  导出报表
    public function getExcel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '导出失败';
        $result = $this->getDespeakData(input());   
        if($result['success']==0){
            $re_msg['msg'] = '查询数据为空,导出失败';
            echo json_encode($re_msg);exit;
        }
        $path = $_SERVER['DOCUMENT_ROOT']; //找到当前脚本所在路径
        $PHPExcel = new PHPExcel(); //例化PHPExcel类，类似于在桌面上新建一个Excel表格
        $PHPSheet = $PHPExcel->getActiveSheet(); //获得当前活动sheet的操作对象
        $PHPSheet->setTitle('数据报表'); //给当前活动sheet设置名称
        // $PHPSheet->setCellValue('A1','姓名')->setCellValue('B1','分数');
        //给当前活动sheet填充数据，数据填充是按顺序一行一行填充的，假如想给A1留空，可以直接setCellValue(‘A1’,’’);
        // $PHPSheet->setCellValue('A2','张三')->setCellValue('B2','50');
        $arrHeader = array('序号','预约科室','预约医生','姓名','预约者身份证','预约者手机号','就诊日期','就诊时间','状态','预约组','预约人员','预约来源','备注');
        //填充表头信息
        $letter = explode(',',"A,B,C,D,E,F,G,H,I,J,K,L,M");
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $PHPSheet->setCellValue($letter[$i]."1",$arrHeader[$i]);
        };
        foreach ($result['msg'] as $key => $value) {
            $k = $key+2;
            $PHPSheet->setCellValue("A".$k,$value["despeak_id"]);
            $PHPSheet->setCellValue("B".$k,$value["hallName"]);
            $PHPSheet->setCellValue("C".$k,$value["queName"]);
            $PHPSheet->setCellValue("D".$k,$value["name"]);
            $PHPSheet->setCellValue("E".$k,$value["idcard"]);
            $PHPSheet->setCellValue("F".$k,$value["mobile"]);
            $PHPSheet->setCellValue("G".$k,$value["despeakDate"]);
            $PHPSheet->setCellValue("H".$k,$value["time_Part_S"]."-".$value["time_Part_O"]);
            $PHPSheet->setCellValue("I".$k,$value["status"]);
            $PHPSheet->setCellValue("J".$k,$value["DispName"]);
            $PHPSheet->setCellValue("K".$k,$value["FullName"]);
            $PHPSheet->setCellValue("L".$k,$value["platform"]);
            $PHPSheet->setCellValue("M".$k,$value["remark"]);
        }
        $PHPSheet->getColumnDimension('B')->setWidth(15);
        $PHPSheet->getColumnDimension('C')->setWidth(12);
        $PHPSheet->getColumnDimension('E')->setWidth(23);
        $PHPSheet->getColumnDimension('F')->setWidth(15);
        $PHPSheet->getColumnDimension('G')->setWidth(14);
        $PHPSheet->getColumnDimension('H')->setWidth(18);
        $PHPSheet->getColumnDimension('M')->setWidth(25);

        $PHPWriter = PHPExcel_IOFactory::createWriter($PHPExcel,'Excel2007');
        //按照指定格式生成Excel文件，‘Excel2007’表示生成2007版本的xlsx，‘Excel5’表示生成2003版本Excel文件
        //header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        //告诉浏览器输出07Excel文件
        //header('Content-Type:application/vnd.ms-excel');
        //告诉浏览器将要输出Excel03版本文件
        //header('Content-Disposition: attachment;filename="'.time().'.xlsx"');
        //告诉浏览器输出浏览器名称
        //header('Cache-Control: max-age=0');//禁止缓存
        // $PHPWriter->save("php://output");
        $url = "/excel/".time().".xlsx";
        $all_url = $path.$url;
        //表示在$path路径下面生成demo.xlsx文件
        $rs = $PHPWriter->save($all_url); 
        if(file_exists($all_url)){
            $re_msg['success'] = 1;
            $re_msg['msg'] = $url;
        }
        echo json_encode($re_msg);
    }
    // 预约记录
    public function registration(){
        
        $where = array();
        if($this->userid!=1){
            if($this->user['hallid']){
                $where['HallNo'] = $this->hallid;
            }
        }
        $where['EnableFlag'] = 1;
        $result = db("serque")->where($where)->select();
        $this->assign('list',$result);
        // 模板输出
        return $this->fetch('registration');
    }
    // 获取专家名字
    public function getName(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '查询失败';
        if (Request::instance()->isPost())
        {
            $id = input('id',0);
            $where = array();
            $where[] = ['que_id','like','%|'.$id.'|%'];
            $result = db("z_doctor")->where($where)->select();
            if($result){
                $re_msg['success'] = 1;
                $re_msg['msg'] = $result;
            }
        }
        echo json_encode($re_msg);
    }
    // 获取时间
    public function getTime(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '查询失败';

        if (Request::instance()->isPost())
        {
            // new logic 2019-01-09
            $que_id = input('hallno',0);    //队列ID
            $doctor_id = input('queid',0);  //医生ID
            $cla = new \app\admin\model\ClassTime;
            $data = $cla->checktime($doctor_id,$que_id);
            if($data['data']){
                $re_msg['success'] = 1;
                $re_msg['msg'] = $data;
            }
        }
        echo json_encode($re_msg);
    }
    // 获取预约时间
    public function getCheckTime(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '查询失败';
        $data = array();
        if (Request::instance()->isPost())
        {
            $que_id = input('hallno',0);    //队列ID
            $doctor_id = input('queid',0);  //医生ID
            $ndata      = input('ndata','');
            // 获取上班时间
            $cla = new \app\admin\model\ClassTime;
            $data = $cla->getCheckTimes($doctor_id,$que_id,$ndata);
            if($data){
                $re_msg['success'] = 1;
                $re_msg['msg'] = $data;
            }
        }
        echo json_encode($re_msg);
    }
    
    //添加预约信息
    public function addDespeak(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '预约失败';
        $data['mobile']     = input("mobile",0);
        $data['name']       = input("name",'');
        $data['idcard']     = input("idcard",0);
        $data['queId']      = input("que_id",0);
        $data['queName']    = input("que_name",'');
        $data['doctor_id']   = input("queid",0);
        $data['d_name']      = input("quename",'');
        $data['despeakDate'] = input("date1",0);
        $data['hallNo']      = $this->hallid;
        $time1 = input("time1",'');
        $arr = explode('-', $time1);
        $data['time_Part_S'] = $arr[0].':00';
        $data['time_Part_O'] = $arr[1].':00';
        $data['despeakTime'] = strtotime($data['despeakDate']." ".$data['time_Part_O']);
        $data['remark']      = input("remark",'');
        $data['inTime']      = date("Y-m-d",time());
        $data['addtime']     = time();
        $data['manager_id']  = $this->userid;
        $where = array(
            'idcard'    =>$data['idcard'],
            'hallNo'    =>$data['hallNo'],
            'despeakDate'=>$data['despeakDate'],
            );
        if(empty($data['idcard'])){
            $re_msg['msg'] = "身份证号不能为空！";
            echo json_encode($re_msg);exit;
        }
        if(empty($data['mobile'])){
            $re_msg['msg'] = "手机号不能为空！";
            echo json_encode($re_msg);exit;
        }
        if(empty($data['name'])){
            $re_msg['msg'] = "姓名不能为空！";
            echo json_encode($re_msg);exit;
        }
        $result = db("despeak")->where($where)->find();
        if(empty($result)){
            $data['platform'] = 'phone';
            $id = db("despeak")->insertGetId($data);
            if($id){
                $re_msg['success'] = 1;
                $re_msg['msg'] = db("despeak")->where('despeak_id',$id)->find();
            }
        }else{
            $re_msg['msg'] = '已经预约';
        }
        echo json_encode($re_msg);
    }
    // 医院单位
    public function listunit(){

        $where = array();
        if($this->userid!=1){
            $where["UnitId"] = $this->unitid;
        }
        $result = db("unit")->where($where)->paginate(20);

        $manage = db("manager")->where("group_id",1)->select();
        $page = $result->render();
        $this->assign("page",$page);
        $this->assign('list',$result);
        $this->assign('manage',$manage);
        // 模板输出
        return $this->fetch('listunit');
    }
    // 添加单位
    public function saveUnit(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '新增失败';
        if (Request::instance()->isPost()){
            $arr['unitname']    = input("unitname",'');
            $arr['dispname']    = input("dispname",'');
            $arr['u_code']      = input("u_code",'');
            $arr['api_code']    = input("api_code",'');
            $arr['AlternateField1'] = input("AlternateField1",'');
            $arr['EnableFlag']  = input("EnableFlag",'');
            $arr['UnitId']      = input("UnitId",'');
            if(empty($arr['unitname'])){
                $re_msg['msg'] = '请填写区域全称';
                echo json_encode($re_msg);exit;
            }         
            if(strlen($arr['u_code'])!=3){
                $re_msg['msg'] = '请填写3个字符唯一标识';
                echo json_encode($re_msg);exit;
            } 
            $uwh[] = ["u_code",'=',$arr['u_code']];
            $uwh[] = ["UnitId",'<>',$arr['UnitId']];
            $u_rs = db("unit")->where($uwh)->select();
            if($u_rs){
                $re_msg['msg'] = '唯一标识已经存在，请更换';
                echo json_encode($re_msg);exit;
            }
            if($arr['api_code']){                
                if(strlen($arr['api_code'])!=6){
                    $re_msg['msg'] = '请填写6个字符接口编码';
                    echo json_encode($re_msg);exit;
                }   
                $awh[] = ["api_code",'=',$arr['api_code']];
                $awh[] = ["UnitId",'<>',$arr['UnitId']];
                $a_rs = db("unit")->where($awh)->select();
                if($a_rs){
                    $re_msg['msg'] = '接口编码已经存在，请更换';
                    echo json_encode($re_msg);exit;
                }
            }
            $arr['InTime'] = date("Y-m-d H:i:s",time());
            if($arr['UnitId']>0){
                $id = $arr['UnitId'];
                unset($arr['UnitId']);
                $flag = db("unit")->where('UnitId',$id)->update($arr);
                if($flag!==false){
                    $re_msg['success'] = 1;
                    $re_msg['msg'] = '更新成功';
                }else{
                    $re_msg['msg'] = '更新失败';
                }
            }else if($this->userid==1){
                unset($arr['UnitId']);
                $flag = db("unit")->insert($arr);
                if($flag){
                    $re_msg['success'] = 2;
                    $re_msg['msg'] = '新增成功';
                }
            }else{
                $re_msg['msg'] = '没有权限添加';
            }     
        }
        echo json_encode($re_msg);
    }
    // 编辑单位
    public function editUnit(){
        $id = input('id',0);        
        $result = array();
        $result = db("unit")->where("UnitId",$id)->find();
        $this->assign('list',$result);
        // 模板输出
        return $this->fetch('editunit');
    }
    // 删除单位
    public function unitDel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $id = input("id",0);
        $result = db("unit")->where("UnitId",$id)->find();
        if($result){
            if($this->userid==1){                
                $flag = db("unit")->where("UnitId",$id)->delete();
                if($flag){
                    $re_msg['success'] = 1;
                    $re_msg['msg'] = '删除成功';
                    db("hall")->where("UnitId",$id)->delete();
                    db("serque")->where("UnitId",$id)->delete();
                }
            }else{
                $re_msg['msg'] = '没有权限删除';
            }
        }else{
            $re_msg['msg'] = '该数据已经不存在了';
        }
        echo json_encode($re_msg);
    }
    // 获取单位区域数据列表
    public function getRegion(){
        $re_msg['success'] = 0;
        $re_msg['msg']  = '获取失败';

        $unit_id = input("unit_id",0);

        $re_msg['title'] = db("unit")->where("UnitId",$unit_id)->value("unitname");
        $where[] = ['unit_id', '=', $unit_id];
        $list = db("unit_region")->where($where)->select();
        if($list){
            $re_msg['success'] = 1;
            $re_msg['msg']  = '获取成功';
            $re_msg['data'] = $list;
        }
        echo json_encode($re_msg);
    }
    // 获取单位区域数据
    public function getRegionInfo(){
        $re_msg['success'] = 0;
        $re_msg['msg']  = '获取失败';

        $id = input("id",0);
        $list = db("unit_region")->where("id",$id)->find();
        if($list){
            $re_msg['success'] = 1;
            $re_msg['msg']  = '获取成功';
            $re_msg['data'] = $list;
        }
        echo json_encode($re_msg);
    }
    // 添加单位区域
    public function regionSave(){
        $re_msg['success'] = 0;
        $re_msg['msg']  = '保存失败';
        $id                  = input("id",0);
        $data['unit_id']     = input("unit_id","");
        $data['region_name'] = input("region_name","");
        $data['region_code'] = input("region_code","");
        $data['region_note'] = input("region_note","");
        if(empty($data['region_name'])){
            $re_msg['msg']  = '区域名称不能为空';
            echo json_encode($re_msg);exit;
        }
        if(empty($data['region_code'])){
            $re_msg['msg']  = '区域编码不能为空';
            echo json_encode($re_msg);exit;
        }
        if($id){
            $rs = db("unit_region")->where("id",$id)->update($data);
            if($rs!==false){
                $re_msg['success'] = 1;
                $re_msg['msg']  = '更新成功';
            }
        }else{
            $rs = db("unit_region")->insertGetId($data);
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg']  = '添加成功';
            }
        }
        echo json_encode($re_msg);
    }
    // 删除区域
    public function regionDel(){
        $re_msg['success'] = 0;
        $re_msg['msg']  = '删除失败';
        $id                  = input("id",0);
        $rs = db("unit_region")->delete($id);
        if($rs){
            $data['region_id'] = 0;
            db("hall")->where("region_id",$id)->update($data);
            $re_msg['success'] = 1;
            $re_msg['msg']  = '删除成功';
        }
        echo json_encode($re_msg);
    }
    // 管理员列表
    public function listmanage(){
        $where = array();
        if($this->userid!=1){
            $where[] = ['s.UnitId','=',$this->unitid];
            if($this->hallid){
                $where[] = ['s.hallid','=',$this->hallid];
            }
            $lists = $this->getNextUid();
            if($lists){
                $where[] = ['s.UserId','in',$lists];
            }else{
                $where[] = ['s.UserId','<>',$this->userid];
            }
        }
        
        // $where[] = ['s.Types','=',1];
        $manage = db("manager")->alias('s')
                    ->field('s.*,g.title,u.unitname,h.HallName')
                    ->leftJoin('auth_group_access ga','ga.uid = s.UserId')
                    ->leftJoin('auth_group g','g.id = ga.group_id')
                    ->leftJoin('unit u','u.UnitId=s.unitid')
                    ->leftJoin('hall h','h.HallNo=s.hallid')
                    ->where($where)
                    ->order("s.UserId asc")
                    ->paginate(20);
        $page = $manage->render();

        $this->assign("page",$page);
        $this->assign('manage',$manage);
        // 模板输出
        return $this->fetch('listmanage');
    }
    // 管理员管理
    public function manageAdd(){
        $id = input('id',0);
        $type = input("type",0);
        $where = array();
        $gere = array();
        $list = db("manager")->where("UserId",$id)->find();
        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
            $gere['unitId'] = $this->unitid;
        }
        $where['EnableFlag'] = 1;
        $unit = db("unit")->where($where)->select();

        $group_id = db("auth_group_access")->where("uid",$id)->value("group_id");
        $glist = db("yygroup")->where($gere)->select();   

        if($this->userid==1){
            $gwh[] = ['id','>=',$this->user['group_id']];
        }else{
            $gwh[] = ['id','>',$this->user['group_id']];
        }
        
        $gwh[] = ['status','=',1];
        // $gwh[] = ['id','<=',3];        
        $group = db("auth_group")->where($gwh)->select();    

        $this->assign('glist',$glist);
        $this->assign('unit',$unit);        
        $this->assign('list',$list);
        $this->assign('group',$group);
        $this->assign('group_id',$group_id);
        return $this->fetch('manageadd');
    }
     // 预约人员管理
    public function manageAddc(){
        $id = input('id',0);
        $type = input("type",0);
        $where = array();
        $gere = array();
        $list = db("manager")->where("UserId",$id)->find();
        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
            $gere['unitId'] = $this->unitid;
        }
        $where['EnableFlag'] = 1;
        $unit = db("unit")->where($where)->select();

        if($this->userid==1){
            $gwh[] = ['id','>=',$this->user['group_id']];
        }else{
            $gwh[] = ['id','>',$this->user['group_id']];
        }
        $gwh[] = ['status','=',1];
        $gwh[] = ['id','>',3];
        $group = db("auth_group")->where($gwh)->select();    

        $group_id = db("auth_group_access")->where("uid",$id)->value("group_id");
        $glist = db("yygroup")->where($gere)->select();   

        $this->assign('glist',$glist);
        $this->assign('unit',$unit);        
        $this->assign('list',$list);
        $this->assign('group',$group);
        $this->assign('group_id',$group_id);
        return $this->fetch('manageaddc');
    }
    // 删除管理员
    public function manageDel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '删除失败';
        $id = input("id",'');
        if (Request::instance()->isPost()){
            if($id == 1){
                $re_msg['msg'] = '超级管理员不能删除';
                echo  json_encode($re_msg);exit;
            }
            $flag = db("manager")->delete($id);
            if($flag){
                $re_msg['success'] = 1;
                $re_msg['msg'] = '删除成功';
            }
        }
        echo json_encode($re_msg);
    }
    // 获取大厅数据
    public function gethall(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '获取失败';
        $id = input('id',0);
        $where = array();
        if($this->userid!=1){
            $where['UnitId'] = $this->unitid;
        }else if($id!=0){
            $where['UnitId'] = $id;
        }        
        $result = db("hall")->where($where)->select();
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg'] = $result;
        }
        
        echo json_encode($re_msg);
    }
    // 分组信息
    public function getGroup(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '获取失败';
        $id = input('id',0);
        $where = array();
        if($this->userid!=1){
            $where['unitId'] = $this->unitid;
        }else if($id!=0){
            $where['unitId'] = $id;
        }        
        $result = db("yygroup")->where($where)->select();
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg'] = $result;
        }
        
        echo json_encode($re_msg);
    }
    // 保存管理员信息
    public function manageSave(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '保存失败';

        if (Request::instance()->isPost()){     

            $data['UserName'] = input("UserName","");
            $data['FullName'] = input("FullName","");
            $data['password'] = input("password",'');
            $password1        = input("password1",'');
            $data['group_id'] = input("group_id",0);
            $data['Sex']    = input("Sex","");
            $data['BodNo']  = input("BodNo","");
            $data['unitid'] = input("unitid","");
            $data['hallid'] = input("hallid","");
            $group          = $data['group_id'];
            $data['yygroup_id'] = input("yygroup_id",0);
            $data['Types']  = $group>3?2:1;

            $id             = input("id",0);
            if(empty($data['UserName'])){
                $re_msg['msg'] = '用户名不能为空';
                echo json_encode($re_msg);exit;
            }
            if($data['password'] != $password1){
                $re_msg['msg'] = '两次输入的密码不一样';
                echo json_encode($re_msg);exit;
            }
            if(empty($group)){
                $re_msg['msg'] = '请选择角色';
                echo json_encode($re_msg);exit;
            }
            
            $validate =  new \app\admin\validate\Manage;
            if (!$validate->check($data)) {
                $re_msg['msg'] = $validate->getError();
                echo json_encode($re_msg);exit;
            }
            
            $uid = 0;
            $flag = 0;
            $data['password'] = md5($data['password']);
            if($id>0){            
                $uid = $id;
                $where['UserId'] = $id;

                $flag = db("manager")->where($where)->update($data);
                $flag2 = db("auth_group_access")->where("uid",$where['UserId'])->delete();
                if($flag!==false || $flag2){
                    $re_msg['success'] = 2;
                    $re_msg['msg'] = '更新成功';
                }else{
                    $re_msg['msg'] = '更新失败';
                }
            }else{

                $rs = db("manager")->where("UserName",input("UserName"))->find();

                if($rs){
                    $re_msg['msg'] = '用户名已存在,请更换';
                }else{                    
                    $data['InTime'] = date("Y-m-d H:i:s",time());
                    $flag = db("manager")->insertGetId($data);
                    $uid = $flag;
                    if($flag){
                        $re_msg['success'] = 1;
                        $re_msg['msg'] = '添加成功';
                    }
                }
            }
            if(!empty($group) && $flag!==false){
                $arr['uid'] = $uid;
                $arr['group_id'] = $group;                
                $rs = db("auth_group_access")->insert($arr);
            }            
        }
        echo json_encode($re_msg);
    }

    // 区域配置
    public function region(){
        $where = array();
        if($this->userid!=1){
            $where["UnitId"] = $this->unitid;
        }
        $result = db("unit")->where($where)->select();
        $this->assign('list',$result);
        // 模板输出
        return $this->fetch('region');
    }
    // 获取科室列表
    public function getHallList(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '获取失败';
        $id = input("id",0);
        $regs = db("unit_region")->where("id",$id)->find();
        $re_msg['title'] = $regs['region_name'];
        $where[] = ['region_id','in',[$id,0]];
        $where[] = ['UnitId','=',$regs['unit_id']];
        $list = db("hall")->where($where)->select();
        if($list){
            $re_msg['success']  = 1;
            $re_msg['msg']      = '获取成功';
            $re_msg['data']     = $list;
        }
        echo json_encode($re_msg);
    }
    // 更新区域科室分配
    public function regionAllot(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '更新失败';
        $hall_id    = input("hall_id",'');
        $region_id  = input("region_id","");
        $where[] = ['HallNo','in',explode(',', $hall_id)];
        $list = db("hall")->where($where)->update(['region_id'=>$region_id]);
        if($list){
            $re_msg['success']  = 1;
            $re_msg['msg']      = '更新成功';
        }
        echo json_encode($re_msg);
    }
}