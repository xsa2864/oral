<?php 
namespace app\admin\model;

use think\Model;
use think\Db;
use think\facade\Env;
use think\facade\Cache;

class Relations extends Model
{
	// 上下级关系
	public function getRelations($arr,$pid=0){
		krsort($arr);
		$id = 0;
		$data = array();
        foreach ($arr as $key => $value) {
            if($value['pid']==$pid){
                $value['child'] = self::getRelations($arr,$value['id']);
                $data[] = $value;
            }
        }
        return $data;
	}
	// 获取值
	public function getValue($arr){	
		$id = 0;
		if(!empty($arr[0]['child'])){
			$id = self::getValue($arr[0]['child']);
		}else{
			$id = $arr[0]['id'];
		}
		return $id;
	}
	/*
	 * que_id 科室  doctor_id 医生
	 * 获取等待人数
	 */
	public function getWaitNum($que_id=0,$doctor_id=0,$quick=0,$type=''){
		$num = 0;
		$where = array();
		if($type=='all'){
			$where[] = ['status','>=',0];
		}else{
			$where[] = ['status','=',1];
		}		
		$where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
		if($quick){
			$where[] = ["sort",'=',$quick];
		}
		if($doctor_id){
			$where[] = ['doctor_id','=',$doctor_id];
			$rs = Db::name("z_ticket")->field("count(*) as num")->where($where)->find();
		}else{
			$where[] = ['que_id','=',$que_id];
			$rs = Db::name("z_ticket")->field("count(*) as num")->where($where)->find();
		}
		if($rs){
			$num = $rs['num']-1;
		}
		return $num;
	}

	/*
	 * 队列归类
	 */
	public function listTicket($arr)
	{
		$data = array();
		$sort = array();
		foreach ($arr as $key => $value) {
			if(!isset($data[$value['que_id']]['num'])){
				$data[$value['que_id']]['num'] = $this->getWaitNum($value['que_id']);
				$data[$value['que_id']]['all_num'] = $this->getWaitNum($value['que_id'],0,0,'all');
			}
			$data[$value['que_id']]['title'] = $value['title'];
			$sort[$value['que_id']][$value['id']] = $value;
			ksort($sort[$value['que_id']]);
			$sorts = array_values($sort[$value['que_id']]);
			$data[$value['que_id']]['data'] = $sorts;
		}
		return $data;
	}

	/*
	 * 变更顺序
	 */
	public function changeNumber($id,$number)
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "变更失败";
		$where  = array();
		$result = DB::name("z_ticket")->where("id",$id)->find();		
		if($result && $number!=0){
			$where[] = ['status','=',1];
			$where[] = ['sort','=',$result['sort']];
			$where[] = ['que_id','=',$result['que_id']];			
			$where[] = ['add_time','>',strtotime(date("Y-m-d",time()))];
			if($number>0){
				$where[] = ['pid','<',$result['pid']];
				$order  = "pid desc";
				$num = -1;
			}else{
				$where[] = ['pid','>',$result['pid']];
				$order  = "pid asc";
				$num = 1;
			}	
			$rs = DB::name("z_ticket")->where($where)->order($order)->limit(abs($number)+1)->select();
			if($rs){
				$rs_count = count($rs);
				$da = array();
				if($rs_count <= abs($number)){		//实际数据少于查询数据		
					if($number>0){
						$pids = $rs[$rs_count-1]['pid']/2;
						if($pids>1){
							$da['pid'] = $pids;
						}else{
							$re_msg['msg'] 	   = "该位置不能插队,请更换位置";
						}
					}else{
						$da['pid'] = $rs[$rs_count-1]['pid']+10000;
					}					
				}else{
					if(abs($rs[$rs_count -1]['pid']-$rs[$rs_count-2]['pid'])>1){
						$da['pid'] = ($rs[$rs_count -1]['pid']+$rs[$rs_count-2]['pid'])/2;
					}else{
						$re_msg['msg'] 	   = "该位置不能插队,请更换位置";
					}
				}
				if(!empty($da)){
					$fs = DB::name("z_ticket")->where("id",$id)->update($da);
					if($fs){
						$re_msg['success'] = 1;
						$re_msg['msg'] 	   = "变更成功";
					}
				}
			}else{
				$re_msg['msg'] 	   = "变更成功";
			}		
		}
		return $re_msg;
	}

	/*
	 * 获取当前在线医生
	 * return array
	 */
    public function getOnLine($hall_id=0,$user_id=0)
    {
    	$wh = array();
    	if($user_id!=1){
    		$wh[] = ["hall_id",'=',$hall_id];
    	}
    	$rs = DB::name("z_terminal")->where($wh)->column("ip");
        $line = array();
    	if($rs){    		
	        $json = cache("terminal");
	        $arr  = json_decode($json,1);
	        if(!empty($arr)){
	            foreach ($arr as $key => $value) {	            
	                if($value['devices_status']==1){
	                	if(in_array($value['devices_ip'], $rs)){
	                		$line[$key] = isset($value['devices_name'])?$value['devices_name']:'';
		            	}
	                }
	            }
	        }
    	}
        return $line;
    } 

	/*
	 * 生成号码 
	 * quick 9= 是否绿色通道 2= 预约
	 * return array
	 */
	public function makeNumber($arr,$que_id=0,$doctor_id=0,$quick=0){
		$info = array();
		$where = array();
		$where[] = ["sort",'=',$quick];
		$info['pid'] = 10000;
		$result = '';
		if($doctor_id){
			$where[] = ["doctor_id",'=',$doctor_id];
			$where[] = ["add_time",'>',strtotime(date("Y-m-d",time()))];
			$result = Db::table("t_z_ticket")->field("max(pid) as pid,max(code) as code")->where($where)->find();
		}else{
			$where[] = ["que_id",'=',$que_id];
			$where[] = ["add_time",'>',strtotime(date("Y-m-d",time()))];
			$result = Db::table("t_z_ticket")->field("max(pid) as pid,max(code) as code")->where($where)->find();
		}
		if(!empty($result['code'])){
			$n = strlen($arr['StarNo']);
			$info['code'] = sprintf("%0".$n."d", ($result['code']+$arr['step']));
			// $info['code'] = $result['code'] + $arr['step'];		
			$pid = $result['pid']+10000;
			$info['pid']  = $pid;
		}else{			
			$info['code'] = $arr['StarNo'];
		}
		return $info;
	}

	/*
	 * 根据用户信息-生成票号
	 * return array
	 */
	public function makeTicket($arrs,$flag=true)
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "票号生成失败";

		$QueId 			= $arrs['QueId'];
		$quick 			= $arrs['quick'];
		$doctor_id 		= isset($arrs['doctor_id'])?$arrs['doctor_id']:0;
		$data['doctor_id'] = $doctor_id;
		$data['order'] 	= isset($arrs['order'])?$arrs['order']:0;
		$data['idcard'] = $arrs['idcard'];
		$data['name'] 	= $arrs['name'];
		$data['stime'] 	= isset($arrs['stime'])?$arrs['stime']:0;
		$data['etime']  = isset($arrs['etime'])?$arrs['etime']:0;
		$data['tips1'] 	= isset($arrs['tips1'])?$arrs['tips1']:'';
		$data['tips2']  = isset($arrs['tips2'])?$arrs['tips2']:'';
		$data['sex'] 	= isset($arrs['sex'])?$arrs['sex']:0;
		$data['birth'] 	= isset($arrs['birth'])?strtotime($arrs['birth']):0;
		$data['pid'] 	= $data['code'] = isset($arrs['code'])?$arrs['code']:'';

	    $result = db("serque")->where("QueId",$QueId)->find();
	    if($result){
	    	if($flag){	    		
		    	$fw[] = ['que_id','=',$result['QueId']];
		    	$fw[] = ['idcard','=',$data['idcard']];		    	
		    	$fw[] = ['idcard','<>',''];
		    	$fw[] = ['status','=',1];
		    	$fw[] = ['add_time','>=',strtotime(date("Y-m-d",time()))];
		    	$flags = db("z_ticket")->where($fw)->find();
		    	if($flags){
		    		$re_msg['msg'] 	   = "已经取过号";
		    		return $re_msg;
		    	}
	    	}
	    	if($quick){
	    		$data['sort'] 	 = $quick;
	    		$data['prefix']  = $result['quick_char'];
	    	}else{
	    		$data['prefix']  = $result['NoChar'];
	    	}
	    	$arr = $this->makeNumber($result,$QueId,$doctor_id,$quick);
	    	$data['code']  	 = !empty($data['code'])?$data['code']:$arr['code'];
	    	$data['pid']  	 = !empty($data['pid'])?$data['pid']:$arr['pid'];	    	
	    	$data['que_id']  = $QueId;
	    	$data['unit_id'] = $result['UnitId'];
	    	$data['hall_id'] = $result['HallNo'];
	    	$data['title']   = $result['QueName'];
	    	$data['add_time']= time();
	    	$data['in_date'] = date("Y-m-d H:i:s",time());
		    $rs = db("z_ticket")->insert($data);
		    if($rs){
		    	$re_msg['success'] = 1;
				$re_msg['msg'] 	   = "票号生成成功,注意收好";
				$data['num']	   = $this->getWaitNum($QueId,$doctor_id,$quick);
				$data['add_time']  = date("Y-m-d H:i",time());
				$re_msg['data']	   = $data;
				// 更新前端数据
		        $ip = db("z_terminal")->where("hall_id",$data['hall_id'])->column("ip");
		        if($ip){		        	
		        	$soc = new \app\admin\model\Socket;
		        	foreach ($ip as $key => $value) {
		        		$iprs = $soc->terminalSocke($value,['reload'=>1]);
		        	}
		        }
		    }
	    }else{
	    	$re_msg['msg'] 	   = "队列不存在";
	    }
	    return $re_msg;
	}
	/*
	 * 无用户信息-生成票号
	 * return array
	 */
	public function makeTickets($arrs)
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "票号生成失败";

		$doctor_id 		= isset($arrs['doctor_id'])?$arrs['doctor_id']:0;
		$data['doctor_id'] = $doctor_id;
		$QueId 			= $arrs['QueId'];
		$quick 			= $arrs['quick'];

	    $result = Db::name("serque")->where("QueId",$QueId)->find();

	    if($result){	    	
	    	if($quick){
	    		$data['sort'] = 9;
	    		$data['prefix']  = $result['quick_char'];
	    	}else{
	    		$data['prefix']  = $result['NoChar'];
	    	}
	    	$arr = $this->makeNumber($result,$QueId,0,$quick);
	    	$data['name'] 	 = "";
	    	$data['code']  	 = $arr['code'];
	    	$data['pid']  	 = $arr['pid'];	    	
	    	$data['que_id']  = $QueId;
	    	$data['hall_id'] = $result['HallNo'];
	    	$data['title']   = $result['QueName'];
	    	$data['add_time']= time();
	    	$data['in_date'] = date("Y-m-d H:i:s",time());

	    	Db::startTrans();		
	    	$lwh[] = ['que_id',"=",$data['que_id']];
			$lwh[] = ['code',"=",$data['code']];
			$lwh[] = ['add_time','=',$data['add_time']];
			$lrs = Db::table("t_z_ticket")->where($lwh)->lock(true)->find();

		    $rs = Db::name("z_ticket")->insert($data);
		    if($rs && !$lrs){
		    	Db::commit();
		    	$re_msg['success'] = 1;
				$re_msg['msg'] 	   = "票号生成成功,注意收好";
				$data['num']	   = $this->getWaitNum($QueId,0,$quick);
				$data['add_time']  = date("Y-m-d H:i",time());
				$re_msg['data']	   = $data;

				// 更新前端数据
		        $ip = db("z_terminal")->where("hall_id",$data['hall_id'])->column("ip");
		        if($ip){		        	
		        	$soc = new \app\admin\model\Socket;
		        	foreach ($ip as $key => $value) {
		        		$iprs = $soc->terminalSocke($value,['reload'=>1]);
		        	}
		        }
		    }else{
        		$re_msg['msg']  = "请重新取号";
        		$re_msg['data'] = '';
	        	Db::rollback();	        	
	        }
	    }else{
	    	$re_msg['msg'] 	   = "队列不存在";
	    }
	    return $re_msg;
	}

	// 检查是否可以生成票号
	public function checkValid($id=0)
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 	   = "未查询到预约数据";
		$item = db("despeak")->where('despeak_id',$id)->find();
		$flag = false;
		if($item){
			if($item['status']==1){				
				$config = db("config_fetch")->where("unitid",1)->find(); 
				$wh['d.idcard'] 		= $item['idcard'];
				$wh['d.despeakDate'] 	= date("Y-m-d",time());
				$wh['d.status']			= 2;
				//取票次数限制是否分区域
				if($config['fetch_area']){
					$wh['d.hallNo'] = $hall_id;
				}
				$cou = db("despeak")->alias('d')->where($wh)->select();

				if(count($cou)>=$config['fetch_number']){
					$re_msg['msg'] 				= '超过取号限制次数';
				}

				$stime = strtotime($item['despeakDate'].$item['time_Part_S'])-$item['fetchTime']*60;
				$etime = strtotime($item['despeakDate'].$item['time_Part_O']);

				if(strtotime(date("Y-m-d")."23:59:59") < $etime){
					$re_msg['msg'] 	= $item['despeakDate'].'前来取号';
				}else if(time()<$stime){
					if($item['fetchTime'] != 0){
						$re_msg['msg'] 	= '预约时间前'.$item['fetchTime']."分钟才能取号";
					}else{
						$re_msg['msg'] 	= $item['despeakDate'].$item['time_Part_S']."后才能取号";
					}					
				}else{					
					if(time() < $etime){
						$flag = true;						
					}else{
						if($config['half_day']){
							$item['time_Part_S'] = date("H:i",(time()-1800)).":00";
							$item['time_Part_O'] = date("H:i",(time()+1800)).":00";
							$flag = true;	
						}else{
							$re_msg['msg'] 	   = "您的预约号已经过期";
						}
					}
				}
				if($flag){
					$re_msg['success'] = 1;
					$re_msg['msg'] 	   = "有效预约";
					$re_msg['data']	   = $item;
				}
			}else{
				$re_msg['msg'] 	   = "没有有效预约";
			}
		}
		return $re_msg;
	}

	/*
	 * 生成预约号
	 * return array
	 */
	public function orderTicket($id=0,$hall_id=0)
	{
		$re_msg['success'] = 0;
        $re_msg['msg']  = "票号生成失败";
		$item = $this->checkValid($id);
		if($item['success']==1){
			$data = $item['data'];
			if($item['data']['hallNo']==$hall_id){				
				$arrs['QueId']  = $data['queId'];
				$arrs['quick']  = 2;
				$arrs['order']	= 1;
				$arrs['doctor_id']	= $data['doctor_id'];
				$arrs['idcard']	= $data['idcard'];
				$arrs['name']	= isset($data['name'])?$data['name']:'**';
				$arrs['stime']	= $data['despeakTime'];
				$arrs['etime']	= strtotime($data['despeakDate'].' '.$data['time_Part_O']);
				$arrs['sex']	= $data['sex'];
				$arrs['birth']	= $data['birth'];
				$arrs['code']	= $data['queNo'];
				$result = $this->makeTicket($arrs);
				if($result['success']==1){
					$re_msg['success'] = 1;
					$re_msg['msg']  = $result['msg'];
					$re_msg['data'] = $result['data'];
					$d['status'] = 2;
					db("despeak")->where("despeak_id",$data['despeak_id'])->data($d)->update();
				}else{
					$re_msg['msg']  = $result['msg'];
				}
			}else{
				$re_msg['msg']  = "该预约不能在本区域取号,请前往对应区域取号";
			}
		}else{
			$re_msg['msg']  = $item['msg'];
		}
        return $re_msg;
	}
}