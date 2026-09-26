<?php

namespace AC\core\modules\text\controllers\admin;

use AC\app\config\LangConfig;
use AC\app\controllers\AdminController;
use AC\core\modules\text\entities\dto\TextDto;
use AC\core\modules\text\models\TextModel;
use Service;

/**
 * Административный контроллер для управления текстовыми блоками.
 */
class TextController extends AdminController
{
  protected $base_model = 'TextModel';

  /**
   * Текущий алиас текста, выбранный пользователем.
   */
  protected string $alias = '';
  
  /**
   * Ключ страницы в структуре (config_text_alias).
   */
  protected string $pageKey = 'config_text_address';
  
  protected LangConfig $langConfig;

  /**
   * @param mixed       $action
   * @param bool        $runController
   * @param array       $segments
   */
  public function __construct($action = false, $runController = true, $segments = [])
  {
    $this->langConfig = config('lang');
    $this->setAlias($segments['alias'] ?? Service::request()->_('alias'));
    parent::__construct($action, $runController, $segments);
  }

  /**
   * Подготовить параметры вида (ресурсы, ключ страницы и т.п.).
   *
   * @return void
   */
  public function setViewParams()
  {
    parent::setViewParams();
    $this->view->addJsFile('visualeditor/ckeditor', 'admin', null, 'cdn');
    $this->view->key  = $this->getPageKey();
    $this->view->href = $this->buildAliasHref($this->alias);
  }

  /**
   * Показать список/форму редактирования текстов.
   *
   * @return string
   */
  public function show()
  {
    /** @var TextModel $model */
    $model     = $this->model;
    $langsData = $model->getLangsData($this->alias, $this->langConfig->getActiveLanguages());

    return $this->render('form', [
      'langsData'       => $langsData,
      'aliasOptions'    => $this->getAliasOptions(),
      'currentAlias'    => $this->alias,
      'formAction'      => Service::structure()->getPageHrefByKey($this->getPageKey()),
      'structureData'   => Service::structure()->getPageDataByKey($this->getPageKey()),
      'aliasBaseHref'   => $this->buildAliasHref(''),
      'default_language'=> $this->langConfig->getDefault(),
    ]);
  }

  /**
   * Сохранить изменения для всех языков.
   */
  /**
   * @param bool $new
   * @return string
   */
  public function update($new = false)
  {
    $texts = Service::request()->_post('texts');
    if (empty($texts) || !is_array($texts)) {
      $this->view->addMessage(lang('input data error', 'message_error'), 'error');

      return $this->redirectDefaultAction($this->getDefaultUrl());
    }

    $defaultLang = $this->langConfig->getDefault();
    $defaultData = $texts[$defaultLang]['content'] ?? '';

    /** @var TextModel $model */
    $model        = $this->model;
    $defaultDto   = TextDto::fromArray([
      'alias'    => $this->alias,
      'language' => $defaultLang,
      'content'  => $defaultData,
    ]);
    $model->saveText($defaultDto);

    foreach ($this->langConfig->getActiveLanguages() as $language) {
      if ($language === $defaultLang) {
        continue;
      }
      $content = trim((string)($texts[$language]['content'] ?? ''));
      $dto     = TextDto::fromArray([
        'alias'    => $this->alias,
        'language' => $language,
        'content'  => $content !== '' ? $content : $defaultDto->getContent(),
      ]);
      $model->saveText($dto);
    }

    $this->view->addMessage(lang('message_element_base_update', 'message_success'), 'success');

    return $this->redirectDefaultAction($this->getDefaultUrl());
  }

  /**
   * Получить список доступных алиасов из структуры.
   *
   * @return array<string, string>
   */
  protected function getAliasOptions(): array
  {
    $structure = Service::structure();
    $keys      = array_filter(
      $structure->getAllKeysByPattern('config_text_'),
      static fn($key) => $key !== 'config_text'
    );

    $options = [];
    foreach ($keys as $key) {
      $alias = substr($key, strlen('config_text_'));
      if (!$alias) {
        continue;
      }
      $page            = $structure->getPageDataByKey($key);
      $options[$alias] = $page['title'] ?? strtoupper($alias);
    }

//    /** @var TextModel $model */
//    $model       = $this->model;
//    $defaultLang = $this->langConfig->getDefault();
//    $langFilter  = $model->supportsLanguages() ? $defaultLang : null;
//    foreach ($model->getAliases($langFilter) as $alias) {
//      if (!isset($options[$alias])) {
//        $options[$alias] = strtoupper($alias);
//      }
//    }

    return $options;
  }
  
  /**
   * Собрать относительный путь к странице текста по алиасу.
   *
   * @param string $alias
   * @return string
   */
  protected function buildAliasHref(string $alias): string
  {
    return 'text/alias/' . $alias;
  }

  /**
   * Текущий ключ страницы в структуре.
   *
   * @return string
   */
  protected function getPageKey(): string
  {
    return $this->pageKey;
  }

  /**
   * Нормализовать и установить текущий алиас + calculate page key.
   *
   * @param string|null $alias
   * @return void
   */
  protected function setAlias(?string $alias): void
  {
    $alias = $this->sanitizeAlias($alias) ?: $this->getDefaultAlias();
    $key   = $this->buildPageKey($alias);

    if (!Service::structure()->isPageKey($key)) {
      $alias = $this->getDefaultAlias();
      $key   = $this->buildPageKey($alias);
    }

    $this->alias   = $alias;
    $this->pageKey = $key;
  }

  /**
   * Проверить и привести алиас к допустимому формату.
   *
   * @param string|null $alias
   * @return string
   */
  protected function sanitizeAlias(?string $alias): string
  {
    $alias = strtolower((string)$alias);

    return preg_match('/^[a-z0-9_]+$/', $alias) ? $alias : '';
  }

  /**
   * Алиас, используемый по умолчанию.
   *
   * @return string
   */
  protected function getDefaultAlias(): string
  {
    return 'address';
  }

  /**
   * Построить ключ страницы в структуре.
   *
   * @param string $alias
   * @return string
   */
  protected function buildPageKey(string $alias): string
  {
    return 'config_text_' . $alias;
  }
}


