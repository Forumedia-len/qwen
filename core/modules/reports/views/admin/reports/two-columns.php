<?php
/**
 * @var array $data
 */

use AC\app\entities\enums\EventMode;
use AC\app\services\DataService;
use AC\core\modules\reports\entities\enums\ModeClientByPayment;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\TimeHelper;

$sportsByType = DataService::sportsByType();
/** @var EventMode $eventMode */
$eventMode = $data['eventMode'];
?>
<div id="main">
  <div id="top">
    <h1><?= $data['title'] ?></h1>
  </div>
  <?php foreach ($data['clientsByColumns'] as $typeSport => $paymentMethods): ?>
    <h2><b><?= $sportsByType[$typeSport]->title_site_url ?> - <?= $data['dateTitle'] ?> (<?= $eventMode->titleReport() ?>)</b></h2>
    <?php foreach (ModeClientByPayment::withdrawalOrder() as $paymentMethod):
      [$clientMode, $encash] = explode('_', $paymentMethod->value); ?>
      <?php if ($paymentMethod->usePayment()): ?>
      <div class='typeTitle'><?= $paymentMethod->titleReport() ?></div>
      <?php if (!empty($paymentMethods[$paymentMethod->value][0])): ?>
        <?php for ($columnKey = 0; $columnKey < count($paymentMethods[$paymentMethod->value]); $columnKey++):
          ksort($paymentMethods[$paymentMethod->value][$columnKey]); ?>
          <div id='<?= ($columnKey == 0 ? 'columnLeft' : 'columnRight') ?>'>
            <?php foreach (array_keys($paymentMethods[$paymentMethod->value][$columnKey]) as $client):
              [$clientName, $clientId, $clubStateTitle] = explode('|', $client); ?>
              <div class='client'>
                <div class='title'>
                  <span class='header'><?= lang('Client') ?>: </span> <?= $clientName ?> (<?= $clubStateTitle ?>)<br>
                  <?php if ($eventMode == EventMode::Reservation || $eventMode == EventMode::All): ?>
                    <span class='header'><?= lang('Booking', 'reports') ?>: </span>
                    <?= TimeHelper::convertMinutes2MySQLTime($data['gameTimeClients'][$typeSport][$paymentMethod->value][$client][EventMode::Reservation->label()]) ?> /
                    <?= NumberHelper::valute($data['sumPrices']['client'][$typeSport][$paymentMethod->value][$client][EventMode::Reservation->label()]) ?>
                    <br>
                  <?php endif; ?>
                  <?php if ($eventMode == EventMode::Ticket || $eventMode == EventMode::All): ?>
                    <span class='header'><?= lang('Abo', 'reports') ?>: </span>
                    <?= TimeHelper::convertMinutes2MySQLTime($data['gameTimeClients'][$typeSport][$paymentMethod->value][$client][EventMode::Ticket->label()]) ?> /
                    <?= NumberHelper::valute($data['sumPrices']['client'][$typeSport][$paymentMethod->value][$client][EventMode::Ticket->label()]) ?>
                    <br>

                  <?php endif; ?>
                </div>
                <div class='periods'>
                  <table cellspacing='0' class='periods'>
                    <tbody>
                    <?php ksort($data['rows'][$typeSport][$paymentMethod->value][$client]);
                    foreach ($data['rows'][$typeSport][$paymentMethod->value][$client] as $datum => $period): ?>
                      <tr>
                        <td class='a'><?= $period['eventTitle'] ?></td>
                        <td class='p'><?= $period['date_time'] ?></td>
                        <td class='pr <?= $encash == 0 ? ' encash' : ''?>'><?= $period['prices'] ?></td>
                        <td><?= $period['optional'] ?></td>
                      </tr>
                    <?php endforeach; ?>
                    </tbody>
                  </table>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
        <?php endfor; ?>
      <?php endif; ?>
      <div style='clear:both; text-align:left'>
        <?= lang('Total amount', 'reports') ?>
        : <?= NumberHelper::valute($data['sumPrices']['encash'][$typeSport][$paymentMethod->value]['full']) ?>
        <?php if ($eventMode == EventMode::All): ?>
          (
          <?= lang('Booking', 'reports') ?>
          : <?= NumberHelper::valute($data['sumPrices']['encash'][$typeSport][$paymentMethod->value][EventMode::Reservation->label()]) ?> |
          <?= lang('Abo', 'reports') ?>
          : <?= NumberHelper::valute($data['sumPrices']['encash'][$typeSport][$paymentMethod->value][EventMode::Ticket->label()]) ?>
          )
        <?php endif; ?>
      </div>
      <br><br>
    <?php endif; ?>
    <?php endforeach; ?>
    <div id='bottom'>
      <p><b><?= lang('Total amount', 'reports') ?>: <?= NumberHelper::valute($data['sumPrices']['full_type_sport'][$typeSport]['full']) ?></b>
        <?php if ($eventMode == EventMode::All): ?>
          (
          <?= lang('Booking', 'reports') ?>
          : <?= NumberHelper::valute($data['sumPrices']['full_type_sport'][$typeSport][EventMode::Reservation->label()]) ?> |
          <?= lang('Abo', 'reports') ?>
          : <?= NumberHelper::valute($data['sumPrices']['full_type_sport'][$typeSport][EventMode::Ticket->label()]) ?>
          )
        <?php endif; ?>
      </p>
      <?= lang('Printed on', 'reports') . ': ' . date('d.m.Y H:i:s') ?>
    </div>
    <hr>
  <?php endforeach; ?>
</div>