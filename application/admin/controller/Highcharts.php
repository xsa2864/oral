<?php
namespace app\admin\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;
use think\facade\Env;
use PHPExcel_IOFactory;
use PHPExcel;

class Highcharts extends Base
{
	// 团队业绩
    public function team(){    	
        $wh = array();
        if($this->userid!=1){
            $wh[] = ['UnitId','=',$this->unitid];
        }
        $unit = db("unit")->where($wh)->select();
        $this->assign("unit",$unit);
    	return $this->fetch('team');
    }
    //获取统计数据
    public function getDataTeam($flag=true){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '查询失败';
        $re_msg['data']    = array();
        $where = array();        
        $unit_id = input("unit_id",0);
        $stime = input("stime",0);
        $etime = input("etime",0);
        $type  = input("type",1);
        $title = db("unit")->where("UnitId",$unit_id)->value("unitname");
        $re_msg['title'] = $title." ".$stime." ~ ".$etime;
        $re_msg['time'] = $stime." ~ ".$etime;
        $where[] = ['m.unitid','=',$unit_id];
        $where[] = ['d.add_time','>=',strtotime($stime)];
        $where[] = ['d.add_time','<',strtotime("+1 day",strtotime($etime))];
        if($type==1){            
            $where[] = ['y.DispName','>',0];
            $result = db("despeak")
                    ->alias("d")
                    ->field("y.DispName,count(*) as num")
                    ->leftJoin("manager m","m.UserId=d.manager_id")
                    ->leftJoin("yygroup y","y.id=m.yygroup_id")
                    ->where($where)
                    ->group("yygroup_id")
                    ->select();
        }else{
            $result = db("despeak")->alias("d")
                    ->field("m.UserName,m.FullName as DispName,count(*) as num")
                    ->join("manager m","m.UserId=d.manager_id")
                    ->where($where)
                    ->group("d.manager_id")
                    ->select();
        }
        if($result){
            $re_msg['success'] = 1;
            $re_msg['msg']     = '查询成功';
            $re_msg['type']    = $type;
            $re_msg['data']    = $result;
        }
        if($flag){
            echo json_encode($re_msg);
        }else{
            return $re_msg;
        }
    }
    // 预约来源统计
    public function platform(){        
        $wh = array();
        if($this->userid!=1){
            $wh[] = ['UnitId','=',$this->unitid];
        }
        $unit = db("unit")->where($wh)->select();
        $this->assign("unit",$unit);
        return $this->fetch('platform');
    }
    //获取预约来源统计数据
    public function getDataPlat($flag=true){
        $re_msg['success'] = 0;
        $re_msg['msg']     = '查询失败';
        $re_msg['data']    = array();
        $where = array();        
        $hall_id  = input("hall_id",0);
        $day_unit = input("day_unit",1);
        $stime    = input("stime",0);
        $etime    = input("etime",0);

        $title = db("hall")->where("HallNo",$hall_id)->value("HallName");
        $re_msg['title'] = $title." ".$stime." ~ ".$etime;

        $where[] = ['d.hall_id','=',$hall_id];
        $where[] = ['d.add_time','>=',strtotime($stime)];
        $where[] = ['d.add_time','<',strtotime("+1 day",strtotime($etime))];

        $day_num  = (strtotime($etime)-strtotime($stime))/3600/24+1;        
        if($stime>$etime){
            $re_msg['success'] = -1;
            $re_msg['msg']     = '开始时间不能大于结束时间';
            echo json_encode($re_msg);
            exit;
        }
        if($day_unit==1){            
            if($day_num>31){
                $re_msg['success'] = -1;
                $re_msg['msg']     = '查询时间范围不能超过31天';
                echo json_encode($re_msg);
                exit;
            }
            $result = db("z_ticket")
                    ->alias("d")
                    ->field("FROM_UNIXTIME(d.add_time,'%Y-%m-%d') as dtime,d.status, COUNT(*) as num")
                    ->where($where)
                    ->group("FROM_UNIXTIME(d.add_time,'%Y-%m-%d'),d.status")
                    ->select();
        }else{
            $s_date = date("Y-m",strtotime("+12 month",strtotime($stime)));
            $e_date = date("Y-m",strtotime($etime));
            if($s_date<$e_date){
                $re_msg['success'] = -1;
                $re_msg['msg']     = '查询时间范围不能超过一年';
                echo json_encode($re_msg);
                exit;
            }
            $result = db("z_ticket")
                    ->alias("d")
                    ->field("FROM_UNIXTIME(d.add_time,'%Y-%m') as dtime,d.status, COUNT(*) as num")
                    ->where($where)
                    ->group("FROM_UNIXTIME(d.add_time,'%Y-%m'),d.status")
                    ->select();
            $day_num = $day_num/30;
        }
        if($result){            
            // 平台根据时间归整数量
            $windows    = array();
            $android    = array();
            $other    = array();
            $phone  = array();
            foreach ($result as $key => $value) {
                if($value['status']=='0'){
                    $windows[$value['dtime']]   = $value['num'];
                }
                if($value['status']=='1'){
                    $android[$value['dtime']]   = $value['num'];
                }
                if($value['status']=='2'){
                    $other[$value['dtime']]     = $value['num'];
                }
                if($value['status']=='5'){
                    $phone[$value['dtime']]     = $value['num'];
                }
            }
        }

        $win_str = '';
        $and_str = '';
        $ios_str = '';
        $pho_str = '';
        $show_date = '';

        for ($i=0; $i < $day_num; $i++) { 
            if($day_unit==1){     
                $da = date("Y-m-d",strtotime("+$i day",strtotime($stime)));
            }else{
                $da = date("Y-m",strtotime("+$i month",strtotime($stime)));
            }
            $win_str .= $win_str != '' ?',':'';
            $and_str .= $and_str != '' ?',':'';
            $ios_str .= $ios_str != '' ?',':'';
            $pho_str .= $pho_str != '' ?',':'';
            $show_date .= $show_date != '' ?',':'';
            if($day_unit==1){ 
                $show_date .= date("d",strtotime("+$i day",strtotime($stime)));
            }else{
                $show_date .= date("Y-m",strtotime("+$i month",strtotime($stime)));
            }
            if(isset($windows["$da"])){
                $win_str .= $windows["$da"];
            }else{
                $win_str .= 0;
            }
            if(isset($android[$da])){
                $and_str .= $android[$da];
            }else{
                $and_str .= 0;
            }

            if(isset($IOS[$da])){
                $ios_str .= $IOS[$da];
            }else{
                $ios_str .= 0;
            }

            if(isset($phone[$da])){
                $pho_str .= $phone[$da];
            }else{
                $pho_str .= 0;
            }
        }
        $data = array();
        $data['list']['windows'] = explode(',', $win_str);
        $data['list']['android'] = explode(',', $and_str);
        $data['list']['IOS']     = explode(',', $ios_str);
        $data['list']['phone']   = explode(',', $pho_str);
        $data['show_date']       = explode(',', $show_date);

        $re_msg['success'] = 1;
        $re_msg['msg']     = '查询成功';
        $re_msg['data']    = $data;
        if($flag){
            echo json_encode($re_msg);
        }else{
            return $re_msg;
        }
    }

    //  导出报表
    public function getExcel(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '导出失败';
        $result = $this->getDataTeam(false);  

        $path = $_SERVER['DOCUMENT_ROOT']; //找到当前脚本所在路径
        $PHPExcel = new PHPExcel(); //例化PHPExcel类，类似于在桌面上新建一个Excel表格
        $PHPSheet = $PHPExcel->getActiveSheet(); //获得当前活动sheet的操作对象
        $PHPSheet->setTitle($result['title']); //给当前活动sheet设置名称
        // $PHPSheet->setCellValue('A1','姓名')->setCellValue('B1','分数');
        //给当前活动sheet填充数据，数据填充是按顺序一行一行填充的，假如想给A1留空，可以直接setCellValue(‘A1’,’’);
        // $PHPSheet->setCellValue('A2','张三')->setCellValue('B2','50');
        $arrHeader = array('单位','名称/用户名','数量');
        //填充表头信息
        $letter = explode(',',"A,B,C,D,E,F,G,H,I,J,K,L,M");
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $PHPSheet->setCellValue($letter[$i]."1",$arrHeader[$i]);
        };
        foreach ($result['data'] as $key => $value) {
            $k = $key+2;
            $PHPSheet->setCellValue("A".$k,$result['title']);
            $PHPSheet->setCellValue("B".$k,$value["DispName"]);
            $PHPSheet->setCellValue("C".$k,$value["num"]);
        }
        $PHPSheet->getColumnDimension('A')->setWidth(35);
        $PHPSheet->getColumnDimension('B')->setWidth(15);
        $PHPSheet->getColumnDimension('C')->setWidth(12);

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

    //  导出报表
    public function getExcelPlat(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '导出失败';
        $result = $this->getDataPlat(false);  


        $path = $_SERVER['DOCUMENT_ROOT']; //找到当前脚本所在路径
        $PHPExcel = new PHPExcel(); //例化PHPExcel类，类似于在桌面上新建一个Excel表格
        $PHPSheet = $PHPExcel->getActiveSheet(); //获得当前活动sheet的操作对象
        $PHPSheet->setTitle($result['title']); //给当前活动sheet设置名称
        // $PHPSheet->setCellValue('A1','姓名')->setCellValue('B1','分数');
        //给当前活动sheet填充数据，数据填充是按顺序一行一行填充的，假如想给A1留空，可以直接setCellValue(‘A1’,’’);
        // $PHPSheet->setCellValue('A2','张三')->setCellValue('B2','50');
        $arrHeader = $result['data']['show_date'];
        array_unshift($arrHeader,$result['title']);
        
        //填充表头信息
        $letter = explode(',',"A,B,C,D,E,F,G,H,I,J,K,L,M,N,O,P,Q,R,S,T,U,V,W,X,Y,Z,AA,AB,AC,AD,AE,AF");
        $lenth =  count($arrHeader);
        for($i = 0;$i < $lenth;$i++) {
            $PHPSheet->setCellValue($letter[$i]."1",$arrHeader[$i]);
        };

        $k = 1;
        foreach ($result['data']['list'] as $key => $value) {
            $k++;
            foreach ($value as $keys => $val) {            
                if($keys==0){
                    $name = '其它';
                    if($key=='android'){
                        $name = 'APP';
                    }else if($key=='windows'){
                        $name = '官网';
                    }else if($key=='phone'){
                        $name = '电话';
                    }
                    $PHPSheet->setCellValue($letter['0'].$k,$name);
                }
                $PHPSheet->setCellValue($letter[($keys+1)].$k,$val);
            }
        }

        $PHPSheet->getColumnDimension('A')->setWidth(35);

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

    // 数量
    public function line()
    {
        $wh = array();
        if($this->userid!=1){
            $wh[] = ['HallNo','=',$this->hallid];
        }
        $hall = db("hall")->where($wh)->select();
        $this->assign("hall",$hall);
        return $this->fetch("line");
    }
}