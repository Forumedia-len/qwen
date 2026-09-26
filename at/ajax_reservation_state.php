<?php
//запрет кеширования

use AC\core\engines\Engines;

//время работы площадки
$reservation_id = Service::request()->_('reservation_id', null);
if($reservation_id !== null){
	$r = new Engines();

	if($r->changePaymentState($reservation_id, 1))
	    echo '<strong style="color:green">['.CURR_VALUTE.']</strong>';
	else
	    echo '<strong style="color:red">['.CURR_VALUTE.'!]</strong>';
} else
    echo '<strong style="color:red">['.CURR_VALUTE.'!]</strong>'."\n";
?>