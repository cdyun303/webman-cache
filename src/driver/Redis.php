<?php
/**
 * Redis.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/23 23:29
 */
declare (strict_types=1);

namespace Cdyun\WebmanCache\driver;

use Cdyun\WebmanCache\core\Driver;
use DateInterval;
use DateTimeInterface;
use RedisException;
use Workerman\Coroutine\Pool;

class Redis extends Driver
{

    /**
     * @var Pool[]
     * @author cdyun(121625706@qq.com)
     */
    protected static array $pools = [];

    /**
     * @var \Redis
     * @author cdyun(121625706@qq.com)
     */
    protected ?object $handler;

    /**
     * 配置参数
     * @var array
     * @author cdyun(121625706@qq.com)
     */
    protected array $options = [
        'host' => '127.0.0.1',
        'port' => 6379,
        'password' => '',
        'select' => 0,
        'timeout' => 0,
        'expire' => 0,
        'persistent' => false,
        'prefix' => '',
        'tag_prefix' => 'tag:',
        'serialize' => [],
    ];

    /**
     * 架构函数
     * @access public
     * @param array $options 缓存参数
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function __construct(array $options = [])
    {
        if (!empty($options)) {
            $this->options = array_merge($this->options, $options);
        }

        $this->handler = new \Redis;
        $this->handler->connect($this->options['host'], (int)$this->options['port'], (int)$this->options['timeout']);
        if ('' != $this->options['password']) {
            $this->handler->auth($this->options['password']);
        }

        if (0 != $this->options['select']) {
            $this->handler->select((int)$this->options['select']);
        }
    }

    /**
     * 判断缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function has(string $name): bool
    {
        return $this->handler->exists($this->getCacheKey($name)) ? true : false;
    }

    /**
     * 读取缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed|null $default 默认值
     * @return mixed
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function get(string $name, mixed $default = null): mixed
    {
        $key = $this->getCacheKey($name);
        $value = $this->handler->get($key);

        if (false === $value || is_null($value)) {
            return $default;
        }

        return $this->unserialize($value);
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param DateInterval|DateTimeInterface|integer|null $expire 有效时间（秒）
     * @return bool
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function set(string $name, mixed $value, DateInterval|DateTimeInterface|int $expire = null): bool
    {
        if (is_null($expire)) {
            $expire = $this->options['expire'];
        }

        $key = $this->getCacheKey($name);
        $expire = $this->getExpireTime($expire);
        $value = $this->serialize($value);

        if ($expire) {
            $this->handler->setex($key, $expire, $value);
        } else {
            $this->handler->set($key, $value);
        }

        return true;
    }

    /**
     * 自增缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function inc(string $name, int $step = 1): bool|int
    {
        $key = $this->getCacheKey($name);

        return $this->handler->incrby($key, $step);
    }

    /**
     * 自减缓存（针对数值缓存）
     * @access public
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function dec(string $name, int $step = 1): bool|int
    {
        $key = $this->getCacheKey($name);

        return $this->handler->decrby($key, $step);
    }

    /**
     * 删除缓存
     * @access public
     * @param string $name 缓存变量名
     * @return bool
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function delete(string $name): bool
    {
        $key = $this->getCacheKey($name);
        $result = $this->handler->del($key);
        return $result > 0;
    }

    /**
     * 清除缓存
     * @access public
     * @return bool
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function clear(): bool
    {
        $this->handler->flushDB();
        return true;
    }

    /**
     * 删除缓存标签
     * @access public
     * @param array $keys 缓存标识列表
     * @return void
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function clearTag(array $keys): void
    {
        // 指定标签清除
        $this->handler->del($keys);
    }

    /**
     * 追加TagSet数据
     * @access public
     * @param string $name 缓存标识
     * @param mixed $value 数据
     * @return void
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function append(string $name, mixed $value): void
    {
        $key = $this->getCacheKey($name);
        $this->handler->sAdd($key, $value);

        // 避免tag键长期占用内存，设置一个超过其他缓存的过期时间
        $tagExpire = $this->options['tag_expire'] ?? 0;
        if ($tagExpire) {
            $this->handler->expire($key, $tagExpire);
        }
    }

    /**
     * 获取标签包含的缓存标识
     * @access public
     * @param string $tag 缓存标签
     * @return array
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function getTagItems(string $tag): array
    {
        $name = $this->getTagKey($tag);
        $key = $this->getCacheKey($name);
        return $this->handler->sMembers($key);
    }

    /**
     * @return void
     * @throws RedisException
     * @author cdyun(121625706@qq.com)
     */
    public function close()
    {
        $this->handler->close();
        $this->handler = null;
    }

}