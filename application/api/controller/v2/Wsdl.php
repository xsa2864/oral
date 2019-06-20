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

	public function show()
	{		
		echo 'ok';
	}

	public function setDoctor()
	{
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<MESSAGE>
				  <BODY>
				    <META>
				      <TOPIC_ID>A001</TOPIC_ID>
				      <APP_ID>EMR</APP_ID>
				    </META>
				    <ROWS>
				      <ROW>
				       	<unit_id>1</unit_id>
				       	<unit_name>口腔医院</unit_name>
				       	<hall_id>1</hall_id>
				       	<hall_name>一楼区域</hall_name>
				       	<que_id>1</que_id>	
						<que_name>队列</que_name>
						<staff_code>100001</staff_code>
						<doctor_name>刘医</doctor_name>
						<type>广告</type>
						<mobile>17095999878</mobile>
						<introduce>哈哈哈哈哈</introduce>
						<pic>http://wwww.baidu.com</pic>
						<sex>1</sex>
						<hour_sum>4</hour_sum>
						<no_char>Y</no_char>
						<star_no>1000</star_no>
						<step>1</step>
						<status>1</status>
						<worker_gs_time>09:00:00</worker_gs_time>
						<worker_ge_time>12:00:00</worker_ge_time>
						<worker_as_time>14:00:00</worker_as_time>
						<worker_ae_time>18:00:00</worker_ae_time>
				      </ROW>
				    </ROWS>
				  </BODY>
				</MESSAGE>';		

		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		$soap = new \app\api\model\UserInfo;
		  // 调用函数 
	
		// echo $soap->getName()."<br>";		
  		echo '<pre>';
		$rs = $soap->doctorInfo($xml);
		echo $rs;						
	}

	// 患者信息
	public function setPatient()
	{
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<MESSAGE>
				  <BODY>
				    <META>
				      <TOPIC_ID>A001</TOPIC_ID>
				      <APP_ID>EMR</APP_ID>
				    </META>
				    <ROWS>
				      <ROW>
				       	<unit_id>1</unit_id>
				       	<unit_name>口腔医院</unit_name>
				       	<hall_id>1</hall_id>
				       	<hall_name>一楼区域</hall_name>
				       	<que_id>1</que_id>	
						<que_name>队列</que_name>
						<staff_code>1</staff_code>
						<doctor_name>刘医生</doctor_name>
						<original_id>2234</original_id>
						<card_no>11112345</card_no>
						<idcard>1111113124</idcard>
						<prefix>A</prefix>
						<code>111</code>
						<name>张三</name>
						<mobile>17095989123</mobile>
						<sex>1</sex>
						<birth>2011-10-03</birth>
						<operation>0</operation>
						<fetch_status>1</fetch_status>
						<date>2019-06-19</date>
						<stime>17:00:00</stime>
						<etime>18:00:00</etime>
				      </ROW>				      
				    </ROWS>
				  </BODY>
				</MESSAGE>';		

		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		$soap = new \app\api\model\UserInfo;
		// 调用函数 
  		echo '<pre>';
		$rs = $soap->patient($xml);
		echo $rs;						
	}
	public function setClass()
	{
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存
		$xml = '<?xml version="1.0" encoding="UTF-8"?>
				<MESSAGE>
				  <BODY>
				    <META>
				      <TOPIC_ID>A001</TOPIC_ID>
				      <APP_ID>EMR</APP_ID>
				    </META>
				    <ROWS>
				      <ROW>
				       	<unit_id>1</unit_id>
				       	<unit_name>口腔医院</unit_name>
				       	<hall_id>1</hall_id>
				       	<hall_name>一楼区域</hall_name>
				       	<que_id>002</que_id>	
						<que_name>队列</que_name>
						<doctor_name>刘医生</doctor_name>
						<staff_code>10802</staff_code>
						<status>0</status>
						<date>0,1,2,3,6,7,8,13</date>
				      </ROW>	
				      <ROW>
				       	<unit_id>1</unit_id>
				       	<unit_name>口腔医院</unit_name>
				       	<hall_id>1</hall_id>
				       	<hall_name>一楼区域</hall_name>
				       	<que_id>002</que_id>	
						<que_name>队列</que_name>
						<doctor_name>刘医生</doctor_name>
						<staff_code>6666</staff_code>
						<status>0</status>
						<date>0,1,2,3,4,5,6,7,8,13</date>
				      </ROW>	
				    </ROWS>
				  </BODY>
				</MESSAGE>';		

		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		// $soap = new \app\api\model\UserInfo;
		// 调用函数 
		$rs = $soap->doctorClass($xml);
		echo $rs;
	}
}