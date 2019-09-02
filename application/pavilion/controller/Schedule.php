<?php
namespace app\pavilion\controller;


use think\Controller;
use think\helper\Hash;
use think\Queue;
use think\Validate;
use think\facade\Env;
use think\facade\Request;

class Schedule extends Controller
{
	public function index()
	{
		$where[] = ['class','<>',0];
		$result = db("z_doctor_class")->alias("dc")->field("dc.*,s.QueName,dr.QueName as name,dr.type")
            ->leftJoin("serque s","s.QueId=dc.que_id")
			->rightJoin("z_doctor dr","dr.id=dc.doctor_id")
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
		$this->assign("list",$list);
        $config = db("z_temp_config")->where("id","1")->find();
        $this->assign("config",$config);
		return $this->fetch("index");
	}
    public function showClass()
    {
        return $this->fetch("vueIndex");
    }
    // 口腔医院
    public function showOralClass()
    {
        return $this->fetch("vueOral");
    }
	// 预约
    public function getMark()
    {
        $id   		= input("id","");
        $date		= input("date",date("Y-m-d",strtotime("+1 day",time())));
        $list = db("z_doctor_class")->alias('dc')->field("dc.*,s.QueName,d.QueName as name,d.WorkTime1,d.WorkTime2,d.WorkTime3,d.WorkTime4")
                ->leftJoin('serque s','s.QueId=dc.que_id')
                ->leftJoin('z_doctor d','dc.doctor_id=d.id')
                ->where("dc.id",$id)
                ->find();

        $this->assign("date",'');
    	$this->assign("list",$list);

    	$cla = new \app\admin\model\ClassTime;
        $data = $cla->checktime($list['doctor_id'],$list['que_id']);        

        $this->assign("data",$data);
        $this->assign("id",$id);
    	$this->assign("Subtitle","填写预约信息");
        return $this->fetch('mark');
    }
    // 获取预约时间
    public function getCheckTime(){
        $re_msg['success'] = 0;
        $re_msg['msg'] = '查询失败';
        $data = array();
        if (Request::instance()->isPost())
        {
            $que_id = input('que_id',0);    //队列ID
            $doctor_id = input('doctor_id',0);  //医生ID
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
        $class_id   = input("class_id",0);
        $v_data['time1'] = $time1      = input("radio1",'');        
        $v_data['mobile'] = $data['mobile']     = input("mobile",0);
        $v_data['idcard'] = $data['idcard']     = input("idcard",0);        
        $v_data['despeakDate'] = $data['despeakDate'] = input("mktime",0);

        $rule = [
            'idcard'    => 'require|checkIdCard',
            'mobile'    => 'require|regex:/^1[3-8]{1}[0-9]{9}$/',
            'despeakDate' =>  'date',
            'time1'     => 'require',
        ];
        $msg = [
            'idcard.require'    => '请输入身份证号码',
            'idcard.checkIdCard' => '身份证号码输入不正确',
            'mobile.require'    => '请输入手机号码',
            'mobile.regex'      => '手机号码格式不正确',
            'despeakDate'       => '选择预约时间',
            'time1.require'     => '选择预约时间',
        ];
        Validate::extend('checkIdCard', function ($idcard) {
            if(strlen($idcard)!=18){
                return false;
            }     
            $idcard_base = substr($idcard, 0, 17);     
            $verify_code = substr($idcard, 17, 1);     
            $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);  
            $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
            $total = 0;
            for($i=0; $i<17; $i++){
                $total += substr($idcard_base, $i, 1)*$factor[$i];
            }     
            $mod = $total % 11;     
            if($verify_code == $verify_code_list[$mod]){
                return true;
            }else{
                return false;
            }     
        });
        $validate   = Validate::make($rule,$msg);
        $result = $validate->check($v_data);

        if(!$result){
            $re_msg['msg'] = $validate->getError();
            echo json_encode($re_msg);exit;
        }

        $arr = explode('-', $time1);
        $data['time_Part_S'] = $arr[0].':00';
        $data['time_Part_O'] = $arr[1].':00';
        $data['despeakTime'] = strtotime($data['despeakDate']." ".$data['time_Part_O']);
        $data['inTime']      = date("Y-m-d",time());
        $data['addtime']     = time();
        $where = array(
            'idcard'    =>$data['idcard'],
            'despeakDate'=>$data['despeakDate'],
            );
        

        $list = db("z_doctor_class")->alias('dc')->field("dc.*,s.QueName,d.QueName as name")
                ->leftJoin('serque s','s.QueId=dc.que_id')
                ->leftJoin('z_doctor d','d.id=dc.doctor_id')
                ->where("dc.id",$class_id)
                ->find();
                
        $data['queName']	= $list['QueName'];
        $data['d_name']     = $list['name'];
        $data['queId']      = $list['que_id'];
        $data['doctor_id']  = $list['doctor_id'];
        $result = db("despeak")->where($where)->find();
        if(empty($result)){
            $data['platform'] = 'phone';
            $rs = db("despeak")->insertGetId($data);
            if($rs){
                $re_msg['success'] = 1;
                $re_msg['msg'] = "预约成功";
            }
        }else{
            $re_msg['msg'] = '已经预约';
        }
        echo json_encode($re_msg);
    }
     //验证身份证是否有效
    protected function checkIdCard($idcard,$rule=''){     
        // 只能是18位
        if(strlen($idcard)!=18){
            return $rule;
        }     
        // 取出本体码
        $idcard_base = substr($idcard, 0, 17);     
        // 取出校验码
        $verify_code = substr($idcard, 17, 1);     
        // 加权因子
        $factor = array(7, 9, 10, 5, 8, 4, 2, 1, 6, 3, 7, 9, 10, 5, 8, 4, 2);     
        // 校验码对应值
        $verify_code_list = array('1', '0', 'X', '9', '8', '7', '6', '5', '4', '3', '2');
        // 根据前17位计算校验码
        $total = 0;
        for($i=0; $i<17; $i++){
            $total += substr($idcard_base, $i, 1)*$factor[$i];
        }     
        // 取模
        $mod = $total % 11;     
        // 比较校验码
        if($verify_code == $verify_code_list[$mod]){
            return true;
        }else{
            return $rule;
        }     
    }
	// 二维码
	public function showQr()
	{
        $id = input("id",0);
        $this->assign("id",$id);
		return $this->fetch('qrcode');
	}
	public function qrcode()
    {
        $id = input("id",0);
        $result = db("z_doctor_class")->alias("dc")->field("s.code,s.QueName,dr.staff_code,dr.QueName as name,dr.url")
            ->leftJoin("z_doctor dr","dr.id=dc.doctor_id")
            ->leftJoin("serque s","s.QueId=dc.que_id")
            ->where("dc.id",$id)->find();
		// 自定义二维码配置
        $config = [
            'title'         => true,
            'title_content' => '请用微信刷以上二维码进行预约！',
            'logo'          => false,
            'logo_url'      => Env::get("ROOT_PATH").'/public/static/admin/images/doctor.jpg',
            'logo_size'     => 80,
        ];

        // 直接输出
        // $qr_url = 'http://114.116.81.59/app/hall/getMark/code/'.$result['code'].'/staff_code/'.$result['staff_code'];
        $qr_img = '';
        $qr_url = isset($result['url'])?$result['url']:'无';
        $qr_code = new \app\admin\model\QrcodeServer($config);
        $qr_img = $qr_code->createServer($qr_url);
        echo $qr_img;
	}
}