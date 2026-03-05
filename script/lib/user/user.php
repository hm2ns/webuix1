<?php
require_once includeLib("user/authorities");
require_once includeLib("user/department");

class USER
{
    private array $nowUser = [];
    private ?DB $db = null;
    private ?RC $redis = null;
    const string COOKIE_NAME = "uix_usr_token_";
    const int REDIS_PAGE = 0;
    private int $tokenlifetime = SECONDS_PER_DAY * 7;

    // 反查表 key 格式：user_tokens:{uid}
    const string USER_TOKENS_KEY_PREFIX = "user_tokens:";

    // 缓存相关
    private array $userInfoCache = [];
    private int $cacheLifetime = 300; // 缓存有效期 5 分钟

    const string BASIC_ROWS = "id,username,nickname,department,role,authority,status,profile"; // 用户基本信息列
    const string PASS_ROWS = "id,username,password,status"; // 身份验证列
    const string SOCIAL_ROWS = "id,username,nickname,email,phone,department,role,status,profile,create_time,update_time"; //
    const string BINDING_ROWS = "id,username,nickname,email,phone,department,role,status,profile,create_time,update_time,binding_stuid,binding_grade,binding_class";
    const string ALL_ROWS = "*"; // 所有列

    const string KEY_ID = "id";                     // 用户id
    const string KEY_USERNAME = "username";         // 用户名
    const string KEY_PASSWORD = "password";         // 密码
    const string KEY_EMAIL = "email";               //邮箱
    const string KEY_PHONE = "phone";               //手机
    const string KEY_NICKNAME = "nickname";         // 昵称
    const string KEY_DEPARTMENT = "department";     //部门
    const string KEY_ROLE = "role";                 //角色
    const string KEY_AUTHORITY = "authority";       //权限
    const string KEY_STATUS = "status";             //状态
    const string KEY_PROFILE = "profile";           //个人简介
    const string KEY_CREATE_TIME = "create_time";   //创建时间
    const string KEY_UPDATE_TIME = "update_time";   //更新时间
    const string KEY_BINDING_STUID = "binding_stuid"; //学号
    const string KEY_BINDING_GRADE = "binding_grade"; //年级
    const string KEY_BINDING_CLASS = "binding_class"; //班级

    const STATUS_NORMAL = "normal";     //正常
    const STATUS_DISABLED = "disabled"; //禁用
    const STATUS_DELETED = "deleted";   //删除
    const STATUS_BANNED = "banned";     //封禁

    const TABLE_NAME = "users"; // 用户表名

    const PASSWORD_CODER = PASSWORD_ARGON2ID; //密码加密方式

    const array ROLES = [ //角色
        "ROOT" => "ROOT",
        "ADMIN" => "系统管理员",
        "MANAGER" => "管理员",
        "TEACHER" => "教师",
        "STUDENT" => "学生",
    ];

    const DEFAULT_ROLE = "STUDENT";
    const ROOT_ROLE = "ROOT";
    const ADMIN_ROLE = "ADMIN";
    const MANAGER_ROLE = "MANAGER";
    const TEACHER_ROLE = "TEACHER";

    /**
     * 创建用户表
     * 结构：
     * id: 用户id
     * username: 用户名
     * password: 密码
     * email: 邮箱
     * phone: 手机号
     * nickname: 昵称
     * department: 部门
     * role: 角色
     * authority: 权限
     * status: 状态
     * profile: 个人简介
     * create_time: 创建时间
     * update_time: 更新时间
     * @return bool
     */
    public static function createTable(): bool
    {
        $db = DB_Connections::default();
        if ($db->createTable(self::TABLE_NAME, [
            "id" => "VARCHAR(255) primary key not null",
            "username" => "VARCHAR(255) not null unique",
            "password" => "VARCHAR(255) not null",
            "email" => "VARCHAR(255) unique",
            'phone' => 'VARCHAR(32)',
            'nickname' => 'VARCHAR(64) not null',
            'department' => 'VARCHAR(64)',
            'role' => 'VARCHAR(64)',
            'authority' => 'JSONB',
            'status' => 'VARCHAR(16)',
            'profile' => 'JSONB',
            'create_time' => 'TIMESTAMP not null default CURRENT_TIMESTAMP',
            'update_time' => 'TIMESTAMP not null default CURRENT_TIMESTAMP',
            'binding_stuid' => 'VARCHAR(40)',
            'binding_grade' =>  'VARCHAR(5)',
            'binding_class' => 'VARCHAR(5)'
        ])) {
            return $db->createIndex(self::TABLE_NAME, "username") &&
                $db->createIndex(self::TABLE_NAME, "email") &&
                $db->createIndex(self::TABLE_NAME, "phone") &&
                $db->createIndex(self::TABLE_NAME, "binding_stuid") &&
                $db->createIndex(self::TABLE_NAME, "department");
        }
        return false;
    }

    /**
     * 获取用户信息[id]
     * @param string $uid 用户id
     * @param string $rows 返回列
     * @return array|false
     */
    public static function getUserInfoById(string $uid, string $rows = self::BASIC_ROWS): array|false
    {
        $db = DB_Connections::default();
        return self::userInfoDecoder_($db->select(self::TABLE_NAME, [
            DB::whereEncoder("id", "=", $uid),
            DB::whereEncoder("status", "!=", self::STATUS_DELETED),
            DB::whereEncoder("status", "!=", self::STATUS_DISABLED),
        ], rows: $rows));
    }

    /**
     * 获取用户信息[username]
     * @param string $username 用户名
     * @param string $rows 列
     * @param bool $force 强制获取（包括已删除和已禁用的用户）
     * @return array|false
     */
    public static function getUserInfoByUsername(string $username, string $rows = self::BASIC_ROWS, bool $force = false): array|false
    {
        $db = DB_Connections::default();
        $where = [DB::whereEncoder("username", "=", $username)];
        if (!$force) {
            $where[] = DB::whereEncoder("status", "!=", self::STATUS_DELETED);
            $where[] = DB::whereEncoder("status", "!=", self::STATUS_DISABLED);
        }
        return self::userInfoDecoder_($db->select(self::TABLE_NAME, $where, rows: $rows));
    }
    /**
     * 获取用户信息[Stuid]
     * @param string $Stuid 学生唯一id
     * @param string $rows 列
     * @param bool $force 强制获取（包括已删除和已禁用的用户） 默认启用
     * @return array|false
     */
    public static function getUserInfoByStuid(string $stuid, string $rows = self::BASIC_ROWS, bool $force = true): array|false
    {
        $db = DB_Connections::default();
        $where = [DB::whereEncoder(self::KEY_BINDING_STUID, "=", $stuid)];
        if (!$force) {
            $where[] = DB::whereEncoder("status", "!=", self::STATUS_DELETED);
            $where[] = DB::whereEncoder("status", "!=", self::STATUS_DISABLED);
        }
        return self::userInfoDecoder_($db->select(self::TABLE_NAME, $where, rows: $rows));
    }
    /**
     * 获取用户信息[email]
     * @param string $email 邮箱
     * @param string $rows 列
     * @param bool $force 强制获取（包括已删除和已禁用的用户） 默认启用
     * @return array|false
     */
    public static function getUserInfoByEmail(string $email, string $rows = self::BASIC_ROWS, bool $force = false): array|false
    {
        $db = DB_Connections::default();
        $where = [DB::whereEncoder("email", "=", $email)];
        if (!$force) {
            $where[] = DB::whereEncoder("status", "!=", self::STATUS_DELETED);
            $where[] = DB::whereEncoder("status", "!=", self::STATUS_DISABLED);
        }
        return self::userInfoDecoder_($db->select(self::TABLE_NAME, $where, rows: $rows));
    }
    /**
     * 获取用户信息[phone]
     * @param string $phone 手机号
     * @param string $rows 列
     * @return array|false
     */
    public static function getUserInfoByPhone(string $phone, string $rows = self::BASIC_ROWS): array|false
    {
        $db = DB_Connections::default();
        return self::userInfoDecoder_($db->select(self::TABLE_NAME, [
            DB::whereEncoder("phone", "=", $phone),
            DB::whereEncoder("status", "!=", self::STATUS_DELETED),
            DB::whereEncoder("status", "!=", self::STATUS_DISABLED),
        ], rows: $rows));
    }

    /**
     * 返回默认的用户配置信息
     * @return array
     */
    public static function defaultCfg(string $username = "", string $uid = "", string $password = ""): array
    {
        $uid = $uid ?: uuidGenerator("usr_");
        $username = $username ?: "user_" . substr($uid, -8);
        return [
            'id' => $uid,
            'username' => $username,
            'password' => $password ? password_hash($password, self::PASSWORD_CODER) : "",
            'email' => '',
            'phone' => '',
            'nickname' => $username,
            'department' => '',
            'role' => self::DEFAULT_ROLE,
            'authority' => [],
            'status' => self::STATUS_DISABLED,
            'profile' => [],
            'create_time' => time(),
            'update_time' => time(),
        ];
    }

    /**
     * 解码用户信息
     * @param array &$cfg 用户配置
     * @return array
     */
    public static function userInfoDecoder(array &$cfg)
    {
        if (!is_array($cfg[self::KEY_PROFILE] ?? []))
            $cfg[self::KEY_PROFILE] = json_decode($cfg[self::KEY_PROFILE], true);
        if (!is_array($cfg[self::KEY_AUTHORITY] ?? []))
            $cfg[self::KEY_AUTHORITY] = json_decode($cfg[self::KEY_AUTHORITY], true);

        // 如果权限为空（即没有任何键值对），通过ROLE自动匹配权限组
        if (empty($cfg[self::KEY_AUTHORITY]) || !is_array($cfg[self::KEY_AUTHORITY])) {
            $cfg[self::KEY_AUTHORITY] = AUTHORITIES::getUserFullPermissions($cfg);
        }

        if (is_string($cfg[self::KEY_CREATE_TIME] ?? 0)) {
            $cfg[self::KEY_CREATE_TIME] = strtotime($cfg[self::KEY_CREATE_TIME]);
        }
        if (is_string($cfg[self::KEY_UPDATE_TIME] ?? 0)) {
            $cfg[self::KEY_UPDATE_TIME] = strtotime($cfg[self::KEY_UPDATE_TIME]);
        }
        return $cfg;
    }
    private static function userInfoDecoder_(array $cfg)
    {
        if (empty($cfg) || !isset($cfg[0])) {
            return [];
        }
        $cfg = $cfg[0];
        self::userInfoDecoder($cfg);
        return $cfg;
    }
    /**
     * 编码用户信息
     * @param array &$cfg 用户配置
     * @return array
     */
    public static function userInfoEncoder(array &$cfg)
    {
        if (is_array($cfg[self::KEY_PROFILE] ?? ""))
            $cfg[self::KEY_PROFILE] = json_encode($cfg[self::KEY_PROFILE]);
        if (is_array($cfg[self::KEY_AUTHORITY] ?? ""))
            $cfg[self::KEY_AUTHORITY] = json_encode($cfg[self::KEY_AUTHORITY]);
        if (is_int($cfg[self::KEY_CREATE_TIME] ?? "")) {
            $cfg[self::KEY_CREATE_TIME] = date("Y-m-d H:i:s", $cfg[self::KEY_CREATE_TIME]);
        }
        if (is_int($cfg[self::KEY_UPDATE_TIME] ?? "")) {
            $cfg[self::KEY_UPDATE_TIME] = date("Y-m-d H:i:s", $cfg[self::KEY_UPDATE_TIME]);
        }
        return $cfg;
    }

    /**
     * 创建用户
     * @param array $cfg 用户配置
     * @return uid
     */
    public static function createUser(array $cfg = []): string
    {
        $db = DB_Connections::default();
        $cfg = array_merge(self::defaultCfg(), $cfg);
        self::userInfoEncoder($cfg);
        try {
            $res = $db->insert(self::TABLE_NAME, $cfg, self::KEY_ID);
            return $res;
        } catch (Exception $e) {
            throw $e;
            return false;
        }
    }

    /**
     * 更新用户数据
     * @param array $cfg 用户配置
     * @return bool
     */
    public static function updateUser(array $cfg = []): bool
    {
        $db = DB_Connections::default();
        self::userInfoEncoder($cfg);
        try {
            $res = $db->update(self::TABLE_NAME, [
                DB::whereEncoder(self::KEY_ID, "=", $cfg[self::KEY_ID]),
            ], $cfg);
            return $res;
        } catch (Exception $e) {
            throw $e;
            return false;
        }
    }

    /**
     * 删除用户
     * @param string $id 用户ID
     * @return bool
     */
    public static function deleteUser(string $id): bool
    {
        $db = DB_Connections::default();
        try {
            $res = $db->update(self::TABLE_NAME, [
                DB::whereEncoder(self::KEY_ID, "=", $id),
            ], [
                self::KEY_STATUS => self::STATUS_DELETED,
            ]);
            return $res;
        } catch (Exception $e) {
            throw $e;
            return false;
        }
    }

    static private USER $instance;

    /**
     * 获取用户实例
     * @param bool $force 强制初始化Redis
     * @return USER

     */
    public static function getInstance(bool $force = false): USER
    {
        if (!isset(self::$instance)) {
            //先销毁旧实例
            self::$instance = new USER(false);
        }
        if (self::$instance->redis === null && $force) {  // 强制初始化Redis
            self::$instance->redis = new RC();
            self::$instance->redis->select(self::REDIS_PAGE);
        }
        return self::$instance;
    }

    /**
     * 构造函数 - 自动从Cookie中加载当前用户会话
     * @param bool $force 强制初始化Redis
     */
    public function __construct(bool $force = false)
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!empty($token) || $force) {
            $this->redis = new RC();
            $this->redis->select(self::REDIS_PAGE);
            $this->db = DB_Connections::default();
            $this->loadSession($token);
        }
    }

    // ==================== 动态类方法（当前用户状态管理）====================

    /**
     * 加载会话
     * @param string $token 令牌
     * @return bool
     */
    public function loadSession(string $token): bool
    {
        $redisKey = $token;
        $sessionData = $this->redis->get($redisKey);

        if (empty($sessionData)) {
            return false;
        }

        $sessionData = json_decode($sessionData, true);
        if (empty($sessionData)) {
            return false;
        }

        // 检查会话是否过期
        if (time() > $sessionData['expire']) {
            $this->logout();
            return false;
        }

        $this->nowUser = $sessionData;
        $this->nowUser =  array_merge($this->nowUser, $this->getCurrentUserInfo(self::BASIC_ROWS)); // 加载基本用户信息到会话中
        $this->nowUser[self::KEY_AUTHORITY] = AUTHORITIES::getUserFullPermissions($this->nowUser);
        return true;
    }

    /**
     * 检查用户是否已登录
     * @return bool
     */
    public function isLoggedIn(): bool
    {
        return !empty($this->nowUser) && isset($this->nowUser['uid']) && time() <= $this->nowUser['expire'];
    }

    /**
     * 获取当前用户ID
     * @return string|null
     */
    public function getCurrentUserId(): ?string
    {
        return $this->nowUser['uid'] ?? null;
    }

    /**
     * 获取当前用户完整信息
     * @param string $rows 返回的列
     * @param bool $useCache 是否使用缓存
     * @return array|false
     */
    public function getCurrentUserInfo(string $rows = self::BASIC_ROWS, bool $useCache = true): array|false
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userId = $this->getCurrentUserId();
        $cacheKey = $userId . ':' . $rows;

        // 尝试从缓存获取
        if ($useCache && isset($this->userInfoCache[$cacheKey])) {
            return $this->userInfoCache[$cacheKey];
        }

        // 从数据库查询
        $userInfo = self::getUserInfoById($userId, $rows);

        // 存入缓存
        if ($userInfo) {
            $this->userInfoCache[$cacheKey] = $userInfo;
        }

        return $userInfo;
    }

    /**
     * 清除当前用户信息缓存
     * @param string|null $rows 指定清除的列类型，null 表示清除所有缓存
     * @return void
     */
    public function clearUserInfoCache(?string $rows = null): void
    {
        if (!$this->isLoggedIn()) {
            return;
        }

        $userId = $this->getCurrentUserId();

        if ($rows === null) {
            // 清除该用户的所有缓存
            foreach (array_keys($this->userInfoCache) as $key) {
                if (str_starts_with($key, $userId . ':')) {
                    unset($this->userInfoCache[$key]);
                }
            }
        } else {
            // 清除指定列类型的缓存
            $cacheKey = $userId . ':' . $rows;
            unset($this->userInfoCache[$cacheKey]);
        }
    }

    /**
     * 获取当前会话信息
     * @return array
     */
    public function getSessionInfo(): array
    {
        return $this->nowUser ?? [];
    }

    /**
     * 当前用户所有权限组
     * @return array
     */
    public function getUserAuthority(): array
    {
        if (!$this->isLoggedIn()) {
            return [];
        }

        $userInfo = $this->getCurrentUserInfo(self::BASIC_ROWS);
        if (!$userInfo) {
            return [];
        }

        if ($userInfo[self::KEY_ROLE] == self::ROOT_ROLE) { //默认ROOT用户拥有所有权限
            $rootdefault = RBAC::defaultGroup();
            foreach ($rootdefault as $key => $value) {
                $userInfo[self::KEY_AUTHORITY][$key] = true;
            }
        }

        return AUTHORITIES::getUserFullPermissions($userInfo);
    }
    /**
     * 验证当前用户权限
     * @param string $permission 权限标识
     * @return bool
     */
    public function hasPermission(string $permission): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        // ROOT用户拥有所有权限
        if ($this->nowUser['role'] === 'ROOT') {
            return true;
        }

        $userInfo = $this->getCurrentUserInfo(self::ALL_ROWS);
        if (!$userInfo) {
            return false;
        }

        $authority = $userInfo[self::KEY_AUTHORITY] ?? [];
        return ($authority[$permission] ?? false);
    }

    /**
     * 验证当前用户角色
     * @param string|array $roles 角色或角色数组
     * @return bool
     */
    public function hasRole(string|array $roles): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        return in_array($this->nowUser['role'], $roles);
    }

    /**
     * 更新当前用户信息
     * @param array $data 要更新的数据
     * @return bool
     */
    public function updateCurrentUser(array $data): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $data[self::KEY_ID] = $this->getCurrentUserId();
        $result = self::updateUser($data);

        if ($result) {
            // 更新成功后刷新会话中的角色信息
            if (isset($data[self::KEY_ROLE])) {
                $this->nowUser['role'] = $data[self::KEY_ROLE];
            }

            // 清除缓存，确保数据一致性
            $this->clearUserInfoCache();
        }

        return $result;
    }

    /**
     * 修改当前用户密码
     * @param string $oldPassword 旧密码
     * @param string $newPassword 新密码
     * @return bool
     */
    public function changePassword(string $oldPassword, string $newPassword): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $userInfo = $this->getUserInfoById($this->getCurrentUserId(), self::PASS_ROWS);
        if (!$userInfo) {
            return false;
        }

        // 验证旧密码
        if (!password_verify($oldPassword, $userInfo[self::KEY_PASSWORD])) {
            return false;
        }

        // 更新密码
        return $this->updateCurrentUser([
            self::KEY_PASSWORD => password_hash($newPassword, self::PASSWORD_CODER)
        ]);
    }

    /**
     * 刷新会话过期时间
     * @param int $lifetime 新的过期时间（秒）
     * @return bool
     */
    public function refreshSession(int $lifetime = 0): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $lifetime = $lifetime ?? $this->tokenlifetime;
        $this->nowUser['expire'] = time() + $lifetime;
        $token = $_COOKIE[self::COOKIE_NAME] ?? '';

        if (empty($token)) {
            return false;
        }

        // 注意：会话存储的 key 就是 token 本身，不包含 COOKIE_NAME 前缀
        $redisKey = $token;
        $result = $this->redis->set($redisKey, json_encode($this->nowUser), $lifetime);

        // 同步更新反查表中的过期时间
        if ($result) {
            $tokensKey = self::USER_TOKENS_KEY_PREFIX . $this->getCurrentUserId();
            $this->redis->zadd($tokensKey, $token, $this->nowUser['expire']);
        }

        return $result;
    }

    /**
     * 登出当前用户
     * @return bool
     */
    public function logout(): bool
    {
        $token = $_COOKIE[self::COOKIE_NAME] ?? '';
        if (!empty($token)) {
            // 注意：会话存储的 key 就是 token 本身，不包含 COOKIE_NAME 前缀
            $redisKey = $token;
            $this->redis->del($redisKey);
            setcookie(self::COOKIE_NAME, '', time() - 3600, '/');

            // 从反查表移除 token
            if (!empty($this->nowUser['uid'])) {
                $tokensKey = self::USER_TOKENS_KEY_PREFIX . $this->nowUser['uid'];
                $this->redis->zrem($tokensKey, $token);
            }
        }

        $this->nowUser = [];
        return true;
    }

    /**
     * 获取用户的所有登录设备
     * @return array
     */
    public function getLoginDevices(): array
    {
        if (!$this->isLoggedIn()) {
            return [];
        }

        $uid = $this->getCurrentUserId();
        $currentToken = $_COOKIE[self::COOKIE_NAME] ?? '';

        // 从反查表获取用户的所有 token
        $tokensKey = self::USER_TOKENS_KEY_PREFIX . $uid;
        $tokens = $this->redis->zrange($tokensKey);

        if (empty($tokens)) {
            return [];
        }

        $devices = [];

        // 遍历 token 列表，获取每个会话的详细信息
        foreach ($tokens as $token) {
            // 注意：登录时存储的 key 就是 token 本身，不包含 COOKIE_NAME 前缀
            $redisKey = $token;
            $sessionData = $this->redis->get($redisKey);

            if ($sessionData) {
                $sessionData = json_decode($sessionData, true);
                // 确保会话数据匹配当前用户（防御性编程）
                if ($sessionData['uid'] === $uid) {
                    $devices[] = [
                        'token' => $token,
                        'device_info' => $sessionData['device_info'] ?? [],
                        'ip' => $sessionData['ip'] ?? '',
                        'login_time' => $sessionData['login_time'] ?? 0,
                        'expire' => $sessionData['expire'] ?? 0,
                        'is_current' => $token === $currentToken
                    ];
                } else {
                    // 数据不一致，从反查表移除无效 token
                    $this->redis->zrem($tokensKey, $token);
                }
            } else {
                // 会话已过期或不存在，从反查表移除
                $this->redis->zrem($tokensKey, $token);
            }
        }

        // 清理反查表中的过期 token（只删除已经不存在对应会话的）
        // 注意：这里需要谨慎，因为 ZSET 的 score 是过期时间戳
        // 但我们已经在上面的循环中处理了不存在的会话
        // 所以这里不再需要额外清理，避免误删

        return $devices;
    }

    /**
     * 踢出指定设备
     * @param string $deviceToken 设备令牌
     * @return bool
     */
    public function logoutDevice(string $deviceToken): bool
    {
        if (!$this->isLoggedIn()) {
            return false;
        }

        $uid = $this->getCurrentUserId();
        // 注意：会话存储的 key 就是 token 本身，不包含 COOKIE_NAME 前缀
        $redisKey = $deviceToken;
        $sessionData = $this->redis->get($redisKey);

        if (!$sessionData) {
            return false;
        }

        $sessionData = json_decode($sessionData, true);
        if ($sessionData['uid'] !== $uid) {
            return false;
        }

        // 删除会话数据
        $result = $this->redis->del($redisKey);

        // 从反查表移除 token
        if ($result) {
            $tokensKey = self::USER_TOKENS_KEY_PREFIX . $uid;
            $this->redis->zrem($tokensKey, $deviceToken);
        }

        return $result;
    }

    /**
     * 踢出除当前设备外的所有设备
     * @return int 被踢出的设备数量
     */
    public function logoutOtherDevices(): int
    {
        if (!$this->isLoggedIn()) {
            return 0;
        }

        $uid = $this->getCurrentUserId();
        $currentToken = $_COOKIE[self::COOKIE_NAME] ?? '';
        $count = 0;

        $devices = $this->getLoginDevices();
        foreach ($devices as $device) {
            if ($device['token'] !== $currentToken) {
                $this->logoutDevice($device['token']);
                $count++;
            }
        }

        return $count;
    }

    // ==================== 静态类方法（通用操作）====================

    /**
     * 用户登录
     * @param string $username 用户名
     * @param string $password 密码
     * @param array $deviceInfo 设备信息
     * @param bool $rememberMe 是否记住登录
     * @return array|false 返回用户信息或false
     */
    public static function login(string $username, string $password, array $deviceInfo = [], bool $rememberMe = false): array|false
    {
        $userInfo = self::getUserInfoByUsername($username, self::PASS_ROWS);
        if (!$userInfo) {
            return false;
        }

        // 验证密码
        if (!password_verify($password, $userInfo[self::KEY_PASSWORD])) {
            return false;
        }

        // 检查用户状态
        if ($userInfo[self::KEY_STATUS] !== self::STATUS_NORMAL) {
            return false;
        }

        // 生成令牌
        $token = uuidGenerator(self::COOKIE_NAME);
        $instance = self::getInstance(true);
        $lifetime = $instance->tokenlifetime;

        $sessionData = [
            'uid' => $userInfo[self::KEY_ID],
            'username' => $userInfo[self::KEY_USERNAME],
            'device_info' => $deviceInfo,
            'ip' => REQUEST::IP(),
            'login_time' => time(),
            'expire' => time() + $lifetime
        ];
        var_dump($token, json_encode($sessionData), $lifetime);
        // 存储会话到Redis
        $instance->redis->set($token, json_encode($sessionData), $lifetime);

        // 将 token 添加到用户的反查表（使用 ZSET，score 为过期时间戳）
        $tokensKey = self::USER_TOKENS_KEY_PREFIX . $userInfo[self::KEY_ID];
        $instance->redis->zadd($tokensKey, $token, $sessionData['expire']);

        // 设置Cookie
        setcookie(self::COOKIE_NAME, $token, time() + $lifetime, '/', '', false, true);

        return $userInfo;
    }

    /**
     * 验证密码
     * @param string $username 用户名
     * @param string $password 密码
     * @return bool
     */
    public static function verifyPassword(string $username, string $password): bool
    {
        $userInfo = self::getUserInfoByUsername($username, self::PASS_ROWS);
        if (!$userInfo) {
            return false;
        }

        return password_verify($password, $userInfo[self::KEY_PASSWORD]);
    }

    /**
     * 根据角色查询用户列表
     * @param string|array $roles 角色
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @return array
     */
    public static function getUsersByRole(string|array $roles, int $limit = 0, int $offset = 0): array
    {
        $db = DB_Connections::default();

        if (!is_array($roles)) {
            $roles = [$roles];
        }

        $where = [
            DB::whereEncoder("status", "!=", self::STATUS_DELETED),
            DB::whereEncoder("status", "!=", self::STATUS_DISABLED),
            DB::whereEncoder("role", "IN", $roles)
        ];

        $result = $db->select(self::TABLE_NAME, $where, rows: self::BASIC_ROWS, limit: $limit, offset: $offset);

        if (empty($result)) {
            return [];
        }

        return array_map(function ($user) {
            self::userInfoDecoder($user);
            return $user;
        }, $result);
    }

    /**
     * 根据部门查询用户列表
     * @param string $department 部门
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @return array
     */
    public static function getUsersByDepartment(string $department, int $limit = 0, int $offset = 0): array
    {
        $db = DB_Connections::default();

        $where = [
            DB::whereEncoder("status", "!=", self::STATUS_DELETED),
            DB::whereEncoder("status", "!=", self::STATUS_DISABLED),
            DB::whereEncoder("department", "=", $department)
        ];

        $result = $db->select(self::TABLE_NAME, $where, rows: self::BASIC_ROWS, limit: $limit, offset: $offset);

        if (empty($result)) {
            return [];
        }

        return array_map(function ($user) {
            self::userInfoDecoder($user);
            return $user;
        }, $result);
    }

    /**
     * 搜索用户
     * @param string $keyword 关键词
     * @param array $searchFields 搜索字段
     * @param int $limit 限制数量
     * @param int $offset 偏移量
     * @return array
     */
    public static function searchUsers(string $keyword, array $searchFields = ['username', 'nickname', 'email', 'phone'], int $limit = 0, int $offset = 0): array
    {
        $db = DB_Connections::default();

        $where = [
            DB::whereEncoder("status", "!=", self::STATUS_DELETED),
            DB::whereEncoder("status", "!=", self::STATUS_DISABLED)
        ];

        // 添加搜索条件
        foreach ($searchFields as $field) {
            $where[] = DB::whereEncoder($field, "LIKE", "%$keyword%", "OR");
        }

        $result = $db->select(self::TABLE_NAME, $where, rows: self::BASIC_ROWS, limit: $limit, offset: $offset);

        if (empty($result)) {
            return [];
        }

        return array_map(function ($user) {
            self::userInfoDecoder($user);
            return $user;
        }, $result);
    }

    /**
     * 批量更新用户状态
     * @param array $uids 用户ID数组
     * @param string $status 状态
     * @return bool
     */
    public static function batchUpdateStatus(array $uids, string $status): bool
    {
        $db = DB_Connections::default();

        $where = [
            DB::whereEncoder(self::KEY_ID, "IN", $uids)
        ];

        return $db->update(self::TABLE_NAME, $where, [
            self::KEY_STATUS => $status,
            self::KEY_UPDATE_TIME => date('Y-m-d H:i:s')
        ]);
    }
    /**
     * 检查用户名是否存在
     * @param string $username 用户名
     * @param string|null $excludeUid 排除的用户ID（用于更新时排除自己）
     * @return bool
     */
    public static function usernameExists(string $username, ?string $excludeUid = null): bool
    {
        $db = DB_Connections::default();

        $where = [
            DB::whereEncoder(self::KEY_USERNAME, "=", $username)
        ];

        if ($excludeUid) {
            $where[] = DB::whereEncoder(self::KEY_ID, "!=", $excludeUid);
        }

        $result = $db->select(self::TABLE_NAME, $where, rows: "COUNT(*) as count");
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * 检查邮箱是否存在
     * @param string $email 邮箱
     * @param string|null $excludeUid 排除的用户ID
     * @return bool
     */
    public static function emailExists(string $email, ?string $excludeUid = null): bool
    {
        if (empty($email)) {
            return false;
        }

        $db = DB_Connections::default();

        $where = [
            DB::whereEncoder(self::KEY_EMAIL, "=", $email)
        ];

        if ($excludeUid) {
            $where[] = DB::whereEncoder(self::KEY_ID, "!=", $excludeUid);
        }

        $result = $db->select(self::TABLE_NAME, $where, rows: "COUNT(*) as count");
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * 检查手机号是否存在
     * @param string $phone 手机号
     * @param string|null $excludeUid 排除的用户ID
     * @return bool
     */
    public static function phoneExists(string $phone, ?string $excludeUid = null): bool
    {
        if (empty($phone)) {
            return false;
        }

        $db = DB_Connections::default();

        $where = [
            DB::whereEncoder(self::KEY_PHONE, "=", $phone)
        ];

        if ($excludeUid) {
            $where[] = DB::whereEncoder(self::KEY_ID, "!=", $excludeUid);
        }

        $result = $db->select(self::TABLE_NAME, $where, rows: "COUNT(*) as count");
        return ($result[0]['count'] ?? 0) > 0;
    }

    /**
     * 根据Role获取中文名称
     * @param string $role 角色
     * @return string
     */
    public static function getRoleName(string $role): string
    {
        $role = strtoupper($role);
        return self::ROLES[$role] ?? '未知';
    }

    public static function getStatusName(string $status, bool $html = true): string
    {
        $status = strtolower($status);
        $name = "";
        $color = "";
        switch ($status) {
            case self::STATUS_NORMAL:
                $name = '正常';
                $color = "success";
                break;
            case self::STATUS_DISABLED:
                $name = '禁用';
                $color = "warning";
                break;
            case self::STATUS_DELETED:
                $name = '已删除';
                $color = "danger";
                break;
            default:
                $color = "secondary";
                $name = '未知';
        }
        return $html ? "<span class='badge bg-{$color}'>{$name}</span>" : $name;
    }

    /**
     * 获取用户头像
     * @param array $profile 用户信息 默认为当前用户信息
     * @return string
     */
    static public function getAvatar(array $profile = []): string
    {
        $defaultAvatar = GLOBAL_CONFIG::get("default_avatar", GLOBAL_CONFIG::get("logo", ""));
        $avatarProvider = GLOBAL_CONFIG::get("avatar_provider", "https://weavatar.com/avatar/");
        $userData = $profile ?: self::getInstance()->getCurrentUserInfo(self::SOCIAL_ROWS);
        if (!$userData) {
            return $defaultAvatar;
        }

        $email = $userData[self::KEY_EMAIL] ?? "";

        if ($email) {
            $avatar = rtrim($avatarProvider, '/') . '/' . hash("sha256", $email);
        } else {
            $avatar = $defaultAvatar;
        }

        return $avatar;
    }

    /**
     * 获取用户昵称
     * @param array $profile 用户信息 默认为当前用户信息
     * @return string
     */
    static public function getNickname(array $profile = []): string
    {
        $userData = $profile ?: self::getInstance()->getCurrentUserInfo(self::BASIC_ROWS);
        if (!$userData) {
            return "未知用户";
        }
        return $userData[self::KEY_NICKNAME] ?? $userData[self::KEY_USERNAME];
    }
}
