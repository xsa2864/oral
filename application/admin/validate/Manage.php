<?php
namespace app\admin\validate;

use think\Validate;

class Manage extends Validate
{
    
    protected $rule = [
        'UserName'      =>  'require',
        'BodNo'         =>  ['require', 'regex' => "/^1[34578]\d{9}$/"],
        'FullName'      =>  'require',
        'unitid'        =>  'require|gt:0',
    ];

    protected $message  =   [
        'UserName'          => '用户名不能为空',
        'BodNo'             => '手机号填写错误',
        'FullName'          => '名字不能为空',
        'unitid.require'    => '请选择单位',
        'unitid.gt'         => '请选择单位',
    ];
}