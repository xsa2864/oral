<?php
namespace app\api\controller\a1;

use think\Controller;
use think\facade\Request;
use think\DB;
use think\facade\Cookie;

class Order extends Controller
{
	// 获取日期
	public function getDate()
	{
		$doctor_id 	= input("doctor_id",0);
		$que_id 	= input("que_id",0);
		$data = array();
		if($doctor_id){			
			$cla = new \app\admin\model\ClassTime;
	        $data['list'] = $cla->checktime($doctor_id,$que_id);
		}
        $data['if_name'] = DB::name("config_fetch")->where("unitid",$this->unitid)->value("if_name");
        $where[] = ['c.doctor_id','=',$doctor_id];
        $where[] = ['c.que_id','=',$que_id];
		$data['path'] = DB::name("z_doctor_class")->alias("c")
						->field("s.HallNo as hall_id,h.HallName as hname,s.QueName as qname,t.QueName as dname")
						->leftJoin("serque s","c.que_id=s.QueId")
						->leftJoin("z_doctor t","t.id=c.doctor_id")
						->leftJoin("hall h","h.HallNo=s.HallNo")
						->where($where)
						->find();
		return json($data);
	}
	// 获取时间
	public function getTime()
	{
		$que_id 	= input('que_id',0);    //队列ID
        $doctor_id 	= input('doctor_id',0);  //医生ID
        $ndata      = input('ndata','');
        // 获取上班时间
        $cla = new \app\admin\model\ClassTime;
        $data = $cla->getCheckTimes($doctor_id,$que_id,$ndata);
        return json($data);
	}

	//添加预约信息
    public function makeOrder(){
        $re_msg['code'] 	= 201;
        $re_msg['msg'] 		= '预约失败';
        $data['mobile']     = input("mobile",0);
        $data['name']       = input("name",'**');
        $data['idcard']     = input("idcard",0);
        $data['queId']      = input("que_id",0);
        $data['queName']    = input("qname",'');
        $data['doctor_id']   = input("doctor_id",0);
        $data['d_name']      = input("dname",'');
        $data['hallName']    = input("hname",'');
        $data['despeakDate'] = input("date",0);
        $time = input("time",'');
        $arr = explode('-', $time);
        $data['time_Part_S'] = isset($arr[0]) ? $arr[0].':00':'';
        $data['time_Part_O'] = isset($arr[1]) ? $arr[1].':00':'';
        $validate = new \app\api\validate\Order;
		if (!$validate->check($data)) {
			$re_msg['msg'] = $validate->getError();
            return json($re_msg);
        }
        $data['despeakTime'] = strtotime($data['despeakDate']." ".$data['time_Part_O']);
        $data['inTime']      = date("Y-m-d",time());
        $data['addtime']     = time();
        $data['manager_id']  = 0;
        $data['hallNo']      = input("hall_id",'');
        $where = array(
            'idcard'     =>$data['idcard'],
            'queId'     =>$data['queId'],
            'despeakDate'=>$data['despeakDate'],
            );
        $data['fetchTime'] = Db::name("config_fetch")->where("unitid",$this->unitid)->value("fetchTime");
        $result = DB::name("despeak")->where($where)->find();
        if(empty($result)){
            $data['unitId']   = Db::name("serque")->where("QueId",$data['queId'])->value("UnitId");
            $data['platform'] = '';
            $id = DB::name("despeak")->insertGetId($data);
            if($id){
                $re_msg['code'] = 200;
                $re_msg['msg']  = '预约成功';
                $re_msg['url']  = url("app/hall/getOrderDetail",['id'=>$id]);
            }
        }else{
            $re_msg['msg'] = '已经预约';
        }
        return json($re_msg);
    }
    // 预约详情
    public function orderDetail()
    {
    	$id = input("id",0);
    	$result = DB::name("despeak")->where("despeak_id",$id)->find();
    	if($result){
    		if(!empty($result['name'])){
	    		$sent = new \app\api\model\Sentence;    		
	    		$result['name'] = $sent->pregName($result['name']);
	    	}    			
    		$result['idcard'] = preg_replace('/(\d{3})\d{11}(\d{4})/', '$1***********$2', $result['idcard']);
    		$result['mobile'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $result['mobile']);
    	}
    	return json($result);
    }
    // 获取预约列表
    public function getOrderList()
    {
    	$idcard = input("idcard",'');
    	$mobile = input("mobile",'');
    	$status = input("status",'');
    	$where = array();
    	if($idcard){
    		$where[] = ['idcard','=',$idcard];
    	}
    	if($mobile){
    		$where[] = ['mobile','=',$mobile];
    	}
    	if($status!==''){
    		$where[] = ['status','=',$status];
    	}
        $where[] = ['addtime','>=',strtotime("-2 month",time())];
    	$data = array();
    	if(empty($idcard) && empty($mobile)){
    		return '';
    	}else{    		
	    	$result = DB::name("despeak")
	    			->where($where)
	    			->order("despeakTime","desc")
	    			->limit(20)
	    			->select();
	    	if($result){
		    	$sent = new \app\api\model\Sentence;   
		    	$sta = new \app\api\model\Status;
	    		foreach ($result as $key => $value) {
		    		$value['name'] = $sent->pregName($value['name']);
	    			$value['idcard'] = preg_replace('/(\d{3})\d{11}(\d{4})/', '$1***********$2', $value['idcard']);
	    			$value['mobile'] = preg_replace('/(\d{3})\d{4}(\d{4})/', '$1****$2', $value['mobile']);
	    			$str = $sta->despeak($value);
	    			$value['status_name'] = $str['msg'];
	    			$data[] = $value;
	    		}
	    	}
    	}
    	return json($data);
    }
    // 取消预约
    public function cancelOrder()
    {
    	$id = input("id",0);
    	$idcard = input("idcard",0);
    	$mobile = input("mobile",0);
    	if($idcard){
    		$where[] = ['idcard','=',$idcard];
    	}else{
    		$where[] = ['mobile','=',$mobile];
    	}
    	$data['status'] = 0;
    	$where[] = ['despeak_id','=',$id];    	
    	$rs = DB::name("despeak")->where($where)->update($data);
    	return $rs;
    }
}