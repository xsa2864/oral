<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/1/14/014
 * Time: 2:47
 */

namespace app\tool\exception;


use think\Exception;


class Base extends Exception
{
    public $status = 0;   //HTTP状态吗
    public $msg = '参数错误';    //错误具体信息
    public $data = [];      //错误码
    public $code = 200;


    /**
     * @desc 构造函数 初始化成员变量
     * BaseException constructor.
     * @param array $params
     * @throws Exception
     */
    public function __construct($params = [])
    {
        //如果传入的参数不是数组就直接抛出异常
        if(!is_array($params)){
            throw new Exception('参数必须是数组');
        }

        //判断数组中是否存在下标是code的值
        //如果存在就给成员变量赋值
        if(array_key_exists('status',$params)){
            $this->status = $params['status'];
        }

        //判断数组中是否存在下标是 msg 的值
        //如果存在就给成员变量赋值
        if(array_key_exists('msg',$params)){
            $this->msg = $params['msg'];
        }

        //判断数组中是否存在下标是 error_code 的值
        //如果存在就给成员变量赋值
        if(array_key_exists('data',$params)){
            $this->data = $params['data'];
        }

        if(array_key_exists('code',$params)){
            $this->code = $params['code'];
        }
    }

}