<?php
namespace app\pavilion\controller;

use think\Controller;
use think\Db;
use think\facade\Cookie;
use think\facade\Request;
use think\facade\View;
use think\facade\Env;
use think\facade\Config;


class Catalog extends Controller
{
	public function index()
    {
        $show = input("show",0);
        if(request()->ip()=='192.168.0.239'||request()->ip()=='192.168.0.133' || true){        
            $screen = [
                ['op'=>url('pavilion/display/roomTone'),'title'=>'诊室屏(横)'],
                ['op'=>url('pavilion/display/roomVone'),'title'=>'诊室屏(竖)'],
                ['op'=>url('pavilion/display/hallTone'),'title'=>'综合显示屏(横)'],
                ['op'=>url('pavilion/display/hallVone'),'title'=>'综合显示屏(竖)'],
                ['op'=>url('pavilion/display/operationTone'),'title'=>'手术显示屏'],
            ];
            $download = [
                ['op'=>'/uploads/video/app.apk','title'=>'浏览窗口apk'],
                ['op'=>'/uploads/video/MIPS_DS_Basic_FREE_V4.0.0.apk','title'=>'信息发布apk'],
                ['op'=>'/uploads/video/webclient.rar','title'=>'PC客户端'],
                ['op'=>'/uploads/video/CLodop_Setup_for_Win32NT_https_3.080Extend.zip','title'=>'下载打印驱动'],
            ];
            $admin = [
                ['op'=>url('admin/login/index'),'title'=>'护士站'],
            ];
            $ter = [
                ['op'=>url('pavilion/login/index'),'title'=>'呼叫器'],
            ];
            $fetch = [
                ['op'=>url('pavilion/fetch/showCard'),'title'=>'取票端'],
            ];
            $class = [
                ['op'=>url('pavilion/schedule/showClass'),'title'=>'排班情况'],
            ];
            $code = [
                ['op'=>url('tool/token/getToken'),'title'=>'生成激活码'],
            ];
            $app = [
                ['op'=>'../../app/index/indexs','title'=>'预约通道'],
            ];
            $socket = [
                ['op'=>'/oral/start.php','title'=>'启动通信'],
            ];
            $api = [
                ['op'=>url('pavilion/catalog/test'),'title'=>'api接口']
            ];
            $list = [
                ['show'=>1,'op'=>'','logo'=>'&#xe629;','title'=>'显示屏','data'=>$screen],
                ['show'=>1,'op'=>'','logo'=>'&#xe665;','title'=>'护士站','data'=>$admin],
                ['show'=>1,'op'=>'','logo'=>'&#xe770;','title'=>'呼叫器','data'=>$ter],
                ['show'=>1,'op'=>'','logo'=>'&#xe638;','title'=>'取票端','data'=>$fetch],
                ['show'=>1,'op'=>'','logo'=>'&#xe663;','title'=>'排班情况','data'=>$class],
                ['show'=>1,'op'=>'','logo'=>'&#xe653;','title'=>'生成激活码','data'=>$code],                
                ['show'=>1,'op'=>'','logo'=>'&#xe601;','title'=>'下载','data'=>$download],
                ['show'=>1,'op'=>'','logo'=>'&#xe63c;','title'=>'预约通道','data'=>$app],
                ['show'=>1,'op'=>'','logo'=>'&#xe64c;','title'=>'api接口','data'=>$api],
                ['show'=>1,'op'=>'','logo'=>'&#xe628;','title'=>'启动通信','data'=>$socket],
            ];

            $this->assign("list",$list);
            $root = $show?$_SERVER['DOCUMENT_ROOT']:'';
            $this->assign("DOCUMENT_ROOT",$root);
            return $this->fetch("index");
        }
    }
    
    public function test()
    {               
        return $this->fetch("test");
    }
	function getLocalIP() {
	 	$preg = "/\A((([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\.){3}(([0-9]?[0-9])|(1[0-9]{2})|(2[0-4][0-9])|(25[0-5]))\Z/";
		//获取操作系统为win2000/xp、win7的本机IP真实地址
	 	exec("ipconfig", $out, $stats);
	 	if (!empty($out)) {
            $flag = false;
            foreach ($out AS $row) {
                if(strstr($row, "VirtualBox")){
                    $flag = true;
                }
                if (strstr($row, "IP") && strstr($row, ":") && !strstr($row, "IPv6")) {
                    if($flag){
                        $flag = false;
                        continue;
                    }

	 	 	 	 	$tmpIp = explode(":", $row);
	 	 	 	 	if (preg_match($preg, trim($tmpIp[1]))) {
	 	 	 	 	 	return trim($tmpIp[1]);
	 	 	 	 	}
	 	 	 	}
	 	 	}
	 	}
		//获取操作系统为linux类型的本机IP真实地址
		exec("ifconfig", $out, $stats);
		if (!empty($out)) {
		  	if (isset($out[1]) && strstr($out[1], 'addr:')) {
		  	 	$tmpArray = explode(":", $out[1]);
		  	 	$tmpIp = explode(" ", $tmpArray[1]);
		  	 	if (preg_match($preg, trim($tmpIp[0]))) {
		  	 	 	return trim($tmpIp[0]);
		  	 	}
		  	}
		}
		return '127.0.0.1';
	} 

}