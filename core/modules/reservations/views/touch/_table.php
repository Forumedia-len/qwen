

<?php
if (!config('reservations')->isOpenType((int)$type_id)) {
  echo $this->render('close/_table', $params);
} else {
  echo $this->render('open/_table', $params);
}
