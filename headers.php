<?php
/**
 * Security Headers Configuration
 * Настройка заголовков безопасности для защиты веб-приложения
 */



class SecurityHeaders
{
    private string $nonce;
    private bool $reportOnly = false;
    private ?string $reportUri = null;

    public function __construct()
    {
        $this->nonce = base64_encode(random_bytes(16));
    }

    public function getNonce(): string
    {
        return $this->nonce;
    }

    /**
     * Включить режим отладки (ТОЛЬКО ДЛЯ РАЗРАБОТКИ)
     */
    public function enableDebugMode(): self
    {
        $this->reportOnly = true;
        $this->reportUri = '/csp-report.php';
        return $this;
    }

    private function buildCSP(): string
    {
        // Директивы для скриптов: разрешаем nonce + unsafe-inline как fallback
        $scriptSrc = implode(' ', [
            "'nonce-{$this->nonce}'",
            "'unsafe-inline'", // fallback для старых браузеров без поддержки nonce
            "'self'",
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://code.jquery.com',
            'https://www.googletagmanager.com',
            'https://www.google-analytics.com',
            'https://unpkg.com',
            'https://ajax.googleapis.com',
            'https://*.googleapis.com',
            'https://cdn.active-court.com',
            'https://testsystem.active-court.com' // для тестового окружения
        ]);

        // Директивы для стилей: ТОЛЬКО unsafe-inline (без nonce!)
        // Причина: атрибуты style="" НЕ МОГУТ иметь nonce, и 'unsafe-inline' игнорируется при наличии nonce
        $styleSrc = implode(' ', [
            "'unsafe-inline'", // ОБЯЗАТЕЛЬНО без nonce!
            "'self'",
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://fonts.googleapis.com',
            'https://cdn.active-court.com',
            'https://testsystem.active-court.com'
        ]);

        // Директивы для изображений
        $imgSrc = implode(' ', [
            "'self'",
            'https://*',
            'data:',
            'blob:',
            'https://cdn.active-court.com',
            'https://testsystem.active-court.com',
            'https://*.googleapis.com',
            'https://*.googleusercontent.com'
        ]);

        // Директивы для шрифтов
        $fontSrc = implode(' ', [
            "'self'",
            'https://cdn.jsdelivr.net',
            'https://cdnjs.cloudflare.com',
            'https://fonts.gstatic.com',
            'https://cdn.active-court.com',
            'https://testsystem.active-court.com',
            'data:'
        ]);

        // Директивы для подключений (AJAX, WebSocket, fetch)
        $connectSrc = implode(' ', [
            "'self'",
            'https://cdn.active-court.com',
            'https://testsystem.active-court.com',
            'https://www.google-analytics.com',
            'https://*.googleapis.com',
            'wss://*'
        ]);

        $directives = [
            "default-src 'self'",
            "script-src {$scriptSrc}",
            "style-src {$styleSrc}",
            "img-src {$imgSrc}",
            "font-src {$fontSrc}",
            "connect-src {$connectSrc}",
            "frame-ancestors 'none'",
            "object-src 'none'",
            "base-uri 'self'",
            "form-action 'self'",
            "frame-src 'self' https://www.googletagmanager.com",
            "manifest-src 'self'",
            "media-src 'self' https://cdn.active-court.com",
            "worker-src 'self' blob:",
            "upgrade-insecure-requests"
        ];

        if ($this->reportUri) {
            $directives[] = "report-uri {$this->reportUri}";
        }

        return implode('; ', $directives);
    }

    public function apply(): void
    {
        $headerName = $this->reportOnly ? 'Content-Security-Policy-Report-Only' : 'Content-Security-Policy';
//        header($headerName . ': ' . $this->buildCSP());
        
        // HSTS (только для HTTPS)
       // if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            header('Strict-Transport-Security: max-age=31536000; includeSubDomains; preload');
       // }
        
        // X-Frame-Options
        header('X-Frame-Options: SAMEORIGIN');
        
        // X-Content-Type-Options
        header('X-Content-Type-Options: nosniff');
        
        // X-XSS-Protection (для старых браузеров)
        header('X-XSS-Protection: 1; mode=block');
        
        // Referrer-Policy
        header('Referrer-Policy: strict-origin-when-cross-origin');
        
        // Permissions-Policy
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=()');
        
        // Feature-Policy (устаревший, но для совместимости)
        header('Feature-Policy: geolocation \'none\'; microphone \'none\'; camera \'none\'');
        
        // Настройки сессий
        ini_set('session.cookie_httponly', '1');
        if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            ini_set('session.cookie_secure', '1');
        }
        ini_set('session.cookie_samesite', 'Strict');
        ini_set('session.use_strict_mode', '1');
    }

    /**
     * Генерация тега <script> с правильным nonce
     */
    public function scriptTag(string $content): string
    {
        return sprintf(
            "<script nonce=\"%s\">%s</script>",
            htmlspecialchars($this->nonce, ENT_QUOTES, 'UTF-8'),
            $content
        );
    }
}
