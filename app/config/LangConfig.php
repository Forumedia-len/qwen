<?php

namespace AC\app\config;

use AC\core\engines\LanguagesEngine;
use AC\core\system\config\BaseConfig;
use AC\core\system\language\dto\LanguageDto;
use PDO;
use Service;


/**
 * Конфиг для переводов
 */
class LangConfig extends BaseConfig
{
  /**
   * Используемый язык по умолчанию
   * @var string
   */
  public        $defaultLanguage = 'de';
  public string $sessionKey      = 'lang';
  
  
  public function getCurrentLang(): string
  {
    if (!Service::session()->exists($this->sessionKey)) {
      Service::session()->set($this->sessionKey, $this->getDefault());
    }
    return Service::session()->get($this->sessionKey);
  }
  
  /**
   * Получить список активных языков
   *
   * @param bool $full Возвращать полную информацию (LanguageDto) или только имена
   * @return array Массив языков в формате name => LanguageDto или просто имена
   */
  public function getActiveLanguages(bool $full = false): array
  {
    static $languages;
    
    if (empty($languages)) {
      $rows = $this->getEngine()->all(['active' => 1], [], ['style' => PDO::FETCH_ASSOC]) ?: [$this->getDefault(true)->toArray()];
      foreach ($rows as $row) {
        $languages[$row['name']] = LanguageDto::fromArray($row);
      }
    }
    
    return $full
      ? $languages
      : array_keys($languages);
  }
  
  /**
   * Получить язык по умолчанию
   *
   * @param bool $full Возвращать LanguageDto или только имя языка
   * @return string|LanguageDto Имя языка или объект LanguageDto
   */
  public function getDefault(bool $full = false): string|LanguageDto
  {
    static $language;
    if (!$language) {
      $language = LanguageDto::fromArray($this->getEngine()->getDefault() ?: $this->getDefaultLanguageStandard());
    }
    
    return $full ? $language : $language->getName();
  }
  
  
  /**
   * Получить движок языков
   *
   * @return LanguagesEngine Экземпляр движка языков
   */
  protected function getEngine(): LanguagesEngine
  {
    return getEngine('languages');
  }
  
  /**
   * Получить стандартный язык по умолчанию
   *
   * @return array Массив с данными языка по умолчанию
   */
  public function getDefaultLanguageStandard(): array
  {
    return [
      'name'    => $this->defaultLanguage,
      'title'   => 'Deutsch',
      'active'  => 1,
      'default' => 1
    ];
  }
  
  /**
   * Установить текущий язык
   *
   * @param string|null $lang Код языка для установки, если null - используется текущий язык
   * @return void
   */
  public function setLang(?string $lang = null): void
  {
    $lang ??= $this->getCurrentLang();
    if (Service::lang()->issetLang($lang)) {
      Service::session()->set($this->sessionKey, $lang);
      Service::lang()->setLang($lang);
    }
  }
  
  /**
   *  Получить ключ сессии
   * @return string
   */
  public function sessionKey(): string
  {
    return $this->sessionKey;
  }
  
  /**
   * Нормализация языка - преобразование в 2‑х символьный код или возврат текущего
   *
   * @param string|null $language
   * @return string
   */
  public function normalize(?string $language = null): string
  {
    if ($language && preg_match('/^[a-z]{2}/i', $language, $matches)) {
      $language = mb_strtolower($matches[0]);
    }
    
    return mb_strtolower((in_array($language, $this->getActiveLanguages()) ? $language : $this->getDefault()));
  }
  
  /**
   * Проверить наличие колонки language в таблице и необходимость мультиязычности
   *
   * @param string|null $tableName Имя таблицы для проверки
   * @return bool Есть ли поддержка мультиязычности для таблицы
   */
  public function detectLanguageColumn(?string $tableName = null, $columnName = 'language'): bool
  {
    static $detected;
    if (!$tableName) {
      return false;
    }
    if ($detected[$tableName] === null) {
      $detected[$tableName] = match (true) {
        count($this->getActiveLanguages()) <= 1                        => false,
        Service::query()::getDB()->checkField($columnName, $tableName) => true,
        default                                                        => false,
      };
    }
    
    return $detected[$tableName];
  }
}