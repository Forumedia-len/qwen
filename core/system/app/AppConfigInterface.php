<?php

declare(strict_types=1);

namespace AC\core\system\app;

/**
 * Интерфейс конфигурации приложения
 */
interface AppConfigInterface
{
  /**
   * Получить базовый URL
   * 
   * @return string
   */
  public function getBaseURL(): string;

  /**
   * Получить имя хоста
   * 
   * @return string
   */
  public function getHostName(): string;

  /**
   * Проверить, является ли локальным сервером
   * 
   * @return bool
   */
  public function isLocalServer(): bool;

  /**
   * Получить настройки безопасности
   * 
   * @return array
   */
  public function getSecuritySettings(): array;

  /**
   * Получить настройки сессий
   * 
   * @return array
   */
  public function getSessionSettings(): array;
}
