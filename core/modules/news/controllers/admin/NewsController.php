<?php

namespace AC\core\modules\news\controllers\admin;

use AC\app\config\LangConfig;
use AC\app\controllers\AdminController;
use AC\core\modules\news\engines\NewsEngine;
use AC\core\modules\news\entities\dto\NewsDto;
use AC\core\modules\news\models\NewsModel;
use Service;

class NewsController extends AdminController
{
  protected $base_model = 'NewsModel';
  
  /**
   * @var NewsModel
   */
  public $model;
  
  protected LangConfig $langConfig;
  
  public function __construct($action = false, $runController = true, $segments = [])
  {
    $this->langConfig = config('lang');
    parent::__construct($action, $runController, $segments);
  }
  
  public function setViewParams()
  {
    parent::setViewParams();
    $this->view->addJsFile('visualeditor/ckeditor', 'admin', null, 'cdn');
    $this->view->addJsFile('jquery-ui/jquery-ui.min', 'third', true, 'cdn');
    $this->view->addCssFile('jquery-ui/jquery-ui.min', 'third', true, 'cdn');
    $this->view->addJsFile('jquery-ui/i18n/datepicker-' . config('lang')->getCurrentLang(), 'third', true, 'cdn');
    $this->view->key = 'news';
  }
  
  public function show()
  {
    return [$this->list(), $this->form(NewsDto::fromArray([]))];
  }
  
  /**
   * Список новостей
   * @return array
   */
  public function list()
  {
    // Получаем только основные новости на дефолтном языке
    $conditions = [];
    if (count($this->langConfig->getActiveLanguages()) > 1) {
      $conditions = [
        "language = '{$this->langConfig->getDefault()}'"
      ];
    }
    $this->model->getListData($news, $conditions, ['date desc'], ['as' => 'dto', 'dtoClass' => NewsDto::class]);
    
    return
      $this->render('list', ['news' => $news]);
  }
  
  /**
   * Изменить новость
   */
  public function update($new = false)
  {
    if (($newsData = Service::request()->_post('news'))
      && !empty($newsData[$this->langConfig->getDefault()])) {
      $mainNewsDto = NewsDto::fromArray($newsData[$this->langConfig->getDefault()]);
      if (empty($mainNewsDto->getTitle())) {
        $this->view->addMessage(lang('is required', 'message_error', ['field' => lang('Title')]), 'error');
        return $this->form($mainNewsDto);
      }
      if (empty($mainNewsDto->getContent())) {
        $this->view->addMessage(lang('is required', 'message_error', ['field' => lang('Content')]), 'error');
        return $this->form($mainNewsDto);
      }
      
      if ($this->saveNews($mainNewsDto)) {
        foreach ($newsData as $lang => $news) {
          if ($lang === $this->langConfig->getDefault()) {
            continue;
          }
          $newsDto = NewsDto::fromArray($news);
          $newsDto->setLanguage($lang);
          if ($lang !== $this->langConfig->getDefault()) {
            $newsDto->setMainNewsId($mainNewsDto->getId());
            $newsDto->setDateTime($mainNewsDto->getDateTime());
            $newsDto->setPublished($mainNewsDto->getPublished());
            if (empty($newsDto->getTitle())) {
              $newsDto->setTitle($mainNewsDto->getTitle());
            }
            if (empty($newsDto->getContent())) {
              $newsDto->setContent($mainNewsDto->getContent());
            }
          }
          $this->saveNews($newsDto);
        }
        $this->view->addMessage(lang('News data saved.', 'message_success'), 'success');
      } else {
        $this->view->addMessage(lang('Couldn\'t save the news data.', 'message_error'), 'error');
      }
    } else {
      $this->view->addMessage(lang('input data error', 'message_error'), 'error');
    }
    return $this->redirectDefaultAction();
  }
  
  protected function saveNews(NewsDto $newsDto): bool
  {
    if (count($this->langConfig->getActiveLanguages()) === 1) {
      $newsDto->language     = null;
      $newsDto->main_news_id = null;
    }
    if (($newsDto->getId() && $this->newsEngine()->changeNewsItemById(
          $newsDto->getId(),
          $newsDto->getDate('Y-m-d'),
          trim($newsDto->getTitle()),
          trim($newsDto->getContent()),
          $newsDto->getPublished(),
          $newsDto->getLanguage(),
          $newsDto->getMainNewsId()
        ))
      || $this->newsEngine()->insertNewsItem(
        $newsDto->getDate('Y-m-d'),
        trim($newsDto->getTitle()),
        trim($newsDto->getContent()),
        $newsDto->getPublished(),
        $newsDto->id,
        $newsDto->getLanguage(),
        $newsDto->getMainNewsId()
      )) {
      return true;
    }
    
    return false;
  }
  
  /**
   * Удалить новость
   */
  public function remove()
  {
    if (($news_id = Service::request()->_get('news_id')) && $this->newsEngine()->removeNewsItemById($news_id)) {
      $this->view->addMessage(lang('News removed.', 'message_success'), 'success');
    } else {
      $this->view->addMessage(lang('Couldn\'t remove the news.', 'message_error'), 'error');
    }
    return $this->redirectDefaultAction();
  }
  
  /**
   * Форма редактирования новости
   */
  public function edit()
  {
    if (($news_id = Service::request()->_get('news_id'))
      && $this->newsEngine()->getNewsItemById($news_id, $news_item) && $news_item) {
      return [$this->form(NewsDto::fromArray($news_item)), $this->backButton()];
    }
    
    return $this->redirectDefaultAction($this->getDefaultUrl());
  }
  
  /**
   * Рендер формы создания/редактирования новости
   * @param NewsDto|null $newsDto Данные новости для редактирования
   * @return string
   */
  protected function form(?NewsDto $newsDto = null)
  {
    $langsData = [$newsDto->getLanguage() => $newsDto];
    if (count($this->langConfig->getActiveLanguages()) > 1) {
      $dataLangContents = [];
      $newsDto->getId() && $this->newsEngine()->getNewsItems(
        $dataLangContents,
        ['main_news_id = "' . $newsDto->getId() . '"'],
        [],
        ['as' => 'dto', 'dtoClass' => NewsDto::class, 'key' => 'language']);
      foreach ($this->langConfig->getActiveLanguages() as $langCode) {
        if ($langCode !== $this->langConfig->getDefault()) {
          
          $langsData[$langCode] = $dataLangContents[$langCode] ?? NewsDto::fromArray(array_merge($newsDto->toArray(), [
            'language'     => $langCode,
            'news_id'      => null,
            'main_news_id' => $newsDto->getId()
          ]));
        }
      }
    }
    
    return $this->render('form', [
      'langsData'        => $langsData,
      'default_language' => $this->langConfig->getDefault(),
      'main_news_id'     => $newsDto->getId(),
    ]);
  }
  
  protected function newsEngine(): NewsEngine
  {
    return getEngine('news', false);
  }
}