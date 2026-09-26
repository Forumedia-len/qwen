# Примеры использования трейта InstallTableIfNotExist

## Обзор улучшений

Трейт `InstallTableIfNotExist` был значительно улучшен для повышения надежности, производительности и удобства использования.

### Основные улучшения:

1. **Логирование** - детальное логирование всех операций
2. **Кэширование** - кэш проверенных таблиц для оптимизации
3. **Обработка ошибок** - улучшенная обработка исключений
4. **Валидация** - проверка входных параметров
5. **Цепочка вызовов** - поддержка fluent interface
6. **Статистика** - получение статистики установки
7. **Гибкость** - возможность принудительной переустановки

## Примеры использования

### Базовое использование

```php
<?php

use AC\core\system\engine\InstallTableIfNotExist;

class MyController
{
    use InstallTableIfNotExist;

    public function __construct()
    {
        // Добавляем таблицы для проверки
        $this->setCheckTable(['users', 'products', 'orders']);
    }

    public function initializeTables()
    {
        // Проверяем и устанавливаем таблицы
        $results = $this->checkAndInstallTables();

        // Анализируем результаты
        foreach ($results as $table => $result) {
            echo "Table {$table}: {$result['status']} - {$result['message']}\n";
        }
    }
}
```

### Использование с цепочкой вызовов

```php
<?php

class DatabaseManager
{
    use InstallTableIfNotExist;

    public function setupTables()
    {
        return $this
            ->setCheckTable('users')
            ->setCheckTable(['products', 'categories'])
            ->setCheckTable('orders')
            ->checkAndInstallTables();
    }
}
```

### Принудительная переустановка таблиц

```php
<?php

class TableUpdater
{
    use InstallTableIfNotExist;

    public function updateTables()
    {
        // Принудительно переустанавливаем таблицы
        $results = $this->checkAndInstallTables(['users', 'products'], true);

        return $results;
    }
}
```

### Получение статистики

```php
<?php

class TableMonitor
{
    use InstallTableIfNotExist;

    public function getStats()
    {
        $this->setCheckTable(['users', 'products', 'orders']);
        $this->checkAndInstallTables();

        $stats = $this->getInstallationStats();

        echo "Total tables to check: {$stats['total_tables']}\n";
        echo "Cached tables: {$stats['cached_tables']}\n";
        echo "Tables: " . implode(', ', $stats['tables_to_check']) . "\n";
    }
}
```

### Настройка поведения при ошибках

```php
<?php

class FlexibleTableInstaller
{
    use InstallTableIfNotExist;

    protected function shouldContinueOnError(): bool
    {
        // Продолжаем установку даже при ошибках
        return true;
    }

    public function installWithErrorHandling()
    {
        $results = $this->checkAndInstallTables(['users', 'products', 'orders']);

        $errors = array_filter($results, function($result) {
            return $result['status'] === 'error';
        });

        if (!empty($errors)) {
            echo "Some tables failed to install:\n";
            foreach ($errors as $table => $result) {
                echo "- {$table}: {$result['message']}\n";
            }
        }
    }
}
```

### Очистка кэша

```php
<?php

class CacheManager
{
    use InstallTableIfNotExist;

    public function refreshTableStatus()
    {
        // Очищаем кэш для принудительной перепроверки
        $this->clearTableCache();

        // Проверяем таблицы заново
        return $this->checkAndInstallTables();
    }
}
```

## Логирование

Трейт автоматически логирует все операции:

```
[2024-01-15 10:30:15] [info] [table_installation] Added tables to check: users, products, orders
[2024-01-15 10:30:15] [info] [table_installation] Starting table installation check for: users, products, orders
[2024-01-15 10:30:15] [info] [table_installation] Table users check result: not found
[2024-01-15 10:30:15] [info] [table_installation] Starting installation of table: users
[2024-01-15 10:30:15] [info] [table_installation] Generated 2 queries for table users
[2024-01-15 10:30:15] [info] [table_installation] Generated insert query for users with 5 records
[2024-01-15 10:30:15] [info] [table_installation] Successfully installed table: users
[2024-01-15 10:30:15] [info] [table_installation] Table installation completed | Context: {"users":{"status":"installed","message":"Installed successfully"}}
```

## Обработка ошибок

Трейт предоставляет детальную информацию об ошибках:

```php
try {
    $results = $this->checkAndInstallTables(['invalid_table']);
} catch (\RuntimeException $e) {
    echo "Installation failed: " . $e->getMessage();
}

// Или с продолжением при ошибках:
$results = $this->checkAndInstallTables(['table1', 'table2']);
foreach ($results as $table => $result) {
    if ($result['status'] === 'error') {
        // Обработка ошибки для конкретной таблицы
        echo "Failed to install {$table}: {$result['message']}\n";
    }
}
```

## Рекомендации по использованию

1. **Инициализация**: Добавляйте таблицы в конструкторе или методе инициализации
2. **Обработка результатов**: Всегда проверяйте результаты установки
3. **Логирование**: Используйте логи для отладки и мониторинга
4. **Кэширование**: Не очищайте кэш без необходимости для оптимизации
5. **Ошибки**: Настройте поведение при ошибках в зависимости от требований
6. **Тестирование**: Тестируйте установку таблиц в изолированной среде
