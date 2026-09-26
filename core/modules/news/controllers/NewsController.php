<?php

namespace AC\core\modules\news\controllers;

use AC\app\config\LangConfig;
use AC\core\modules\news\engines\NewsEngine;
use AC\core\modules\news\entities\dto\NewsDto;
use AC\core\modules\news\models\NewsModel;
use AC\core\system\controller\BaseController;
use AC\core\system\helpers\TextHelper;
use Service;


class NewsController extends BaseController
{
  protected $base_model = 'NewsModel';
  
  public function setViewParams()
  {
    $this->setViewParam('key', 'aktuelles');
    $this->setViewParam('title', 'Aktuelles');
    
    parent::setViewParams();
  }
  
  public function show()
  {
    if (($id = Service::request()->_get('news_id')) && is_numeric($id)) {
      return $this->viewNews($id);
    }
    $news       = new NewsModel();
    $result     = '';
    $conditions = ['publish = 1'];
    if ($news->getListData($data, $conditions, ['date desc'], [
      'as'        => 'dto',
      'dtoClass'  => NewsDto::class,
      'limit'     => 100,
    ])) {
      /** @var LangConfig $langConfig */
      $langConfig = config('lang');
      if (count($langConfig->getActiveLanguages()) > 1) {
        $dataTmp = [];
        /** @var NewsDto $item */
        foreach ($data as $item) {
          if($item->getContent() && $item->getTitle()) {
            $dataTmp[$item->getLanguage()][$item->getDate()] = $item;
          }
        }
        $data = array_merge($dataTmp[$langConfig->getDefault()], $dataTmp[$langConfig->getCurrentLang()] ?? []);
        krsort($data);
      }
      /** @var NewsDto $item */
      foreach ($data as $item) {
        $content_small = TextHelper::textSoftCut(TextHelper::fullStripTags($item->getContent()), 250);
        if (strlen($content_small) < strlen($item->getContent())) {
          $content_small = nl2br(
              $content_small
            ) . ' <a href="' . Service::structure()->getPageHrefByKey('aktuelles') . '?news_id=' . $item->getId() . '" class="link_red_bold">[...]</a>';
        }
        $item->setContent($content_small);
        $result .= $this->render('item', ['item' => $item]);
      }
    } else {
      $result = '<p>' . lang('no_news_added', 'news') . '</p>';
    }
    
    
    return $this->render('list', ['content' => $result]);
  }
  
  public function viewNews($id = null)
  {
    /** @var LangConfig $langConfig */
    $langConfig = config('lang');
    if ($id && $this->newsEngine()->getNewsItemById($id, $newsData)) {
      $newsDto = NewsDto::fromArray($newsData);
      if ($newsDto->getLanguage() != $langConfig->getCurrentLang()) {
        $newsId     = $newsDto->getMainNewsId() ?? $id;
        $conditions = [];
        $params     = ['as' => 'dto', 'dtoClass' => NewsDto::class];
        if (count($langConfig->getActiveLanguages()) > 1) {
          $conditions[]  = '(news_id = "' . $newsId . '" OR main_news_id = "' . $newsId . '")';
          $params['key'] = 'language';
          if ($this->model->getListData($data, $conditions, ['date desc'], $params)) {
            if($data[$langConfig->getCurrentLang()]?->getTitle()) {
              $newsDto->setTitle($data[$langConfig->getCurrentLang()]->getTitle());
            }
            if($data[$langConfig->getCurrentLang()]?->getContent()) {
              $newsDto->setContent($data[$langConfig->getCurrentLang()]->getContent());
            }
          }
        }
      }
      
      return $this->render('item-full',
        ['item' => $newsDto, 'backUrl' => Service::structure()->getPageHrefByKey('aktuelles')]);
    }
    return $this->redirectDefaultAction();
  }
  
  protected function newsEngine(): NewsEngine
  {
    return getEngine('news', false);
  }
  
}