<?php
/**
 * CacheHandlerInterface.php
 * @author cdyun(121625706@qq.com)
 * @date 2025/9/23 23:07
 */
declare(strict_types=1);

namespace Cdyun\WebmanCache\core;

use DateInterval;
use DateTimeInterface;
use Psr\SimpleCache\CacheInterface;

interface CacheHandlerInterface extends CacheInterface
{

    /**
     * 自增缓存（针对数值缓存）
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     * @author cdyun(121625706@qq.com)
     */
    public function inc(string $name, int $step = 1): bool|int;

    /**
     * 自减缓存（针对数值缓存）
     * @param string $name 缓存变量名
     * @param int $step 步长
     * @return false|int
     * @author cdyun(121625706@qq.com)
     */
    public function dec(string $name, int $step = 1): bool|int;

    /**
     * 读取缓存并删除
     * @param string $name 缓存变量名
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    public function pull(string $name): mixed;

    /**
     * 如果不存在则写入缓存
     * @param string $name 缓存变量名
     * @param mixed $value 存储数据
     * @param DateInterval|DateTimeInterface|int|null $expire 有效时间 0为永久
     * @return mixed
     * @author cdyun(121625706@qq.com)
     */
    public function remember(string $name, mixed $value, DateInterval|DateTimeInterface|int $expire = null): mixed;

    /**
     * 缓存标签
     * @param array|string $name 标签名
     * @return TagSet
     * @author cdyun(121625706@qq.com)
     */
    public function tag(array|string $name): TagSet;

    /**
     * 删除缓存标签
     * @param array $keys 缓存标识列表
     * @return void
     * @author cdyun(121625706@qq.com)
     */
    public function clearTag(array $keys): void;

}