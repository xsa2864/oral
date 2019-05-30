<?php
namespace app\tool\controller;

use think\View;
use think\Controller;
use think\facade\Request;
use think\Db;
use think\facade\Log;

class Http extends Controller
{
	// 同步数据
	public function insertDespeak()
	{
		$max_id = db("despeak")->max("despeak_id");
		$url = "http://114.116.81.59/api/v1/Appointment/getDespeak";
		$data['token'] = $this->token;
		$data['max_id'] = $max_id;
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行请求
        $output = curl_exec($ch);
        $arr = json_decode($output,1);
        $flag = 0;
        if($arr['code'] == 1){
        	$data = $arr['data'];
        	$flag = db("despeak")->insertAll($data);
        }
        echo $flag;
	}

	//身份证获取预约信息
	public function return_info(){
		$re_msg['server_handle_result'] = 0;
		$re_msg['allow_get_ticket'] 	= 0;
		$re_msg['reserve_check_result'] = 0;
		$re_msg['tip_msg'] 				= '参数错误';
		$re_msg['reserve_date'] 		= '';
		$re_msg['reserve_service_no'] 	= '';
		$re_msg['reserve_start_time'] 	= '';
		$re_msg['reserve_end_time'] 	= '';
		//{"\/tool\/http\/return_info":"","id":"350124198912282892","st":"1541733838943","area":"1001","sig":"7b46cb2f8a938131b60dd40061cc1d7f"}
		//35012219880209461X 1538035345 210 91c5ac320b1f016cf2d7620aaef72a84
		//35012219880209461X 1538035345 211 24a1a7c7eaff8b199acb7ded5588f909
		$id 	= input("id",'');
		$st 	= input("st","");
		$area 	= input("area",'');
		$sig 	= input("sig",'');
		// Log::record('测试日志信息，这是警告级别'.json_encode(input()),'notice');
		$SecretKey = 'Szhr_Pasr_2016';
		$str = $id.$st.$area.$SecretKey;
		$ch_sig = strtolower(md5($str));
		
		$config = db("config_fetch")->where("unitid",0)->find(); 

		// 是否验证通过
		if($ch_sig==$sig){
			$wh['d.idcard'] 		= $id;
			$wh['d.despeakDate'] 	= date("Y-m-d",time());
			$wh['d.status']			= 2;
			//取票次数限制是否分区域
			if($config['fetch_area']){
				$wh['h.SerInterface'] = $area;
				$cou = db("despeak")
					->alias('d')
					->leftJoin("hall h","h.HallNo=d.hallNo")
					->where($wh)
					->select();
			}else{
				$cou = db("despeak")->alias('d')->where($wh)->select();
			}

			if(count($cou)>=$config['fetch_number']){
				$re_msg['tip_msg'] 				= '超过取号限制次数';
				echo json_encode($re_msg);
				exit;
			}
			//是否半天预约可以取票 0=否 1=是 
			$half_time = time();
			if($config['half_day']){
				if(date("H",time())>12){
					$half_time = strtotime(date("Y-m-d",time())." 12:00:00");	//下午时间
				}else{
					$half_time = strtotime(date("Y-m-d",time()));	//早上时间
				}
			}

			$where[] = ['h.SerInterface','=',$area];
			$where[] = ['d.idcard','=',$id];
			$where[] = ['d.status','=',1];
			$where[] = ['d.despeakTime','>',$half_time];
			$result = db("despeak")
					->alias('d')
					->field('d.despeak_id,d.despeakDate,d.time_Part_S,d.time_Part_O,s.FetchTime,s.InterfaceID')
					->leftJoin("serque s","s.QueId=d.queId")
					->leftJoin("hall h","h.HallNo=d.hallNo")
					->where($where)
					->order('d.despeakTime', 'asc')
					->limit(1)
					->select();

			if($result){
				$result = $result[0];
				$stime = strtotime($result['despeakDate'].$result['time_Part_S'])-$result['FetchTime']*60;
				$etime = strtotime($result['despeakDate'].$result['time_Part_O']);

				if($etime > strtotime(date("Y-m-d")."23:59:59")){
					$re_msg['tip_msg'] 				= $result['despeakDate'].'前来取号';
				}else if($stime > time() && $result['FetchTime'] != 0){
					$re_msg['tip_msg'] 	= '预约时间前'.$result['FetchTime']."分钟才能取号";
				}else{
					$flag = true;
					if($etime < time()){
						if($config['half_day']){
							$result['time_Part_S'] = date("H:i",(time()-1800)).":00";
							$result['time_Part_O'] = date("H:i",(time()+1800)).":00";
						}else{
							$flag = false;
						}
					}
					if($flag){						
					    // WEB服务器处理结果，1为成功，0 为失败，为 0时请在 
					    $re_msg['server_handle_result'] = 1;  
					    // 预约检测结果，1 为已预约，0为未预约；
					    $re_msg['reserve_check_result'] = 1;
					    // 是否允许取票，1为允许，0 为不允许
						$re_msg['allow_get_ticket'] 	= 1;
						// 说明原因
					    $re_msg['tip_msg'] 				= '执行成功';
					    // 预约的业务编号，值为数字，需与排队机业务设置中的编号一一对应。 
					    $re_msg['reserve_service_no'] 	= $result['InterfaceID'];
					    // 预约日期，未预约时填空
					    $re_msg['reserve_date'] 		= $result['despeakDate'];
					    // 预约开始时间，未预约时填空
					    $re_msg['reserve_start_time'] 	= $result['time_Part_S'];
					    // 预约结束时间，未预约时填空
					    $re_msg['reserve_end_time'] 	= $result['time_Part_O'];
					    // 更新状态
					    $up['inTime'] = date("Y-m-d H:i:s");
					    $up['status'] = 2;
					    db("despeak")->where('despeak_id',$result['despeak_id'])->update($up);
					}
				}
			}else{
				$re_msg['tip_msg'] 				= '没有有效预约信息';
			}
		}
		echo json_encode($re_msg);
	} 


 	public function showtest()
 	{ 		
 		echo base64_encode('n1zb/ma5\vt0i28-pxuqy*6lrkdg9_ehcswo4+f37j')."<br><br>";
 		echo urldecode("%6E1%7A%62%2F%6D%615%5C%76%740%6928%2D%70%78%75%71%79%2A6%6C%72%6B%64%679%5F%65%68%63%73%77%6F4%2B%6637%6A");

 	}
}