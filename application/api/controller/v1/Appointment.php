<?php

namespace app\api\controller\v1;

use think\Controller;
use think\Request;
use app\api\controller\Base;


class Appointment extends Base
{
    /**
     * 预约数据
     *
     * @return json
     */
    public function getDespeak()
    {      
        $re_msg['code'] = 0;
        $re_msg['msg'] = '获取失败';

        $max_id = input("max_id",0);
        $where['despeak_id'] = [">",$max_id];
        $where['status']     = 1;
        $result = db("despeak")->where($where)->limit(30)->select();
        if($result){
            $re_msg['code'] = 1;
            $re_msg['msg']  = '获取成功';
            $re_msg['data'] = $result;
        }
        echo json_encode($re_msg);
    }

    /**
     * 更新数据信息
     * id
     * @return json
     */
    public function saveDespeak()
    {
        $re_msg['code'] = 0;
        $re_msg['msg'] = '更新失败';

        $id = input("id",0);
        $data['status'] = 2;
        $rs = db("despeak")->where("despeak_id=$id")->update($data);

        if($rs){
            $re_msg['code'] = 1;
            $re_msg['msg']  = '更新成功';
        }
        echo json_encode($re_msg);
    }

    public function http(){
        // 文本加一个回车
        // $data['key'] = 'ksulal';
        // $buffer1 = json_encode($data)."\n";
        // 在php中双引号中的\n代表一个换行符，例如"\n"
        // $buffer2 = '{"type":"say", "content":"hello"}'."\n";
//         echo $buffer2;
// exit;
        // 与服务端建立socket连接
        // $client = stream_socket_client('tcp://127.0.0.11:2345');
        // 以text协议发送buffer1数据
        // fwrite($client, $buffer1);
        // echo fread($client, 2026);
        // 以text协议发送buffer2数据
        // fwrite($client, $buffer2);
        // echo fread($client, 2026);
        // 指明给谁推送，为空表示向所有在线用户推送
        // $to_uid = "";
        // // 推送的url地址，使用自己的服务器地址
        // $push_api_url = "http://127.0.0.1:2345/";
        // $post_data = array(
        //    "type" => "publish",
        //    "content" => "这个是推送的测试数据",
        //    "to" => $to_uid, 
        // );
        // $ch = curl_init ();
        // curl_setopt ($ch, CURLOPT_URL, $push_api_url );
        // curl_setopt ($ch, CURLOPT_POST, 1 );
        // curl_setopt ($ch, CURLOPT_HEADER, 0 );
        // curl_setopt ($ch, CURLOPT_RETURNTRANSFER, 1 );
        // curl_setopt ($ch, CURLOPT_POSTFIELDS, $post_data );
        // curl_setopt ($ch, CURLOPT_HTTPHEADER, array("Expect:"));
        // $return = curl_exec ( $ch );
        // curl_close ( $ch );
        // var_export($return);

    }
}
