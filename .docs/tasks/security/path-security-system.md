# Система безопасности путей

> **Статус:** требует актуализации перед реализацией  
> **Область:** безопасность удалённых файловых операций  
> **Последняя проверка:** 2026-08-24  
> **Связанный код:** `core/system/security/` (планируется)

## Обзор

Система безопасности путей предназначена для предотвращения несанкционированного доступа к файлам и директориям через удаленные вызовы.

## Проблемы безопасности

### 1. Path Traversal атаки

- Попытки доступа к файлам вне разрешенных директорий
- Использование `../` для навигации по директориям
- Доступ к системным файлам

### 2. Удаленный доступ к файлам

- Прямые ссылки на файлы
- Обход ограничений через параметры URL
- Доступ к конфигурационным файлам

## Решение

### 1. Валидатор безопасности путей

```php
class PathSecurityValidator
{
    private array $allowedPaths = [];
    private array $forbiddenPaths = [];
    private string $rootPath;

    public function __construct(string $rootPath = null)
    {
        $this->rootPath = $rootPath ?: $_SERVER['DOCUMENT_ROOT'];
        $this->loadAllowedPaths();
        $this->loadForbiddenPaths();
    }

    public function validate(string $path): bool
    {
        // Нормализация пути
        $normalizedPath = $this->normalizePath($path);

        // Проверка на запрещенные пути
        if ($this->isForbiddenPath($normalizedPath)) {
            return false;
        }

        // Проверка на разрешенные пути
        return $this->isAllowedPath($normalizedPath);
    }

    private function normalizePath(string $path): string
    {
        // Удаление null bytes
        $path = str_replace(chr(0), '', $path);

        // Нормализация разделителей
        $path = str_replace(['\\', '/'], DIRECTORY_SEPARATOR, $path);

        // Удаление множественных разделителей
        $path = preg_replace('/' . preg_quote(DIRECTORY_SEPARATOR, '/') . '+/', DIRECTORY_SEPARATOR, $path);

        // Получение реального пути
        $realPath = realpath($path);

        return $realPath ?: $path;
    }

    private function isForbiddenPath(string $path): bool
    {
        foreach ($this->forbiddenPaths as $forbiddenPath) {
            if (strpos($path, $forbiddenPath) === 0) {
                return true;
            }
        }
        return false;
    }

    private function isAllowedPath(string $path): bool
    {
        foreach ($this->allowedPaths as $allowedPath) {
            if (strpos($path, $allowedPath) === 0) {
                return true;
            }
        }
        return false;
    }
}
```

### 2. Список разрешенных путей

```php
private function loadAllowedPaths(): void
{
    $this->allowedPaths = [
        // Публичные директории
        $_SERVER['DOCUMENT_ROOT'] . '/uploads/',
        $_SERVER['DOCUMENT_ROOT'] . '/assets/',
        $_SERVER['DOCUMENT_ROOT'] . '/images/',

        // Директории проекта (только для авторизованных пользователей)
        $_SERVER['DOCUMENT_ROOT'] . '/core/modules/',
        $_SERVER['DOCUMENT_ROOT'] . '/app/',

        // Временные файлы
        sys_get_temp_dir(),
    ];
}
```

### 3. Список запрещенных путей

```php
private function loadForbiddenPaths(): void
{
    $this->forbiddenPaths = [
        // Системные директории
        '/etc/',
        '/var/log/',
        '/proc/',
        '/sys/',

        // Конфигурационные файлы
        $_SERVER['DOCUMENT_ROOT'] . '/config.php',
        $_SERVER['DOCUMENT_ROOT'] . '/.env',
        $_SERVER['DOCUMENT_ROOT'] . '/.htaccess',

        // Директории с правами доступа
        $_SERVER['DOCUMENT_ROOT'] . '/.git/',
        $_SERVER['DOCUMENT_ROOT'] . '/vendor/',

        // Логи и отладочные файлы
        $_SERVER['DOCUMENT_ROOT'] . '/logs/',
        $_SERVER['DOCUMENT_ROOT'] . '/debug/',
    ];
}
```

## Интеграция в систему

### 1. Middleware для проверки путей

```php
class PathSecurityMiddleware
{
    private PathSecurityValidator $validator;

    public function __construct()
    {
        $this->validator = new PathSecurityValidator();
    }

    public function handle($request, $next)
    {
        // Проверка параметров запроса
        $this->validateRequestParameters($request);

        // Проверка загружаемых файлов
        $this->validateUploadedFiles($request);

        return $next($request);
    }

    private function validateRequestParameters($request): void
    {
        $parameters = array_merge(
            $request->getQueryParams(),
            $request->getParsedBody() ?? []
        );

        foreach ($parameters as $key => $value) {
            if (in_array($key, ['file', 'path', 'directory', 'folder'])) {
                if (!$this->validator->validate($value)) {
                    throw new SecurityException('Недопустимый путь: ' . $value);
                }
            }
        }
    }
}
```

### 2. Хелпер для безопасной работы с файлами

```php
class SecureFileHelper
{
    private PathSecurityValidator $validator;

    public function __construct()
    {
        $this->validator = new PathSecurityValidator();
    }

    public function getSecurePath(string $path): string
    {
        if (!$this->validator->validate($path)) {
            throw new SecurityException('Недопустимый путь: ' . $path);
        }

        return $path;
    }

    public function readFile(string $path): string
    {
        $securePath = $this->getSecurePath($path);

        if (!file_exists($securePath)) {
            throw new FileNotFoundException('Файл не найден: ' . $securePath);
        }

        return file_get_contents($securePath);
    }

    public function writeFile(string $path, string $content): bool
    {
        $securePath = $this->getSecurePath($path);

        return file_put_contents($securePath, $content) !== false;
    }
}
```

## Конфигурация

### 1. Файл конфигурации безопасности

```php
// config/security.php
return [
    'paths' => [
        'allowed' => [
            'uploads/',
            'assets/',
            'images/',
            'temp/',
        ],
        'forbidden' => [
            'config/',
            'logs/',
            '.git/',
            'vendor/',
            'node_modules/',
        ],
        'root_path' => $_SERVER['DOCUMENT_ROOT'],
    ],
    'validation' => [
        'strict_mode' => true,
        'log_violations' => true,
        'block_suspicious' => true,
    ],
];
```

### 2. Логирование нарушений

```php
class SecurityLogger
{
    public function logViolation(string $path, string $ip, string $userAgent): void
    {
        $logEntry = [
            'timestamp' => date('Y-m-d H:i:s'),
            'path' => $path,
            'ip' => $ip,
            'user_agent' => $userAgent,
            'type' => 'path_violation',
        ];

        file_put_contents(
            $_SERVER['DOCUMENT_ROOT'] . '/logs/security.log',
            path-security-system.mdjson_encode($logEntry) . PHP_EOL,
            FILE_APPEND | LOCK_EX
        );
    }
}
```

## Тестирование

### 1. Unit тесты

```php
class PathSecurityValidatorTest extends TestCase
{
    private PathSecurityValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new PathSecurityValidator();
    }

    public function testValidPaths(): void
    {
        $validPaths = [
            '/uploads/file.jpg',
            '/assets/css/style.css',
            '/images/logo.png',
        ];

        foreach ($validPaths as $path) {
            $this->assertTrue($this->validator->validate($path));
        }
    }

    public function testInvalidPaths(): void
    {
        $invalidPaths = [
            '/etc/passwd',
            '/var/log/apache2/access.log',
            '../../../config.php',
            '/.git/config',
        ];

        foreach ($invalidPaths as $path) {
            $this->assertFalse($this->validator->validate($path));
        }
    }
}
```

## Мониторинг и отчеты

### 1. Дашборд безопасности

- Количество попыток нарушения безопасности
- Список заблокированных путей
- Статистика по IP адресам
- Время последних нарушений

### 2. Уведомления

- Email уведомления при критических нарушениях
- SMS уведомления для администраторов
- Интеграция с системами мониторинга

## Планы развития

1. **Машинное обучение**: Анализ паттернов атак
2. **Геолокация**: Блокировка по географическому расположению
3. **Rate limiting**: Ограничение количества запросов
4. **Honeypot**: Ловушки для злоумышленников
5. **Аудит**: Подробные отчеты о доступе к файлам
