<?php
namespace app\api\controller\v2;

use think\Controller;
use think\Request;
use think\Validate;
use think\DB;
use think\facade\Cookie;

class Tests extends Base
{
	// 医生工号、呯叫器ID、当前号码与姓名、三个等候的号码与姓名、
	public function show()
	{
		echo 'ok';
	}
}
