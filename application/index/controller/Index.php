<?php
namespace app\index\controller;

use think\facade\Cache;

class Index
{
    public function index()
    {
    	$rs = db("ads")->select();
        return '<style type="text/css">*{ padding: 0; margin: 0; } div{ padding: 4px 48px;} a{color:#2E5CD5;cursor: pointer;text-decoration: none} a:hover{text-decoration:underline; } body{ background: #fff; font-family: "Century Gothic","Microsoft yahei"; color: #333;font-size:18px;} h1{ font-size: 100px; font-weight: normal; margin-bottom: 12px; } p{ line-height: 1.6em; font-size: 42px }</style><div style="padding: 24px 48px;"> <h1>:) </h1><p> ThinkPHP V5.1<br/><span style="font-size:30px">12载初心不改（2006-2018） - 你值得信赖的PHP框架</span></p></div><script type="text/javascript" src="https://tajs.qq.com/stats?sId=64890268" charset="UTF-8"></script><script type="text/javascript" src="https://e.topthink.com/Public/static/client.js"></script><think id="eab4b9f840753f8e7"></think>';
    }

    public function hello($name = 'ThinkPHP5')
    {
	    // $cache = Cache::init();
		// 获取缓存对象句柄
		// $handler = $cache->handler();
		echo "<pre>";
		// echo $handler->sockert;
		// print_r($handler);
		$data = [1,2,3,4,5];
		// Cache::tag("devices")->set("name2",json_encode($data));
		// Cache::tag("devices")->set("name3",json_encode($data));
		// Cache::tag('tag',['name2','name3']);
		Cache::clear("roomScreen");
		// Cache::remember('name2',function(){
		// 	return time();
		// });
		// $org = new \app\pavilion\model\Organize;
		// $arr = $org->setDefault('192.168.0.238',0);
        // $jarr = json_decode(Cache::get("devices"),1);
        $jarr = Cache::get("ok");
        var_dump(json_decode($jarr,1));
        var_dump($jarr['12']);
        return ;
		
    }
 
}
