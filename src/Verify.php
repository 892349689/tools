<?php


namespace Layman\Tools;



class Verify
{
    /**
     * +----------------------------------------------------------------------------------
     * + 验证码写入reids基于RedisCli
     * +----------------------------------------------------------------------------------
     */

    private $redis;
    private $prefix = 'account_verify:';

    private function __construct($prefix = null)
    {
        if ($prefix){
            $this->prefix = $prefix;
        }
        $this->redis = RedisCli::getRedisCli($this->prefix);
    }

    /**
     * Notes: 传递-$len长度位数验证码
     * @param int $len
     * @return string|null
     */
    public function verifyCode(int $len)
    {
        if ($len < 4){
            return 'The captcha cannot be less than four digits';
        }
        if ($len > 10){
            return 'Captcha must not be larger than four digits';
        }
        $code = null;
        for ($i = 1; $i <= $len; $i++){
            $num = rand($i, 9);
            $code .= $num;
        }
        return $code;
    }

    /**
     * Notes: 验证码存入redis
     * @param $key          //键
     * @param $len          //验证码长度
     * @param int $timeout  //有效时间
     * @return bool
     */
    public function setVerify($key, $len, $timeout=300)
    {
        $this->redis->set($key,self::verifyCode($len),$timeout);
        return true;
    }

    /**
     * Notes: 验证验证码是否正确
     * @param $key          //键
     * @param $verifyCode   //要验证的验证码
     * @return bool
     */
    public function getVerify($key,$verifyCode)
    {
        try {
            $code = $this->redis->get($key);
            if ($verifyCode == $code){
                $this->redis->del($key);
                return true;
            }else{
                return false;
            }
        }catch (\Exception $exception){
            return false;
        }
    }
}

