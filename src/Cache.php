<?php
/**
 * 基于Think-Cache 的封装，增加了协程和连接池支持，同时支持webman非协程环境。 本缓存只支持redis和文件驱动。
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/23 23:57
 */
declare(strict_types=1);

namespace Cdyun\WebmanCache;

use Cdyun\WebmanCache\core\CacheManager;
use think\Facade;

/**
 * 缓存操作类
 * @mixin CacheManager
 */
class Cache extends Facade
{
    /**
     * 获取当前Facade对应类名（或者已经绑定的容器对象标识）.
     * @return string
     * @author cdyun(121625706@qq.com)
     */
    protected static function getFacadeClass(): string
    {
        return CacheManager::class;
    }

}