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
		$url = 'http://'.$_SERVER['SERVER_NAME'].':'.$_SERVER['SERVER_PORT'];	
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
		$xml = input("str",'');
		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		// $soap = new \app\api\model\UserInfo;
		// 调用函数 	
		$rs = $soap->doctorInfo($xml);
		return $rs;	
	}

	public function saveClass()
	{
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存
		$xml = input("str",'');
		$url = 'http://'.$_SERVER['SERVER_NAME'];
		$url = $url.'/UserInfo.wsdl';
		$soap = new \SoapClient($url);
		$soap = new \app\api\model\UserInfo;
		  // 调用函数
		$rs = $soap->doctorClass($xml);
		return $rs;	
	}

	public function savePatient()
	{		
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存		
		$xml = input("str",'');
		$url = 'http://'.$_SERVER['SERVER_NAME'];	
		$url = $url.'/UserInfo.wsdl';
		
		$soap = new \SoapClient($url);
		// $soap = new \app\api\model\UserInfo;
		// 调用函数 	
		$rs = $soap->patient($xml);
		return $rs;
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
		return $rs;
	}
	// 增加排班
	public function httpSaveClass()
	{
		$str = input("str",'');
		$http = new \app\api\model\UserInfo;
		$rs = $http->doctorClass($str);
		return $rs;
	}
	// 增加患者
	public function httpSavePatient()
	{
		$str = input("str",'');
		$http = new \app\api\model\UserInfo;
		$rs = $http->patient($str);
		return $rs;
	}
	
}