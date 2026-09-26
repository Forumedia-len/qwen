<?php

namespace AC\core\modules\mailing\controllers\admin;

use AC\core\modules\mailing\engines\LettersTemplatesEngine;
use AC\core\modules\mailing\entities\dto\LetterTemplateDto;
use Service;

class MailingLetterTemplatesController extends MailingController
{
  public $default_template = 'letter_templates';
  
  
  public function show()
  {
    $typeMode = (int)(Service::request()->_get('typeMode') ?? 1);
    
    return $this->render('list', [
      'templates'  => $this->getActualTemplates($typeMode),
      'typeModes'  => $this->getEngine()->getCurrentModes(),
      'activeType' => $typeMode,
      'baseUrl'    => $this->getDefaultUrl(),
    ]);
  }
  
  /** Получить актуальные шаблоны писем
   * @param int $mode
   * @return array
   */
  protected function getActualTemplates(int $mode = 1): array
  {
    $this->getEngine()->getTemplates($templates, [$mode], config('lang')->getDefault());
    
    return $templates;
  }
  
  public function update($new = false)
  {
    $typeMode = (int)Service::request()->_get('typeMode');
    $alias    = Service::request()->_get('alias');
    if (Service::request()->checkGet('typeMode') && $alias) {
      return [
        $this->form($alias, $typeMode),
        $this->backButton()
      ];
    }
    $this->view->addMessage(lang('Template not found', 'message_error'), 'error');
    
    return $this->redirectDefaultAction();
  }
  
  
  protected function getTemplateByLanguages($alias, $typeMode): array
  {
    $templateByLanguages = [];
    if ($template = $this->getEngine()->getTemplateByActualAlias($typeMode, $alias)) {
      $defaultLanguage = config('lang')->getDefault();
      /** @var LetterTemplateDto $defaultTemplate */
      $defaultTemplate = $template[$defaultLanguage] ?? LetterTemplateDto::fromArray(
        [
          'alias'    => $alias,
          'mode'     => $typeMode,
          'title'    => $this->getTitleTemplate($alias, $typeMode),
          'subject'  => '',
          'content'  => '',
          'language' => $defaultLanguage,
        ]);
      foreach (config('lang')->getActiveLanguages() as $langCode) {
        $templateRow = clone $defaultTemplate;
        $templateRow->setLanguage($langCode);
        if (!empty($template[$langCode]?->getContent())) {
          $templateRow->setContent($template[$langCode]->getContent());
        }
        if (!empty($template[$langCode]?->getSubject())) {
          $templateRow->setSubject($template[$langCode]->getSubject());
        }
        $templateByLanguages[$langCode] = $templateRow;
      }
    }
    
    return $templateByLanguages;
  }
  
  public function save()
  {
    if (Service::request()->checkPost('typeMode')
      && Service::request()->checkPost('alias')
      && Service::request()->checkPost('subject')
      && Service::request()->checkPost('content')
    ) {
      $mode            = (int)Service::request()->_post('typeMode');
      $alias           = Service::request()->_post('alias');
      $subjects        = (array)Service::request()->_post('subject');
      $contents        = (array)Service::request()->_post('content');
      $defaultLanguage = config('lang')->getDefault();
      $title           = $this->getTitleTemplate($alias, $mode, '', false);
      $defaultTemplate = LetterTemplateDto::fromArray(
        [
          'alias'    => $alias,
          'mode'     => $mode,
          'subject'  => $subjects[$defaultLanguage] ?? current($subjects),
          'content'  => $contents[$defaultLanguage] ?? current($contents),
          'language' => $defaultLanguage,
          'title'    => $title
        ]);
      foreach (config('lang')->getActiveLanguages() as $lang) {
        $template = clone $defaultTemplate;
        $template->setContent($contents[$lang] ?? $defaultTemplate->getContent());
        $template->setSubject($subjects[$lang] ?? $defaultTemplate->getSubject());
        $template->setLanguage($lang);
        
        $this->getEngine()->setTemplate($template);
      }
      
      $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
    }
    
    return $this->redirectDefaultAction();
  }
  
  /**
   * @param string $alias
   * @param int    $typeMode
   * @param string $default
   * @param bool   $addSuffix
   * @return string
   */
  protected function getTitleTemplate(string $alias, int $typeMode, string $default = '', bool $addSuffix = true): string
  {
    return config('letterTemplates')->getTitleTemplate($alias, $typeMode, $default, $addSuffix);
  }
  
  protected function form(string $alias, int $typeMode = 1, string $view = '_form', $aliases = []): string
  {
    $defaultLanguage     = config('lang')->getDefault();
    $baseAlias           = config('letterTemplates')->getBaseAlias($alias);
    $templateByLanguages = $this->getTemplateByLanguages($alias, $typeMode);
    
    $this->view->addJsFile('visualeditor/ckeditor');
    
    return $this->render($view, [
      'templateByLanguages' => $templateByLanguages,
      'typeMode'            => $typeMode,
      'alias'               => $alias,
      'aliases'             => $aliases,
      'baseUrl'             => $this->getDefaultUrl(),
      'activeLanguage'      => $defaultLanguage,
      'languages'           => array_keys($templateByLanguages),
      'variableTemplate'    => $this->getVariableTemplate($baseAlias)
    ]);
  }
  
  public function getEngine(): LettersTemplatesEngine
  {
    return getEngine('lettersTemplates', false);
  }
  
  public function getVariableTemplate($alias = 'order_new'): array
  {
    return match (config('letterTemplates')->getBaseAlias($alias)) {
      'order_new'        => [
        [lang('First name'), '%CLIENT_NAME%'],
        [lang('Family name'), '%CLIENT_SURNAME%'],
        [lang('Email'), '%EMAIL%'],
        [lang('Place type', 'mailing'), '%PLACE_TYPE%'],
        [lang('Place title', 'mailing'), '%PLACE_TITLE%'],
        [lang('Price'), '%PRICE%'],
        [lang('Payment method'), '%ENCASH%'],
        [lang('Booking options', 'mailing'), '%STOCKS%'],
//      [lang('Booking options code', 'mailing'), '%STOCK_CODE%'],
//      [lang('Booking options title', 'mailing'), '%STOCK_TITLE%'],
        [lang('Comment'), '%MEMO%'],
        [lang('Date'), '%DATE%'],
        [lang('Period'), '%PERIOD%'],
        [lang('Door code', 'areas'), '%DOOR_CODE%'],
        [lang('additional message after booking', 'order'), '%ADDITIONAL_MESSAGE_AFTER_BOOKING%'],
      ],
      'registration'     => [
        [lang('User name'), '%LOGIN%'],
        [lang('First name'), '%NAME%'],
        [lang('Family name'), '%SURNAME%'],
        [lang('Birthday'), '%BIRTHDAY%'],
        [lang('Phone'), '%PHONE%'],
        [lang('Mobile'), '%PHONE_MOBILE%'],
        [lang('Fax'), '%FAX%'],
        [lang('Zip'), '%POST_CODE%'],
        [lang('City'), '%CITY%'],
        [lang('Address'), '%ADDRESS%'],
        [lang('E-mail'), '%EMAIL%'],
        [lang('Bank account holder'), '%BANK_ACCOUNT_HOLDER%'],
        [lang('Bank name'), '%BANK_NAME%'],
        [lang('Firma'), '%FIRMA%'],
        [lang('Student(Yes, Not)'), '%STATE_STUD%'],
        [lang('Member(Yes, Not)'), '%CLUB_STATE%'],
      ],
      'query_prepayment' => [
        [lang('First name'), '%CLIENT_NAME%'],
        [lang('Family name'), '%CLIENT_SURNAME%'],
        [lang('E-mail'), '%EMAIL%'],
        [lang('Price'), '%PRICE%'],
      ],
      'recover_password' => [
        [lang('First name'), '%CLIENT_NAME%'],
        [lang('Family name'), '%CLIENT_SURNAME%'],
        [lang('User name'), '%LOGIN%'],
        [lang('password'), '%PASSWORD%'],
      ],
      'activate'         => [
        [lang('First name'), '%CLIENT_NAME%'],
        [lang('Family name'), '%CLIENT_SURNAME%'],
      ],
      default            => []
    };
  }
  
  public function remove()
  {
    if (Service::request()->checkGet('typeMode')
      && Service::request()->checkGet('alias')
      && $this->getEngine()->remove(Service::request()->_get('typeMode'), Service::request()->_get('alias'))) {
      $this->view->addMessage(lang('message_element_base_remove', 'message_success'), 'success');
    }
    
    $this->redirectDefaultAction();
  }
  
  protected function backButton($backButtonUrl = null): string
  {
    $typeMode      = (int)(Service::request()->_get('typeMode') ?? 1);
    $backButtonUrl = $backButtonUrl ?? $this->getDefaultUrl();
    return parent::backButton($backButtonUrl . '?typeMode=' . $typeMode);
  }
  
  public function redirect($path, $param = [])
  {
    $param['typeMode'] = (int)(Service::request()->_('typeMode') ?? 1);
    parent::redirect($path, $param);
  }
}