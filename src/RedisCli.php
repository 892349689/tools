<?php


namespace Layman\Tools;


use Illuminate\Support\Facades\Redis;

class RedisCli
{
    /**
     * +----------------------------------------------------------------------------------
     * + 每个键设置了一个前缀，方法名为setKey
     * +----------------------------------------------------------------------------------
     * + 存值之前先转换值，setVal方法，方法判断是否是数组或对象，自动序列化数组或对象
     * +----------------------------------------------------------------------------------
     * + 取值时需转换值，getVal方法，判断是否是数组或对象，若是则反序列化
     * +----------------------------------------------------------------------------------
     */

    private $prefix = "layman:";  # redis前缀-谨慎修改，修改后之前缓存将全部无法使用
    private $redis;
    private $connect;
    private static $redisCli;

    private function __construct($prefix = "",$connect = "default")
    {
        if ($prefix){
            $this->prefix = $prefix;
        }
        if ($connect){
            $this->connect = $connect;
        }
        $this->redis = Redis::connection($connect);
    }

    /**
     * Notes: 获取redis客户端
     * @param string $prefix
     * @param string $connect
     */
    public static function getRedisCli($prefix = "",$connect = "default")
    {
        if (!self::$redisCli instanceof RedisCli){
            self::$redisCli = new RedisCli($prefix, $connect);
        }
        if ($prefix !== self::$redisCli->getPrefix()){
            self::$redisCli->prefix = $prefix;
        }
        if ($connect !== self::$redisCli->getConnect()){
            self::$redisCli->redis = Redis::connection($connect);
            self::$redisCli->connect =$connect;
        }
    }

    /**
     * Notes: 获取前缀
     * @return string
     */
    public function getPrefix()
    {
        return $this->prefix;
    }

    /**
     * Notes: 获取连接对象名称
     * @return string
     */
    public function getConnect()
    {
        return $this->connect;
    }

    /**
     * Notes: 设置键
     * @param $key
     * @return array|string
     */
    private function setKey($key)
    {
        if (is_array($key)){
            foreach ($key as &$value){
                $value = $this->prefix . $value;
            }
        }else{
            $key = $this->prefix . $key;
        }
        return $key;
    }

    /**
     * Notes: 设置值---redis的数组和object序列化
     * @param $value
     * @return string
     */
    private function setValue($value)
    {
        if (is_array($value) || is_object($value)){
            $value =$this->prefix . serialize($value);
        }
        return $value;
    }

    /**
     * Notes: 重返序列化
     * @param $value
     * @return false|string
     */
    private function getValue($value)
    {
        if (strpos($value,$this->prefix) === 0) {
            $value = substr($value,strlen($this->prefix));
            $value = unserialize($value);
        }
        return $value;
    }

    /**
     * Notes: 写入缓存
     * @param $key
     * @param $value
     * @param int $timeout
     */
    public function set($key, $value, $timeout = 0)
    {
        $key = $this->setKey($key);
        $value = $this->setValue($value);
        if ($timeout){
            $this->redis->setex($key, $timeout, $value);
        }else{
            $this->redis->set($key, $value);
        }
    }

    /**
     * Notes: 键名获取缓存内容
     * @param $key
     * @return mixed
     */
    public function get($key)
    {
        $key = $this->setKey($key);
        $value = $this->redis->get($key);
        return $this->getValue($value);
    }

    /**
     * Notes: 键名删除缓存
     * @param $key
     */
    public function del($key)
    {
        $key = $this->setKey($key);
        $this->redis->del($key);
    }

    /**
     * Notes: 在list前面写入
     * @param $key
     * @param $value
     */
    public function lPush($key, $value)
    {
        $key    = $this->setKey($key);
        $value  = $this->setValue($value);
        $this->redis->lpush($key, $value);
    }

    /**
     * Notes: 在list后面写入
     * @param $key
     * @param $value
     */
    public function rPush($key, $value)
    {
        $key    = $this->setKey($key);
        $value  = $this->setValue($value);
        $this->redis->rpush($key, $value);
    }

    /**
     * Notes: 获取链表的值
     * @param $key
     * @param int $start
     * @param int $end
     * @return array
     */
    public function lRange($key,$start = 0,$end = -1)
    {
        $key = $this->setKey($key);
        $result = $this->redis->lrange($key,$start,$end);
        foreach ($result as &$value) {
            $value = $this->getValue($value);
        }
        return $result;
    }

    /**
     * Notes: 删除链表指定内容的值
     * @param $key
     * @param $value
     * @param int $num
     */
    public function lRem($key,$value,$num = 1)
    {
        $key = $this->setKey($key);
        $value = $this->setValue($value);
        $this->redis->lrem($key,$num,$value);
    }

    /**
     * Notes: 链表头部取出并删除
     * @param $key
     * @return mixed
     */
    public function lPop($key)
    {
        $key = $this->setKey($key);
        $result = $this->redis->lpop($key);

        return $this->getValue($result);
    }

    /**
     * Notes: 指定key过期时间
     * @param $key
     * @param $time
     */
    public function setTimeout($key, $time)
    {
        $key = $this->setKey($key);
        $this->redis->expire($key, $time);
    }

    /**
     * Notes: 给集合添加元素
     * @param $key
     * @param $value
     * @param bool $is_many
     * @return int
     */
    public function sAdd($key, $value, $is_many = false)
    {
        $key = $this->setKey($key);
        if ($is_many && is_array($value)) {
            foreach ($value as &$v) {
                $value = $this->setValue($v);
            }
        } else {
            $value = $this->setValue($value);
        }

        return $this->redis->sadd($key, $value);
    }

    /**
     * Notes: 获取集合成员数量
     * @param $key
     * @return int
     */
    public function sCard($key)
    {
        $key = $this->setKey($key);
        return $this->redis->scard($key);
    }

    /**
     * Notes: 返回集合的差集
     * @param $key
     * @return array
     */
    public function sDiff($key)
    {
        $key = $this->setKey($key);
        return $this->redis->sdiff($key);
    }

    /**
     * Notes: 集合交集
     * @param $key
     * @return array
     */
    public function sInter($key)
    {
        $key = $this->setKey($key);
        return $this->redis->sinter($key);
    }

    /**
     * Notes: 检测成员是否存在于集合中
     * @param $key
     * @param $value
     * @return int
     */
    public function sisMember($key, $value)
    {
        $key = $this->setKey($key);
        $value = $this->setValue($value);
        return $this->redis->sismember($key, $value);
    }

    /**
     * Notes: 返回集合所有成员
     * @param $key
     * @return array
     */
    public function sMembers($key)
    {
        $key = $this->setKey($key);
        return $this->redis->smembers($key);
    }

    /**
     * Notes: 将val从1移动到2中
     * @param $key1
     * @param $key2
     * @param $value
     * @return int
     */
    public function sMove($key1, $key2, $value)
    {
        $key1 = $this->setKey($key1);
        $key2 = $this->setKey($key2);
        $value = $this->setValue($value);
        return $this->redis->smove($key1, $key2, $value);
    }

    /**
     * Notes: 随机移除指定个元素，返回随机移除的元素
     * @param $key
     * @param int $num
     * @return string|null
     */
    public function sPop($key, $num = 1)
    {
        $key = $this->setKey($key);
        return $this->redis->spop($key, $num);
    }

    /**
     * Notes: 从集合里面随机返回一个或者多个元素
     * @param $key
     * @param int $num
     * @return string|null
     */
    public function sRandMember($key, $num = 1)
    {
        $key = $this->setKey($key);
        return $this->redis->srandmember($key, $num);
    }

    /**
     * Notes: 移除一个或者多个元素，并返回移除的数量
     * @param $key
     * @param $value
     * @param bool $is_many
     * @return int
     */
    public function sRem($key, $value, $is_many = false)
    {
        $key = $this->setKey($key);
        if ($is_many && is_array($value)) {
            foreach ($value as &$val) {
                $value = $this->setValue($val);
            }
        } else {
            $value = $this->setVal($value);
        }
        return $this->redis->srem($key, $value);
    }

    /**
     * Notes: 频道发布内容
     * @param $key
     * @param $value
     * @return int
     */
    public function publish($key, $value)
    {
        $key = $this->setKey($key);
        $value = $this->setValue($value);

        return $this->redis->publish($key, $value);
    }

    /**
     * Notes: 获取列表长度
     * @param $key
     * @return int
     */
    public function llen($key)
    {
        return $this->redis->llen($key);
    }

    /**
     * Notes:监视一个(或多个) key
     * @param $key
     * @return mixed
     */
    public function watch($key)
    {
        $key = $this->setKey($key);
        return $this->redis->watch($key);
    }

    /**
     * Notes:标记一个事务块的开始
     * @return mixed
     */
    public function multi()
    {
        return $this->redis->multi();
    }

    /**
     * Notes:执行所有事务块内的命令
     * @return array
     */
    public function exec()
    {
        return $this->redis->exec();
    }
}
