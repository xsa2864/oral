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
        $xml = '<?xml version="1.0" encoding="UTF-8"?>
                <MESSAGE>
                    <BODY>
                        <META>
                          <TOPIC_ID>A204</TOPIC_ID>                        
                          <APP_ID>JQ_HIS</APP_ID>                        
                        </META>
                        <ROWS>
                            <ROW>
                                <HOSPITAL_ID>1</HOSPITAL_ID>
                                <CARD_NO>='.$idcard.'</CARD_NO>
                            </ROW>
                        </ROWS>
                    </BODY>
                </MESSAGE>';
        return $xml;
        $unitid = '';
        $api_ip = Db::name("config_fetch")->where("unitid",$unitid)->value("api_ip");
        $url = 'http://'.$_SERVER['SERVER_NAME'];
        $url = $url.'/UserInfo.wsdl';
        $soap = new \SoapClient($url);
        // $soap = new \app\api\model\UserInfo;
        // 调用函数     
        $result = $soap->doctorInfo($xml);
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
            if(trim($meta['STATUS'])=='1'){
                if(!isset($arr['BODY']['ROWS']['ROW'][0])){             
                    $result[] = $arr['BODY']['ROWS']['ROW'];  
                    $re_msg['msg'] = '一次只能取一个号';
                }else{
                    $result = $arr['BODY']['ROWS']['ROW'];  

                    $item['IDCARD']         = '350124198912282891';
                    $item['NAME']           = '小明';
                    $item['SEX_CODE']       = '1';
                    $item['PATIENT_ID']     = '123456';
                    $item['HALL_CODE']      = '1999';
                    $item['QUE_CODE']       = '2328';
                    $rs = $this->hisMkTicket($item);
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
        
        return json($re_msg);
    }

}