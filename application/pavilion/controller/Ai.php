<?php
namespace app\pavilion\controller;

use think\Controller;
use think\Db;
use think\facade\Cache;
use think\facade\Request;
use think\facade\View;
use think\facade\Env;
use think\facade\Config;


class Ai extends Controller
{
    function process($token, $request, $audioFile) {
        /**
         * 读取音频文件
         */
        $audioContent = file_get_contents($audioFile);
        if ($audioContent == FALSE) {
            print "The audio file is not exist!\n";
            return;
        }
        $curl = curl_init();
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($curl, CURLOPT_TIMEOUT, 120);
        /**
         * 设置HTTP请求行
         */
        curl_setopt($curl, CURLOPT_URL, $request);
        curl_setopt($curl, CURLOPT_POST,TRUE);
        /**
         * 设置HTTP请求头部
         */
        $contentType = "application/octet-stream";
        $contentLength = strlen($audioContent);
        $headers = array(
            "X-NLS-Token:" . $token,
            "Content-type:" . $contentType,
            "Content-Length:" . strval($contentLength)
        );
        curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);
        /**
         * 设置HTTP请求数据
         */
        curl_setopt($curl, CURLOPT_POSTFIELDS, $audioContent);
        curl_setopt($curl, CURLOPT_NOBODY, FALSE);
        /**
         * 发送HTTP请求
         */
        $returnData = curl_exec($curl);
        curl_close($curl);
        if ($returnData == FALSE) {
            print "curl_exec failed!\n";
            return;
        }
        print $returnData . "<br>";
        $resultArr = json_decode($returnData, true);
        $status = $resultArr["status"];
        if ($status == 20000000) {
            $result = $resultArr["result"];
            print "The audio file recognized result: " . $result . "\n";
        }
        else {
            print "The audio file recognized failed.\n";
        }
    }

    public function show()
    {
        $appkey = "bKPBFw0663iqOjoC";
        $token = "3b3e0a27289b412aa5fb0ff2719e5a63";
        $url = "http://nls-gateway.cn-shanghai.aliyuncs.com/stream/v1/asr";
        $audioFile = "G:/PHPCUSTOM/wwwroot/oral/public/uploads/video/20190708103206.wav";
        $format = "pcm";
        $sampleRate = 16000;
        $enablePunctuationPrediction = TRUE;
        $enableInverseTextNormalization = TRUE;
        $enableVoiceDetection = FALSE;
        /**
         * 设置RESTful 请求参数
         */
        $request = $url;
        $request = $request . "?appkey=" . $appkey;
        $request = $request . "&format=" . $format;
        $request = $request . "&sample_rate=" . strval($sampleRate);
        if ($enablePunctuationPrediction) {
            $request = $request . "&enable_punctuation_prediction=" . "true";
        }
        if ($enableInverseTextNormalization) {
            $request = $request . "&enable_inverse_text_normalization=" . "true";
        }
        if ($enableVoiceDetection) {
            $request = $request . "&enable_voice_detection=" . "true";
        }
        // print "Request: " . $request . "\n";
        $this->process($token, $request, $audioFile);
    }

}
