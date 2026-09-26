<?php

namespace AC\core\system\ftp;

/**
 * Singleton-класс для работы с FTP сервером
 * @version 1.1 (Singleton)
 */
class FTPClient
{
    private static $instance = null;
    private $connection = null;

    // Параметры подключения
    private string $host;
    private int $port;
    private string $username;
    private string $password;
    private bool $passiveMode = true;
    private int $timeout = 90;
    private bool $useSSL = false;

    // Флаг подключения
    private bool $isConnected = false;

    /**
     * Запрещаем прямое создание через new
     */
    private function __construct()
    {
       
    }

    /**
     * Запрещаем клонирование
     */
    private function __clone()
    {
    }

    /**
     * Получить единственный экземпляр класса
     * @param string $host
     * @param int $port
     * @return self
     */
    public static function getInstance(): self
    {
        if (self::$instance === null) {
            self::$instance = new FTPClient();
        }
        return self::$instance;
    }
    
    public static function getInstanceConnect($useSSL = false): self {
        if (self::$instance === null) {
            self::$instance = new FTPClient();
            if (defined('FTP_HOST') && FTP_HOST) {
                self::$instance->setCredentials(FTP_HOST, FTP_USER, FTP_PWD, 21, $useSSL);
                self::$instance->connect();
                if (defined('FTP_DIR') && FTP_DIR) {
                    self::$instance->changeDirectory(FTP_DIR);
                }
            }
        }
        return self::$instance;
    }

    /**
     * Установка учетных данных
     * @param string $username Логин
     * @param string $password Пароль
     * @return self
     */
    public function setCredentials(string $host, string $username, string $password, int $port = 21, bool $useSSL = false): self
    {
        $this->username = $username;
        $this->password = $password;
        $this->host = $host;
        $this->port = $port;
        $this->useSSL = $useSSL;
        return $this;
    }

    /**
     * Установка пассивного режима
     * @param bool $enabled Включить/выключить
     * @return self
     */
    public function setPassiveMode(bool $enabled): self
    {
        $this->passiveMode = $enabled;
        return $this;
    }

    /**
     * Установка таймаута
     * @param int $seconds Секунды
     * @return self
     */
    public function setTimeout(int $seconds): self
    {
        $this->timeout = $seconds;
        return $this;
    }

    /**
     * Подключение к серверу (безопасное повторное подключение)
     * @return bool Успешность подключения
     */
  public function connect(): bool
{
    if ($this->isConnected) {
        return true;
    }

    if ($this->useSSL) {
        $this->connection = ftp_ssl_connect($this->host, $this->port, $this->timeout);
    } else {
        $this->connection = ftp_connect($this->host, $this->port, $this->timeout);
    }

    if (!$this->connection) {
        return false;
    }

    if (!ftp_login($this->connection, $this->username, $this->password)) {
        $this->disconnect();
        return false;
    }

    ftp_pasv($this->connection, $this->passiveMode);
    ftp_set_option($this->connection, FTP_TIMEOUT_SEC, $this->timeout);
    $this->isConnected = true;
    return true;
}

    /**
     * Отключение от сервера
     * @return void
     */
    public function disconnect(): void
    {
        if ($this->connection) {
            ftp_close($this->connection);
            $this->connection = null;
            $this->isConnected = false;
        }
    }

    /**
     * Проверка активности подключения
     * @return bool
     */
    public function isConnected(): bool
    {
        return $this->isConnected && is_resource($this->connection);
    }

    // ------------------------------
    // ОСНОВНЫЕ МЕТОДЫ РАБОТЫ С FTP
    // ------------------------------

    /**
     * Загрузка файла на сервер
     */
    public function uploadFile(string $localFile, string $remoteFile, bool $asciiMode = false): bool
    {
        $this->ensureConnected();
        if (!file_exists($localFile)) {
           
        }
        $mode = $asciiMode ? FTP_ASCII : FTP_BINARY;
        $result = ftp_put($this->connection, $remoteFile, $localFile, $mode);
        if (!$result) {
            
        }
        return true;
    }

    /**
     * Загрузка нескольких файлов из массива строк напрямую на FTP-сервер
     * @param array<string, string> $files Ассоциативный массив: ["имя_файла.txt" => "содержимое файла", ...]
     * @param string $remoteDirectory Директория на сервере
     * @param bool $asciiMode Использовать ASCII-режим
     * @return array Массив результатов
     */
    public function uploadFilesFromString(array $files, string $remoteDirectory = '.', bool $asciiMode = false): array
    {
        $this->ensureConnected();

        if (empty($files)) {
           
        }

        $originalDir = ftp_pwd($this->connection);
        
        try {
            if ($remoteDirectory !== '.') {
                if (!$this->exists($remoteDirectory)) {
                    $this->createDirectory($remoteDirectory);
                }
                $this->changeDirectory($remoteDirectory);
            }

            $uploaded = [];
            $failed = [];

            foreach ($files as $filename => $content) {
                if (!is_string($filename) || !is_string($content)) {
                    $failed[] = [
                        'name' => $filename,
                        'error' => 'Некорректный формат: имя или содержимое не являются строкой'
                    ];
                    continue;
                }

                $tempStream = fopen('php://memory', 'r+');
                if (!$tempStream) {
                    $failed[] = [
                        'name' => $filename,
                        'error' => 'Не удалось создать временный поток'
                    ];
                    continue;
                }

                fwrite($tempStream, $content);
                rewind($tempStream);

                $mode = $asciiMode ? FTP_ASCII : FTP_BINARY;
                $result = ftp_fput($this->connection, $filename, $tempStream, $mode);
                fclose($tempStream);

                if ($result) {
                    $uploaded[] = [
                        'name' => $filename,
                        'size' => strlen($content)
                    ];
                } else {
                    $failed[] = [
                        'name' => $filename,
                        'error' => 'Ошибка загрузки через ftp_fput'
                    ];
                }
            }

            return [
                'uploaded' => $uploaded,
                'failed' => $failed
            ];

        } finally {
            if ($originalDir !== false) {
                @ftp_chdir($this->connection, $originalDir);
            }
        }
    }

    /**
     * Переименование/перемещение файла
     */
    public function rename(string $oldName, string $newName): bool
    {
        $this->ensureConnected();
        $result = ftp_rename($this->connection, $oldName, $newName);
        if (!$result) {
        
        }
        return true;
    }

    /**
     * Удаление файла
     */
    public function deleteFile(string $file): bool
    {
        $this->ensureConnected();
        $result = ftp_delete($this->connection, $file);
        if (!$result) {
           
        }
        return true;
    }

    /**
     * Создание директории
     */
    public function createDirectory(string $directory): bool
    {
        $this->ensureConnected();
        $result = ftp_mkdir($this->connection, $directory);
        if (!$result) {
           
        }
        return true;
    }

    /**
     * Удаление директории
     */
    public function deleteDirectory(string $directory): bool
    {
        $this->ensureConnected();
        $result = ftp_rmdir($this->connection, $directory);
        if (!$result) {
           
        }
        return true;
    }

    /**
     * Изменение текущей директории
     */
    public function changeDirectory(string $directory): bool
    {
        $this->ensureConnected();
        $result = ftp_chdir($this->connection, $directory);
        if (!$result) {
            
        }
        return true;
    }

    /**
     * Получение текущей директории
     */
    public function getCurrentDirectory(): string
    {
        $this->ensureConnected();
        return ftp_pwd($this->connection);
    }

    /**
     * Проверка существования файла/директории
     */
    public function exists(string $path): bool
    {
        $this->ensureConnected();
        return ftp_size($this->connection, $path) !== -1 || $this->isDirectory($path);
    }

    /**
     * Проверка является ли путь директорией
     */
    public function isDirectory(string $path): bool
    {
        $this->ensureConnected();
        $currentDir = ftp_pwd($this->connection);
        if (@ftp_chdir($this->connection, $path)) {
            ftp_chdir($this->connection, $currentDir);
            return true;
        }
        return false;
    }

    /**
     * Убедиться, что соединение активно
     */
    private function ensureConnected(): void
    {
        if (!$this->isConnected()) {
           
        }
    }

    /**
     * Автоматическое отключение при уничтожении
     */
    public function __destruct()
    {
        $this->disconnect();
    }
}