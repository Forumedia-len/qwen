<?php

namespace AC\core\modules\mailing\engines;

use AC\app\config\LangConfig;
use AC\core\modules\mailing\config\LetterTemplatesConfig;
use AC\core\modules\mailing\entities\dto\LetterTemplateDto;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\system\db\Query;
use AC\core\system\engine\BaseEngine;

class LettersTemplatesEngine extends BaseEngine
{
  public function getTemplate(
    int $mode,
    string $alias,
    &$title = null,
    &$subject = null,
    &$content = null,
    ?string $language = null
  ): bool {
    $language = $this->langConfig()->normalize($language);
    $alias    = $this->letterTemplateConfig()->normalizeAlias($alias);
    $actualTemplate = $this->getTemplateByActualAlias($mode, $alias);
    if (!empty($actualTemplate)) {
      $template = $actualTemplate[$language] ?? current($actualTemplate);
      $title    = $template->getTitle();
      $subject  = $template->getSubject();
      $content  = $template->getContent();
      
      return true;
    }
    
    return false;
  }
  
  public function getTemplateByActualAlias(int $mode, string $alias): array
  {
    $resultByAliases   = [];
    $baseAlias         = $this->letterTemplateConfig()->getBaseAlias($alias);
    $params            = [
      (string)$mode,
      $baseAlias . '%'
    ];
    $q                 = 'select * from ' . Query::tableName($this->tableName()) .
      ' where mode = ? and alias LIKE ?';
    if ($temp = Query::sqlQuery($q, $params)) {
      foreach ($temp as $row) {
        $template = LetterTemplateDto::fromArray($row);
        $language = $this->langConfig()->normalize($template->getLanguage());
        $template->setLanguage($language);
        $template->setMode($mode);
        $template->setAlias($alias);
        $template->setTitle($this->letterTemplateConfig()->getTitleTemplate($alias, $mode, $row['title']));
        
        $resultByAliases[$row['alias']][$language] = $template;
      }
    }
    $priorityAlias = $this->letterTemplateConfig()->findFirstAvailablePriorityAlias(
      $alias,
      array_keys($resultByAliases)
    );
    if ($priorityAlias !== null) {
      return $resultByAliases[$priorityAlias];
    }
    
    return [];
  }
  
  public function checkTemplate(int $mode, string $alias, ?string $language = null): int
  {
    $params = [(string)$mode, $alias];
    $where  = ' where mode = ? and alias = ?';
    if ($this->detectLanguageColumn()) {
      $where    .= ' and language = ?';
      $params[] = $this->langConfig()->normalize($language);
    }
    
    return count(
      Query::sqlQuery(
        'select * from ' . Query::tableName($this->tableName()) . $where,
        $params
      )
    );
  }
  
  public function setTemplate(LetterTemplateDto $letterTemplateDto): bool
  {
    $whereLanguage = [];
    $params        = [
      $letterTemplateDto->getSubject() ?? '',
      $letterTemplateDto->getContent() ?? '',
      (string)$letterTemplateDto->getMode(),
      $letterTemplateDto->getAlias(),
    ];
    
    if ($this->detectLanguageColumn()) {
      $params[]                = $this->langConfig()->normalize($letterTemplateDto->getLanguage());
      $whereLanguage['update'] = ' AND language = ?';
      $whereLanguage['insert'] = ', language = ?';
    }
    if ($this->checkTemplate($letterTemplateDto->getMode(), $letterTemplateDto->getAlias(), $letterTemplateDto->getLanguage())) {
      $q = 'UPDATE ' . Query::tableName($this->tableName()) .
        ' SET subject = ? , content = ? ' .
        'where mode = ? AND alias = ?' . ($whereLanguage['update'] ?? '');
    } else {
      $params[] = $letterTemplateDto->getTitle();
      $q        = 'INSERT INTO ' . Query::tableName($this->tableName()) .
        ' SET subject = ? , content = ?, mode = ?, alias = ?' . ($whereLanguage['insert'] ?? '') . ', title = ?';
    }
    
    return Query::sqlQuery($q, $params, false);
  }
  
  public function remove(int $mode, string $alias): bool
  {
    $params = [
      (string)$mode,
      $alias,
    ];
    
    return Query::sqlQuery(
      'delete from ' . Query::tableName($this->tableName()) . ' where mode = ? and alias = ?', $params,
      false
    );
  }
  
  public function getTemplates(?array &$templates = [], ?array $mode = [], ?string $language = null): bool
  {
    $q = 'select * from ' . Query::tableName($this->tableName())
      . ' where alias is not null'
      . (!empty($language) && $this->detectLanguageColumn() ? ' and language = "' . $language . '"' : '')
      . (!empty($mode) ? ' and mode in ("' . implode('","', $mode) . '")' : '')
      . ' order by mode, alias';
    if ($rows = Query::sqlQuery($q)) {
      foreach ($rows as $row) {
        $templates[] = LetterTemplateDto::fromArray($row);
      }
      return true;
    }
    $templates = [];
    
    return false;
  }
  
  /** todo используется на tennishalle-dortmund.de
   *
   * @param string $time
   * @param string $template_alias
   * @param int    $template_mode
   *
   * @return string
   */
  function correctAliasByTime(string $time, string $template_alias, int $template_mode): string
  {
    if ($this->letterTemplateConfig()->checkCorrectAliasByInterval($template_alias, false)) {
      $replaceAlias = $this->letterTemplateConfig()->findFirstAvailablePriorityAlias(
        $template_alias,
        [(string)LETTER_CORRECT_ALIAS_REPLACE]
      );
      if ($replaceAlias !== null) {
        $now   = strtotime($time ?: 'now');
        $start = strtotime(date('Y-m-d') . ' ' . LETTER_CORRECT_TIME_START);
        $stop  = strtotime(date('Y-m-d') . ' ' . LETTER_CORRECT_TIME_STOP);
        if ($now >= $start && $now < $stop) {
          $template_alias = !$this->checkTemplate($template_mode, LETTER_CORRECT_ALIAS_INTERVAL) ? $template_alias
            : LETTER_CORRECT_ALIAS_INTERVAL;
        }
      }
    }

    return $template_alias;
  }
  
  /**
   * Проверить наличие колонки language и включённость мультиязычности.
   */
  protected function detectLanguageColumn(): bool
  {
    static $detected;
    if ($detected === null) {
      $detected = match (true) {
        count($this->langConfig()->getActiveLanguages()) <= 1      => false,
        Query::getDB()->checkField('language', $this->tableName()) => true,
        default                                                    => false,
      };
    }
    
    return $detected;
  }
  
  
  public function replaceTemplateData(string $content, array $templateData): string
  {
    foreach ($templateData as $key => $value) {
      $content = str_replace('%' . $key . '%', ($value ?? ''), $content);
    }
    
    return $content;
  }
  
  /**
   *  Получить список типов шаблонов
   *  0 - шаблоны для писем админов
   *  1 - шаблоны для писем пользователей
   *  2 - шаблоны для писем системных сообщений
   *
   * @return array<ModeTemplate>
   */
  public function getCurrentModes(): array
  {
    static $modes;
    if ($modes === null) {
      foreach (Query::sqlQuery('select distinct mode from ' . Query::tableName($this->tableName())) as $item) {
        $modes[$item['mode']] = ModeTemplate::from((int)$item['mode']);
      }
    }
    
    return $modes;
  }
  
  public function setTableName($table_name = 'letters_templates')
  {
    parent::setTableName($table_name);
  }
  
  protected function langConfig(): LangConfig
  {
    return config('lang');
  }
  
  protected function letterTemplateConfig(): LetterTemplatesConfig
  {
    return config('letterTemplates');
  }
}