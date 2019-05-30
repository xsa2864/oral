<?php
namespace app\app\controller;

use think\Controller;

class Common extends Controller
{
    public function initialize()
    {
    	// $rs = array_map('unlink', glob(TEMP_PATH . '/*.php'));
    	
    	// if(is_dir(TEMP_PATH) && $rs){
    	// 	rmdir(TEMP_PATH);
    	// }
    	// print_r(TEMP_PATH);
        
        // return $this->fetch('public');
    }
}
