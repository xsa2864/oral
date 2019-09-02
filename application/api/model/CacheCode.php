<?php
namespace app\api\model;

use think\Db;
use think\Model;

class CacheCode 
{	
	public $result   = array(); 
    public $macAddrs = array(); //所有mac地址
    public $macAddr;            //第一个mac地址

    //生成一个编号 
    public function getCode($OS='')
    {
    	$code = $this->getClientMac();
    	if(!$code){    		
    		$code = $this->getMac($OS);
    	}
    	return  $code;
    }

	public function getMac($OS='')
	{
		switch ( strtolower($OS) ){
        	case "unix": break;
        	case "solaris": break;
        	case "aix": break;
        	case "linux":
        	    $this->getLinux();
        	    break;
        	default: 
        	    $this->getWindows();
        	    break;
        }
        $tem = array();
        foreach($this->result as $val){
            if(preg_match("/[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f][:-]"."[0-9a-f][0-9a-f]/i",$val,$tem) ){
                $this->macAddr = $tem[0];//多个网卡时，会返回第一个网卡的mac地址，一般够用。
                break;
                //$this->macAddrs[] = $temp_array[0];//返回所有的mac地址
            }
        }
        return $this->macAddr;
	}

	 //Linux系统
    public function getLinux()
    {
        @exec("ifconfig -a", $this->result);
        return $this->result;
    }

    //Windows系统
    public function getWindows()
    {
        @exec("ipconfig /all", $this->result);
        if ( $this->result ) {
            return $this->result;
        } else {
            $ipconfig = $_SERVER["WINDIR"]."\system32\ipconfig.exe";
            if(is_file($ipconfig)) {
                @exec($ipconfig." /all", $this->result);
            } else {
                @exec($_SERVER["WINDIR"]."\system\ipconfig.exe /all", $this->result);
                return $this->result;
            }
        }
    }
    // 客户端的mac地址
    public function getClientMac()
    {
    	$mac = '';
    	@exec("arp -a",$array); //执行arp -a命令，结果放到数组$array中
        foreach($array as $value){
            //匹配结果放到数组$mac_array
            if(strpos($value,$_SERVER["REMOTE_ADDR"]) && preg_match("/(:?[0-9A-F]{2}[:-]){5}[0-9A-F]{2}/i",$value,$mac_array)){
                $mac = $mac_array[0];
                break;
            }
        }
        $mac = Request()->ip();
        return $mac;
    }

}