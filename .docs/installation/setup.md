# Установка и настройка Active Court

## Требования к системе

### Минимальные требования

- **PHP**: 8.2
- **MySQL**: 5.7 или выше / MariaDB 10.2 или выше
- **Веб-сервер**: Apache 2.4+ или Nginx 1.18+
- **Память**: 512 MB RAM
- **Дисковое пространство**: 1 GB свободного места

### Рекомендуемые требования

- **PHP**: 8.2
- **MySQL**: 8.0 или выше
- **Веб-сервер**: Apache 2.4+ с mod_rewrite или Nginx 1.18+
- **Память**: 2 GB RAM
- **Дисковое пространство**: 5 GB свободного места
- **SSL сертификат**: для HTTPS

### PHP расширения

```bash
# Обязательные расширения
php-mysql
php-mbstring
php-json
php-curl
php-gd
php-zip
php-xml
php-intl

# Рекомендуемые расширения
php-opcache
php-redis
php-memcached
php-imagick
```

## Подготовка к установке

### 1. Клонирование репозитория

```bash
# Клонирование проекта
git clone https://github.com/your-org/active-court.git
cd active-court

# Переключение на стабильную версию
git checkout v1.0.0
```

### 2. Настройка прав доступа

```bash
# Установка прав на папки
chmod -R 755 .
chmod -R 777 uploads/
chmod -R 777 logs/
chmod 644 config.php
```

### 3. Установка зависимостей

```bash
# Если используется Composer
composer install --no-dev --optimize-autoloader

# Или копирование внешних библиотек
cp -r classes/kcfinder/ /path/to/web/classes/
cp -r classes/pdf/ /path/to/web/classes/
cp -r classes/PHPMailer/ /path/to/web/classes/
```

## Быстрые команды PowerShell

Канонические команды проекта находятся в `bin/` и работают через PHP 8.2 в Windows и Linux. В Windows поверх них можно установить короткие PowerShell-команды:

```powershell
.\install-profile.ps1
```

Если текущая execution policy запрещает запуск локальных скриптов, разрешите его только для текущего процесса:

```powershell
Set-ExecutionPolicy -Scope Process Bypass
.\install-profile.ps1
```

Установщик изменяет `$PROFILE.CurrentUserAllHosts`, сохраняя пользовательское содержимое вне маркированного блока Active Court. Для другого профиля путь задаётся явно:

```powershell
.\install-profile.ps1 -TargetProfile $PROFILE.CurrentUserCurrentHost
```

После установки команды доступны в текущем сеансе:

| Команда | Назначение |
|---|---|
| `actest [аргументы]` | Запустить `php bin/actest` для текущего worktree с явным `--app-root` |
| `acdocs` | Проверить документацию текущего worktree |
| `acroot` | Перейти в корень текущего worktree |
| `acprofile [параметры]` | Управлять установленной версией профиля из текущего worktree |

Установщик копирует версионируемый `bin/active-court-profile.ps1` в нейтральный пользовательский каталог `%LOCALAPPDATA%\ActiveCourt\PowerShell`. Новые PowerShell-сеансы загружают последнюю явно установленную версию и не зависят от состояния `dev`, `mc` или другого worktree.

После изменения или получения новой версии профиля установите её из текущего worktree:

```powershell
acprofile -Update
# эквивалентно:
.\install-profile.ps1
```

Перезагрузить уже установленную версию только в текущем сеансе:

```powershell
acprofile -Reload
```

Проверить версии и доступность обновления:

```powershell
acprofile -Status
```

Установщик запрещает неявное понижение версии и изменение содержимого без повышения версии. Для осознанной локальной установки или отката исходника используется `-Force`, для восстановления предыдущей установленной копии - `-Rollback`.

Для режимов профиля доступны стабильные короткие параметры: `-s` (`-Status`), `-r` (`-Reload`), `-u` (`-Update`), `-b` (`-Rollback`), `-x` (`-Uninstall`) и `-f` (`-Force`). Например, `acprofile -u -f` выполняет принудительное обновление.

Основной язык CLI - английский. Встроены также немецкий и русский переводы. Язык задаётся при установке или обновлении через `-Language`/`-l` и сохраняется в `%LOCALAPPDATA%\ActiveCourt\PowerShell\settings.json`:

```powershell
.\install-profile.ps1 -l ru
acprofile -u -l de
acprofile -u -l en
```

Без параметра сохраняется текущая настройка, а при первой установке используется `en`. Значение `auto` выбирает встроенный язык по `$PSUICulture`. Допустим другой корректный код локали, но при отсутствии перевода используется английский fallback.

Удаление интеграции не затрагивает остальной профиль:

```powershell
.\install-profile.ps1 -Uninstall
```

Windows PowerShell и PowerShell 7 используют разные файлы профиля. Если нужны быстрые команды в обеих оболочках, запустите установщик отдельно в каждой.

Команды не привязаны к worktree, из которого установлен профиль. При каждом вызове они определяют проект через текущий каталог и `git rev-parse --show-toplevel`. Вне Active Court worktree команда завершается ошибкой и не использует `dev` или другой проект как fallback. Подробная логика и диагностика описаны в [руководстве по быстрым командам](../development/powershell-commands.md).

Краткая справка доступна через `h`, `help`, `-h` или `--help`:

```powershell
actest h
acdocs h
acroot h
acprofile h
```

После установки доступны дополнения по `Tab` и меню по `Ctrl+Space`: `acprofile -<Tab>` предлагает параметры профиля, `acprofile -l <Tab>` - языки, а `actest <Tab>` - suite, группы, специальные команды и основные параметры Codeception. Настройки `PSReadLine` профиль не изменяет.

## Настройка веб-сервера

### Apache конфигурация

#### Основной .htaccess

```apache
RewriteEngine On

# Перенаправление на HTTPS (для продакшена)
# RewriteCond %{HTTPS} off
# RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]

# Обработка маршрутов
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^(.*)$ index.php [QSA,L]

# Безопасность
<Files "config.php">
    Order allow,deny
    Deny from all
</Files>

<Files ".htaccess">
    Order allow,deny
    Deny from all
</Files>

# Кэширование статических файлов
<IfModule mod_expires.c>
    ExpiresActive On
    ExpiresByType text/css "access plus 1 month"
    ExpiresByType application/javascript "access plus 1 month"
    ExpiresByType image/png "access plus 1 month"
    ExpiresByType image/jpg "access plus 1 month"
    ExpiresByType image/jpeg "access plus 1 month"
    ExpiresByType image/gif "access plus 1 month"
</IfModule>
```

#### Виртуальный хост

```apache
<VirtualHost *:80>
    ServerName active-court.local
    ServerAlias www.active-court.local
    DocumentRoot /var/www/active-court

    <Directory /var/www/active-court>
        AllowOverride All
        Require all granted
    </Directory>

    ErrorLog ${APACHE_LOG_DIR}/active-court_error.log
    CustomLog ${APACHE_LOG_DIR}/active-court_access.log combined
</VirtualHost>
```

### Nginx конфигурация

#### Основной конфиг

```nginx
server {
    listen 80;
    server_name active-court.local www.active-court.local;
    root /var/www/active-court;
    index index.php;

    # Логи
    access_log /var/log/nginx/active-court_access.log;
    error_log /var/log/nginx/active-court_error.log;

    # Обработка PHP
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.2-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # Маршрутизация
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Статические файлы
    location ~* \.(css|js|png|jpg|jpeg|gif|ico|svg)$ {
        expires 1M;
        add_header Cache-Control "public, immutable";
    }

    # Безопасность
    location ~ /\. {
        deny all;
    }

    location ~ /(config|classes|core) {
        deny all;
    }
}
```

## Настройка базы данных

### 1. Создание базы данных

```sql
-- Создание базы данных
CREATE DATABASE active_court CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

-- Создание пользователя
CREATE USER 'active_court_user'@'localhost' IDENTIFIED BY 'strong_password_here';

-- Предоставление прав
GRANT ALL PRIVILEGES ON active_court.* TO 'active_court_user'@'localhost';
FLUSH PRIVILEGES;
```

### 2. Импорт структуры

```bash
# Импорт основной структуры
mysql -u active_court_user -p active_court < database/structure.sql

# Импорт начальных данных
mysql -u active_court_user -p active_court < database/initial_data.sql
```

### 3. Настройка конфигурации БД

```php
// app/config/DBConfig.php
class DBConfig extends BaseConfig
{
    public $hostname = 'localhost';
    public $username = 'active_court_user';
    public $password = 'strong_password_here';
    public $database = 'active_court';
    public $port = 3306;
    public $charset = 'utf8mb4';
    public $collation = 'utf8mb4_unicode_ci';
}
```

## Конфигурация приложения

### 1. Основные настройки (config.php)

```php
<?php
// Пути
defined('SHARED')      || define('SHARED', '/var/www/shared/');
defined('ROOT_PATH')   || define('ROOT_PATH', dirname(__FILE__) . DIRECTORY_SEPARATOR);

// Домен и URL
defined('HOST_NAME')     || define('HOST_NAME', 'active-court.local');
defined('BASE_HREF')     || define('BASE_HREF', 'https://'. HOST_NAME.'/');
defined('CDN_HREF')      || define('CDN_HREF', 'https://cdn.active-court.com/');

// Сессии
defined('SESSION_COURT') || define('SESSION_COURT', 'active_court');

// Окружение
defined('LOCAL_SERVER')  || define('LOCAL_SERVER', false);

// Загрузки
defined('UPLOAD_URL') || define('UPLOAD_URL', BASE_HREF . 'uploads/');
defined('UPLOAD_DIR') || define('UPLOAD_DIR', ROOT_PATH . 'uploads/');

// Экспорт
defined('EXPORT_COURT') || define('EXPORT_COURT', 'active_court');
```

### 2. Настройки приложения (AppConfig.php)

```php
class AppConfig extends BaseConfig
{
    public $hostName = HOST_NAME;
    public $baseURL = BASE_HREF;
    public $defaultLocale = 'de';
    public $supportedLocales = ['de', 'ru', 'en'];
    public $timezone = 'Europe/Berlin';
    public $charset = 'UTF-8';

    // Отладка
    public $displayErrors = false;
    public $logErrors = true;
    public $errorLogPath = ROOT_PATH . 'logs/error.log';

    // Безопасность
    public $encryptionKey = 'your-secret-key-here';
    public $sessionTimeout = 3600;
    public $maxLoginAttempts = 5;
}
```

### 3. Настройки авторизации (AuthConfig.php)

```php
class AuthConfig extends BaseConfig
{
    public $defaultGuard = 'web';
    public $sessionTimeout = 3600;
    public $rememberMeDuration = 604800; // 7 дней
    public $passwordMinLength = 8;
    public $requireEmailConfirmation = true;
    public $maxLoginAttempts = 5;
    public $lockoutDuration = 900; // 15 минут
}
```

### 4. Настройки платежей (PaymentConfig.php)

```php
class PaymentConfig extends BaseConfig
{
    public $defaultCurrency = 'EUR';
    public $supportedCurrencies = ['EUR', 'USD', 'RUB'];

    // PayPal
    public $paypalClientId = 'your-paypal-client-id';
    public $paypalSecret = 'your-paypal-secret';
    public $paypalMode = 'sandbox'; // или 'live'

    // Stripe
    public $stripePublishableKey = 'your-stripe-publishable-key';
    public $stripeSecretKey = 'your-stripe-secret-key';
}
```

## Настройка окружения

### 1. Переменные окружения

```bash
# .env файл (если используется)
APP_ENV=production
APP_DEBUG=false
APP_KEY=your-secret-key-here

DB_HOST=localhost
DB_DATABASE=active_court
DB_USERNAME=active_court_user
DB_PASSWORD=strong_password_here

MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME=your-email@gmail.com
MAIL_PASSWORD=your-app-password
```

### 2. Настройка PHP

```ini
; php.ini настройки
memory_limit = 256M
max_execution_time = 300
upload_max_filesize = 10M
post_max_size = 10M
date.timezone = Europe/Berlin

; Оптимизация
opcache.enable = 1
opcache.memory_consumption = 128
opcache.interned_strings_buffer = 8
opcache.max_accelerated_files = 4000
```

## Первоначальная настройка

### 1. Создание администратора

```php
// Скрипт создания админа
<?php
require_once 'bootstrap.php';

$engines = Service::engines();

$adminData = [
    'username' => 'admin',
    'email' => 'admin@active-court.com',
    'password' => 'admin123',
    'name' => 'Администратор',
    'surname' => 'Системы',
    'rights' => 2 // Админ права
];

$result = $engines->users->createUser($adminData);

if ($result) {
    echo "Администратор создан успешно!\n";
    echo "Логин: admin\n";
    echo "Пароль: admin123\n";
} else {
    echo "Ошибка создания администратора\n";
}
```

### 2. Настройка кортов

```sql
-- Добавление типов кортов
INSERT INTO court_types (name, description) VALUES
('Закрытые корты', 'Крытые спортивные площадки'),
('Открытые корты', 'Корты на открытом воздухе'),
('MC Arena', 'Специальные арены');

-- Добавление кортов
INSERT INTO courts (name, type_id, hourly_rate, status) VALUES
('Корт 1', 1, 50.00, 'active'),
('Корт 2', 1, 50.00, 'active'),
('Открытый корт 1', 2, 30.00, 'active'),
('MC Arena 1', 3, 100.00, 'active');
```

### 3. Настройка тарифов

```sql
-- Добавление тарифов
INSERT INTO pricing_rules (name, description, rate, type) VALUES
('Стандартный тариф', 'Обычная цена', 1.00, 'multiplier'),
('Скидка для студентов', 'Скидка 20% для студентов', 0.80, 'multiplier'),
('VIP тариф', 'Премиум обслуживание', 1.50, 'multiplier');
```

## Проверка установки

### 1. Проверка системы

```php
// check_system.php
<?php
require_once 'bootstrap.php';

echo "=== Проверка системы Active Court ===\n\n";

// Проверка PHP версии
echo "PHP версия: " . PHP_VERSION . "\n";
if (version_compare(PHP_VERSION, '8.2.0', '>=')) {
    echo "✓ PHP версия подходит\n";
} else {
    echo "✗ Требуется PHP 8.2\n";
}

// Проверка расширений
$required_extensions = ['mysql', 'mbstring', 'json', 'curl', 'gd', 'zip', 'xml'];
foreach ($required_extensions as $ext) {
    if (extension_loaded($ext)) {
        echo "✓ Расширение $ext установлено\n";
    } else {
        echo "✗ Расширение $ext не установлено\n";
    }
}

// Проверка подключения к БД
try {
    $engines = Service::engines();
    echo "✓ Подключение к базе данных успешно\n";
} catch (Exception $e) {
    echo "✗ Ошибка подключения к БД: " . $e->getMessage() . "\n";
}

// Проверка прав на папки
$writable_dirs = ['uploads', 'logs'];
foreach ($writable_dirs as $dir) {
    if (is_writable($dir)) {
        echo "✓ Папка $dir доступна для записи\n";
    } else {
        echo "✗ Папка $dir недоступна для записи\n";
    }
}

echo "\n=== Проверка завершена ===\n";
```

### 2. Тестовые страницы

- **Главная страница**: `http://active-court.local/`
- **Админ-панель**: `http://active-court.local/admin/`
- **API**: `http://active-court.local/mapi/`
- **Сенсорный экран**: `http://active-court.local/touchscreen/`

## Обновление системы

### 1. Резервное копирование

```bash
# Резервная копия файлов
tar -czf backup_files_$(date +%Y%m%d).tar.gz .

# Резервная копия базы данных
mysqldump -u active_court_user -p active_court > backup_db_$(date +%Y%m%d).sql
```

### 2. Обновление кода

```bash
# Получение обновлений
git pull origin main

# Обновление зависимостей
composer install --no-dev --optimize-autoloader

# Очистка кэша
rm -rf cache/*
```

### 3. Обновление базы данных

Подробная стратегия миграций: **[database-migration-strategy.md](../development/database-migration-strategy.md)**.

Синхронизация сайтовой БД с эталоном выполняется через `mapi/act.php`:

```bash
# Просмотр SQL без выполнения (dry-run)
# GET /mapi/act.php?view=1

# Применение изменений
# GET /mapi/act.php?run=1
```

```bash
# Ручное обновление структуры (если отдельный SQL-файл)
mysql -u active_court_user -p active_court < database/updates.sql
```

## Безопасность

### 1. Настройка SSL

```apache
# Apache SSL конфигурация
<VirtualHost *:443>
    ServerName active-court.local
    DocumentRoot /var/www/active-court

    SSLEngine on
    SSLCertificateFile /path/to/certificate.crt
    SSLCertificateKeyFile /path/to/private.key
    SSLCertificateChainFile /path/to/chain.crt

    # Принудительное перенаправление на HTTPS
    RewriteEngine On
    RewriteCond %{HTTP:X-Forwarded-Proto} !https
    RewriteRule ^(.*)$ https://%{HTTP_HOST}%{REQUEST_URI} [L,R=301]
</VirtualHost>
```

### 2. Настройка файрвола

```bash
# UFW настройки
ufw allow 22/tcp
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### 3. Регулярные обновления

```bash
# Автоматические обновления безопасности
apt update && apt upgrade -y

# Обновление PHP
apt install php8.2-fpm php8.2-mysql php8.2-curl php8.2-gd php8.2-mbstring
```

## Мониторинг и логирование

### 1. Настройка логирования

```php
// Настройка логов
class LoggerConfig extends BaseConfig
{
    public $logPath = ROOT_PATH . 'logs/';
    public $logLevel = 'info';
    public $maxLogFiles = 30;
    public $logRotation = 'daily';
}
```

### 2. Мониторинг системы

```bash
# Проверка состояния сервисов
systemctl status apache2
systemctl status mysql
systemctl status php8.2-fpm

# Мониторинг дискового пространства
df -h

# Мониторинг памяти
free -h
```

## Заключение

После выполнения всех шагов установки система Active Court будет готова к работе. Не забудьте:

1. Изменить пароли по умолчанию
2. Настроить SSL сертификат
3. Настроить резервное копирование
4. Настроить мониторинг
5. Протестировать все функции системы

Для получения дополнительной помощи обратитесь к документации или свяжитесь с командой поддержки.
