<?php
namespace app\api\controller\v2;

use think\Controller;
use app\api\model\UserInfo;
use think\facade\Env;
use think\Db;

class Wsdl 
{
	private $wsdl = 'UserInfo.wsdl';

	// 生成wsdl文件
	public function create()
	{
		new \app\api\model\UserInfo;
		$disc = new \app\api\model\SoapDiscovery('UserInfo','myService');
		$disc->getWSDL();
	}

	public function Service()
	{		
		// if(!file_exists($this->wsdl)){
		// }
		$this->create();
		##此处的Service.wsdl文件是上面生成的
		$server = new \SoapServer("http://oral.com/UserInfo.wsdl"); 	
		$server->setClass(UserInfo::class); //注册Service类的所有方法 
		$server->handle(); //处理请求
	}

	public function test()
	{
		ini_set('soap.wsdl_cache_enabled', "0"); //关闭wsdl缓存

	    // $soap = new \SoapClient('http://oral.com/api/v2/wsdl/Service?wsdl');
	    // $soap = new \SoapClient('http://localhost/service/cometrue.php?wsdl');
	    $soap = new \SoapClient('http://oral.com/UserInfo.wsdl');

	    // echo $soap->strtolink('http://www.baidu.com')."<br/>";
	    echo $soap->getUrl()."<br/>";
	    echo $soap->add(30,100)."==<br/>";
	    // echo $soap->__soapCall('add',array(28,200))."<br/>";
	    // //或这样调用
	    // echo $soap->__Call('add',array(28,300))."<br/>";
	    echo date('Y-m-d H:i:s', time());
	}
}