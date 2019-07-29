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
				if($value['OPERATION_STATUS']==1){
					$re_arr = $this->addDespeak($value);
				}else{
					$re_arr['status']  = 0;
					$re_arr['msg'] = '删除失败';
					$rs = DB::name("despeak")->where('original_id',$value['ORIGINAL_ID'])->delete();
					if($rs){
						$re_arr['status'] = 1;
						$re_arr['msg'] = '删除成功';
					}				
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
		$re_msg['msg']		= '';

		// $validate = new \app\api\validate\Patient;
  //       if (!$validate->check($value)) {
  //           $re_msg['msg'] = $validate->getError();
		// 	return $re_msg;
  //       }

        if(!empty($value['IDCARD']) && !preg_match('/(^[1-9]\d{5}(18|19|([23]\d))\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{3}[0-9Xx]$)|(^[1-9]\d{5}\d{2}((0[1-9])|(10|11|12))(([0-2][1-9])|10|20|30|31)\d{2}$)/', $value['IDCARD']))
		{
			$re_msg['msg']		.= 'IDCARD 身份证号错误,';
		}
		if(!empty($value['TEL']) && !preg_match('/^1[3-9][0-9]\d{8}$/', $value['TEL']))
		{
			$re_msg['msg']		.= 'TEL 手机号错误,';
		}

		if(empty($value['DOCTOR_NAME'])){
			$re_msg['msg']		.= 'DOCTOR_NAME 医生姓名不能为空,';
		}
		if(!in_array($value['SEX_CODE'], [0,1,2]) || !is_numeric($value['SEX_CODE'])){
			$re_msg['msg']		.= 'SEX_CODE 性别代码在0,1,2之间,';
		}

		if(!empty($value['SD_DATE']) && !preg_match('/^[\d]{4}-[\d]{1,2}-[\d]{1,2}$/', $value['SD_DATE'])){
			$re_msg['msg']		.= 'SD_DATE 日期格式错误,';
		}
		if(!empty($value['START_DATE']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $value['START_DATE'])){
			$re_msg['msg']		.= 'START_DATE 时间格式错误,';
		}		
		if(!empty($value['END_DATE']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $value['END_DATE'])){
			$re_msg['msg']		.= 'END_DATE 时间格式错误,';
		}

		$id = DB::name("despeak")->where('original_id',$value['PATIENT_ID'])->value("despeak_id");
		if($id){
			$re_msg['msg'] .= '该患者信息已经存在,';
		}
		// $data['unitId'] 	  	 = $value['HOSPITAL_ID'];
		// $data['hallNo'] 	  	 = $value['HALL_CODE'];
		// $data['queId'] 	  	 	 = $value['QUE_CODE'];
		$data['unitName']  	 	 = $value['HOSPITAL_NAME'];
		$data['hallName']  	 	 = $value['HALL_NAME'];				
		$data['queName']   	 	 = $value['QUE_NAME'];
		$data['data_status'] 	 = 0;
		$rs = DB::name("serque")->field("QueId,HallNo,UnitId,QueName")->where("InterfaceID",$value['QUE_CODE'])->find();
		if($rs){
			$data['data_status'] = 1;
			$data['unitId'] 	 = $rs['UnitId'];
			$data['hallNo'] 	 = $rs['HallNo'];
			$data['queId'] 	 	 = $rs['QueId'];		
			$data['queName']   	 = $rs['QueName'];
		}else{
			$re_msg['msg'] .= 'QUE_CODE 队列信息不存在,';
		}
		$doctor_id = DB::name("z_doctor")->where("original_id",$value['DOCTOR_CODE'])->value("id");
		if(empty($doctor_id)){
			$re_msg['msg'] .= '该医生不存在,';
		}

		if(empty($value['ORIGINAL_ID']) || !is_numeric($value['ORIGINAL_ID'])){
			$re_msg['msg'] .= 'ORIGINAL_ID 只能为整数值,';
		}

		if(!empty($re_msg['msg'])){
			return $re_msg;
		}

		$data['card_no']		= $value['CARD_NO'];
		$data['doctor_id']		= $doctor_id;
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
		$data['despeakTime'] 	= strtotime($value['SD_DATE'].' '.$value['START_DATE']);
		$data['time_Part_S'] 	= $value['START_DATE'];
		$data['time_Part_O'] 	= $value['END_DATE'];
		$data['inTime'] 	 	= date("Y-m-d H:i:s",time());
		$data['addtime'] 	 	= time();

		$id = DB::name("despeak")->where('original_id',$value['ORIGINAL_ID'])->value("despeak_id");
		if(empty($id)){
			$data['original_id']	= $value['ORIGINAL_ID'];
			$rs = Db::name("despeak")->insertGetId($data);
			if($rs){
				$re_msg['status']	= 1;
				$re_msg['msg']		= '添加成功';
				if($value['FETCH_STATUS']==1)
				{
					if($data['data_status']==1){
						$rel = new \app\admin\model\Relations;	
						$ot = $rel->orderTicket($rs,$data['hallNo']);			
						$re_msg['msg'] .= "--".$ot['msg'];
					}
				}
			}else{
				$re_msg['msg']		= '添加失败';
			}			
		}else{
			$rs = DB::name("despeak")->where('despeak_id',$id)->update($data);
			if($rs!==false){
				$re_msg['status']	= 1;
				$re_msg['msg']		= '更新成功';
				if($value['FETCH_STATUS']==1)
				{
					if($data['data_status']==1){
						$rel = new \app\admin\model\Relations;	
						$ot = $rel->orderTicket($id,$data['hallNo']);
						$re_msg['msg'] .= "--".$ot['msg'];
					}
				}
			}else{
				$re_msg['msg']		= '更新失败';				
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
				if($value['OPERATION_STATUS']==0){
					$re_arr['status'] 	= 0;
					$re_arr['msg'] 		= '删除失败';
					if(intval($value['ORIGINAL_ID'])>0){						
						$del[] = ['original_id','=',intval($value['ORIGINAL_ID'])];
						$rs = DB::name("z_doctor_class")->where($del)->delete();
						if($rs){
							$re_arr['status'] 	= 1;
							$re_arr['msg'] 		= '删除成功';
						}
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
		$re_msg['status'] = 0;
		$re_msg['msg'] = '';
		$doctor_id = DB::name("z_doctor")->where('original_id',$value['DOCTOR_CODE'])->value("id");
		if(empty($doctor_id)){
			$re_msg['msg'] .= 'DOCTOR_CODE 医生数据不存在,';
		}
		$que_id = DB::name("serque")->where("InterfaceID",$value['QUE_CODE'])->value("QueId");	
		if(empty($que_id)){
			$re_msg['msg'] .= 'QUE_CODE 队列数据不存在,';
		}	
		// $where[] = ['que_id','=',$que_id];
		// $where[] = ['doctor_id','=',$doctor_id];	
		// $ser = DB::name("z_doctor_class")->where($where)->find();	
		// if($ser){
		// 	$re_msg['msg'] .= '排班已经存在,';
		// }

		if(!empty($re_msg['msg'])){
			return $re_msg;
		}
		$class = new \app\admin\model\ClassTime;
		$data['doctor_id'] = $doctor_id;
		$data['que_id']    = $que_id;
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
				if($value['OPERATION_STATUS']==1){	
					$re_arr = $this->addDocotr($value);
				}else{
					$re_arr['status']  = 0;
					$re_arr['msg'] = '删除失败';
					if(intval($value['ORIGINAL_ID'])>0){		
						$rs = DB::name("z_doctor")->where("original_id",intval($value['ORIGINAL_ID']))->delete();
						if($rs){
							$re_arr['status'] = 1;
							$re_arr['msg'] = '删除成功';
						}
					}
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
		$re_msg['msg']		= '';

		// $validate = new \app\api\validate\Doctor;
  //       if (!$validate->check($value)) {
  //           $re_msg['msg'] = $validate->getError();
		// 	return $re_msg;
  //       }

		$unit_id = DB::name("unit")->where("api_code",$value['HOSPITAL_ID'])->value("UnitId");
		if(!$unit_id){
			$re_msg['msg']		.= '单位不存在,';
		}

		if(!preg_match('/^1[3-9][0-9]\d{8}$/', $value['TEL']))
		{
			$re_msg['msg']		.= 'TEL 手机号错误,';
		}

		if(empty($value['DOCTOR_NAME'])){
			$re_msg['msg']		.= 'DOCTOR_NAME 医生姓名不能为空,';
		}
		if(!in_array($value['SEX_CODE'], [0,1,2]) || !is_numeric($value['SEX_CODE'])){
			$re_msg['msg']		.= 'SEX_CODE 性别代码在0,1,2之间,';
		}

		if(empty($value['APPELLATION'])){
			$re_msg['msg']		.= 'APPELLATION 职务不能为空,';
		}

		if(!empty($value['WORKER_GS_TIME']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $value['WORKER_GS_TIME'])){
			$re_msg['msg']		.= 'WORKER_GS_TIME 时间格式错误,';
		}
		if(!empty($value['WORKER_GE_TIME']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $value['WORKER_GE_TIME'])){
			$re_msg['msg']		.= 'WORKER_GE_TIME 时间格式错误,';
		}		
		if(!empty($value['WORKER_AS_TIME']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $value['WORKER_AS_TIME'])){
			$re_msg['msg']		.= 'WORKER_AS_TIME 时间格式错误,';
		}
		if(!empty($value['WORKER_AE_TIME']) && !preg_match('/^\d{2}:\d{2}:\d{2}$/', $value['WORKER_AE_TIME'])){
			$re_msg['msg']		.= 'WORKER_AE_TIME 时间格式错误,';
		}
		if(mb_strlen($value['INTRO'])>240){
			$re_msg['msg']		.= 'INTRO 介绍说明最多不能超过240个字符,';
		}
		if(!in_array($value['STATUS'],[0,1]) || !is_numeric($value['SEX_CODE'])){
			$re_msg['msg']		.= 'STATUS 状态在0,1之间,';
		}


		if(!empty($re_msg['msg'])){
			return $re_msg;
		}
		$photo = $this->base64_image_content($value['PHOTO']);
		$data['unit_id']		= $unit_id;
		$data['hall_id'] 	  	= 0;
		$data['que_id'] 	  	= '|';
		$data['staff_code'] 	= $value['SOLELY_ID'];
		$data['type'] 			= $value['APPELLATION'];
		$data['doctor_name'] 	= $value['DOCTOR_NAME'];
		$data['QueName'] 		= $value['DOCTOR_NAME'];
		$data['mobile'] 		= $value['TEL'];
		$data['AlternateField1']= $value['INTRO'];
		$data['pic'] 			= $photo;
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
		$data['password'] 		= md5('123456');
		$data['add_time'] 		= time();

		$result = DB::name("z_doctor")->where("original_id",$value['ORIGINAL_ID'])->find();
		if(empty($result)){
			$zd[] = ["staff_code",'=',$value['SOLELY_ID']];
			$oid = DB::name("z_doctor")->where($zd)->value("original_id");
			if($oid){
				$re_msg['msg']		= '医生工号已经存在';
				return $re_msg;
			}
			$data['original_id'] 	= $value['ORIGINAL_ID'];
			$rs = DB::name("z_doctor")->data($data)->insert();
			if($rs){
				$re_msg['status']	= 1;
				$re_msg['msg']		= '添加成功';
			}else{				
				$re_msg['msg']		= '添加失败';
			}
		}else{			
			$zd[] = ["staff_code",'=',$value['SOLELY_ID']];
			$oid = DB::name("z_doctor")->where($zd)->value("original_id");
			if($oid){
				if($oid!=$value['ORIGINAL_ID']){
					$re_msg['msg']		= '医生工号已经存在!';
					return $re_msg;
				}
			}
			$rs = DB::name("z_doctor")->where("id",$result['id'])->update($data);
			if($rs!==false){
				$re_msg['status']	= 1;
				$re_msg['msg']		= '更新成功';
			}else{
				$re_msg['msg']		= '更新失败';
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

	/**
	 * [将Base64图片转换为本地图片并保存]
	 * @param  [Base64] $base64_image_content [要保存的Base64]
	 * @param  [目录] $path [要保存的路径]
	 */
	public function base64_image_content($base64_image_content,$path=''){

		if(empty($path)){
			$path = $_SERVER['DOCUMENT_ROOT']."/uploads/";
		}
	    //匹配出图片的格式
	    if (preg_match('/^(data:\s*image\/(\w+);base64,)/', $base64_image_content, $result)){
	        $type = $result[2];
	        $pic_url 	=  date('Ymd',time())."/";
	        $new_file 	= $path.$pic_url;
	        if(!file_exists($new_file)){
	            //检查是否有该文件夹，如果没有就创建，并给予最高权限
	            mkdir($new_file, 0700);
	        }
	        $pic_url = $pic_url.time().".{$type}";
	        $new_file = $path.$pic_url;
	        if (file_put_contents($new_file, base64_decode(str_replace($result[1], '', $base64_image_content)))){
	            return $pic_url;
	        }else{
	            return '';
	        }
	    }else{
	        return '';
	    }
	}
}
