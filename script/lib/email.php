<?php
class EM
{
    private static $cfg = null;

    static public function init()
    {
        self::$cfg = require ROOTDIR . "/config/email.php";
    }

    static public function request($uri, $jsondata = [])
    {
        $url = self::$cfg['emserver'] . "/" . $uri;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($jsondata));
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Content-Length: ' . strlen(json_encode($jsondata))
        ]);
        $response = curl_exec($ch);
        return json_decode($response, true);
    }

    static public function ERRCODE($code)
    {
        return strtoupper(self::$cfg['errcodes'][$code] ?? $code);
    }

    static public function ERRCODECHI($code)
    {
        return self::$cfg['errcodes_CHI'][$code] ?? $code;
    }

    static public function send($to, $subject, $conntent)
    {
        if (!is_array($to)) {
            $to = [$to];
        }
        if (empty($to) || empty($subject) || empty($conntent)) {
            return false;
        }
        $data = [
            "to" => $to,
            "subject" => "淄博实验中学 -" . $subject,
            "content" => $conntent
        ];
        $res =  self::request("add", $data);
        return $res;
    }

    static public function getTaskInfo($id)
    {
        $res = self::request("task/$id");
        return $res;
    }

    static public function getServerStatus()
    {
        $res = self::request("status");
        return $res;
    }

    static public function getCfg()
    {
        return self::$cfg;
    }

    static public function sendToLevel($level, $subject, $conntent)
    {
        $users = self::getCfg()['receivers'][$level] ?? [];
        if (empty($users) || empty($subject) || empty($conntent)) {
            throw new InvalidArgumentException("邮件发送失败, 参数不完整");
            return false;
        }
        return self::send($users, $subject, $conntent);
    }

    static public function gen($header, $content, $uri = "")
    {
        $site_url = GLOBAL_CONFIG::get("site_url", "http://127.0.0.1/");
        $uri = $site_url . ltrim($uri, '/');

        // GitHub 风格配色
        $colors = [
            'bg' => '#f6f8fa',           // 页面背景
            'card' => '#ffffff',         // 卡片背景
            'text' => '#24292f',         // 主文字
            'text-secondary' => '#57606a', // 次要文字
            'border' => '#d0d7de',       // 边框
            'primary' => '#0969da',      // 主按钮/链接
            'primary-hover' => '#0550ae', // 按钮悬停
            'footer' => '#8c959f'        // 页脚文字
        ];

        return <<<HTML
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{$header}</title>
    <!--[if mso]>
    <noscript>
    <xml>
    <o:OfficeDocumentSettings>
    <o:PixelsPerInch>96</o:PixelsPerInch>
    </o:OfficeDocumentSettings>
    </xml>
    </noscript>
    <![endif]-->
    <style>
        /* 基础重置 */
        body, table, td, p, a, li, blockquote {
            -webkit-text-size-adjust: 100%;
            -ms-text-size-adjust: 100%;
            mso-line-height-rule: exactly;
        }
        table { border-collapse: collapse !important; }
        img { -ms-interpolation-mode: bicubic; border: 0; outline: none; text-decoration: none; }
        
        /* 响应式 */
        @media screen and (max-width: 600px) {
            .container { width: 100% !important; max-width: 100% !important; }
            .content { padding: 20px !important; }
            .btn { width: 100% !important; box-sizing: border-box; }
        }
    </style>
</head>
<body style="margin: 0; padding: 0; background-color: {$colors['bg']}; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', 'Noto Sans', Helvetica, Arial, sans-serif; color: {$colors['text']}; line-height: 1.5;">

    <!-- 外层容器 -->
    <table width="100%" cellpadding="0" cellspacing="0" border="0" style="background-color: {$colors['bg']};">
        <tr>
            <td align="center" style="padding: 24px 16px;">
                
                <!-- 邮件主体卡片 -->
                <table class="container" width="600" cellpadding="0" cellspacing="0" border="0" style="width: 600px; max-width: 600px; background-color: {$colors['card']}; border-radius: 6px; border: 1px solid {$colors['border']}; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.06);">
                    
                    <!-- 内容区域 -->
                    <tr>
                        <td class="content" style="padding: 32px;">
                            
                            <!-- 标题 -->
                            <h3 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 600; color: {$colors['text']}; line-height: 1.25;">
                                {$header}
                            </h3>
                            
                            <!-- 正文 -->
                            <p style="margin: 0 0 24px 0; font-size: 14px; color: {$colors['text']}; line-height: 1.5;">
                                {$content}
                            </p>
                            
                            <!-- 链接区域 -->
                            <p style="margin: 0 0 20px 0; font-size: 13px; color: {$colors['text-secondary']}">
                                相关链接：<a href="{$uri}" style="color:{$colors['primary']}; text-decoration: none; word-break: break-all;">{$uri}</a>
                            </p>
                            
                            <!-- 按钮 -->
                            <table cellpadding="0" cellspacing="0" border="0" style="border-collapse: separate;">
                                <tr>
                                    <td style="border-radius: 6px; background-color: {$colors['primary']}">
                                        <a href="{$uri}" class="btn" target="_blank" style="display: inline-block; padding: 10px 20px; font-size: 14px; font-weight: 500; color: #ffffff; text-decoration: none; border-radius: 6px; background-color: {$colors['primary']}; border: 1px solid{$colors['primary']}; mso-padding-alt: 0;">
                                            前往查看
                                        </a>
                                    </td>
                                </tr>
                            </table>
                            
                        </td>
                    </tr>
                    
                    <!-- 分隔线 -->
                    <tr>
                        <td style="height: 1px; background-color: {$colors['border']}; line-height: 1px;">&nbsp;</td>
                    </tr>
                    
                    <!-- 页脚 -->
                    <tr>
                        <td style="padding: 16px 32px; background-color: {$colors['bg']};">
                            <p style="margin: 0; font-size: 12px; color: {$colors['footer']}; line-height: 1.4;">
                                此邮件由系统自动发送，请勿直接回复。<br>
                                © ZSV Studio. All rights reserved.
                            </p>
                        </td>
                    </tr>
                    
                </table>
                
                <!-- 外部间距 -->
                <div style="height: 32px; font-size: 32px;">&nbsp;</div>
                
            </td>
        </tr>
    </table>

</body>
</html>
HTML;
    }
}
