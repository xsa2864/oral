<?php

namespace app\admin\validate;

use think\Validate;

class Hall extends Validate
{
    protected $rule = [
        'HallName' => 'require',
        'UnitId'    => 'require|gt:0',
        'AlternateField1' => 'max:50',
    ];

    protected $message = [
        'HallName'    => '请输入科室名称!',
        'UnitId'      => '请选择所属单位!',
        'AlternateField1'=> '字数控制在50个字以内',
    ];

}