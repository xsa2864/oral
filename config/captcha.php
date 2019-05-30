<?php

return [
    'codeSet' => '0123456789', //qwrtyuiopasdfghjklzxcvbnm
    'expire' => 180, //验证码过期时间（s）
    'fontSize' => 18,
    'useCurve' => false,
    //useCurve	是否画混淆曲线	true
    //useNoise	是否添加杂点	true
    'imageH' => 40,
    'imageW' => 140,
    'length' => 4,
//reset	验证成功后是否重置	true
];