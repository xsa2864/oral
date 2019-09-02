<?php
namespace app\api\controller\v2;

use think\Controller;
use think\Request;
use think\DB;
use think\facade\Cookie;

class Fetch extends Controller
{
    /**
     * 显示版本信息
     *
     * @return \think\Response
     */
    public function index()
    {
        return json(['Hospital'=>'口腔医院','Version'=>'v2']);
    }
        // 获取所选区域信息
    public function halls()
    {
        $unit_id = cookie("unit_id")?cookie("unit_id"):0;
        $hall_id = cookie("hall_ids")?cookie("hall_ids"):0;

        $where[] = ['UnitId','=',$unit_id];
        if($hall_id){
            $halls = explode('|', $hall_id);
            if(!empty($halls)){
                $where[] = ['HallNo','in',$halls];
            }
        }
        $hwere[] = ['EnableFlag','=',1];        
        $hall = Db::name("hall")->field("*")->where($where)->select();
        $list = Db::name("unit")->field("UnitId,unitname")->where('EnableFlag',"1")->select();

        $ccd = new \app\api\model\CacheCode;
        $devices_ip = $ccd->getCode();  
        $data['ip']     = $devices_ip;        
        $data['host']   = Request()->ip();
        $data['hall']   = $hall;
        $data['list']   = $list;
        $data['hall_id']   = $hall_id;
        $data['select_unit_id'] = $unit_id;
        $data['select_hall_id'] = $hall_id;

        return json($data);
    }
    // 获取基础信息
    public function card()
    {
        $unit_id = cookie("unit_id")?cookie("unit_id"):0;
    	$hall_id = cookie("hall_id")?cookie("hall_id"):0;

        $where[] = ['UnitId','=',$unit_id];
        if($hall_id){
            $halls = explode(',', $hall_id);
            if(!empty($halls)){
                $where[] = ['HallNo','in',$hall_id];
            }
        }
        $hwere[] = ['EnableFlag','=',1];
        $queue = Db::name("serque")->field("QueId,QueName,ClassesTime")->where($where)->select();
        $rel = new \app\admin\model\ClassTime;
        $q_list = $rel->queueValid($queue);
        
        $hall = Db::name("hall")->field("*,GROUP_CONCAT(HallName) AS HallNames")->where($where)->find();
        $list = Db::name("unit")->field("UnitId,unitname")->where('EnableFlag',"1")->select();

        $ccd = new \app\api\model\CacheCode;
        $devices_ip = $ccd->getCode();  
        $data['ip']     = $devices_ip;        
        $data['host']   = Request()->ip();
        $data['hall']   = $hall;
        $data['queue']  = $q_list;
        $data['list']   = $list;
        $data['hall_id']   = $hall_id;

    	return json($data);
    }

    // 获取呼叫信息
    public function getUserInfo(){
    	$idcard = input("idcard",0);
        
        $result = $this->getResult($idcard);
        if(empty($result)){
            $re_msg['code'] = 201;
            $re_msg['msg']  = "无效卡号";
            $re_msg['data']  = "无效卡号";
            return json($re_msg);
        }
        $unitid = '';
        $api_ip = Db::name("config_fetch")->where("unitid",$unitid)->value("api_ip");
        $url = 'http://'.$_SERVER['SERVER_NAME'];
        $url = $url.'/UserInfo.wsdl';
        $soap = new \SoapClient($url);
        // $soap = new \app\api\model\UserInfo;
        // 调用函数     
        // $result = $soap->doctorInfo($xml);
        $re_msg = $this->hisTicket($result);
    	return json($re_msg);
    }
    // 获取区域iD
    public function getHall()
    {
        $unit_id = input('unit_id','');
        $unit = Db::name("hall")->field("HallNo,HallName")->where("UnitId",$unit_id)->select();
        return json($unit);
    }
    // 修改区域编号
    public function setHalls()
    {
        $unit_id = input("unit_id",0);
        $hall_id = input("hall_id",0);
        if(is_array($hall_id)){
            $halls = implode(',', $hall_id);
        }
        if(!empty($unit_id)){
            Cookie::forever('hall_ids',$halls);
            Cookie::forever('unit_id',$unit_id);
            return json("设置成功",200);
        }
        return json("请选择单位",201);
    }
    // 修改区域编号
    public function setHall()
    {
        $hall_id = input("hall_id",0);
        Cookie::forever('hall_id',$hall_id);
        return json("设置成功",200);
    }

    // 生成票号
    public function makeTicket()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "票号生成失败";
        $arrs['doctor_id']  = input("doctor_id",0);
        $que_id             = input("que_id",0);
        $idcard             = input("idcard",0);
        $hall_id            = cookie("hall_id")?cookie("hall_id"):0;
        $arrs['QueId']  = $que_id;
        $arrs['quick']  = 0;
        $flag = Db::name("hall")->where('HallNo',$hall_id)->value("card");
        if($flag){
            // 有用户信息生成票号
            if(is_numeric($idcard)){
                $where[] = ['CardId','=',$idcard];
                $item = Db::name("interface_waitman")->where($where)->find();        
            }else{
                $rule = "/{(.*?)}/i";
                preg_match_all($rule, $idcard,$result);
                if($result[1]){
                    $idcard         = $result[1][0];
                    $item['Name']   = $result[1][1];
                    $item['Sex']    = $result[1][2];
                    $item['Role']  = '';
                    $item['Origin']  = '';
                    $item['Brot']  = ''; 
                }
            }
            if($item){          
                $arrs['idcard'] = $idcard;
                $arrs['name']   = $item['Name'];
                $arrs['tips1']  = $item['Role'];
                $arrs['tips2']  = "取号机";
                $arrs['sex']    = $item['Sex'];
                $arrs['birth']  = $item['Brot'];               
                $rel = new \app\admin\model\Relations;
                $result = $rel->makeTicket($arrs);
                if($result['success']==1){
                    // 更新前端数据
                    $ip = Db::name("z_terminal")->where("hall_id",$hall_id)->column("ip");
                    if($ip){
                        $soc = new \app\admin\model\Socket;
                        foreach ($ip as $key => $value) {
                            $iprs = $soc->terminalSocke($value,['reload'=>1]);
                        }
                    }
                    $re_msg['code'] = 200;
                    $re_msg['msg']  = $result['msg'];
                    $prt = new \app\admin\model\PrintOut;
                    $text = $prt->makeText($result['data'],$hall_id);   
                    $re_msg['data'] = $text;
                }else{
                    $re_msg['msg']  = $result['msg'];
                }
            }else{
                $re_msg['msg']  = "未查询到用户信息";
            }
        }else{      
            // 无用户信息生成票号
            $arrs['QueId']  = $que_id;
            $arrs['quick']  = 0;
            $rel = new \app\admin\model\Relations;
            $result = $rel->makeTickets($arrs);
            if($result['success']==1){
                // 更新前端数据
                $ip = Db::name("z_terminal")->where("hall_id",$hall_id)->column("ip");
                if($ip){
                    $soc = new \app\admin\model\Socket;
                    foreach ($ip as $key => $value) {
                        $iprs = $soc->terminalSocke($value,['reload'=>1]);
                    }
                }
                $re_msg['code'] = 200;
                $re_msg['msg']  = $result['msg'];
                $prt = new \app\admin\model\PrintOut;
                $text = $prt->makeText($result['data'],$hall_id);   
                $re_msg['data'] = $text;
            }else{
                $re_msg['msg']  = $result['msg'];
            }
            $re_msg['time']    = time();
        }
        return json($re_msg);
    }
    public function getDoctor()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "没有查询到医生";
        $re_msg['data']  = "";
        $que_id         = input("que_id",0);
        $where[] = ['zc.que_id','=',$que_id];
        $where[] = ['d.id','>',0];
        $result = DB::name("z_doctor_class")->alias("zc")
                ->field("d.id,d.QueName,zc.class,zc.que_id")
                ->rightJoin("z_doctor d","d.id=zc.doctor_id")
                ->where($where)
                ->select();
        if($result){
            $re_msg['code'] = 200;
            $re_msg['msg']  = "信息获取成功";
            $re_msg['data'] = $result;
        }
        return json($re_msg);
    }
    /*
     * 预约取号
     */
    public function makeSure()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "没有有效预约信息";
        // $this->updateNewData();
        $idcard = input("search",0);
        if(strlen($idcard)==18){
            $where[] = ['d.idcard','=',$idcard];
        }else if(strlen($idcard)==11){
            $where[] = ['d.mobile','=',$idcard];
        }else{
            $where[] = ['d.despeak_id','=',0];
        }
        $where[] = ['d.status','=',1];
        $where[] = ['d.despeakTime','>=',strtotime(date("Y-m-d",time()))];

        $result = db("despeak")
                    ->alias('d')
                    ->field('d.*')
                    ->where($where)
                    ->order('d.despeakTime', 'asc')
                    ->select();
        if($result){
            $re_msg['code'] = 200;
            $re_msg['msg']  = "预约信息";
            $data = Array();
            foreach ($result as $key => $value) {
                $item = array();
                $rel = new \app\admin\model\Relations;
                $item = $rel->checkValid($value['despeak_id']);  
                $value['item'] = $item;
                $data[] = $value;
            }
            $re_msg['data']  = $data;
        }
        return json($re_msg);
    }

    // 打印票号
    public function produceTicket()
    {
        $re_msg['code'] = 201;
        $re_msg['msg']  = "票号生成失败";
        $re_msg['data'] = array();
        $ticket_id = input("ticket_id",'');
        if($ticket_id){
            $hall_id = cookie("hall_id");
            $n = 0;
            foreach ($ticket_id as $key => $value) {
                $item = array();
                $rel = new \app\admin\model\Relations;
                $item = $rel->orderTicket($value,$hall_id);  
                if($item['success']==1){
                    $re_msg['code'] = 200;
                    $re_msg['msg']  = $item['msg'];
                    $prt = new \app\admin\model\PrintOut;
                    $text = $prt->makeText($item['data'],$hall_id);     
                    $re_msg['data'][] = $text;
                    $n++;
                }else if($n==0){
                    $re_msg['msg']  = $item['msg'];
                }
            }
        }else{
            $re_msg['msg']  = "请选择要打印号票";
        }
        return json($re_msg);
    }

    /*
     * 取号
     *
     */
    public function hisTicket($xml='')
    {
        $re_msg['success']  = 0;
        $re_msg['msg']      = '没获取到有效信息';
        $re_msg['data']     = [];
        
        $arr    = json_decode($xml,1);
        $type   = 'json';
        if(empty($arr)){
            $xjson = new \app\api\model\UserInfo;
            $arr    = $xjson->xmlToArray($xml);
            $type   = 'xml';
        }
        if($arr){
            $meta = $arr['BODY']['META'];
            if(trim($meta['TOPIC_ID'])=='A202' && trim($meta['APP_ID'])=='ZKIT'){
                if(isset($arr['BODY']['ROWS']['ROW'][0])){             
                    $result[] = $arr['BODY']['ROWS']['ROW'];  
                    $re_msg['msg'] = '一次只能取一个号';
                }else{
                    $result = $arr['BODY']['ROWS']['ROW']; 
                  
                    $rs = $this->hisMkTicket($result);
                    if($rs['success']==1){
                        $re_msg['success']  = 1;
                        $re_msg['msg']      = '执行成功';
                        $re_msg['data']     = $rs['data'];
                    }
                }       
            }         

            $log_data['type']     = 4;
            $log_data['note']     = json_encode($result);
            $log_data['msg']      = $re_msg['msg'];
            $log_data['status']   = $re_msg['success'];
            $log = new \app\admin\model\OperationLog;
            $log->writeHisLog($log_data);
        }
       
        return $re_msg;
    }

    // 参数回传
    public function noticeHis($idcard=0)
    {
        if($idcard){
            $time = time()-3600;
            $where[] = ['idcard','=',$idcard];
            $where[] = ['add_time','=',$time];
            $rs = Db::name("z_ticket")->where($where)->find();
            $xml = '<?xml version="1.0" encoding="UTF-8"?>
                    <MESSAGE>
                      <BODY>
                        <META>
                        <TOPIC_ID>A205</TOPIC_ID>
                        <APP_ID>ZKIT</APP_ID>                      
                        </META>
                        <ROWS>
                           <ROW>
                            <HOSPITAL_ID></HOSPITAL_ID>
                            <CARD_NO>'.$rs['idcard'].'</CARD_NO>
                            <ORIGINAL_ID>'.$rs['PATIENT_ID'].'</ORIGINAL_ID>
                            <PREFIX>'.$rs['prefix'].'</PREFIX>
                            <QUE_NUM>'.$rs['code'].'</QUE_NUM>
                          </ROW>
                        </ROWS>
                      </BODY>
                    </MESSAGE>';
            // $url = 'http://'.$_SERVER['SERVER_NAME'];
            // $url = $url.'/UserInfo.wsdl';
            // $soap = new \SoapClient($url);
            // $result = $soap->doctorInfo($xml);
        }
    }
    // his接口票号
    public function hisMkTicket($item=[])
    {
        $re_msg['success'] = 0;

        $arrs['idcard']         = $item['IDCARD'];
        $arrs['name']           = $item['NAME'];
        $arrs['platform']       = 'his';
        $arrs['tips1']          = '';
        $arrs['tips2']          = "his接口";
        $arrs['sex']            = $item['SEX_CODE'];
        $arrs['original_id']    = $item['PATIENT_ID'];       
        $wh[] = ['SerInterface','=',$item['HALL_CODE']];
        $hall_id = Db::name("hall")->where($wh)->value("HallNo");        
        $qw[] = ['InterfaceID','=',$item['QUE_CODE']];
        $que_id = Db::name("serque")->where($qw)->value("QueId");
        $arrs['QueId']          = $que_id;
        // 判断是否去过号
        // $has = Db::name("z_ticket")->where("original_id",$item['PATIENT_ID'])->find();
        // if($has){
        //     $re_msg['msg']  = '已经取过号';
        //     return json($re_msg);
        // }
        $rel = new \app\admin\model\Relations;
        $result = $rel->makeTicket($arrs);
        if($result['success']==1){           
            $re_msg['success'] = 1;
            $re_msg['msg']  = $result['msg'];
            $prt = new \app\admin\model\PrintOut;
            $text = $prt->makeText($result['data'],$hall_id);   
            $re_msg['data'] = $text;
        }else{
            $re_msg['msg']  = $result['msg'];
        }
        
        return $re_msg;
    }

    public function getResult($idcard='')
    {
        $str1 = '<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                  <BODY>
                    <META>
                        <TOPIC_ID>A202</TOPIC_ID>
                        <APP_ID>ZKIT</APP_ID>                      
                    </META>
                <ROWS>
                       <ROW>
                        <HOSPITAL_ID>086591000</HOSPITAL_ID>
                        <HOSPITAL_NAME>1</HOSPITAL_NAME>
                        <HALL_CODE>086591000A1980000126</HALL_CODE>
                        <HALL_NAME>1</HALL_NAME>
                        <QUE_CODE>1178</QUE_CODE>
                        <QUE_NAME>郑嘉</QUE_NAME>
                        <DOCTOR_CODE>1178</DOCTOR_CODE>
                        <DOCTOR_NAME>郑嘉</DOCTOR_NAME>
                        <PATIENT_ID>11111111</PATIENT_ID>
                        <PATIENT_TYPE_NO>11111111</PATIENT_TYPE_NO>
                        <NAME>张三</NAME>
                        <TEL>17059555855</TEL>
                        <IDCARD>3522251999122828'.mt_rand(10,99).'</IDCARD>
                        <PREFIX></PREFIX>
                        <QUE_NUM></QUE_NUM>
                        <SEX_CODE>1</SEX_CODE>
                        <SEX_NAME></SEX_NAME>
                        <BIRTHDAY>1</BIRTHDAY>
                        <ORDERS>0</ORDERS>
                        <SIGN_IN>1</SIGN_IN>
                        <OPERATION_STATUS>1</OPERATION_STATUS>
                        <FETCH_STATUS>1</FETCH_STATUS>
                        <SD_DATE>2019-08-221</SD_DATE>
                        <QH_DATE>2019-08-221 02:50:43</QH_DATE>
                        <START_DATE>2019-08-221 02:50:43</START_DATE>
                        <END_DATE>2019-08-221 02:50:43</END_DATE>
                      </ROW>
                    </ROWS>
                  </BODY>
                </MESSAGE>';
        $str2 = '<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                  <BODY>
                    <META>
                        <TOPIC_ID>A202</TOPIC_ID>
                        <APP_ID>ZKIT</APP_ID>                      
                    </META>
                <ROWS>
                       <ROW>
                        <HOSPITAL_ID>086591000</HOSPITAL_ID>
                        <HOSPITAL_NAME>1</HOSPITAL_NAME>
                        <HALL_CODE>086591000A1980000126</HALL_CODE>
                        <HALL_NAME>1</HALL_NAME>
                        <QUE_CODE>1177</QUE_CODE>
                        <QUE_NAME>高广林</QUE_NAME>
                        <DOCTOR_CODE>1177</DOCTOR_CODE>
                        <DOCTOR_NAME>高广林</DOCTOR_NAME>
                        <PATIENT_ID>11111111</PATIENT_ID>
                        <PATIENT_TYPE_NO>11111111</PATIENT_TYPE_NO>
                        <NAME>李四</NAME>
                        <TEL>17059555822</TEL>
                        <IDCARD>3522251999122828'.mt_rand(10,99).'</IDCARD>
                        <PREFIX></PREFIX>
                        <QUE_NUM></QUE_NUM>
                        <SEX_CODE>1</SEX_CODE>
                        <SEX_NAME></SEX_NAME>
                        <BIRTHDAY>1</BIRTHDAY>
                        <ORDERS>0</ORDERS>
                        <SIGN_IN>1</SIGN_IN>
                        <OPERATION_STATUS>1</OPERATION_STATUS>
                        <FETCH_STATUS>1</FETCH_STATUS>
                        <SD_DATE>2019-08-221</SD_DATE>
                        <QH_DATE>2019-08-221 02:50:43</QH_DATE>
                        <START_DATE>2019-08-221 02:50:43</START_DATE>
                        <END_DATE>2019-08-221 02:50:43</END_DATE>
                      </ROW>
      
                    </ROWS>
                  </BODY>
                </MESSAGE>';
        $str3 = '<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                  <BODY>
                    <META>
                        <TOPIC_ID>A202</TOPIC_ID>
                        <APP_ID>ZKIT</APP_ID>                      
                    </META>
                <ROWS>
                       <ROW>
                        <HOSPITAL_ID>086591000</HOSPITAL_ID>
                        <HOSPITAL_NAME>1</HOSPITAL_NAME>
                        <HALL_CODE>086591000A1980000126</HALL_CODE>
                        <HALL_NAME>1</HALL_NAME>
                        <QUE_CODE>1176</QUE_CODE>
                        <QUE_NAME>李璐</QUE_NAME>
                        <DOCTOR_CODE>1176</DOCTOR_CODE>
                        <DOCTOR_NAME>李璐</DOCTOR_NAME>
                        <PATIENT_ID>11111111</PATIENT_ID>
                        <PATIENT_TYPE_NO>11111111</PATIENT_TYPE_NO>
                        <NAME>小陈</NAME>
                        <TEL>17059555811</TEL>
                        <IDCARD>3522251999122828'.mt_rand(10,99).'</IDCARD>
                        <PREFIX></PREFIX>
                        <QUE_NUM></QUE_NUM>
                        <SEX_CODE>1</SEX_CODE>
                        <SEX_NAME></SEX_NAME>
                        <BIRTHDAY>1</BIRTHDAY>
                        <ORDERS>0</ORDERS>
                        <SIGN_IN>1</SIGN_IN>
                        <OPERATION_STATUS>1</OPERATION_STATUS>
                        <FETCH_STATUS>1</FETCH_STATUS>
                        <SD_DATE>2019-08-221</SD_DATE>
                        <QH_DATE>2019-08-221 02:50:43</QH_DATE>
                        <START_DATE>2019-08-221 02:50:43</START_DATE>
                        <END_DATE>2019-08-221 02:50:43</END_DATE>
                      </ROW>
                    </ROWS>
                  </BODY>
                </MESSAGE>';
        $str4 = '<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                  <BODY>
                    <META>
                        <TOPIC_ID>A202</TOPIC_ID>
                        <APP_ID>ZKIT</APP_ID>                      
                    </META>
                <ROWS>
                       <ROW>
                        <HOSPITAL_ID>086591000</HOSPITAL_ID>
                        <HOSPITAL_NAME>1</HOSPITAL_NAME>
                        <HALL_CODE>086591000A1980000126</HALL_CODE>
                        <HALL_NAME>1</HALL_NAME>
                        <QUE_CODE>1176</QUE_CODE>
                        <QUE_NAME>李璐</QUE_NAME>
                        <DOCTOR_CODE>1176</DOCTOR_CODE>
                        <DOCTOR_NAME>李璐</DOCTOR_NAME>
                        <PATIENT_ID>11111112</PATIENT_ID>
                        <PATIENT_TYPE_NO>11111112</PATIENT_TYPE_NO>
                        <NAME>老刘</NAME>
                        <TEL>17059555812</TEL>
                        <IDCARD>3522251999122828'.mt_rand(10,99).'</IDCARD>
                        <PREFIX></PREFIX>
                        <QUE_NUM></QUE_NUM>
                        <SEX_CODE>1</SEX_CODE>
                        <SEX_NAME></SEX_NAME>
                        <BIRTHDAY>1</BIRTHDAY>
                        <ORDERS>0</ORDERS>
                        <SIGN_IN>1</SIGN_IN>
                        <OPERATION_STATUS>1</OPERATION_STATUS>
                        <FETCH_STATUS>1</FETCH_STATUS>
                        <SD_DATE>2019-08-221</SD_DATE>
                        <QH_DATE>2019-08-221 02:50:43</QH_DATE>
                        <START_DATE>2019-08-221 02:50:43</START_DATE>
                        <END_DATE>2019-08-221 02:50:43</END_DATE>
                      </ROW>
                    </ROWS>
                  </BODY>
                </MESSAGE>';
        $arr = [
            '1111' => $str1,
            '2222' => $str2,
            '3333' => $str3,
            '4444' => $str4,
        ];
        return isset($arr[$idcard])?$arr[$idcard]:[];
    }

}