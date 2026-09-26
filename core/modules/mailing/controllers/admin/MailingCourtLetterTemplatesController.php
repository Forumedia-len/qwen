<?php

namespace AC\core\modules\mailing\controllers\admin;

use AC\core\modules\mailing\config\LetterTemplatesConfig;
use AC\core\modules\mailing\entities\dto\LetterTemplateDto;
use AC\core\modules\mailing\entities\dto\LetterTemplateViewListHtmlDto;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use Service;


class MailingCourtLetterTemplatesController extends MailingLetterTemplatesController
{
  public $default_template = 'court_letter_templates';
  
  public function show()
  {
    $typeMode = Service::request()->_get('typeMode', 1);
    $out[]    = parent::show();
    if (config('letterTemplates')->useAdditionLetterTemplate()
      && ModeTemplate::checkCase($typeMode, ModeTemplate::USER)) {
      $out[] = $this->formOfCreation();
    }
    return $out;
  }
  
  /**
   * Получить актуальные шаблоны писем
   * @param int  $mode
   * @param bool $useSport
   * @return array
   */
  protected function getActualTemplates(int $mode = 1, bool $useSport = false): array
  {
    /** @var LetterTemplatesConfig $config */
    $config        = config('letterTemplates');
    $out           = [];
    $rowsTemplates = parent::getActualTemplates($mode);
    if (in_array($mode, ModeTemplate::getModeForPerson())) {
      $rowsTypesSportsTemplates  = array_reduce($rowsTemplates, function ($carry, $item) {
        if (in_array(config('letterTemplates')->getBaseAlias($item->getAlias()),
          config('letterTemplates')->getAvailableTemplateBaseNames())) {
          $carry[$item->alias] = $item;
        }
        return $carry;
      }, []);
      $userMode                  = ModeTemplate::checkCase($mode, ModeTemplate::USER);
      $possibleAliasesOnlySports = array_keys($config->getPossibleAliasesTemplates('onlySports'));
      foreach (array_keys($config->getPossibleAliasesTemplates(null, $userMode)) as $alias) {
        if (!in_array($alias, $possibleAliasesOnlySports)
          || in_array($alias, array_keys($rowsTypesSportsTemplates))
        ) {
          $row = LetterTemplateViewListHtmlDto::fromArray(isset($rowsTypesSportsTemplates[$alias]) ? $rowsTypesSportsTemplates[$alias]->toArray() : [
            'alias' => $alias,
            'mode'  => $mode
          ]);
          if ($userMode
            && in_array($alias, $possibleAliasesOnlySports)) {
            $row->setUseRemove(true);
          }
          $row->setTitle($this->getTitleTemplate($alias, $mode, $row->getTitle()));
          $out[] = $row;
        }
      }
    }
    /** @var LetterTemplateDto $row */
    foreach ($rowsTemplates as $row) {
      $baseAlias = $config->getBaseAlias($row->getAlias());
      if (!in_array($baseAlias, $config->getAvailableTemplateBaseNames())
        && (MC_ARENA === str_ends_with($row->getAlias(), 'mc_arena')
          || !in_array($row->getMode(), ModeTemplate::getModeForPerson()))
      ) {
        $template = LetterTemplateViewListHtmlDto::fromArray($row->toArray());
        $template->setTitle($this->getTitleTemplate($baseAlias, $mode, $row->getTitle()));
        $out[] = $template;
      }
    }
    
    return $out;
  }
  
  /**
   *
   * TODO:: сейчас подтягивается по дефолту шаблон для первого активного типа, нужно доработать чтобы он подтягивался актуального типа, с возможностью
   * при переходе на другой тип выбрать другой шаблон
   * @param string $baseAlias
   * @param int    $typeMode
   * @return string
   */
  public function formOfCreation(string $baseAlias = 'order_new', int $typeMode = 1): string
  {
    $aliasOrderTemplates = config('letterTemplates')->getPossibleAliasesTemplates('onlySports');
    if (count($aliasOrderTemplates) > 1) {
      $alias = $baseAlias . '_' . module('areas')->useModel()?->getFirstActiveType('alias');
      if ($this->getEngine()->getTemplates($templates, [1], config('lang')->getDefault())
        && !empty($templates)) {
        /** @var LetterTemplateDto $templateRow */
        foreach ($templates as $templateRow) {
          if (in_array($templateRow->alias, array_keys($aliasOrderTemplates))) {
            unset($aliasOrderTemplates[$templateRow->alias]);
          }
        }
        if (count($aliasOrderTemplates) > 0) {
          return $this->form($alias, $typeMode, '_form_create', array_map(static fn($item) => $item['title'] ?? '', $aliasOrderTemplates));
        }
      }
    }
    
    return '';
  }
}