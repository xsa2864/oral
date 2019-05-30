<?php
namespace app\tool\controller;

use think\View;
use think\Controller;
use think\Request;
use think\Db;

class Http extends Base
{
	public function insertDespeak()
	{
		$max_id = db("despeak")->max("despeak_id");
		$url = "http://114.116.81.59/api/v1/Appointment/getDespeak";
		$data['token'] = $this->token;
		$data['max_id'] = $max_id;
		$ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS,$data);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        //执行请求
        $output = curl_exec($ch);
        $arr = json_decode($output,1);
        $flag = 0;
        if($arr['code'] == 1){
        	$data = $arr['data'];
        	$flag = db("despeak")->insertAll($data);
        }
        echo $flag;
	}

	// 更新状态
	public function updateDespeak()
	{
		$id = input("id",0);
		$array = [
			'appid'=>'',
		];
	}
	// 人才交流中心网上预约验证接口
	public function check(){
		$id = '430126198806012346';
		$st = time();
		$area = 0101;
		$sig = '';
		$SecretKey = 'Szhr_Pasr_2016';
		$str = $id.$st.$area.$SecretKey;
		$sig = strtolower(md5($str));
		$url = "http://aux.szhr.com/pasr/ticket-check!check.action?id=$id&st=$st&area=$area&sig=$sig";
		$result = file_get_contents($url);
		$arr = json_decode($result,1);
		print_r($arr);
	}
}