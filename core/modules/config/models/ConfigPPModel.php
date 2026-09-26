<?php

namespace AC\core\modules\config\models;

use AC\core\engines\PpEngine;
use AC\core\modules\config\tables\ConfigPpTable;

class ConfigPPModel extends ConfigPpTable
{

  protected $baseEngine = 'PpEngine';
  /**
   * @var PpEngine
   */
  protected $engine;

  /**
   * {@inheritDoc}
   */
  public function rules()
  {
    return array(
      array(array('sort', 'price_real', 'price_account'), 'required'),
      array('sort', 'integer'),
      array(array('price_real', 'price_account'), 'float'),
    );
  }

}