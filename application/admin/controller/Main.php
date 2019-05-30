<?php
namespace app\admin\controller;

use think\View;
use think\Controller;

class Main extends Base
{
    public function index()
    {            	
        return $this->fetch('login/notauth');
    }
}