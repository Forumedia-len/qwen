<?php

namespace AC\core\engines;

use AC\core\system\db\Query;
use AC\core\system\helpers\DataHelper;

useClass('engines\reservation.discount');

//проводимые акции
class DiscountsEngine extends \reservation_discount
{
  public function getAllDiscounts($params = []): array
  {
    static $discounts;
    if(empty($discounts)) {
      $discounts = Query::sqlQuery('select * from ' . Query::tableName('config_discount') . ' order by discount_id');
    }
    return DataHelper::getDataAs($discounts ?: [], $params);
  }
}
