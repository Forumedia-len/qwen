<?php
/**
 * Класс Ical
 * 
 * Генерирует и отправляет календарные файлы формата iCal (.ical) через FTP.
 * Используется для создания расписаний управления освещением, отоплением и сетевым оборудованием.
 * 
 * @package AC\app\cron
 * @author Forumedia
 * @version 1.0
 */

namespace AC\app\cron;

use AC\core\engines\Engines;
use AC\core\system\ftp\FTPClient;
use AC\core\system\db\Query;
use Service;

class Ical
{
    /**
     * Ядро системы (движок)
     * @var Engines
     */
    protected $core = "";

    /**
     * Экземпляр движка
     * @var Engines
     */
    protected $engine;

    /**
     * FTP-сервер
     * @var string
     */
    protected $serv = "";

    /**
     * FTP-пользователь
     * @var string
     */
    protected $user = "";

    /**
     * FTP-пароль
     * @var string
     */
    protected $pwd = "";

    /**
     * FTP-директория
     * @var string
     */
    protected $dir = "";

    /**
     * Сайт/домен (без расширения)
     * @var string
     */
    protected $site;

    /**
     * Массив типов областей
     * @var array
     */
    protected $arr_type = [
        1 => "licht",   // Освещение
        2 => "heizen",  // Отопление
        3 => "netz"      // Сеть
    ];

    /**
     * Экземпляр FTP-клиента
     * @var FTPClient
     */
    protected $ftp;

    /**
     * Устанавливает FTP-параметры и подключается к серверу
     * 
     * @param string $serv FTP-сервер
     * @param string $user FTP-пользователь
     * @param string $pwd FTP-пароль
     * @param string $dir FTP-директория (опционально)
     * @return void
     */
    public function setFtp($serv, $user, $pwd, $dir)
    {
        $this->serv = $serv;
        $this->user = $user;
        $this->pwd = $pwd;

        if (!empty($dir)) {
            $this->dir = $dir;
        }

        // Инициализация или переподключение FTP-клиента
        if (empty($this->ftp)) {
            $this->ftp = FTPClient::getInstance();
        } else {
            $this->ftp->disconnect();
        }

        $this->ftp->setCredentials($this->serv, $this->user, $this->pwd, 21 ,true);
      
        if (!$this->ftp->connect()) {
            return;
        }

        // Смена директории, если указана
        if (!empty($this->dir)) {
            $this->ftp->changeDirectory($this->dir);
        }
    }

    /**
     * Устанавливает путь к ядру системы
     * 
     * @param string $core_path Путь к ядру
     * @return void
     */
    public function setCore($core_path)
    {
        $this->core = $core_path;
    }

    /**
     * Устанавливает параметры базы данных
     * 
     * @param string $bd_name Название БД
     * @param string $bd_prefix Префикс таблиц
     * @return void
     */
    public function setBD($bd_name, $bd_prefix)
    {
        Query::setBD($bd_name, $bd_prefix);
    }

    /**
     * Инициализирует систему и движок
     * 
     * @param string $bd_user Пользователь БД
     * @param string $bd_pwd Пароль БД
     * @return void
     */
    public function init($bd_user, $bd_pwd)
    {
        // Определение констант БД
        defined('DB_USERNAME') || define('DB_USERNAME', $bd_user);
        defined('DB_PASSWORD') || define('DB_PASSWORD', $bd_pwd);
        defined('DB_DATABASE_NAME') || define('DB_DATABASE_NAME', 'at_base_active_court');
        defined('DB_TABLE_PREFIX') || define('DB_TABLE_PREFIX', 'at_base_active_court_');
        defined('SHARED_PATH') || define('SHARED_PATH', $this->core);

        // Подключение конфигурации
        include "{$this->core}config.php";

        // Инициализация движка
        // Service::app(); // Закомментировано
        $this->engine = new Engines();
    }

    /**
     * Отправляет файлы на FTP-сервер
     * 
     * @param array $arr_files Массив файлов [имя => содержимое]
     * @return void
     */
    public function send($arr_files)
    {
        if (!empty($arr_files)) {
            $this->ftp->uploadFilesFromString($arr_files);
        }
    }

    /**
     * Создает массив данных для iCal файла
     * 
     * @param int $area_id ID области
     * @param int $type Тип (1-освещение, 2-отопление, 3-сеть)
     * @param array &$arr_files Ссылка на массив файлов
     * @return void
     */
    public function create_array($area_id, $type, &$arr_files)
    {
        // Генерация данных расписания
        $out = $this->engine->light->runtimeLightDirection($this->engine, [$area_id], $type, 1);

        // Если данные пустые, создаем пустой календарь
        if (empty($out)) {
            $out = 'BEGIN:VCALENDAR' . "\n";
            $out .= 'PRODID:-//Forumedia iCal for Web I/O v.1.0//EN' . "\n";
            $out .= 'END:VCALENDAR' . "\n";
        }

        // Формирование имени файла: сайт_тип_id.ical
        $str_name = "{$this->site}_{$this->arr_type[$type]}_{$area_id}.ical";
        $arr_files[$str_name] = $out;
      $arr_files["{$this->site}_{$this->arr_type[$type]}_{$area_id}_" . date('Y_m_d') . '.ical'] = $out;
    }

    /**
     * Основной метод обработки
     * Генерирует iCal файлы для всех активных областей и отправляет их на FTP
     * 
     * @param string $url URL сайта (например: example.com)
     * @return void
     */
    public function process($url)
    {
        // Извлечение имени сайта без расширения
        $sv = explode('.', $url);
        $this->site = $sv[0];

        // Получение всех областей Web I/O
        $webio_areas = $this->engine->light->getWebIoAreas();
        $arr_files = [];
 
        // Обработка каждой области
        foreach ($webio_areas as $area) {
            // Освещение
            if ($area->light_on == "1") {
                $type = 1;
                $this->create_array($area->area_id, $type, $arr_files);
            }

            // Отопление
            if ($area->heating_on == "1") {
                $type = 2;
                $this->create_array($area->area_id, $type, $arr_files);
            }

            // Сеть
            if ($area->net_on == "1") {
                $type = 3;
                $this->create_array($area->area_id, $type, $arr_files);
            }
        }

        // Отправка файлов на FTP, если есть данные и указан сервер
        if (!empty($arr_files) && !empty($this->serv)) {
            $this->send($arr_files);
        }
    }
}