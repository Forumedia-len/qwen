<?php

namespace AC\mapi\act\repositories\workWith;

use AC\core\system\helpers\TextHelper;
use AC\core\system\language\dto\LanguageDto;
use AC\core\system\modules\modComm\helpers\ModCommHelper;
use AC\mapi\act\repositories\BaseRepository;
use stdClass;

class Translations extends BaseRepository
{
  protected function process(): void
  {
    $this->news();
    $this->text();
    $this->lettersTemplates();
  }
  
  private function news(): void
  {
    if (!$this->db->getDbo()->checkField('language', 'news_simplest')) {
      return;
    }
    $insertData  = ['fields' => $this->baseDB->getColumnsByTableName('news_simplest'), 'values' => []];
    $updateTable = new stdClass();
    unset($insertData['fields'][array_search('news_id', $insertData['fields'])]);
    foreach ($this->db->getFullDataInTable('news_simplest') as $news) {
      if (!isset($news->language)) {
        continue;
      }
      $updateData = [
        'where' => ['news_id' => $news->news_id],
      ];
      if (($content = TextHelper::autoParagraph($news->content)) && $news->content != $content) {
        $updateData['set']['content'] = $content;
      }
      /** @var LanguageDto $lang */
      foreach (config('lang')->getActiveLanguages(true) as $lang) {
        if ($lang->getDefault()) {
          continue;
        }
        $titleKey   = 'title_' . $lang->getName();
        $contentKey = 'content_' . $lang->getName();
        if (!empty($news->$titleKey) && !empty($news->$contentKey)) {
          $_news               = clone $news;
          $_news->title        = $news->$titleKey;
          $_news->content      = TextHelper::autoParagraph($news->$contentKey);
          $_news->language     = $lang->getName();
          $_news->main_news_id = $news->news_id;
          unset($_news->$titleKey, $_news->$contentKey, $_news->news_id);
          $insertData['values'][]         = $_news;
          $updateData['set'][$titleKey]   = '';
          $updateData['set'][$contentKey] = '';
        }
        if (isset($news->$titleKey)) {
          $updateTable->columns[$titleKey] = ['delete_columns' => []];
        }
        if (isset($news->$contentKey)) {
          $updateTable->columns[$contentKey] = ['delete_columns' => []];
        }
      }
      if (!empty($updateData['set'])) {
        $this->query[] = $this->gQDBS->generateUpdateData('news_simplest', $updateData);
      }
    }
    if (!empty($updateTable->columns)) {
      $this->query[] = $this->gQDBS->generateUpdateTable('news_simplest', $updateTable);
    }
    if (!empty($insertData['values'])) {
      $this->query[] = $this->gQDBS->generateInsertData('news_simplest', $insertData);
    }
  }

//------------------------------- тексты ---------------------------------------------------
  private function text(): void
  {
    if (!$this->db->getDbo()->checkField('language', 'config_text')) {
      return;
    }
    $insertData  = ['fields' => $this->baseDB->getColumnsByTableName('config_text'), 'values' => []];
    $updateTable = new stdClass();
    foreach ($this->db->getFullDataInTable('config_text') as $text) {
      if (!isset($text->language)) {
        continue;
      }
      $updateData = [
        'where' => ['alias' => $text->alias, 'language' => $text->language],
      ];
      if (($content = TextHelper::autoParagraph($text->content)) && $text->content != $content) {
        $updateData['set']['content'] = $content;
      }
      foreach (config('lang')->getActiveLanguages(true) as $lang) {
        if ($lang->getDefault()) {
          continue;
        }
        $contentKey = 'content_' . $lang->getName();
        if (!empty($text->$contentKey)) {
          $_text           = clone $text;
          $_text->content  = trim(TextHelper::autoParagraph($text->$contentKey));
          $_text->language = $lang->getName();
          unset($_text->$contentKey);
          $insertData['values'][]         = $_text;
          $updateData['set'][$contentKey] = '';
        }
        if (isset($text->$contentKey)) {
          $updateTable->columns[$contentKey] = ['delete_columns' => []];
        }
      }
      if (!empty($updateData['set'])) {
        $this->query[] = $this->gQDBS->generateUpdateData('config_text', $updateData);
      }
    }
    if (!empty($updateTable->columns)) {
      $this->query[] = $this->gQDBS->generateUpdateTable('config_text', $updateTable);
    }
    if (!empty($insertData['values'])) {
      $this->query[] = $this->gQDBS->generateInsertData('config_text', $insertData);
    }
  }
  
  private function lettersTemplates(): void
  {
    if (!$this->db->getDbo()->checkField('language', 'letters_templates')) {
      return;
    }
    $insertData  = ['fields' => $this->baseDB->getColumnsByTableName('letters_templates'), 'values' => []];
    $updateTable = new stdClass();
    if (!isset($updateTable->columns)) {
      $updateTable->columns = [];
    }
    $typesAlias = array_keys(ModCommHelper::get('areas', 'areasType/selectActiveTypes', ['key' => 'alias'], 'data'));
    foreach ($this->db->getFullDataInTable('letters_templates') as $template) {
      if (!isset($template->language)) {
        continue;
      }
      $updateData = [
        'where' => [
          'mode'     => $template->mode,
          'alias'    => $template->alias,
          'language' => $template->language
        ],
      ];
      if (($content = TextHelper::autoParagraph($template->content)) && $template->content != $content) {
        $updateData['set']['content'] = $content;
      }
      /** @var LanguageDto $lang */
      foreach (config('lang')->getActiveLanguages(true) as $lang) {
        if ($lang->getDefault()) {
          continue;
        }
        $aliasKey = str_replace('_' . $lang->getName(), '', $template->alias);
        if (str_ends_with($template->alias, '_' . $lang->getName())) {
          $updateData['set']['language'] = $lang->getName();
          $updateData['set']['alias']    = $aliasKey;
          if ($aliasKey === 'order_new') {
            foreach ($typesAlias as $type) {
              $_template              = clone $template;
              $_template->alias       = $aliasKey . '_' . $type;
              $_template->language    = $lang->getName();
              $insertData['values'][] = $_template;
            }
          }
        }
        
        if (!empty($updateData['set'])) {
          $this->query[] = $this->gQDBS->generateUpdateData('letters_templates', $updateData);
        }
      }
    }
    
    // Add table update query if there are columns to remove
    if (!empty($updateTable->columns)) {
      $this->query[] = $this->gQDBS->generateUpdateTable('letters_templates', $updateTable);
    }
    
    // Add insert query if there are translations to insert
    if (!empty($insertData['values'])) {
      $this->query[] = $this->gQDBS->generateInsertData('letters_templates', $insertData);
    }
  }
}