PHP开发常用的方法集合
=====
基于Think-Cache 的封装，增加了协程和连接池支持，同时支持webman非协程环境。 本缓存只支持redis和文件驱动。

### 安装
```
composer require cdyun/webman-cache
```

### 基础Cache类
- 提供的接口参考think-cache
```PHP
use Cdyun\WebmanCache\Cache;
```

### 基于基础Cache封装的CacheEnforcer类
- 增加了全局默认参数配置（默认全局标签、默认过期时间）

- 保留默认缓存驱动接口，同时增加了单独的redis缓存接口
```PHP
use Cdyun\WebmanCache\CacheEnforcer;

方法：setRedis、getRedis、delRedis、clearRedis、hasRedis
```