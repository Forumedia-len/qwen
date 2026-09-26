<?php

namespace AC\core\modules\news\models;

use AC\core\modules\news\engines\NewsEngine;
use AC\core\system\model\BaseModel;

class NewsModel extends BaseModel
{
  protected $baseGetFunction        = 'getNewsItemById';
  protected $primary_key            = 'news_id';
  protected $baseEngine             = 'NewsEngine';
  protected $baseGetFunctionAllData = 'getNewsItems';
  public    $tableName              = 'news_simplest';
  
  
  /**
   * @var NewsEngine
   */
  protected $engine;
  
  public function getListData(&$data, $conditions = [], $_order = [], $params = [])
  {
    return $this->engine->getNewsItems($data, $conditions, $_order, $params);
  }
}