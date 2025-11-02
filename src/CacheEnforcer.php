<?php
/**
 * 对Cache的操作进行再次封装，增加了默认参数配置
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/24 0:03
 */
declare(strict_types=1);

namespace Cdyun\WebmanCache;

use Cdyun\WebmanCache\Cache as CacheStatic;
use Cdyun\WebmanCache\core\Driver;
use ReflectionException;
use support\Log;
use Throwable;


class CacheEnforcer
{
    /**
     *默认标签
     * @author cdyun(121625706@qq.com)
     */
    const DEFAULT_TAG = 'sys';

    /**
     *默认过期时间4小时
     * @author cdyun(121625706@qq.com)
     */
    const DEFAULT_EXPIRE = 14400;

    /**
     * 缓存驱动实例
     * @var Driver|null
     * @author cdyun(121625706@qq.com)
     */
    protected static ?Driver $instance = null;
    /**
     *缓存redis驱动
     * @var Driver|null
     * @author cdyun(121625706@qq.com)
     */
    protected static ?Driver $redisInstance = null;

    /**
     * 获取 Redis 缓存值，并支持自定义过期时间和标签名
     * @param string $key
     * @param mixed|null $default
     * @param int|null $expire
     * @param string|array|null $tagName
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    public static function getRedis(string $key, mixed $default = null, ?int $expire = null, string|array|null $tagName = null): mixed
    {
        try {
            $cache = self::redisHandler();
            if ($cache->has($key)) {
                $hit = $cache->get($key);
                if ($hit !== null) {
                    return $hit;
                }
            }
            $defaultValue = is_callable($default) ? $default() : $default;
            if ($defaultValue !== [] && $defaultValue !== null) {
                self::setRedis($key, $defaultValue, self::getExpire($expire), self::getTagName($tagName));
            }
            return $defaultValue;
        } catch (Throwable $e) {
            Log::error("Redis get failed for key: $key", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return is_callable($default) ? $default() : $default;
        }
    }

    /**
     * 获取 Redis 缓存驱动
     * @return Driver|null
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    protected static function redisHandler(): ?Driver
    {
        if (self::$redisInstance === null) {
            self::$redisInstance = CacheStatic::store('redis');
        }
        return self::$redisInstance;
    }

    /**
     * 根据键名判断缓存是否存在
     * @param string $key
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public static function has(string $key): bool
    {
        try {
            return self::handler()->has($key);
        } catch (Throwable $e) {
            // 可选：记录日志或上报监控
            Log::error("Cache has failed for key: $key", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 获取缓存实例
     * @return Driver|null
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    protected static function handler(): ?Driver
    {
        if (self::$instance === null) {
            $default = CacheStatic::getConfig('default');
            self::$instance = CacheStatic::store($default);
        }
        return self::$instance;
    }

    /**
     * 获取缓存值
     * @param string $key
     * @param mixed|null $default
     * @param int|null $expire
     * @param string|array|null $tagName
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    public static function get(string $key, mixed $default = null, ?int $expire = null, string|array|null $tagName = null): mixed
    {
        try {
            $cache = self::handler();
            if ($cache->has($key)) {
                $hit = $cache->get($key);
                if ($hit !== null) {
                    return $hit;
                }
            }
            $defaultValue = is_callable($default) ? $default() : $default;
            if ($defaultValue !== [] && $defaultValue !== null) {
                self::set($key, $defaultValue, self::getExpire($expire), self::getTagName($tagName));
            }
            return $defaultValue;
        } catch (Throwable $e) {
            Log::error("Cache get failed for key: $key", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return is_callable($default) ? $default() : $default;
        }
    }

    /**
     * 设置缓存项
     * @param string $key 缓存项的键
     * @param mixed $value 缓存项的值
     * @param int|null $expire 缓存项的过期时间（秒），null表示使用默认过期时间
     * @param string|array|null $tagName 缓存项的标签，可以是单个标签字符串或多个标签数组，null表示不使用标签
     * @return bool 成功设置缓存项则返回true，否则返回false
     * @author cdyun(121625706@qq.com)
     */
    public static function set(string $key, mixed $value, ?int $expire = null, string|array|null $tagName = null): bool
    {
        try {
            return self::handler()->tag(self::getTagName($tagName))->set($key, $value, self::getExpire($expire));
        } catch (Throwable $e) {
            // 记录设置缓存项时发生的异常信息
            Log::error("Cache set failed for key: $key, tags: " . json_encode($tagName), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 获取标签名称数组
     * @param string|array|null $tagName 可选的标签名称，可以是字符串或数组默认为null
     * @return array 包含唯一标签名称的数组，至少包含一个默认标签
     * @author cdyun(121625706@qq.com)
     */
    public static function getTagName(string|array|null $tagName = null): array
    {
        $tags = $tagName ? (is_array($tagName) ? $tagName : [$tagName]) : [];
        $tags[] = self::DEFAULT_TAG;
        return array_unique($tags);
    }

    /**
     * 获取过期时间
     * @param int|null $expire
     * @return int
     * @author cdyun(121625706@qq.com)
     */
    public static function getExpire(?int $expire = null): int
    {
        // 区分 expire 是否为 null，避免将 0 误判为“假值”
        if ($expire === null) {
            $expire = self::DEFAULT_EXPIRE;
        }
        return $expire;
    }

    /**
     * 设置 Redis 缓存值，并支持自定义过期时间和标签名
     *
     * @param string $key 缓存键名
     * @param mixed $value 缓存值（会自动序列化）
     * @param int|null $expire 过期时间（单位：秒），null 使用默认值，0 表示永不过期
     * @param string|array|null $tagName 标签名，所有缓存都含有默认标签
     * @return bool 成功返回 true，失败返回 false
     * @author cdyun(121625706@qq.com)
     */
    public static function setRedis(string $key, mixed $value, ?int $expire = null, string|array|null $tagName = null): bool
    {
        try {
            return self::redisHandler()->tag(self::getTagName($tagName))->set($key, $value, self::getExpire($expire));
        } catch (Throwable $e) {
            Log::error("Redis set failed for key: $key, tags: " . json_encode($tagName), [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 删除 Redis 缓存值
     * @param string $key
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public static function delRedis(string $key): bool
    {
        try {
            return self::redisHandler()->delete($key);
        } catch (Throwable $e) {
            // 可选：记录日志或上报监控
            Log::error("Redis delete failed for key: $key", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 根据键名删除缓存值
     * @param string $key
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public static function delete(string $key): bool
    {
        try {
            return self::handler()->delete($key);
        } catch (Throwable $e) {
            // 可选：记录日志或上报监控
            Log::error("Cache delete failed for key: $key", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 清空 Redis 缓存，支持按标签清除
     * @param string|array|null $tagName ,为空时使用默认标签
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public static function clearRedis(string|array|null $tagName = null): bool
    {
        try {
            $tag = $tagName ?: self::DEFAULT_TAG;
            return self::redisHandler()->tag($tag)->clear();
        } catch (Throwable $e) {
            // 可选：记录日志或上报监控
            Log::error('Redis clear failed: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'tag' => $tagName,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 清空缓存，支持按标签清除
     * @param string|array|null $tagName ,为空时使用默认标签
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public static function clear(string|array|null $tagName = null): bool
    {
        try {
            $tag = $tagName ?: self::DEFAULT_TAG;
            return self::handler()->tag($tag)->clear();
        } catch (Throwable $e) {
            // 可选：记录日志或上报监控
            Log::error('Cache clear failed: ' . $e->getMessage(), [
                'exception' => $e->getMessage(),
                'tag' => $tagName,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 判断 Redis 缓存是否存在
     * @param string $key
     * @return bool
     * @author cdyun(121625706@qq.com)
     */
    public static function hasRedis(string $key): bool
    {
        try {
            return self::redisHandler()->has($key);
        } catch (Throwable $e) {
            // 可选：记录日志或上报监控
            Log::error("Redis has failed for key: $key", [
                'exception' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    public static function __callStatic($name, $arguments)
    {
        return self::redisHandler()->{$name}(...$arguments);
    }

    /**
     * 魔术方法
     * @param $name
     * @param $arguments
     * @return mixed
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    public function __call($name, $arguments)
    {
        return self::redisHandler()->{$name}(...$arguments);
    }

}