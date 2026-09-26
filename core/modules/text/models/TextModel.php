<?php

namespace AC\core\modules\text\models;

use AC\app\config\LangConfig;
use AC\core\modules\text\engines\TextEngine;
use AC\core\modules\text\entities\dto\TextDto;
use AC\core\system\db\Query;
use AC\core\system\model\BaseModel;

/**
 * Модель для работы с текстовыми блоками (админ/сайт).
 */
class TextModel extends BaseModel
{
  /**
   * @var string
   */
  protected $tableName = 'config_text';
  
  /**
   * Текстовый движок (работа с таблицей config_text).
   *
   * @var TextEngine|null
   */
  protected $engine = null;
  
  /**
   * Конфигурация языков проекта.
   */
  protected LangConfig $langConfig;
  
  /**
   * @param mixed $id
   * @param bool  $necessarilySetDataById
   */
  public function __construct($id = null, $necessarilySetDataById = false)
  {
    parent::__construct($id, $necessarilySetDataById);
    /** @var TextEngine $engine */
    $this->engine     = getEngine('text', false);
    $this->langConfig = config('lang');
  }
  
  /**
   * Получить текст по alias и языку (с optional fallback).
   *
   * @param string      $alias
   * @param string|null $language
   * @param bool        $fallback
   * @return TextDto
   */
  public function getText(string $alias, ?string $language = null, bool $fallback = true): TextDto
  {
    $language = $language ?? $this->langConfig->getCurrentLang();
    $data     = ['alias' => $alias, 'content' => null, 'language' => $language];
    
    if ($this->engine?->getContent($row, $alias, $fallback, $language)) {
      $data['content']  = $row['content'] ?? null;
      $data['language'] = $row['language'] ?? $language;
    }
    
    return TextDto::fromArray($data);
  }
  
  /**
   * Собрать DTO для всех активных языков.
   *
   * @param string   $alias
   * @param string[] $languages
   *
   * @return array<string, TextDto>
   */
  public function getLangsData(string $alias, array $languages): array
  {
    $data = [];
    $this->engine?->getContent($textsData, $alias, false, null,
      ['as' => 'dto', 'dtoClass' => TextDto::class]);
    $mainDto = $textsData[$this->langConfig->getDefault()] ?? TextDto::fromArray([
      'alias'    => $alias,
      'content'  => '',
      'language' => $this->langConfig->getDefault()
    ]);
    
    foreach ($languages as $language) {
      $textDto = $textsData[$language] ?? $mainDto;
      $textDto->setContent($textDto->getContent() ?? $mainDto->getContent());
      $textDto->setLanguage($language);
      $data[$language] = $textDto;
    }
    
    return $data;
  }
  
  /**
   * Сохранить контент для конкретного языка.
   *
   * @param TextDto $textDto
   * @return bool
   */
  public function saveText(TextDto $textDto): bool
  {
    if (!$textDto->getAlias()) {
      return false;
    }
    
    $language = $textDto->getLanguage() ?: config('lang')->getDefault();
    $this->engine?->change($textDto->getAlias(), $textDto->getContent(), $language);
    
    return true;
  }
  
  /**
   * Получить список всех доступных алиасов.
   *
   * @param string|null $language фильтр по языку
   *
   * @return string[]
   */
  public function getAliases(?string $language = null): array
  {
    $params = [];
    $where  = '';
    if ($language !== null && $this->engine?->supportsLanguages()) {
      $where               = ' where language = :language';
      $params[':language'] = $language;
      
    }
    
    $rows = Query::sqlQuery(
      'select distinct alias from ' . Query::tableName('config_text') . $where . ' order by alias',
      $params
    );
    
    return array_column($rows ?? [], 'alias') ?? [];
  }
}

