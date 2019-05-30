<?php
namespace app\worker\controller;

use think\View;
use think\Controller;
use think\facade\Env;
use think\facade\Session;

class Start extends Controller
{
	// 启动通信服务
	public function index()
	{
		$soc = new \app\admin\model\Socket;
		$result = $soc->terminalSocke('all','ping'); 
		if($result['success']==0)
		{
			exec("php ". Env::get("root_path")."think worker:server -d",$arr,$return_val);		
		}else{
			echo '服务已启动,可以关闭此页面';
		}
	}
}