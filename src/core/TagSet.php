<?php
/**
 * TagSet.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/23 23:10
 */
declare (strict_types=1);

namespace Cdyun\WebmanCache\core;

use DateInterval;
use DateTimeInterface;

/**
 * 缓存标签类
 */
class TagSet
{
    /**
     * 架构函数
     * @access public
     * @param array $tag 缓存标签
     * @param Driver $handler 缓存对象
     * @author cdyun(121625706@qq.com)
     */
    public function __construct(protected array $tag, protected Driver $handler)
    {
    }

    /**
     * 写入缓存
     * @access public
     * @param iterable $values 缓存数据
     * @param DateInterval|DateTimeInterface|int|null $ttl 有效时间 0为永久
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public function setMultiple(iterable $values, DateInterval|DateTimeInterface|int $ttl = null): bool
    {
        foreach ($values as $key => $val) {
            $result = $this->set($key, $val, $ttl);

            if (false === $result) {
                return false;
            }
        }

        return true;
    }

    /**
     * 写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param DateInterval|DateTimeInterface|integer|null $expire 有效时间（秒）
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public function set(string $name, mixed $value, DateInterval|DateTimeInterface|int $expire = null): bool
    {
        $this->handler->set($name, $value, $expire);

        $this->append($name);

        return true;
    }

    /**
     * 追加缓存标识到标签
     * @access public
     * @param string $name 缓存变量名
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function append(string $name): void
    {
        $name = $this->handler->getCacheKey($name);

        foreach ($this->tag as $tag) {
            $key = $this->handler->getTagKey($tag);
            $this->handler->append($key, $name);
        }
    }

    /**
     * 如果不存在则写入缓存
     * @access public
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param int|null $expire 有效时间 0为永久
     * @return mixed
     * @throws \throwable
     * @author cdyun(121625706@qq.com)
     */
    public function remember(string $name, mixed $value, int $expire = null)
    {
        $result = $this->handler->remember($name, $value, $expire);

        $this->append($name);

        return $result;
    }

    /**
     * 清除缓存
     * @access public
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public function clear(): bool
    {
        // 指定标签清除
        foreach ($this->tag as $tag) {
            $names = $this->handler->getTagItems($tag);
            $this->handler->clearTag($names);

            $key = $this->handler->getTagKey($tag);
            $this->handler->delete($key);
        }

        return true;
    }

}