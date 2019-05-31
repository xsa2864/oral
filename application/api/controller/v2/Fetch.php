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

    // 获取基础信息
    public function card()
    {
    	$hall_id = cookie("hall_id")?cookie("hall_id"):0;
        $where[] = ['HallNo','=',$hall_id];
        $hwere[] = ['EnableFlag','=',1];
        $queue = Db::name("serque")->field("QueId,QueName,ClassesTime")->where($where)->select();
        $rel = new \app\admin\model\ClassTime;
        $q_list = $rel->queueValid($queue);
        
        $hall = Db::name("hall")->where('HallNo',$hall_id)->find();
        $list = Db::name("hall")->field("HallNo,HallName")->where('EnableFlag',"1")->select();

        $ccd = new \app\api\model\CacheCode;
        $devices_ip = $ccd->getCode();  
        $data['ip']     = $devices_ip;
        $data['hall']   = $hall;
        $data['queue']  = $q_list;
        $data['list']   = $list;
        $data['hall_id']   = $hall_id;

    	return json($data);
    }

    // 获取呼叫信息
    public function getUserInfo(){
    	$idcard = input("idcard",0);
        $where[] = ['CardId','=',$idcard];
        $item = Db::name("interface_waitman")->where($where)->find();        
    	return json($item);
    }

    // 修改区域编号
    public function setHall()
    {
        $hall_id = input("hall_id",0);
        if(!empty($hall_id)){
            Cookie::forever('hall_id',$hall_id);
            return json("设置成功",200);
        }
        return json("请填写区域编号",201);
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
            $where[] = ['CardId','=',$idcard];
            $item = Db::name("interface_waitman")->where($where)->find();
            if($item){          
                $arrs['idcard'] = $idcard;
                $arrs['name']   = $item['Name'];
                $arrs['tips1']  = $item['Role'];
                $arrs['tips2']  = $item['Origin'];
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
        $where[] = ['d.despeakTime','>',strtotime(date("Y-m-d",time()))];
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
}