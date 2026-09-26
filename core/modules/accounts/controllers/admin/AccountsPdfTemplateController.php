<?php

namespace AC\core\modules\accounts\controllers\admin;

use AC\app\controllers\AdminController;
use AC\app\entities\enums\AccountType;
use AC\core\modules\config\models\ConfigModel;
use AC\core\system\language\IniFiles;
use Service;

class AccountsPdfTemplateController extends AdminController
{
  public function show()
  {
    return [$this->editPdfTemplate(), $this->paymentTerms()];
  }
  
  public function editPdfTemplate()
  {
    $templateFileName = paths()->getAssetsDir('files\\main_template.pdf');
    $templateFile     = Service::autoloader()->getPathFile($templateFileName, 'pdf') ? $templateFileName : null;
    
    return $this->render('pdf_template/_form', [
      'model'            => ConfigModel::getByType('account'),
      'templateFile'     => pathAs($templateFile, 'url'),
      'templateFileName' => pathAs($templateFileName, 'url'),
    ]);
  }
  
  public function updatePdfTemplate()
  {
    $load = Service::request()->all();
    foreach (array_keys($load) as $item) {
      if (in_array($item, ['type', 'action'])) {
        unset($load[$item]);
      }
    }
    $load['account_view_mail_view'] = (int)Service::request()->_('account_view_mail_view', 0);
    $load['account_view_nds_view']  = (int)Service::request()->_('account_view_nds_view', 0);
    $load['account_view_bank_view'] = (int)Service::request()->_('account_view_bank_view', 0);
    $config                         = new ConfigModel();
    if ($config->load((object)['account' => $load])) {
      if ($config->save()) {
        $this->message = lang('message_element_base_update', 'message_success');
      }
    }
    
    return $this->redirectDefaultAction();
  }
  
  
  public function paymentTerms()
  {
    $itemsText = [];
    foreach (AccountType::cases() as $item) {
      $itemsText[$item->value] = [
        'url'  => Service::structure()->getPageHrefByKey('accounts_pdf_template') . '/editAccountText?alias=' . $item->alias(),
        'name' => $item->nameTextTitle()
      ];
    }
    
    return $this->render('payment_terms/list', ['itemsText' => $itemsText]);
  }
  
  public function editAccountText()
  {
    if (($alias = Service::request()->_('alias')) && $item = AccountType::fromAlias($alias)) {
      
      return [
        $this->render('payment_terms/_form',
          [
            'title'   => $item->nameTextTitle(),
            'url'     => Service::structure()->getPageHrefByKey('accounts_pdf_template') . '/updateAccountText?alias=' . $item->alias(),
            'content' => lang($item->alias() . '_invoices', 'account_pdf_template_footer_text')
          ]),
        $this->backButton()
      ];
    }
    
    return $this->redirectDefaultAction();
  }
  
  public function updateAccountText()
  {
    if (Service::request()->isPost() && ($alias = Service::request()->_('alias'))) {
      $lang = Service::request()->_('lang', config('lang')->defaultLanguage);
      $file = new IniFiles(pathAs(paths()?->getLangAppDir($lang . '/message.' . $lang, 'admin')));
      $file->write('account_pdf_template_footer_text', $alias . '_invoices',
        str_replace(["\n", "\r"], '', Service::request()->_post('content') ?: ' '));
      if ($file->updateFile()) {
        $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');
      }
    }
    
    return $this->redirectDefaultAction();
  }
  
  public function setViewParams()
  {
    $this->view->addJsFile('accounts', 'admin', null, 'cdn');
    $this->view->addJsFile('visualeditor/ckeditor', 'admin', null, 'cdn');
    
    parent::setViewParams();
  }
}