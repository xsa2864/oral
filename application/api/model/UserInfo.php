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
		$re_msg['CODE']		= '';
		$re_msg['MESSAGE'] 	= '数据格式错误';
		$re_msg['DETAIL'] 	= '';
		$data = array();

		$arr 	= json_decode($xml,1);
		$type 	= 'json';
		if(empty($arr)){
			$arr 	= $this->xmlToArray($xml);
			$type 	= 'xml';
		}
		
		if($arr){
			$meta = $arr['BODY']['META'];
			if(trim($meta['TOPIC_ID'])=='A201' && trim($meta['APP_ID'])=='JQ_HIS'){
				if(!isset($arr['BODY']['ROWS']['ROW'][0])){				
					$result[] = $arr['BODY']['ROWS']['ROW'];	
				}else{
					$result = $arr['BODY']['ROWS']['ROW'];	
				}		
				$rs = $this->mkDespeak($result);
			}else{
				$rs['success'] 	= 0;
				$rs['msg']		= 'TOPIC_ID和APP_ID值无效';
				$rs['data']		= 'TOPIC_ID和APP_ID值无效';
			}			
			if($rs['success']==1){
				$re_msg['STATUS'] = 1;
			}
			$re_msg['MESSAGE'] 	= $rs['msg'];
			$re_msg['DETAIL'] 	= $rs['data'];
		}
		if($type=='json'){
			$xmls = json_encode($re_msg);
		}else{
			$xmls = $this->arrayToXml($re_msg);
		}
		return $xmls;
	}

	// 处理预约信息
	public function mkDespeak($arr=array())
	{
		$re_msg['success'] 	= 1;
		$re_msg['msg'] 		= '';
		$re_msg['data'] 	= '';
		if(is_array($arr)){			
			foreach ($arr as $key => $value) {	
				if($value['OPERATION_STATUS']==2){
					$re_arr['status']  = 0;
					$re_arr['msg'] = '删除失败';
					$rs = DB::name("despeak")->where('original_id',$value['ORIGINAL_ID'])->delete();
					if($rs){
						$re_arr['status'] = 1;
						$re_arr['msg'] = '删除成功';
					}				
				}else if($value['OPERATION_STATUS']==1){
					$re_arr = $this->upDespeak($value);
				}else{
					$re_arr = $this->addDespeak($value);
				}
				if($re_arr['status'] == 0){
					$re_msg['success'] = 0;
				}
				if($re_msg['msg'] != ''){					
					$re_msg['msg']  .= ',';
					$re_msg['data']  .= ',';
				}				
				$re_msg['msg'] 	.= $re_arr['msg'];
				$re_msg['data'] .= $value['ORIGINAL_ID'];	
			} 
		}else{
			$re_msg['success'] = 0;
		}
		return $re_msg;
	}
	// 添加患者
	public function addDespeak($value='')
	{
		$re_msg['status']	= 0;
		$re_msg['msg']		= '添加失败';
		$id = DB::name("despeak")->where('original_id',$value['PATIENT_ID'])->value("despeak_id");
		if($id){
			$re_msg['msg'] = '该患者信息已经存在';
			return $re_msg;
		}
		$data['unitId'] 	  	 = $value['HOSPITAL_ID'];
		$data['hallNo'] 	  	 = $value['HALL_CODE'];
		$data['queId'] 	  	 	 = $value['QUE_CODE'];
		$data['unitName']  	 	 = $value['HOSPITAL_NAME'];
		$data['hallName']  	 	 = $value['HALL_NAME'];				
		$data['queName']   	 	 = $value['QUE_NAME'];
		$data['data_status'] 	 = 0;
		$rs = DB::name("serque")->field("QueId,HallNo,UnitId")->where("InterfaceID",$value['QUE_CODE'])->find();
		if($rs){
			$data['data_status'] = 1;
			$data['unitId'] 	 = $rs['UnitId'];
			$data['hallNo'] 	 = $rs['HallNo'];
			$data['queId'] 	 	 = $rs['QueId'];
		}
		$data['original_id']	= $value['PATIENT_ID'];
		$data['card_no']		= $value['CARD_NO'];
		$doctor_id = DB::name("z_doctor")->where("staff_code",$value['DOCTOR_CODE'])->value("id");
		$data['doctor_id']		= $doctor_id ? $doctor_id : 0;
		$data['d_name'] 	 	= $value['DOCTOR_NAME'];
		$data['idcard'] 	 	= $value['IDCARD'];
		$data['noChar'] 	 	= $value['PREFIX'];
		$data['queNo']     		= $value['QUE_NUM'];
		$data['name']      		= $value['NAME'];
		$data['mobile'] 	 	= $value['TEL'];
		$data['sex'] 			= $value['SEX_CODE'];
		$data['birth'] 			= $value['BIRTHDAY'];		
		$data['fetch_status'] 	= $value['FETCH_STATUS'];				
		$data['despeakDate'] 	= $value['SD_DATE'];				
		$data['despeakTime'] 	= strtotime($value['SD_DATE']);
		$data['time_Part_S'] 	= $value['START_DATE'];
		$data['time_Part_O'] 	= $value['END_DATE'];
		$data['inTime'] 	 	= date("Y-m-d H:i:s",time());
		$data['addtime'] 	 	= time();
		$rs = DB::name("despeak")->insertGetId($data);
		if($rs){
			$re_msg['status']	= 1;
			$re_msg['msg']		= '添加成功';
			if($value['FETCH_STATUS']==1)
			{
				if($data['data_status']==1){
					$rel = new \app\admin\model\Relations;	
					$rel->orderTicket($rs,$data['hallNo']);
				}
			}
		}
		return $re_msg;
	}

	// 更新患者
	public function upDespeak($value='')
	{
		$re_msg['status']	= 0;
		$re_msg['msg']		= '更新失败';
		$id = DB::name("despeak")->where('original_id',$value['PATIENT_ID'])->value("despeak_id");
		if(empty($id)){
			$re_msg['msg'] = '没有患者信息';
			return $re_msg;
		}
		$data['unitId'] 	  	 = $value['HOSPITAL_ID'];
		$data['hallNo'] 	  	 = $value['HALL_CODE'];
		$data['queId'] 	  	 	 = $value['QUE_CODE'];
		$data['unitName']  	 	 = $value['HOSPITAL_NAME'];
		$data['hallName']  	 	 = $value['HALL_NAME'];				
		$data['queName']   	 	 = $value['QUE_NAME'];
		$data['data_status'] 	 = 0;
		$rs = DB::name("serque")->field("QueId,HallNo,UnitId")->where("InterfaceID",$value['QUE_CODE'])->find();
		if($rs){
			$data['data_status'] = 1;
			$data['unitId'] 	 = $rs['UnitId'];
			$data['hallNo'] 	 = $rs['HallNo'];
			$data['queId'] 	 	 = $rs['QueId'];
		}
		$data['card_no']		= $value['CARD_NO'];
		$doctor_id = DB::name("z_doctor")->where("staff_code",$value['DOCTOR_CODE'])->value("id");
		$data['doctor_id']		= $doctor_id ? $doctor_id : 0;
		$data['d_name'] 	 	= $value['DOCTOR_NAME'];
		$data['idcard'] 	 	= $value['IDCARD'];
		$data['noChar'] 	 	= $value['PREFIX'];
		$data['queNo']     		= $value['QUE_NUM'];
		$data['name']      		= $value['NAME'];
		$data['mobile'] 	 	= $value['TEL'];
		$data['sex'] 			= $value['SEX_CODE'];
		$data['birth'] 			= $value['BIRTHDAY'];		
		$data['fetch_status'] 	= $value['FETCH_STATUS'];				
		$data['despeakDate'] 	= $value['SD_DATE'];				
		$data['despeakTime'] 	= strtotime($value['SD_DATE']);
		$data['time_Part_S'] 	= $value['START_DATE'];
		$data['time_Part_O'] 	= $value['END_DATE'];
		$rs = DB::name("despeak")->where('despeak_id',$id)->update($data);
		if($rs){
			$re_msg['status']	= 1;
			$re_msg['msg']		= '更新成功';
			if($value['FETCH_STATUS']==1)
			{
				if($data['data_status']==1){
					$rel = new \app\admin\model\Relations;	
					$rel->orderTicket($id,$data['hallNo']);
				}
			}
		}
		return $re_msg;
	}


	// 医生排班
	public function doctorClass($xml='')
	{
		$re_msg['STATUS'] 	= 0;
		$re_msg['CODE']		= '';
		$re_msg['MESSAGE'] 	= '数据格式错误';
		$re_msg['DETAIL'] 	= '';
		$data = array();

		$arr 	= json_decode($xml,1);
		$type 	= 'json';
		if(empty($arr)){
			$arr 	= $this->xmlToArray($xml);
			$type 	= 'xml';
		}	
		if($arr){			
			$meta = $arr['BODY']['META'];
			if(trim($meta['TOPIC_ID'])=='A204' && trim($meta['APP_ID'])=='JQ_HIS'){
				if(!isset($arr['BODY']['ROWS']['ROW'][0])){				
					$result[] = $arr['BODY']['ROWS']['ROW'];	
				}else{
					$result = $arr['BODY']['ROWS']['ROW'];	
				}		
				$rs = $this->saveClass($result);
			}else{
				$rs['success'] 	= 0;
				$rs['msg']		= 'TOPIC_ID和APP_ID值无效';
				$rs['data']		= 'TOPIC_ID和APP_ID值无效';
			}
			if($rs['success']==1){
				$re_msg['STATUS'] = 1;
			}
			$re_msg['MESSAGE']  = $rs['msg'];
			$re_msg['DETAIL'] 	= $rs['data'];
		}

		if($type=='json'){
			$xmls = json_encode($re_msg);
		}else{
			$xmls = $this->arrayToXml($re_msg);
		}
		return $xmls;
	}
	// 保存排班	
	public function saveClass($arr=array())
	{
		$re_msg['success'] = 1;
		$re_msg['msg'] 		= '';
		$re_msg['data'] 	= '';
		if(is_array($arr)){
			foreach ($arr as $key => $value) {
				if($value['STATUS']==0){
					$re_arr['status'] 	= 0;
					$re_arr['msg'] 		= '删除失败';
					$rs = DB::name("z_doctor_class")->where('original_id',$value['ORIGINAL_ID'])->delete();
					if($rs){
						$re_arr['status'] 	= 1;
						$re_arr['msg'] 		= '删除成功';
					}
				}else{
					$re_arr = $this->upClass($value);
				}	
				if($re_arr['status']==0){
					$re_msg['success'] = 0;
				}
				if($re_msg['msg'] != ''){					
					$re_msg['msg']   .= ',';
					$re_msg['data']  .= ',';
				}			
				$re_msg['msg'] 	.= $re_arr['msg'];
				$re_msg['data'] .= $value['ORIGINAL_ID'];
			}			
		}else{			
			$re_msg['success'] = 0;
		}
		return $re_msg;
	}

	// 更新排班信息
	public function upClass($value='')
	{
		$class = new \app\admin\model\ClassTime;
		$re_msg['status'] = 0;
		$doctor_id = DB::name("z_doctor")->where('staff_code',$value['DOCTOR_CODE'])->find("id"); 
		$data['doctor_id'] = $doctor_id?$doctor_id:0;
		$que_id = DB::name("serque")->where("InterfaceID",$value['QUE_CODE'])->value("QueId");		
		$data['que_id']    = $que_id ? $que_id:0;
		$calss 			   = explode(',', $value['SECHEDUAL_DATE']);
		$data['class'] 	   = $class->binDecClass($calss);
		$id = DB::name("z_doctor_class")->where('original_id',$value['ORIGINAL_ID'])->find(); 
		if($id){
			$rs = DB::name("z_doctor_class")->where('original_id',$value['ORIGINAL_ID'])->update($data); 
			if($rs!==false){
				$re_msg['status'] = 1;
				$re_msg['msg'] = '更新成功';
			}else{
				$re_msg['msg'] = '更新失败';
			}
		}else{
			$data['original_id'] = $value['ORIGINAL_ID'];
			$rs = DB::name("z_doctor_class")->insert($data); 
			if($rs){
				$re_msg['status'] = 1;
				$re_msg['msg'] = '添加成功';
			}else{
				$re_msg['msg'] = '添加失败';
			}
		}
		return $re_msg;
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
		$re_msg['CODE']		= '';
		$re_msg['MESSAGE'] 	= '数据格式错误';
		$re_msg['DETAIL'] 	= '';

		$arr 	= json_decode($xml,1);
		$type 	= 'json';
		if(empty($arr)){
			$arr 	= $this->xmlToArray($xml);
			$type 	= 'xml';
		}

		if($arr){
			$meta = $arr['BODY']['META'];
			if(trim($meta['TOPIC_ID'])=='A203' && trim($meta['APP_ID'])=='JQ_HIS'){
				if(!isset($arr['BODY']['ROWS']['ROW'][0])){				
					$result[] = $arr['BODY']['ROWS']['ROW'];	
				}else{
					$result = $arr['BODY']['ROWS']['ROW'];	
				}		
				$rs = $this->saveDoctor($result);
			}else{
				$rs['success'] 	= 0;
				$rs['msg']		= 'TOPIC_ID和APP_ID值无效';
				$rs['data']		= 'TOPIC_ID和APP_ID值无效';
			}
			if($rs['success']==1){
				$re_msg['STATUS'] = 1;
			}
			$re_msg['MESSAGE'] 	= $rs['msg'];
			$re_msg['DETAIL'] 	= $rs['data'];
		}
		if($type=='json'){
			$xmls = json_encode($re_msg);
		}else{
			$xmls = $this->arrayToXml($re_msg);
		}
		return $xmls;
	}
	// 保存医生信息	
	public function saveDoctor($arr=array())
	{
		$re_msg['success']  = 1;
		$re_msg['msg'] 		= '';
		$re_msg['data'] 	= '';

		if(is_array($arr)){
			foreach ($arr as $key => $value) {
				if($value['OPERATION_STATUS']==2){	
					$re_arr['status']  = 0;
					$re_arr['msg'] = '删除失败';
					$rs = DB::name("z_doctor")->where("original_id",$value['ORIGINAL_ID'])->delete();
					if($rs){
						$re_arr['status'] = 1;
						$re_arr['msg'] = '删除成功';
					}
				}else if($value['OPERATION_STATUS']==1){
					$re_arr = $this->upDocotr($value);
				}else{
					$re_arr = $this->addDocotr($value);
				}	
				if($re_arr['status']==0){
					$re_msg['success']  = 0;
				}
				if($re_msg['msg'] != ''){					
					$re_msg['msg']  .= ',';
					$re_msg['data']  .= ',';
				}				
				$re_msg['msg'] 	.= $re_arr['msg'];
				$re_msg['data'] .= $value['ORIGINAL_ID'];	
 			}			
		}else{
			$re_msg['success']  = 0;
		}

		return $re_msg;
	}
	// 添加医生
	public function addDocotr($value=array())
	{
		$re_msg['status']	= 0;
		$re_msg['msg']		= '添加失败';
		$id = 0;
		$id = DB::name("z_doctor")->where("staff_code",$value['SOLELY_ID'])->value("id");
		$unit_id = DB::name("unit")->where("u_code",$value['HOSPITAL_ID'])->value("UnitId");
		$data['unit_id']		= $unit_id ? $unit_id : 0;
		$data['hall_id'] 	  	= 0;
		$data['que_id'] 	  	= '|';
		$data['staff_code'] 	= $value['SOLELY_ID'];
		$data['type'] 			= $value['APPELLATION'];
		$data['doctor_name'] 	= $value['DOCTOR_NAME'];
		$data['QueName'] 		= $value['DOCTOR_NAME'];
		$data['mobile'] 		= $value['TEL'];
		$data['AlternateField1']= $value['INTRO'];
		$data['pic'] 			= $value['PHOTO'];
		$data['sex'] 			= $value['SEX_CODE'];				
		$data['HourSum'] 		= $value['HOUR_SUM'];
		$data['NoChar'] 		= $value['NO_CHAR'];
		$data['StarNo'] 		= $value['START_NO'];
		$data['step'] 			= $value['STEP'];
		$data['status'] 		= $value['STATUS'];
		$data['WorkTime1'] 		= $value['WORKER_GS_TIME'];
		$data['WorkTime2'] 		= $value['WORKER_GE_TIME'];
		$data['WorkTime3'] 		= $value['WORKER_AS_TIME'];
		$data['WorkTime4'] 		= $value['WORKER_AE_TIME'];
		$data['original_id'] 	= $value['ORIGINAL_ID'];
		$data['password'] 		= md5('123456');
		$data['add_time'] 		= time();
		$rs = DB::name("z_doctor")->data($data)->insert();
		if($rs){
			$re_msg['status']	= 1;
			$re_msg['msg']		= '添加成功';
		}
		return $re_msg;
	}
	// 添加医生
	public function upDocotr($value=array())
	{
		$re_msg['status']	= 0;
		$re_msg['msg']		= '更新失败';
		$original_id 		= $value['ORIGINAL_ID'];
		$result = DB::name("z_doctor")->where("original_id",$original_id)->find();
		if(empty($result)){
			$re_msg['msg'] = '数据不存在';
			return $re_msg;
		}
		$id = 0;
		$id = DB::name("z_doctor")->where("staff_code",$value['SOLELY_ID'])->value("id");
		$unit_id = DB::name("unit")->where("u_code",$value['HOSPITAL_ID'])->value("UnitId");
		$data['unit_id']		= $unit_id ? $unit_id : 0;
		$data['hall_id'] 	  	= 0;
		$data['que_id'] 	  	= '|';
		$data['staff_code'] 	= $value['SOLELY_ID'];
		$data['type'] 			= $value['APPELLATION'];
		$data['doctor_name'] 	= $value['DOCTOR_NAME'];
		$data['QueName'] 		= $value['DOCTOR_NAME'];
		$data['mobile'] 		= $value['TEL'];
		$data['AlternateField1']= $value['INTRO'];
		$data['pic'] 			= $value['PHOTO'];
		$data['sex'] 			= $value['SEX_CODE'];				
		$data['HourSum'] 		= $value['HOUR_SUM'];
		$data['NoChar'] 		= $value['NO_CHAR'];
		$data['StarNo'] 		= $value['START_NO'];
		$data['step'] 			= $value['STEP'];
		$data['status'] 		= $value['STATUS'];
		$data['WorkTime1'] 		= $value['WORKER_GS_TIME'];
		$data['WorkTime2'] 		= $value['WORKER_GE_TIME'];
		$data['WorkTime3'] 		= $value['WORKER_AS_TIME'];
		$data['WorkTime4'] 		= $value['WORKER_AE_TIME'];
		$rs = DB::name("z_doctor")->where("original_id",$original_id)->update($data);
		if($rs!==false){
			$re_msg['status']	= 1;
			$re_msg['msg']		= '更新成功';
		}
		return $re_msg;
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
				
				if($value['operation']==0){
					$data['original_id'] 	= $value['original_id'];
					$data['password'] 		= md5('123456');
					$data['add_time'] 		= time();
					$rs = DB::name("z_doctor")->data($data)->insert();
					if($rs){
						$number += 1;
						$lmsg = '添加成功';
					}else{
						$lmsg = '添加失败';
					}
				}else if($value['operation']==1){
					$rs = DB::name("z_doctor")->where("original_id",$value['original_id'])->update($data);
					if($rs!==false){
						$number++;
						$lmsg = '更新成功';
					}else{
						$lmsg = '更新失败';
					}
				}else if($value['operation']==2 && $value['original_id']){
					$rs = DB::name("z_doctor")->where("original_id",$value['original_id'])->delete();
					if($rs){
						$number++;
						$lmsg = '删除成功';
					}else{
						$lmsg = '删除失败';
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

	public function arrayToXml($arr=array())
    {
    	if(!is_array($arr) || count($arr) <= 0){
		    return false;
		}
        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<MESSAGE>';
        $xml .= '<HEADER>';
        if($arr['STATUS']==1){
        	$xml .= '<STATUS>1</STATUS>';
        }else{        	
        	$xml .= '<STATUS>0</STATUS>';
        	$xml .= '<BODY>';
	        foreach ($arr as $key=>$val)
	        {
	        	if($key=='STATUS'){
	        		continue;
	        	}
	            if (is_numeric($val)){
	                $xml.="<".$key.">".$val."</".$key.">";
	            }else{
	                 $xml.="<".$key.">".$val."</".$key.">";
	                 // $xml.="<".$key."><![CDATA[".$val."]]></".$key.">";
	            }
	        }
	        $xml .= '</BODY>';
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
