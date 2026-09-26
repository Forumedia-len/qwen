<?php
/**
 *  Вывод таблицы расписания для кортов
 * @var array  $sports_tabs    - все спроты этого типа площадок
 * @var array  $pages_tabs     - вкладки для страниц страницы
 * @var array  $weeks_tabs     - вкладки для недельного и обычного рассписания
 * @var int    $sport_id       - индетификатор текущего спорта
 * @var int    $type_id        - тип корта
 * @var int    $client_id      - индефикатор зарегистрированного клиента
 * @var int    $on_reservation - возможность бронировать этот день
 * @var string $date           - дата
 * @var int    $page           - номер страницы
 * @var array  $areas          - площадки
 * @var bool   $week           - недельный отчет показывать
 * @var View   $this
 * @var array  $params
 * @var string $courtType
 * @var array  $template
 */

use AC\core\system\view\View;


?>
<div class="aroundBox">
  <div class="header">
  </div>
  <div class="content" style="padding:0px;">
    <?php
    echo $this->render('_columns', $params);
    ?>
  </div>
  <div class="footer">
  </div>
</div>