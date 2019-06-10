<?php
namespace app\tool\controller;

use think\View;
use think\Controller;
use think\facade\Request;
use think\Db;
use think\facade\Log;

class Encryption extends Controller
{
	/*
	 * 简单加密
	 */
	public function enSimple($value='')
	{
		$filename = 'G:\PHPCUSTOM\wwwroot\oral\application\tool\controller\Tool.php';
	    $type = strtolower(substr(strrchr($filename,'.'),1));  

	    // 如果是PHP文件 并且可写 则进行压缩编码  
	    if ('php' == $type && is_file($filename) && is_writable($filename)) { 
	         $contents = file_get_contents($filename); // 判断文件是否已经被编码处理  
	         $contents = php_strip_whitespace($filename);   

	         // 去除PHP头部和尾部标识  
	         $headerPos = strpos($contents,'<?php');  
	         $footerPos = strrpos($contents,'?>');  
	         $contents = substr($contents, $headerPos + 5);  
	         // $contents = substr($contents, $headerPos + 5, $footerPos - $headerPos); 
	         $encode = base64_encode(gzdeflate($contents)); // 开始编码  
	         $encode = '<?php'."\n eval(gzinflate(base64_decode("."'".$encode."'".")));\n\n?>";
	         return file_put_contents($filename.'3.php', $encode);  
	    }  
	    return false;  
	}

	/*
	 * 复杂加密
	 */
	public function enComplex($value='')
	{
		$filename = 'G:\PHPCUSTOM\wwwroot\oral\application\tool\controller\Tool.php'; //要加密的文件  
		$T_k1 	= $this->RandAbc(); //随机密匙1  
		$T_k2 	= $this->RandAbc(); //随机密匙2  
		$vstr 	= file_get_contents($filename);  
		$v1 	= base64_encode($vstr);  
		$c 		= strtr($v1, $T_k1, $T_k2); //根据密匙替换对应字符。 
		$c 		= $T_k1.$T_k2.$c;  
		$q1 	= "O00O0O";  
		$q2 	= "O0O000";  
		$q3 	= "O0OO00";  
		$q4 	= "OO0O00";  
		$q5 	= "OO0000";  
		$q6 	= "O00OO0";  
		$s = '$'.$q6.'=urldecode("%6E1%7A%62%2F%6D%615%5C%76%740%6928%2D%70%78%75%71%79%2A6%6C%72%6B%64%679%5F%65%68%63%73%77%6F4%2B%6637%6A");$'.$q1.'=$'.$q6.'{3}.$'.$q6.'{6}.$'.$q6.'{33}.$'.$q6.'{30};$'.$q3.'=$'.$q6.'{33}.$'.$q6.'{10}.$'.$q6.'{24}.$'.$q6.'{10}.$'.$q6.'{24};$'.$q4.'=$'.$q3.'{0}.$'.$q6.'{18}.$'.$q6.'{3}.$'.$q3.'{0}.$'.$q3.'{1}.$'.$q6.'{24};$'.$q5.'=$'.$q6.'{7}.$'.$q6.'{13};$'.$q1.'.=$'.$q6.'{22}.$'.$q6.'{36}.$'.$q6.'{29}.$'.$q6.'{26}.$'.$q6.'{30}.$'.$q6.'{32}.$'.$q6.'{35}.$'.$q6.'{26}.$'.$q6.'{30};eval($'.$q1.'("'.base64_encode('$'.$q2.'="'.$c.'";eval(\'?>\'.$'.$q1.'($'.$q3.'($'.$q4.'($'.$q2.',$'.$q5.'*2),$'.$q4.'($'.$q2.',$'.$q5.',$'.$q5.'),$'.$q4.'($'.$q2.',0,$'.$q5.'))));').'"));';  
		 
		$s = '<?php '."\n".$s."\n".' ?>';  
		// 生成 加密后的PHP文件  
		$fpp1 = fopen($filename.'.php', 'w');  
		fwrite($fpp1, $s) or die('写文件错误');  
	}

	/*
	 * 返回随机字符串
	 */
	function RandAbc($length = "") 
	{
	    $str = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";  
	    return str_shuffle($str);  
	}
}