<?php
namespace app\api\controller\v2;

use think\Controller;
use think\facade\Env;
use app\api\model\UserInfo;

class Wsdl 
{
	public function create()
	{
        new \app\api\model\UserInfo;
        $disc = new \app\api\model\SoapDiscovery('UserInfo','HB');
        $disc->getWSDL();
    }

	public function server()
	{
		$this->create();
		// define('WSDL_URL',Env::get('ROOT_PATH')."public/UserInfo.wsdl");		        
		$url = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];
		// 创建 SoapServer 对象
		// $parameter = array(
		// 	'uri' => $url.'/api/v2/wsdl/server',
		// );
		// $s = new \SoapServer(null,$parameter);
        ##此处的Service.wsdl文件是上面生成的
        $url = $url.'/UserInfo.wsdl';
        $s = new \SoapServer($url);
		// 导出 SiteInfo 类中的全部函数
		$s->setClass(UserInfo::class);
		// 处理一个SOAP请求，调用必要的功能，并发送回一个响应。
		$s->handle();
	}
	
	public function saveDoctor()
	{
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<MESSAGE>
				  <BODY>
				    <META>
				      <TOPIC_ID> A203</TOPIC_ID>
				      <APP_ID> JQ_HIS </APP_ID>                   
				    </META>
					<ROWS>
					    <ROW>
					       <HOSPITAL_ID>1</HOSPITAL_ID>
					        <HOSPITAL_NAME>1</HOSPITAL_NAME>
					        <HALL_CODE>1</HALL_CODE>
					        <HALL_NAME>1</HALL_NAME>
					        <DOCTOR_CODE>1</DOCTOR_CODE>
					        <DOCTOR_NAME>1</DOCTOR_NAME>
					        <SOLELY_ID>1</SOLELY_ID>
					        <QUE_CODE>1</QUE_CODE>
					        <QUE_NAME>1</QUE_NAME>
					        <APPELLATION>1</APPELLATION>
					        <TEL>1</TEL>
					        <INTRO>1</INTRO>
					        <PHOTO>1</PHOTO>
					        <SEX_CODE>1</SEX_CODE>
					        <SEX_NAME>1</SEX_NAME>
					        <HOUR_SUM>1</HOUR_SUM>
					        <NO_CHAR>1</NO_CHAR>
					        <START_NO>1</START_NO>
					        <STEP>1</STEP>
					        <WORKER_GS_TIME>1</WORKER_GS_TIME>
					        <WORKER_GE_TIME>1</WORKER_GE_TIME>
					        <WORKER_AS_TIME>1</WORKER_AS_TIME>
					        <WORKER_AE_TIME>1</WORKER_AE_TIME>
					        <STATUS>1</STATUS>
					        <OPERATION_STATUS>2</OPERATION_STATUS>
					        <ORIGINAL_ID>1</ORIGINAL_ID>
					    </ROW>
					    <ROW>
					       <HOSPITAL_ID>1</HOSPITAL_ID>
					        <HOSPITAL_NAME>1</HOSPITAL_NAME>
					        <HALL_CODE>1</HALL_CODE>
					        <HALL_NAME>1</HALL_NAME>
					        <DOCTOR_CODE>1</DOCTOR_CODE>
					        <DOCTOR_NAME>1</DOCTOR_NAME>
					        <SOLELY_ID>1</SOLELY_ID>
					        <QUE_CODE>1</QUE_CODE>
					        <QUE_NAME>1</QUE_NAME>
					        <APPELLATION>1</APPELLATION>
					        <TEL>1</TEL>
					        <INTRO>1</INTRO>
					        <PHOTO>1</PHOTO>
					        <SEX_CODE>1</SEX_CODE>
					        <SEX_NAME>1</SEX_NAME>
					        <HOUR_SUM>1</HOUR_SUM>
					        <NO_CHAR>1</NO_CHAR>
					        <START_NO>1</START_NO>
					        <STEP>1</STEP>
					        <WORKER_GS_TIME>2</WORKER_GS_TIME>
					        <WORKER_GE_TIME>2</WORKER_GE_TIME>
					        <WORKER_AS_TIME>2</WORKER_AS_TIME>
					        <WORKER_AE_TIME>2</WORKER_AE_TIME>
					        <STATUS>1</STATUS>
					        <OPERATION_STATUS>1</OPERATION_STATUS>
					        <ORIGINAL_ID>2</ORIGINAL_ID>
					    </ROW>
					</ROWS>
				  </BODY>
				</MESSAGE>';

		$xml = '{"BODY":{"META":{"TOPIC_ID":" A203","APP_ID":" JQ_HIS "},"ROWS":{"ROW":[{"HOSPITAL_ID":"1","HOSPITAL_NAME":"1","HALL_CODE":"1","HALL_NAME":"1","DOCTOR_CODE":"1","DOCTOR_NAME":"1","SOLELY_ID":"1","QUE_CODE":"1","QUE_NAME":"1","APPELLATION":"1","TEL":"1","INTRO":"1","PHOTO":"1","SEX_CODE":"1","SEX_NAME":"1","HOUR_SUM":"1","NO_CHAR":"1","START_NO":"1","STEP":"1","WORKER_GS_TIME":"1","WORKER_GE_TIME":"1","WORKER_AS_TIME":"1","WORKER_AE_TIME":"1","STATUS":"1","OPERATION_STATUS":"2","ORIGINAL_ID":"1"},{"HOSPITAL_ID":"1","HOSPITAL_NAME":"1","HALL_CODE":"1","HALL_NAME":"1","DOCTOR_CODE":"1","DOCTOR_NAME":"1","SOLELY_ID":"1","QUE_CODE":"1","QUE_NAME":"1","APPELLATION":"1","TEL":"1","INTRO":"1","PHOTO":"1","SEX_CODE":"1","SEX_NAME":"1","HOUR_SUM":"1","NO_CHAR":"1","START_NO":"1","STEP":"1","WORKER_GS_TIME":"2","WORKER_GE_TIME":"2","WORKER_AS_TIME":"2","WORKER_AE_TIME":"2","STATUS":"1","OPERATION_STATUS":"1","ORIGINAL_ID":"2"}]}}}';

		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		$soap = new \app\api\model\UserInfo;
		// 调用函数 	
		$rs = $soap->doctorInfo($xml);
		echo $rs;		
	}

	public function saveClass()
	{
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<MESSAGE>
				  	<BODY>
					    <META>
					      <TOPIC_ID>A204</TOPIC_ID>                        
					      <APP_ID>JQ_HIS</APP_ID>                        
					    </META>
						<ROWS>
						    <ROW>
						        <ORIGINAL_ID>1</ORIGINAL_ID>
						        <HOSPITAL_ID>1</HOSPITAL_ID>
						        <HOSPITAL_NAME>1</HOSPITAL_NAME>
						        <HALL_CODE>1</HALL_CODE>
						        <HALL_NAME>1</HALL_NAME>
						        <DOCTOR_CODE>1</DOCTOR_CODE>
						        <DOCTOR_NAME>1</DOCTOR_NAME>
						        <QUE_CODE>1</QUE_CODE>
						        <QUE_NAME>1</QUE_NAME>
						        <SECHEDUAL_DATE>1,2,3,4,5</SECHEDUAL_DATE>
						        <STATUS>0</STATUS>
						    </ROW>
						    <ROW>
						        <ORIGINAL_ID>2</ORIGINAL_ID>
						        <HOSPITAL_ID>1</HOSPITAL_ID>
						        <HOSPITAL_NAME>1</HOSPITAL_NAME>
						        <HALL_CODE>1</HALL_CODE>
						        <HALL_NAME>1</HALL_NAME>
						        <DOCTOR_CODE>1</DOCTOR_CODE>
						        <DOCTOR_NAME>1</DOCTOR_NAME>
						        <QUE_CODE>1</QUE_CODE>
						        <QUE_NAME>1</QUE_NAME>
						        <SECHEDUAL_DATE>1,2,3,4,5</SECHEDUAL_DATE>
						        <STATUS>1</STATUS>
						    </ROW>
						</ROWS>
				  	</BODY>
				</MESSAGE>';

		$xml = '{"BODY":{"META":{"TOPIC_ID":"A204","APP_ID":"JQ_HIS"},"ROWS":{"ROW":[{"ORIGINAL_ID":"1","HOSPITAL_ID":"1","HOSPITAL_NAME":"1","HALL_CODE":"1","HALL_NAME":"1","DOCTOR_CODE":"1","DOCTOR_NAME":"1","QUE_CODE":"1","QUE_NAME":"1","SECHEDUAL_DATE":"1,2,3,4,5","STATUS":"1"},{"ORIGINAL_ID":"2","HOSPITAL_ID":"1","HOSPITAL_NAME":"1","HALL_CODE":"1","HALL_NAME":"1","DOCTOR_CODE":"1","DOCTOR_NAME":"1","QUE_CODE":"1","QUE_NAME":"1","SECHEDUAL_DATE":"1,2,3,4,5","STATUS":"1"}]}}}';
		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		// $soap = new \app\api\model\UserInfo;
		  // 调用函数
		$rs = $soap->doctorClass($xml);
		echo $rs;		
	}

	public function savePatient()
	{		
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<MESSAGE>
				  <BODY>
				    <META>
				      <TOPIC_ID>A201</TOPIC_ID>
				      <APP_ID>JQ_HIS</APP_ID>                   
				    </META>
					<ROWS>
					    <ROW>
					       	<HOSPITAL_ID>1</HOSPITAL_ID>
					        <HOSPITAL_NAME>1</HOSPITAL_NAME>
					        <HALL_CODE>1</HALL_CODE>
					        <HALL_NAME>1</HALL_NAME>
					        <QUE_CODE>1</QUE_CODE>
					        <QUE_NAME>1</QUE_NAME>
					        <DOCTOR_CODE>1</DOCTOR_CODE>
					        <DOCTOR_NAME>1</DOCTOR_NAME>
					        <ORIGINAL_ID>1</ORIGINAL_ID>
					        <CARD_NO>1</CARD_NO>
					        <IDCARD>1</IDCARD>
					        <PREFIX>1</PREFIX>
					        <QUE_NUM>1</QUE_NUM>
					        <PATIENT_ID>1</PATIENT_ID>
					        <LOCAL_ID>1</LOCAL_ID>
					        <NAME>1</NAME>
					        <TEL>1</TEL>
					        <SEX_CODE>1</SEX_CODE>
					        <SEX_NAME>1</SEX_NAME>
					        <BIRTHDAY>1</BIRTHDAY>
        					<OPERATION_STATUS>2</OPERATION_STATUS>
        					<ORDERS>1</ORDERS>
        					<SIGN_IN>1</SIGN_IN>
					        <STATUS>1</STATUS>
					        <ORDERS>1</ORDERS>
					        <SIGN_IN>1</SIGN_IN>
					        <FETCH_STATUS>1</FETCH_STATUS>
					        <SD_DATE>2019-06-169</SD_DATE>
					        <QH_DATE>2019-06-169 09:30:11</QH_DATE>
					        <START_DATE>2019-06-169 09:30:11</START_DATE>
					        <END_DATE>2019-06-169 09:30:11</END_DATE>
					    </ROW>
					</ROWS>
				  </BODY>
				</MESSAGE>';

		$xml = '{"BODY":{"META":{"TOPIC_ID":"A201","APP_ID":"JQ_HIS"},"ROWS":{"ROW":{"HOSPITAL_ID":"1","HOSPITAL_NAME":"1","HALL_CODE":"1","HALL_NAME":"1","QUE_CODE":"1","QUE_NAME":"1","DOCTOR_CODE":"1","DOCTOR_NAME":"1","ORIGINAL_ID":"1","CARD_NO":"1","IDCARD":"1","PREFIX":"1","QUE_NUM":"1","PATIENT_ID":"1","LOCAL_ID":"1","NAME":"1","TEL":"1","SEX_CODE":"1","SEX_NAME":"1","BIRTHDAY":"1","OPERATION_STATUS":"0","ORDERS":["1","1"],"SIGN_IN":["1","1"],"STATUS":"1","FETCH_STATUS":"1","SD_DATE":"2019-06-169","QH_DATE":"2019-06-169 09:30:11","START_DATE":"2019-06-169 09:30:11","END_DATE":"2019-06-169 09:30:11"}}}}';

		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		// $soap = new \app\api\model\UserInfo;
		// 调用函数 	
		$rs = $soap->patient($xml);
		echo $rs;		
	}

	/*
	 * ============================================================
	 * ========================请求类型  http ======================
	 * ============================================================
	 */

	// 增加医生
	public function httpSaveDoctor()
	{
		$str = input("str",'');
		$http = new \app\api\model\UserInfo;
		$rs = $http->doctorInfo($str);
		echo $rs;
	}
	// 增加排班
	public function httpSaveClass()
	{
		$str = input("str",'');
		$http = new \app\api\model\UserInfo;
		$rs = $http->doctorClass($str);
		echo $rs;
	}
	// 增加患者
	public function httpSavePatient()
	{
		$str = input("str",'');
		$http = new \app\api\model\UserInfo;
		$rs = $http->patient($str);
		echo $rs;
	}
}