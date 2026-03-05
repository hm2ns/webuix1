<?php

/**
 * Redis 数据库类
 */
class RC
{
    /** @var \Redis */
    protected $redis;
    protected $mintable = 0;
    protected $maxtable = 0;
    protected $nowtable = 0;
    
    // [优化] 增加连接超时和读取超时配置，防止网络波动导致长时间阻塞
    protected $timeout = 2.5;
    protected $readTimeout = 2.5;

    public function __construct()
    {
        $cfg = GLOBAL_CONFIG::get('redis');
        if (empty($cfg)) {
            throw new configException('Redis 配置不存在');
        }
        if (empty($cfg['host']) || empty($cfg['port'])) {
            throw new configException('Redis 配置不完整');
        }
        $this->mintable = $cfg['offset'] ?? 0;
        $this->maxtable = $this->mintable + ($cfg['baserange'] ?? 1) - 1;
        if ($this->mintable < 0 || $this->maxtable < 0 || $this->mintable > $this->maxtable) {
            throw new configException('Redis 表范围配置错误');
        }
        
        $this->redis = new Redis();
        
        // [优化] 使用 pconnect 持久连接，减少 TCP 握手开销
        // 参数：host, port, timeout, persistent_id, retry_interval, read_timeout
        $persistentId = "redis_persist_" . $cfg['host'] . "_" . $cfg['port'];
        $connected = $this->redis->pconnect(
            $cfg['host'], 
            (int)$cfg['port'], 
            $this->timeout, 
            $persistentId, 
            100, 
            $this->readTimeout
        );

        if (!$connected) {
            throw new Exception('Redis 连接失败');
        }

        if (isset($cfg['password']) && !empty($cfg['password'])) {
            $this->redis->auth($cfg['password']);
        }
        
        // [优化] 生产环境建议注释掉 ping，减少握手开销，依赖 connect 返回值判断即可
        // if ($this->redis->ping() != '+PONG') {
        //     throw new Exception('Redis 连接失败');
        // }
    }
    
    /**
     * 选择 Redis 表，表范围由配置文件中的 offset 和 baserange 参数决定
     * @param int $table 表编号，必须在配置的范围内
     * @return bool 成功返回 true，失败抛出异常
     */
    public function select(int $table)
    {
        $table = intval($table);
        $table += $this->mintable;
        if ($table < $this->mintable || $table > $this->maxtable) {
            throw new Exception('Redis 表选择错误，超出范围');
        }
        if ($this->nowtable == $table) {
            return true;
        }
        $this->nowtable = $table;
        return $this->redis->select($table);
    }
    
    /**
     * 获取指定键的值，如果键不存在则返回 null
     */
    public function get(string $key)
    {
        // [优化] 移除 exists 检查，直接 get。redis->get 不存在时返回 false，避免 2 次网络请求
        $value = $this->redis->get($key);
        return ($value === false) ? null : $value;
    }
    
    /**
     * 设置指定键的值，如果键已经存在则覆盖原值
     */
    public function set(string $key, $value, int $expire = 0)
    {
        // [优化] 明确 expire 参数处理，兼容 phpredis 版本差异
        if ($expire > 0) {
            return $this->redis->set($key, $value, $expire);
        }
        return $this->redis->set($key, $value);
    }
    
    /**
     * 删除指定键，如果键不存在则返回 false，成功删除返回 true
     */
    public function del(string $key)
    {
        return $this->redis->del($key) > 0;
    }
    
    /**
     * 检查指定键是否存在，存在返回 true，不存在返回 false
     */
    public function exists(string $key)
    {
        return $this->redis->exists($key) > 0;
    }
    
    /**
     * 保存当前数据库状态到磁盘，成功返回 true，失败返回 false
     */
    public function save()
    {
        // [优化] 严禁使用同步 save 会阻塞 Redis 主线程，改为异步 bgsave
        // 注意：bgsave 返回的是状态信息，这里统一返回 bool 以符合原注释约定
        return $this->redis->bgsave(); 
    }
    
    /**
     * 列出当前数据库中所有键，支持通配符规则，默认返回所有键
     */
    public function list($rule = "*")
    {
        // [优化] 使用 SCAN 替代 KEYS，避免阻塞 Redis 服务
        $keys = [];
        $iterator = null;
        while ($arr_keys = $this->redis->scan($iterator, $rule, 1000)) {
            foreach ($arr_keys as $str_key) {
                $keys[] = $str_key;
            }
        }
        return $keys;
    }
    
    /**
     * keys 方法，=list 方法
     */
    public function keys($rule = "*")
    {
        return $this->list($rule);
    }

    /**
     * 向集合中添加一个或多个成员
     * @param string $key 集合键
     * @param string|array $members 成员或成员数组
     * @return int 添加的成员数量
     */
    public function sadd(string $key, $members): int
    {
        if (is_array($members)) {
            return $this->redis->sAdd($key, ...$members);
        }
        return $this->redis->sAdd($key, $members);
    }

    /**
     * 获取集合中的所有成员
     * @param string $key 集合键
     * @return array 成员数组
     */
    public function smembers(string $key): array
    {
        $members = $this->redis->sMembers($key);
        return $members ?: [];
    }

    /**
     * 从集合中移除一个或多个成员
     * @param string $key 集合键
     * @param string|array $members 成员或成员数组
     * @return int 移除的成员数量
     */
    public function srem(string $key, $members): int
    {
        if (is_array($members)) {
            return $this->redis->sRem($key, ...$members);
        }
        return $this->redis->sRem($key, $members);
    }

    /**
     * 检查成员是否在集合中
     * @param string $key 集合键
     * @param string $member 成员
     * @return bool 存在返回 true，否则返回 false
     */
    public function sismember(string $key, string $member): bool
    {
        return $this->redis->sIsMember($key, $member);
    }

    /**
     * 获取集合的成员数量
     * @param string $key 集合键
     * @return int 成员数量
     */
    public function scard(string $key): int
    {
        return $this->redis->sCard($key);
    }

    /**
     * 向有序集合添加成员
     * @param string $key 有序集合键
     * @param string $member 成员
     * @param float $score 分数（这里用于存储过期时间戳）
     * @return int 添加的成员数量
     */
    public function zadd(string $key, string $member, float $score): int
    {
        return $this->redis->zAdd($key, $score, $member);
    }

    /**
     * 获取有序集合中的所有成员
     * @param string $key 有序集合键
     * @return array 成员数组
     */
    public function zrange(string $key): array
    {
        $members = $this->redis->zRange($key, 0, -1);
        return $members ?: [];
    }

    /**
     * 从有序集合移除成员
     * @param string $key 有序集合键
     * @param string $member 成员
     * @return int 移除的成员数量
     */
    public function zrem(string $key, string $member): int
    {
        return $this->redis->zRem($key, $member);
    }

    /**
     * 从有序集合移除分数范围内的成员
     * @param string $key 有序集合键
     * @param float $min 最小分数
     * @param float $max 最大分数
     * @return int 移除的成员数量
     */
    public function zremrangebyscore(string $key, float $min, float $max): int
    {
        return $this->redis->zRemRangeByScore($key, $min, $max);
    }

    /**
     * 获取有序集合的成员数量
     * @param string $key 有序集合键
     * @return int 成员数量
     */
    public function zcard(string $key): int
    {
        return $this->redis->zCard($key);
    }
}