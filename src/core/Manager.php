<?php
/**
 * Manager.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/23 23:18
 */
declare(strict_types=1);

namespace Cdyun\WebmanCache\core;

use InvalidArgumentException;
use ReflectionException;
use think\Container;
use think\helper\Str;
use Throwable;
use Webman\Context;
use Workerman\Coroutine\Pool;

abstract class Manager
{

    /**
     * @var Pool[]
     * @author cdyun(121625706@qq.com)
     */
    protected static array $pools = [];

    /**
     * 驱动
     * @var array
     * @author cdyun(121625706@qq.com)
     */
    protected array $drivers = [];

    /**
     * 驱动的命名空间
     * @var ?string
     * @author cdyun(121625706@qq.com)
     */
    protected ?string $namespace = null;

    /**
     * 移除一个驱动实例
     *
     * @param array|string|null $name
     * @return $this
     * @author cdyun(121625706@qq.com)
     */
    public function forgetDriver(array|string|null $name = ''): static
    {
        $name = $name ?: $this->getDefaultDriver();

        foreach ((array)$name as $cacheName) {
            if (isset($this->drivers[$cacheName])) {
                unset($this->drivers[$cacheName]);
            }
        }

        return $this;
    }

    /**
     * 默认驱动
     * @return string|null
     * @author cdyun(121625706@qq.com)
     */
    abstract public function getDefaultDriver(): ?string;

    /**
     * 动态调用
     * @param string $method
     * @param array $parameters
     * @return mixed
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    public function __call(string $method, array $parameters)
    {
        return $this->driver()->$method(...$parameters);
    }

    /**
     * 获取驱动实例
     * @param null|string $name
     * @return mixed
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    protected function driver(string $name = null): mixed
    {
        $name = $name ?: $this->getDefaultDriver();

        if (is_null($name)) {
            throw new InvalidArgumentException(sprintf(
                'Unable to resolve NULL driver for [%s].',
                static::class
            ));
        }

        return $this->getDriver($name);
    }

    /**
     * 获取驱动实例
     * @param string $name
     * @return mixed
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    protected function getDriver(string $name): mixed
    {
        return $this->createDriver($name);
    }

    /**
     * 创建驱动
     *
     * @param string $name
     * @return mixed
     *
     * @throws ReflectionException
     * @author cdyun(121625706@qq.com)
     */
    protected function createDriver(string $name): mixed
    {
        $type = $this->resolveType($name);

        $method = 'create' . Str::studly($type) . 'Driver';

        $params = $this->resolveParams($name);

        if (method_exists($this, $method)) {
            return $this->$method(...$params);
        }

        $class = $this->resolveClass($type);

        if (strtolower($type) === 'redis') {
            $key = "think-cache.stores.$name";
            $connection = Context::get($key);
            if (!$connection) {
                if (!isset(static::$pools[$name])) {
                    $poolConfig = $params[0]['pool'] ?? [];
                    $pool = new Pool($poolConfig['max_connections'] ?? 10, $poolConfig);
                    $pool->setConnectionCreator(function () use ($class, $params) {
                        return Container::getInstance()->invokeClass($class, $params);
                    });
                    $pool->setConnectionCloser(function ($connection) {
                        $connection->close();
                    });
                    $pool->setHeartbeatChecker(function ($connection) {
                        $connection->get('PING');
                    });
                    static::$pools[$name] = $pool;
                }
                try {
                    $connection = static::$pools[$name]->get();
                    Context::set($key, $connection);
                } finally {
                    Context::onDestroy(function () use ($connection, $name) {
                        try {
                            $connection && static::$pools[$name]->put($connection);
                        } catch (Throwable) {
                            // ignore
                        }
                    });
                }
            }
        } else {
            $connection = Container::getInstance()->invokeClass($class, $params);
        }

        return $connection;
    }

    /**
     * 获取驱动类型
     * @param string $name
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    protected function resolveType(string $name): mixed
    {
        return $name;
    }

    /**
     * 获取驱动参数
     * @param $name
     * @return array
     * @author cdyun(121625706@qq.com)
     */
    protected function resolveParams($name): array
    {
        $config = $this->resolveConfig($name);
        return [$config];
    }

    /**
     * 获取驱动配置
     * @param string $name
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    protected function resolveConfig(string $name): mixed
    {
        return $name;
    }

    /**
     * 获取驱动类
     * @param string $type
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    protected function resolveClass(string $type): string
    {
        if ($this->namespace || str_contains($type, '\\')) {
            $class = str_contains($type, '\\') ? $type : $this->namespace . Str::studly($type);

            if (class_exists($class)) {
                return $class;
            }
        }

        throw new InvalidArgumentException("Driver [$type] not supported.");
    }

}