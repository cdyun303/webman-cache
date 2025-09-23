<?php
/**
 * @desc CacheManager.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/23 22:37
 */
declare(strict_types = 1);

namespace Cdyun\WebmanCache\core;
use DateInterval;
use DateTimeInterface;
use Psr\SimpleCache\CacheInterface;
use Psr\SimpleCache\InvalidArgumentException;
use ReflectionException;
use think\helper\Arr;

/**
 * 缓存管理器
 * @Mixin Driver
 */
class CacheManager extends Manager implements CacheInterface
{

    /**
     * @var string|null
     */
    protected ?string $namespace = '\\Webman\\WebmanCache\\driver\\';

    /**
     * @return string|null
     * @author cdyun(121625706@qq.com)
     * @desc 默认驱动
     */
    public function getDefaultDriver(): ?string
    {
        return $this->getConfig('default');
    }

    /**
     * @access public
     * @param string|null $name 名称
     * @param mixed|null $default 默认值
     * @return mixed
     * @author cdyun(121625706@qq.com)
     * @desc 获取缓存配置
     */
    public function getConfig(string $name = null, mixed $default = null): mixed
    {
        if (!is_null($name)) {
            return config('plugin.cdyun.webman-cache.cache.' . $name, $default);
        }
        return config('plugin.cdyun.webman-cache.cache');
    }

    /**
     * @param string $store
     * @param string $name
     * @param mixed|null $default
     * @return mixed
     * @author cdyun(121625706@qq.com)
     * @desc 获取驱动配置
     */
    public function getStoreConfig(string $store, string $name = '', mixed $default = null): mixed
    {
        if ($config = $this->getConfig("stores.{$store}")) {
            return $name ? Arr::get($config, $name, $default) : $config;
        }

        throw new \InvalidArgumentException("Store [$store] not found.");
    }

    /**
     * @param string $name
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    protected function resolveType(string $name): mixed
    {
        return $this->getStoreConfig($name, 'type', 'file');
    }

    /**
     * @param string $name
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    protected function resolveConfig(string $name): mixed
    {
        return $this->getStoreConfig($name);
    }

    /**
     * @access public
     * @param string $name 连接配置名
     * @return Driver
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 连接或者切换缓存
     */
    public function store(string $name = ''): Driver
    {
        return $this->driver($name);
    }

    /**
     * @access public
     * @return bool
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 清空缓冲池
     */
    public function clear(): bool
    {
        return $this->store()->clear();
    }

    /**
     * @access public
     * @param string $key 缓存变量名
     * @param mixed $default 默认值
     * @return mixed
     * @throws InvalidArgumentException|ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 读取缓存
     */
    public function get(string $key, mixed $default = null): mixed
    {
        return $this->store()->get($key, $default);
    }

    /**
     * @access public
     * @param string $key 缓存变量名
     * @param mixed $value 存储数据
     * @param DateInterval|DateTimeInterface|int|null $ttl 有效时间 0为永久
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 写入缓存
     */
    public function set(string $key, mixed $value, DateInterval|DateTimeInterface|int $ttl = null): bool
    {
        return $this->store()->set($key, $value, $ttl);
    }

    /**
     * @access public
     * @param string $key 缓存变量名
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 删除缓存
     */
    public function delete(string $key): bool
    {
        return $this->store()->delete($key);
    }

    /**
     * @access public
     * @param iterable $keys 缓存变量名
     * @param mixed|null $default 默认值
     * @return iterable
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 读取缓存
     */
    public function getMultiple(iterable $keys, mixed $default = null): iterable
    {
        return $this->store()->getMultiple($keys, $default);
    }

    /**
     * @access public
     * @param iterable $values 缓存数据
     * @param DateInterval|int|null $ttl 有效时间 0为永久
     * @return bool
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 写入缓存
     */
    public function setMultiple(iterable $values, DateInterval|int $ttl = null): bool
    {
        return $this->store()->setMultiple($values, $ttl);
    }

    /**
     * @access public
     * @param iterable $keys 缓存变量名
     * @return bool
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 删除缓存
     */
    public function deleteMultiple(iterable $keys): bool
    {
        return $this->store()->deleteMultiple($keys);
    }

    /**
     * @access public
     * @param string $key 缓存变量名
     * @return bool
     * @throws InvalidArgumentException|ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 判断缓存是否存在
     */
    public function has(string $key): bool
    {
        return $this->store()->has($key);
    }

    /**
     * @access public
     * @param array|string $name 标签名
     * @return TagSet
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     * @desc 缓存标签
     */
    public function tag(array|string $name): TagSet
    {
        return $this->store()->tag($name);
    }
}