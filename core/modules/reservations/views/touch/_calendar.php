<?php
/**
 *  Переменные для календаря
 * @var BaseObject $date     - дата
 * @var int        $type_id  - тип корта
 * @var int        $sport_id - тип спорта
 * @var int        $area_id  - тип спорта
 * @var int        $page     - номер страницы
 * @var array      $months   - месяцы
 * @var array      $weeks    - недели
 * @var View       $this
 */

use AC\core\system\object\BaseObject;
use AC\core\system\view\View;

?>
<?php if (!config('reservations')->isOpenType((int)$type_id)) {
  echo $this->render('close/_calendar', $params);
} else {
  echo $this->render('open/_calendar', $params);
}
?>