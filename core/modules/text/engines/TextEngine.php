<?php

namespace AC\core\modules\text\engines;

use AC\app\config\LangConfig;
use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;

/**
 * Движок управления текстовыми блоками (config_text) с поддержкой языков.
 * Каждая запись хранится с явным кодом языка, fallback выполняется автоматически.
 */
class TextEngine extends BaseEngine
{
  /** @var LangConfig Конфигурация языков проекта. */
  protected LangConfig $langConfig;
  
  /** @var bool Признак поддержки колонки language */
  protected bool $languageColumnAvailable = false;
  
  public function __construct($model = null)
  {
    parent::__construct($model);
    $this->langConfig              = config('lang');
    $this->languageColumnAvailable = $this->detectLanguageColumn();
  }
  
  /**
   * Поддерживает ли таблица отдельные языковые записи.
   */
  public function supportsLanguages(): bool
  {
    return $this->languageColumnAvailable;
  }
  
  /**
   * Создать или обновить текст по alias и языку.
   *
   * @param string      $alias    ключ текста
   * @param string|null $content  содержимое
   * @param string|null $language код языка
   *
   * @return bool
   */
  public function change(string $alias, ?string $content, ?string $language = null): bool
  {
    $language = $this->resolveLanguage($language);
    $table    = $this->getTableName();
    
    $fields = ['alias = :alias', 'content = :content'];
    $params = [':alias' => $alias, ':content' => $content];
    if ($this->supportsLanguages()) {
      $fields[]            = 'language = :language';
      $params[':language'] = $language;
    }
    $query = 'insert into ' . $table . ' set ' . implode(', ', $fields)
      . ' on duplicate key update content = values(content)';
    Query::sqlQuery($query, $params, false);
    
    return true;
  }
  
  /**
   * Получить контент по alias с учётом языка и fallback-а.
   *
   * @param ?array      $result       данные текста
   * @param string|null $alias        ключ текста
   * @param bool        $oneTranslate fallback(true) на текущий язык или (false)все доступные языки
   * @param string|null $language
   * @param array|null  $params
   * @return bool
   */
  public function getContent(
    ?array &$result = [],
    ?string $alias = null,
    bool $oneTranslate = true,
    ?string $language = null,
    ?array $params = []
  ): bool {
    $result = [];
    $select = $this->supportsLanguages()
      ? 'alias, language, content'
      : 'alias, content';
    if (!empty($alias)
      && ($rows = Query::sqlQuery(
        'select ' . $select . ' from ' . $this->getTableName() . ' where alias = :alias',
        [':alias' => $alias]))
      && !empty($rows)) {
      $rows     = $this->getDataAs($rows, array_merge(['key' => 'language'], $params));
      $language = $this->resolveLanguage($language);
      if ($oneTranslate && $this->supportsLanguages() && isset($rows[$language])) {
        // Если есть перевод на текущий язык, возвращаем его иначе берём перевод по умолчанию проверяем контент
        if (is_object($rows[$language])) {
          $rows[$language]->content = $rows[$language]->content ?: $rows[$this->langConfig->getDefault()]->content;
        } else {
          $rows[$language]['content'] = $rows[$language]['content'] ?: $rows[$this->langConfig->getDefault()]['content'];
        }
      }
      $result = match (true) {
        // если не поддерживается языки, возвращаем первый
        !$this->supportsLanguages() => $oneTranslate ? array_shift($rows) : [$this->langConfig->getDefault() => array_shift($rows)],
        // по умолчанию возвращаем текущий язык
        $oneTranslate               => $rows[$language] ?? $rows[$this->langConfig->getDefault()],
        // возвращаем все языки
        default                     => $rows,
      };
      
      return true;
    }
    
    return false;
  }
  
  /**
   * Проверить существование текста для указанного alias и языка.
   *
   * @param string      $alias
   * @param string|null $language
   *
   * @return bool
   */
  public function aliasExists(string $alias, ?string $language = null): bool
  {
    $params = [':alias' => $alias];
    $where  = 'alias = :alias';
    if ($this->supportsLanguages()) {
      $params[':language'] = $language ?: $this->langConfig->getDefault();
      $where               .= ' and language = :language';
    }
    
    $q = 'select alias from ' . $this->getTableName() . ' where ' . $where . ' limit 1';
    
    return (bool)Query::sqlQuery($q, $params, true, ['onlyOne' => true]);
  }
  
  /**
   * @return string имя таблицы для хранения текстов
   */
  protected function getTableName(): string
  {
    return Query::tableName($this->tableName());
  }
  
  /**
   * Задать название таблицы
   *
   * @param null|string $table_name
   */
  public function setTableName($table_name = 'config_text')
  {
    parent::setTableName($table_name);
  }
  
  /**
   * Нормализовать язык: если не задан, берём текущий, иначе язык по умолчанию.
   *
   * @param string|null $language
   *
   * @return string
   */
  protected function resolveLanguage(?string $language = null): string
  {
    if (!$this->supportsLanguages()) {
      return $this->langConfig->getDefault();
    }
    $language = $language ?? $this->langConfig->getCurrentLang();
    
    return $language ?: $this->langConfig->getDefault();
  }
  
  /**
   * Проверить наличие колонки language и включённость мультиязычности.
   */
  protected function detectLanguageColumn(): bool
  {
    static $detected;
    if ($detected === null) {
      $detected = match (true) {
        count($this->langConfig->getActiveLanguages()) <= 1           => false,
        Query::getDB()->checkField('language', $this->tableName()) => true,
        default                                                       => false,
      };
    }
    
    return $detected;
  }
}