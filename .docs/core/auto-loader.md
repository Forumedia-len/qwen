# Система автозагрузки классов

## Обзор

Система автозагрузки классов в Active Court представляет собой PSR-4 совместимый автозагрузчик с поддержкой множественных пространств имен и приоритетной системой загрузки. Система обеспечивает гибкую и эффективную загрузку классов из различных директорий проекта.

## Основные компоненты

### Autoloader.php - Главный класс автозагрузчика

**Расположение**: `core/system/autoloader/Autoloader.php`

**Назначение**: Основной класс автозагрузчика, реализующий PSR-4 стандарт с дополнительными возможностями.

#### Основные свойства

```php
class Autoloader
{
    /** Namespace для ядра системы */
    protected $sharedNamespace;

    /** Путь до namespace ядра системы */
    protected $pathSharedNamespace;

    /** Namespace для сайта */
    protected $rootNamespace;

    /** Путь до namespace сайта */
    protected $pathRootNamespace;

    /** Базовые пространства имен */
    protected $baseNamespace = [];

    /** Префиксы пространств имен */
    protected $prefixes = [];

    /** Карта классов */
    protected $classmap = [];

    /** Список файлов для загрузки */
    protected $files = [];
}
```

#### Основные методы

```php
// Инициализация автозагрузчика
public function initialize(AutoloaderConfig $config): Autoloader

// Регистрация автозагрузчика
public function register()

// Загрузка класса
public function useClass($className)

// Добавление пространства имен
public function addNamespace($namespace, $path = null)

// Получение путей для пространства имен
public function getNamespace($prefix = null)

// Удаление пространства имен
public function removeNamespace($namespace)
```

### FileLocator.php - Локатор файлов

**Расположение**: `core/system/autoloader/FileLocator.php`

**Назначение**: Вспомогательный класс для поиска файлов классов в файловой системе.

#### Основные методы

```php
// Поиск файла класса
public function findFile($className, $namespace = null)

// Проверка существования файла
public function fileExists($filePath)

// Получение полного пути к файлу
public function getFullPath($relativePath)
```

### TemplateResolver.php — Иерархия шаблонов

`Service::templates()` возвращает `core/system/view/TemplateResolver.php`. Сервис определяет порядок
интерфейсов: поиск первого файла идёт `widget → site`, а объединение структуры и переводов — `site → widget`.
У остальных устройств список состоит из самого устройства. Общие слои (`common`) добавляет потребитель
по своим прежним правилам. `Url::getTemplateHierarchy()` сохранён как совместимая обёртка.

- `getLookupOrder()`, `getMergeOrder()` и `getParentTemplates()` возвращают нужный порядок устройств.
- `buildPaths()` собирает кандидатов по устройствам; внутри устройства сохраняется порядок callback.
- `findFile()` ищет первый кандидат через `FileLocator`, включая layouts с расширениями, отличными от PHP.
- `resolveTemplate()` ищет именованный шаблон в `app/tpl`, `resolvePath()` добавляет родительский вариант
  только для пути внутри выбранного интерфейса. Произвольные и общие пути не получают такое наследование.

Сначала проверяются все namespace одного кандидата, затем следующий кандидат. Поэтому базовый шаблон
widget имеет приоритет перед локальным шаблоном site, а локальный widget — перед базовым widget.
`View`, `Layouts`, `SiteDeviceAction` и `HttpErrorPage` используют этот сервис. Контроллеры сохраняют
отдельный порядок: каталог устройства, класс с суффиксом, каталоги родителей, общий класс.
При объединении слоёв сохраняется существующий порядок namespace `FileLocator::search()`.

`FileLocator::findPriorityPathToFile()` сохраняет прежнюю сигнатуру и PHP по умолчанию;
новый `findPriorityPathToFileWithExtension()` позволяет явно задать расширение.
Правила автозагрузки классов и произвольных файлов не меняются.

## Поддерживаемые пространства имен

### Основные пространства имен

```php
// Публичная часть (высший приоритет)
'' => 'site/'

// Основное пространство имен проекта
'AC\' => 'dev/'

// Приложение
'AC\app\' => 'dev/app/'

// Ядро системы
'AC\core\' => 'dev/core/'

// Модули системы
'AC\core\modules\{module_name}\' => 'dev/core/modules/{module_name}/'

// Системные компоненты
'AC\core\system\' => 'dev/core/system/'

// Движки
'AC\core\engines\' => 'dev/core/engines/'

// Сторонние библиотеки
'PHPMailer\' => 'dev/classes/PHPMailer/'
'Psr\' => 'dev/classes/Psr/'
```

### Приоритеты загрузки

1. **site/** (пустой namespace) - Высший приоритет

   - Публичная часть системы
   - Переопределение классов для конкретного сайта
   - Кастомизация функциональности

2. **dev/** (namespace AC\) - Основная разработка

   - Основной код системы
   - Модули и компоненты
   - Бизнес-логика

3. **Сторонние библиотеки** - Низший приоритет
   - PHPMailer для отправки email
   - PSR стандарты
   - Другие внешние зависимости

## Конфигурация автозагрузчика

### AutoloaderConfig.php

**Расположение**: `app/config/AutoloaderConfig.php`

```php
class AutoloaderConfig
{
    /** Namespace для ядра системы */
    public string $sharedNamespace = 'AC\\';

    /** Путь до namespace ядра системы */
    public string $pathSharedNamespace = 'dev/';

    /** Namespace для сайта */
    public string $rootNamespace = '';

    /** Путь до namespace сайта */
    public string $pathRootNamespace = 'site/';

    /** PSR-4 пространства имен */
    public array $psr4 = [
        'AC\\' => 'dev/',
        'PHPMailer\\' => 'dev/classes/PHPMailer/',
        'Psr\\' => 'dev/classes/Psr/'
    ];

    /** Карта классов */
    public array $classmap = [
        'Service' => 'app/locators/Service.php',
        'paths' => 'app/locators/Paths.php'
    ];

    /** Файлы для загрузки */
    public array $files = [
        'app/uses/defined.php',
        'app/uses/mysql.config.php'
    ];
}
```

## Автоматическое обнаружение классов

### Сканирование директорий

Система автоматически сканирует следующие директории для поиска классов:

```php
// Поддерживаемые пути для автоматической загрузки
$paths = [
    'site/',                                    // Основной приоритет
    'dev/core/modules/{module_name}/controllers/modComm/',
    'dev/core/system/module/modComm/controllers/',
    'dev/core/engines/',
    'dev/app/controllers/',
    'dev/app/models/',
    'dev/classes/',
    'dev/core/system/thirdParty/'
];
```

### Автоматическое определение пространств имен

Система автоматически определяет пространства имен на основе структуры директорий:

```php
// Примеры автоматического определения
'site/controllers/UserController.php' => '' (пустой namespace)
'dev/core/modules/accounts/controllers/AccountsController.php' => 'AC\core\modules\accounts\controllers\'
'dev/core/engines/AreasEngine.php' => 'AC\core\engines\'
'dev/app/models/UserModel.php' => 'AC\app\models\'
```

## Использование автозагрузчика

### Инициализация

```php
// В bootstrap.php
$autoloader = new Autoloader();
$config = new AutoloaderConfig();
$autoloader->initialize($config);
$autoloader->register();
```

### Загрузка классов

```php
// Автоматическая загрузка при использовании класса
$user = new UserController();  // Автоматически загрузит UserController.php

// Ручная загрузка класса
$autoloader->useClass('AC\core\modules\accounts\controllers\AccountsController');

// Загрузка файла с переменными
$autoloader->useFile('config.php', ['APP_NAME' => 'Active Court']);
```

### Добавление новых пространств имен

```php
// Добавление пространства имен для модуля
$autoloader->addNamespace('AC\core\modules\new_module', 'dev/core/modules/new_module/');

// Добавление сторонней библиотеки
$autoloader->addNamespace('Vendor\Library', 'vendor/library/src/');
```

## Модульная система автозагрузки

### Автоматическое добавление модулей

Система автоматически добавляет пространства имен для модулей:

```php
// Автоматическое добавление для модуля 'accounts'
'AC\core\modules\accounts\' => 'dev/core/modules/accounts/'
'AC\core\modules\accounts\controllers\' => 'dev/core/modules/accounts/controllers/'
'AC\core\modules\accounts\models\' => 'dev/core/modules/accounts/models/'
'AC\core\modules\accounts\views\' => 'dev/core/modules/accounts/views/'
```

### Приоритеты модулей

```php
// Повышение приоритета модуля
$autoloader->raisePriorityNamespaceModuleUp('accounts');

// Результат: модуль 'accounts' будет искаться раньше других модулей
```

## Кэширование загруженных классов

### Механизм кэширования

```php
// Кэширование загруженных классов
protected $loadedClasses = [];

public function useClass($className)
{
    // Проверка кэша
    if (isset($this->loadedClasses[$className])) {
        return $this->loadedClasses[$className];
    }

    // Загрузка класса
    $class = $this->loadInNamespace($className);

    // Сохранение в кэш
    $this->loadedClasses[$className] = $class;

    return $class;
}
```

### Очистка кэша

```php
// Очистка кэша загруженных классов
public function clearCache()
{
    $this->loadedClasses = [];
}
```

## Обработка ошибок

### Ошибки загрузки

```php
// Обработка ошибок при загрузке класса
protected function loadInNamespace($class)
{
    try {
        // Попытка загрузки класса
        $filePath = $this->findClassFile($class);

        if ($filePath && file_exists($filePath)) {
            require_once $filePath;
            return $class;
        }

        throw new ClassNotFoundException("Class {$class} not found");

    } catch (Exception $e) {
        // Логирование ошибки
        $this->logError($e);

        // Возврат false или выброс исключения
        return false;
    }
}
```

### Логирование ошибок

```php
// Логирование ошибок автозагрузки
protected function logError(Exception $e)
{
    $logger = Service::logger('autoloader');
    $logger->error('Autoloader error: ' . $e->getMessage(), [
        'class' => $class,
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
```

## Производительность

### Оптимизации

1. **Кэширование путей** - Кэширование найденных путей к файлам
2. **Ленивая загрузка** - Загрузка классов только при необходимости
3. **Приоритетная система** - Быстрый поиск в приоритетных директориях
4. **Индексация** - Предварительная индексация структуры директорий

### Метрики производительности

```php
// Отслеживание производительности
protected $metrics = [
    'classes_loaded' => 0,
    'cache_hits' => 0,
    'cache_misses' => 0,
    'load_time' => 0
];

public function getMetrics()
{
    return $this->metrics;
}
```

## Расширение автозагрузчика

### Создание кастомного автозагрузчика

```php
class CustomAutoloader extends Autoloader
{
    public function loadCustomClass($className)
    {
        // Кастомная логика загрузки
        $customPath = $this->getCustomPath($className);

        if (file_exists($customPath)) {
            require_once $customPath;
            return true;
        }

        return false;
    }

    protected function getCustomPath($className)
    {
        // Логика определения пути
        return 'custom/path/' . str_replace('\\', '/', $className) . '.php';
    }
}
```

### Интеграция с Composer

```php
// Интеграция с Composer автозагрузчиком
protected function discoverComposerNamespaces()
{
    $composerFile = 'vendor/composer/autoload_psr4.php';

    if (file_exists($composerFile)) {
        $composerNamespaces = require $composerFile;

        foreach ($composerNamespaces as $namespace => $paths) {
            $this->addNamespace($namespace, $paths[0]);
        }
    }
}
```

## Безопасность

### Валидация путей

```php
// Валидация путей к файлам
protected function validatePath($path)
{
    // Проверка на попытки обхода директорий
    if (strpos($path, '..') !== false) {
        throw new SecurityException('Invalid path detected');
    }

    // Проверка расширения файла
    if (pathinfo($path, PATHINFO_EXTENSION) !== 'php') {
        throw new SecurityException('Only PHP files are allowed');
    }

    return true;
}
```

### Санитизация имен классов

```php
// Санитизация имен классов
public function sanitizeClassName($className)
{
    // Удаление опасных символов
    $className = preg_replace('/[^a-zA-Z0-9\\_]/', '', $className);

    // Проверка на попытки инъекций
    if (strpos($className, 'eval') !== false || strpos($className, 'exec') !== false) {
        throw new SecurityException('Dangerous class name detected');
    }

    return $className;
}
```

## Отладка

### Режим отладки

```php
// Включение режима отладки
protected $debug = false;

public function enableDebug()
{
    $this->debug = true;
}

protected function debugLog($message, $data = [])
{
    if ($this->debug) {
        $logger = Service::logger('autoloader_debug');
        $logger->info($message, $data);
    }
}
```

### Информация о загруженных классах

```php
// Получение информации о загруженных классах
public function getLoadedClassesInfo()
{
    return [
        'total_loaded' => count($this->loadedClasses),
        'classes' => array_keys($this->loadedClasses),
        'metrics' => $this->metrics
    ];
}
```
