<?php
namespace app\admin\validate;

use think\Validate;

class Vsms extends Validate
{
    protected $rule = [
        // 'mobile' => ['require', 'regex' => "/^1[34578]\d{9}$/"],
        'content'   => 'require',
        // 'code'  => 'require',
        // 'signs' => 'require',
    ];

    protected $message = [
        // 'mobile'   => '手机号码填写有误',
        'content'   => '短信内容不能为空',
        // 'code'  => '模版CODE不能为空',
        // 'signs' => '短信签名不能为空',
    ];
}