<?php
namespace app\api\model;

use think\Db;
use think\Request;

/*===== 口腔医院 webserbice =====*/
class UserInfo 
{	
	/* XML数据格式如下
	 *<?xml version="1.0" encoding="UTF-8"?>
	 *<MESSAGE>
	 *  <BODY>
	 *    <META>
	 *      <TOPIC_ID>A001</TOPIC_ID>                   //平台提供TOPIC_ID
	 *      <APP_ID>HIS</APP_ID>                        //平台提供APP_ID
	 *    </META>
	 *<ROWS>
	 *      <ROW>
	 *        ……（此处节点依据患者基本信息接口的数据结构定义并赋值）
	 *      </ROW>
	 *</ROWS>
	 *  </BODY>
	 *</MESSAGE>
	 */


	public function getName(){
	    return "口腔医院 webservice !";
	}	

	/*
	 * 患者信息
	 *
	 */
	public function patient($xml='')
	{
		$re_msg['STATUS'] 	= 0;
		$re_msg['MESSAGE'] 	= '数据格式错误';
		$re_msg['DATA'] 	= '';
		$data = array();
		if(!empty($xml)){
			$arr = $this->xmlToArray($xml);
			if(!isset($arr['BODY']['ROWS']['ROW'][0])){
				$result[] = $arr['BODY']['ROWS']['ROW'];	
			}else{
				$result = $arr['BODY']['ROWS']['ROW'];	
			}		
			$rs = 0;
			$rs = $this->mkDespeak($result);
			if($rs['success']==1){
				$re_msg['STATUS'] = 1;
			}
			$re_msg['MESSAGE'] 	= $rs['msg'];
			$re_msg['DATA'] 	= $rs['data'];
		}
		$xmls = $this->arrayToXml($re_msg);
		return $xmls;
	}

	// 处理预约信息
	public function mkDespeak($arr=array())
	{
		$re_msg['success'] 	= 0;
		$re_msg['msg'] 		= '';
		$re_msg['data'] 	= '';
		if(is_array($arr)){				
			$rel = new \app\admin\model\Relations;	
			$data = array();
			$number = 0;
			foreach ($arr as $key => $value) {			
				$lmsg = '';
				$ldata = '';
				unset($data);
				$is_rs = DB::name("despeak")->where('original_id',$value['original_id'])->find();	
				if($value['operation']==2){		//删除步骤
					if($is_rs && $value['original_id']!=0){
						$rs2 = DB::name("despeak")->where('original_id',$value['original_id'])->delete();
					}else{
						$rs2 = 1;
					}
					if($rs2){
						$number++;
						$lmsg  = '删除成功';
					}else{
						$lmsg  = '删除失败';
					}
				}else{
					$data['unitId'] 	  	 = $value['unit_id'];
					$data['hallNo'] 	  	 = $value['hall_id'];
					$data['queId'] 	  	 	 = $value['que_id'];
					$data['unitName']  	 	 = $value['unit_name'];
					$data['hallName']  	 	 = $value['hall_name'];				
					$data['queName']   	 	 = $value['que_name'];
					$data['data_status'] 	 = 0;
					$rs = DB::name("serque")->field("QueId,HallNo,UnitId")->where("InterfaceID",$value['que_id'])->find();
					if($rs){
						$data['data_status'] = 1;
						$data['unitId'] 	 = $rs['UnitId'];
						$data['hallNo'] 	 = $rs['HallNo'];
						$data['queId'] 	 	 = $rs['QueId'];
					}
					$data['original_id']	= $value['original_id'];
					$data['card_no']		= $value['card_no'];
					$doctor_id = DB::name("z_doctor")->where("staff_code",$value['staff_code'])->value("id");
					$data['doctor_id']		= $doctor_id ? $doctor_id : 0;
					$data['d_name'] 	 	= $value['doctor_name'];
					$data['idcard'] 	 	= $value['idcard'];
					$data['noChar'] 	 	= $value['prefix'];
					$data['queNo']     		= $value['code'];
					$data['name']      		= $value['name'];
					$data['mobile'] 	 	= $value['mobile'];
					$data['sex'] 			= $value['sex'];
					$data['birth'] 			= $value['birth'];		
					$data['fetch_status'] 	= $value['fetch_status'];				
					$data['despeakDate'] 	= $value['date'];				
					$data['despeakTime'] 	= strtotime($value['date']);
					$data['time_Part_S'] 	= $value['stime'];
					$data['time_Part_O'] 	= $value['etime'];
					$data['inTime'] 	 	= date("Y-m-d H:i:s",time());
					$data['addtime'] 	 	= time();	
					if($value['operation']==1){
						if($is_rs){
							$rs2 = DB::name("despeak")->where('original_id',$value['original_id'])->update($data);
							if($rs2 !== false){
								$number++;
								$lmsg  = '更新成功';
								$rs2 = $is_rs['despeak_id'];
							}else{
								$lmsg  = '更新失败';
							}
						}else{
							$lmsg  = '数据不存在,更新失败';
							$value['fetch_status']=0;
						}
					}else{
						if($is_rs){
							$rs2 = $is_rs['despeak_id'];
							$lmsg  = '数据已经存在';
							$value['fetch_status'] = 0;
						}else{
							$rs2 = DB::name("despeak")->insertGetId($data);
							if($rs2){
								$number++;
								$lmsg  = '添加成功';
							}else{
								$lmsg  = '添加失败';
							}
						}
					}

					if($value['fetch_status']==1)
					{
						if($is_rs && $value['operation']!=1){
							$data['data_status'] = $is_rs['data_status'];
						}
						if($data['data_status']==1){
							$rss = $rel->orderTicket($rs2,$data['hallNo']);
							if($rss['success']==1){
								$re_msg['success'] 	= 1;				
							}
							$lmsg = $rss['msg'];
						}else{
							$lmsg = '数据不完整';
						}
					}
				}
				if($re_msg['msg'] != ''){					
					$re_msg['msg']  .= ',';
					$re_msg['data']  .= ',';
				}				
				$re_msg['msg'] 	.= $lmsg;
				$re_msg['data'] .= $value['original_id'];	
			} 
			if(count($arr)==$number){
				$re_msg['success'] = 1;
			}
		}
		return $re_msg;
	}

	// 医生排班
	public function doctorClass($xml='')
	{
		$re_msg['STATUS'] 	= 0;
		$re_msg['MESSAGE'] 	= '数据格式错误';
		$re_msg['DATA'] 	= '';
		$data = array();
		if(!empty($xml)){
			$arr = $this->xmlToArray($xml);
			if(!isset($arr['BODY']['ROWS']['ROW'][0])){
				$result[] = $arr['BODY']['ROWS']['ROW'];	
			}else{
				$result = $arr['BODY']['ROWS']['ROW'];	
			}			
			$rs = $this->setClass($result);
			if($rs['success']==1){
				$re_msg['STATUS'] = 1;
			}
			$re_msg['MESSAGE']  = $rs['msg'];
			$re_msg['DATA'] 	= $rs['data'];
		}
		$xmls = $this->arrayToXml($re_msg);
		return $xmls;
	}

	// 设置排班	
	public function setClass($arr=array())
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 		= '';
		$re_msg['data'] 	= '';
		if(is_array($arr)){
			$class = new \app\admin\model\ClassTime;
			$data = array();
			$number = 0;
			foreach ($arr as $key => $value) {
				$lmsg = '';
				$ldata = '';
				unset($data);
				$que_id = DB::name("serque")->where("InterfaceID",$value['que_id'])->value("QueId");
				if(empty($que_id)){
					$re_msg['msg'] = '没有找到队列信息';
					continue;
				}
				$data['que_id']    = $wh['que_id'] = $que_id;
				$doctor_id = DB::name("z_doctor")->where("staff_code",$value['staff_code'])->value("id");
				if(empty($doctor_id)){
					$re_msg['msg'] = '没有找到医生信息';
					continue;
				}
				$data['doctor_id'] = $wh['doctor_id'] = $doctor_id;
				$calss 			   = explode(',', $value['date']);
				$data['class'] 	   = $class->binDecClass($calss);
				$id = DB::name("z_doctor_class")->where($wh)->value("id");
				$rs = 0;
				if($id){
					if($value['status']==1){
						$rs = DB::name("z_doctor_class")->where("id",$id)->data($data)->update();
						if($rs!==false){
							$number ++;
							$lmsg = '更新成功！';
						}else{
							$lmsg = '更新失败';
						}
					}else{
						$rs = DB::name("z_doctor_class")->delete($id);
						if($rs){
							$number ++;
							$lmsg = '删除成功';
						}else{
							$lmsg = '删除失败';
						}
					}
				}else{
					if($value['status']==1){
						$rs = DB::name("z_doctor_class")->data($data)->insert();
						if($rs){
							$number ++;
							$lmsg = '添加成功';
						}else{
							$lmsg = '添加失败';
						}
					}else{
						$number ++;
						$lmsg = '数据已经存在';
					}
				}		
				if($re_msg['msg'] != ''){					
					$re_msg['msg']   .= ',';
					$re_msg['data']  .= ',';
				}			
				$re_msg['msg'] 	.= $lmsg;
				$re_msg['data'] .= $value['staff_code'];
			}
			if(count($arr)==$number){
				$re_msg['success'] = 1;
			}
		}
		return $re_msg;
	}

	// 医生信息
	public function doctorInfo($xml='')
	{
		$re_msg['STATUS'] 	= 0;
		$re_msg['MESSAGE'] 	= '数据格式错误';
		$re_msg['DATA'] 	= '';
		if(!empty($xml)){
			$arr = $this->xmlToArray($xml);
			if(!isset($arr['BODY']['ROWS']['ROW'][0])){				
				$result[] = $arr['BODY']['ROWS']['ROW'];	
			}else{
				$result = $arr['BODY']['ROWS']['ROW'];	
			}		
			$rs = $this->setDoctor($result);
			if($rs['success']==1){
				$re_msg['STATUS'] = 1;
			}
			$re_msg['MESSAGE'] 	= $rs['msg'];
			$re_msg['DATA'] 	= $rs['data'];
		}
		$xml = $this->arrayToXml($re_msg);
		return $xml;
	}
	
	// 设置医生信息	
	public function setDoctor($arr=array())
	{
		$re_msg['success'] = 0;
		$re_msg['msg'] 		= '';
		$re_msg['data'] 	= '';
		if(is_array($arr)){
			$data = array();
			$number = 0;	
			foreach ($arr as $key => $value) {
				$lmsg = '';
				$ldata = '';
				unset($data);
				$id = 0;
				$id = DB::name("z_doctor")->where("staff_code",$value['staff_code'])->value("id");
				$data['unit_id'] 	  	= DB::name("unit")->where("u_code",$value['unit_id'])->value("UnitId");
				$data['hall_id'] 	  	= 0;
				$data['que_id'] 	  	= '|';
				$data['staff_code'] 	= $value['staff_code'];
				$data['type'] 			= $value['type'];
				$data['doctor_name'] 	= $value['doctor_name'];
				$data['QueName'] 		= $value['doctor_name'];
				$data['mobile'] 		= $value['mobile'];
				$data['AlternateField1']= $value['introduce'];
				$data['pic'] 			= $value['pic'];
				$data['sex'] 			= $value['sex'];				
				$data['HourSum'] 		= $value['hour_sum'];
				$data['NoChar'] 		= $value['no_char'];
				$data['StarNo'] 		= $value['star_no'];
				$data['step'] 			= $value['step'];
				$data['status'] 		= $value['status'];
				$data['WorkTime1'] 		= $value['worker_gs_time'];
				$data['WorkTime2'] 		= $value['worker_ge_time'];
				$data['WorkTime3'] 		= $value['worker_as_time'];
				$data['WorkTime4'] 		= $value['worker_ae_time'];
		
				if($id){
					$rs = DB::name("z_doctor")->where("id",$id)->update($data);
					if($rs!==false){
						$number++;
						$lmsg = '更新成功';
					}else{
						$lmsg = '更新失败';
					}
				}else{
					$data['password'] 		= md5('123456');
					$data['add_time'] 		= time();
					$rs = DB::name("z_doctor")->data($data)->insert();
					if($rs){
						$number += 1;
						$lmsg = '添加成功';
					}else{
						$lmsg = '添加失败';
					}
				}
				if($re_msg['msg'] != ''){					
					$re_msg['msg']  .= ',';
					$re_msg['data']  .= ',';
				}				
				$re_msg['msg'] 	.= $lmsg;
				$re_msg['data'] .= $value['staff_code'];	
 			}
			if(count($arr)==$number){
				$re_msg['success'] = 1;
			}
		}
		return $re_msg;
	}

	public function arrayToXml($arr=array())
    {
    	if(!is_array($arr) || count($arr) <= 0){
		    return false;
		}
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<MESSAGE>';
        $xml .= '<HEADER>';
        foreach ($arr as $key=>$val)
        {
            if (is_numeric($val)){
                $xml.="<".$key.">".$val."</".$key.">";
            }else{
                 // $xml.="<".$key.">".$val."</".$key.">";
                 $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
            }
        }
        $xml .= '</HEADER>';
        $xml .= '</MESSAGE>';

        return $xml;
    }

    //将XML转为array
    public function xmlToArray($xml='')
    {
        //禁止引用外部xml实体
        // libxml_disable_entity_loader(true);
        // $xmls = simplexml_load_string($xml, 'SimpleXMLElement', LIBXML_NOCDATA);
        $xmls = simplexml_load_string($xml);
        $json = json_encode($xmls);
        $arr  = json_decode($json,true);
        return $arr;
    }
}
