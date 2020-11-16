<?php


namespace Layman\Tools;


class Common
{
    /**
     * Notes: curl请求
     * @param $url
     * @param string $method
     * @param array $data
     * @param array $header
     * @param bool $ssl
     * @return bool|string
     */
    public static function sendUrl($url, $method = 'GET', $data = [], $header = [], $ssl = false)
    {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);  //设置请求方式为POST
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);  //设置请求发送参数内容,参数值为关联数组
        curl_setopt($ch, CURLOPT_HTTPHEADER, $header );  //设置请求报头的请求格式为json, 参数值为非关联数组
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 1); // 连接最大等待时间
        curl_setopt($ch, CURLOPT_TIMEOUT, 15); // 执行超时时间
        if($ssl){
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);   //服务器要求使用安全链接https请求时，不验证证书和hosts
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        }

        $result = curl_exec($ch);  //发送请求并获取结果

        curl_close($ch); //关闭curl
        return $result;
    }

}