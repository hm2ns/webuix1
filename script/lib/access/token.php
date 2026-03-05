<?php

/**
 * CSRF token管理 生成和验证
 */
class CSRFTokenManager
{
    static public function generateToken(string $domin = "main"): string
    {
        $salt = "ZSV&**(&*&*" . date("YmdHis") . $domin;
        if (MyAcc->isLoggedIn()) {
            $salt .= MyAcc->getCurrentUserId();
        }
        $token = hash('sha384', $salt . rand(100000000, 999999999));
        $_SESSION['csrf_token_' . $domin] = $token;
        return $token;
    }

    static public function verifyToken(string $token, string $domin = "main"): bool
    {
        if (!$token) {
            return false;
        }
        $res = isset($_SESSION['csrf_token_' . $domin]) && $_SESSION['csrf_token_' . $domin] === $token;
        if ($res) {
            self::clearToken($domin);
        }
        return $res;
    }

    static public function clearToken(string $domin = "main"): void
    {
        unset($_SESSION['csrf_token_' . $domin]);
    }

    static public function getToken(string $domin = "main"): string
    {
        if (!isset($_SESSION['csrf_token_' . $domin])) {
            self::generateToken($domin);
        }
        return $_SESSION['csrf_token_' . $domin];
    }

    static public function getTokenInput($domin = "main"): string
    {
        return '<input type="hidden" name="csrf_token" value="' . self::getToken($domin) . '">';
    }

    static public function verifyTokenINPUT(string $method = "get", string $domin = "main"): bool
    {
        $method = strtolower($method);
        switch ($method) {
            case 'get':
                return self::verifyToken($_GET['csrf_token'] ?? '', $domin);
            case 'post':
                return self::verifyToken($_POST['csrf_token'] ?? '', $domin);
            case 'put':
                return self::verifyToken($_PUT['csrf_token'] ?? '', $domin);
            case 'delete':
                return self::verifyToken($_DELETE['csrf_token'] ?? '', $domin);
            default:
                return self::verifyToken($method ?? '', $domin);
        }
    }
}
