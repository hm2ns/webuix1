<?php
// 安全配置文件
return [
    'banIP' => [
        //'127.0.0.1'
    ],
    'allowedReferers' => ["*"],
    'apiTokens' => [
        [
            "appname" => "appkey",
            "publicKey" => "yS2mN6qS9zF3xB0qI6pX4kQ8",
            "privateKey" => "uT3xU5jZ2sF8lB2uA5cI0lK8fG0zH6yD0sZ2vM4oI6sG4fP5dZ1dJ6lN7nO5pZ9oT4yV3aS8wL5kR8gS6dP0jA6kB8oZ0kV7hL6oB1zS4rJ4gU9vF8dS6lG6mM4qL8aY",
            "eol" => time() + 3600, // 1小时后过期
        ]
    ]
];
