<?php

namespace AC\core\modules\clients\controllers\admin;

use AC\app\controllers\AdminController;
use AC\app\entities\enums\Encash;
use AC\core\engines\AccountsEngine;
use AC\core\engines\Engines;
use AC\core\system\helpers\DateHelper;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\modules\modComm\helpers\ModCommHelper;

class ClientsPersonalDataController extends AdminController
{

  protected Engines        $r;
  protected AccountsEngine $a;

  public function getReservation($client_id)
  {
    $reservation2display[] = [0, lang('Booking', 'personal_data')];
    $reservation_data      = [];

    if ($this->r->getReservationDataByClient($client_id, null, $reservation_data)) {
      foreach ($reservation_data as $data) {
        $encash                = Encash::from($data['encash']);
        $reservation2display[] = [
          1,
          date('<b>d.m.Y</b> H:i', strtotime($data['start'])) . date(' - H:i',
            strtotime($data['finish'])) . '<br/><span class="green">' . date('d.m.Y H:i', strtotime($data['ordered'])) . '</span>',
          $data['sport_title'] . ', ' . $data['area_title'] . ($encash ? ', [<b>' . $encash->shortLabel() . '</b>]' : '')
          . ', ' . NumberHelper::valute($data['price']),
        ];
      }
    }

    return $reservation2display;
  }

  public function getRemovedReservation($client_id, &$removed2display, &$removedAbo2display)
  {
    $removed2display[]    = [0, lang('Cancellations (Single)', 'personal_data')];
    $removedAbo2display[] = [0, lang('Cancellations (SUBSCRIPTION)', 'personal_data')];

    $remove_reservation = $this->r->getArchReservByUser($client_id);
    foreach ($remove_reservation as $data) {
      $encash            = Encash::tryFrom($data['reservation_data']['encash']);
      $removed2display[] = [
        1,
        date('<b>d.m.Y</b> H:i', strtotime($data['reservation_data']['start'])) . date(' - H:i',
          strtotime($data['reservation_data']['finish'])) . '<br/><span class="red">' . date('d.m.Y H:i',
          strtotime($data['date_delete'])) . '</span>',
        $data['reservation_data']['areas_title'] .
        ($encash ? ', [<b>' . $encash->shortLabel() . '</b>]' : '') . ', <br/>' .
        NumberHelper::valute($data['reservation_data']['price']),
      ];
    }

    $remove_abo = $this->r->tickets->getArchTicketsByUser($client_id);

    foreach ($remove_abo as $data) {
      $removedAbo2display[] = [
        1,
        date('<b>d.m.Y</b> H:i', strtotime($data['start'])) . date(' - H:i',
          strtotime($data['finish'])) . '<br/><span class="red">' . date('d.m.Y H:i', strtotime($data['created_at'])) . '</span>',
        $data['type_title'] . ', ' . $data['area_title'] . ', ' . NumberHelper::valute($data['price']),
      ];
    }

    if (\Service::query()::getDB()->checkTable('reservations_log')) {
      if ($this->r->getRemovedReservationDataByClient($client_id, $remove_reservation_data)) {
        foreach ($remove_reservation_data as $data) {
          $encash = Encash::from($data['encash']);
          if ($data['ticket_id'] > 0) {
            $removedAbo2display[] = [
              1,
              date('<b>d.m.Y</b> H:i', strtotime($data['start'])) . date(' - H:i',
                strtotime($data['finish'])) . '<br/><span class="red">' . date('d.m.Y H:i', strtotime($data['removed'])) . '</span>',
              $data['type_title'] . ', ' . $data['area_title'] . ', ' . NumberHelper::valute($data['price']),
            ];
          } else {
            $removed2display[] = [
              1,
              date('<b>d.m.Y</b> H:i', strtotime($data['start'])) . date(' - H:i',
                strtotime($data['finish'])) . '<br/><span class="red">' . date('d.m.Y H:i', strtotime($data['removed'])) . '</span>',
              $data['type_title'] . ', ' . $data['area_title'] . ($encash ? ', [<b>' . $encash->shortLabel() . '</b>]'
                : '') . ', ' . NumberHelper::valute($data['price']),
            ];
          }
        }
      }
    }
  }

  public function getAccounts($client_id, $client_data_id)
  {
    $prepayment2display[] = [0, lang('Credit Access', 'personal_data')];
    $rows                 = [];
    if (($response = ModCommHelper::callSafe('clients', 'privateAccount/transactions',
        ['clientId' => $client_data_id, 'typeDirection' => 'full'])) && $response->isSuccess()) {
      $transactionData = $response->getData();
      foreach ($transactionData['transactions']['all'] as $item) {
        $rows[] = ['date_start' => $item->created_at, 'sum' => (($item->type_direction == 'in') ? '' : '-') . $item->amount];
      }
    }

    if ($prepayment_data = $this->a->getReportPrepaymentAccountsByClientId($client_id)) {
      foreach ($prepayment_data as $data) {
        $rows[] = ['date_start' => $data['date_start'], 'sum' => $data['sum']];
      }
    }

    usort($rows, fn($a, $b) => $b['date_start'] <=> $a['date_start']);
    foreach ($rows as $data) {
      $prepayment2display[] = [1, date('<b>d.m.Y</b>', strtotime($data['date_start'])), NumberHelper::valute($data['sum'])];
    }

    return $prepayment2display;
  }

  public function statistics(): string
  {
    ob_start();

    $client_id = $_GET['client_id'];
    $this->r   = \Service::engines();
    if (isset($_GET['client_id']) && $this->r->clients->getClientData((int)$_GET['client_id'], $client_data)) {
      ?>
      <div style="display: flex;width: 100%;justify-content:space-around;">
        <?
        echo "<table height=\"100%\" border=\"0\" cellspacing=\"1\" cellpadding=\"3\"  bgcolor=\"#FFFFFF\" class=\"main\">\n";
        //формируем массив с данными
        //структура - тип, поле, [значение], [прочее]
        $data2display   = [];
        $data2display[] = [0, lang('Client data', 'clients')];
        //$data2display[] = array (1, 'Kunden-ID:', $client_data['client_id']);
        $data2display[] = [1, lang('Client') . ':', $client_data['mode'] == 1 ? lang('Online') : lang('Offline')];
        $data2display[] = [1, lang('Registered on') . ':', date('d.m.Y H:i:s', strtotime($client_data['registered']))];
        $data2display[] = [1, lang('Mg.no') . ':', $client_data['number']];
        $data2display[] = [1, lang('First name') . ':', $client_data['name']];
        $data2display[] = [1, lang('Family name') . ':', $client_data['surname']];
        $data2display[] = [1, lang('Phone') . ':', $client_data['phone']];
        $data2display[] = [1, lang('Mobile') . ':', $client_data['phone_mobile']];
        $data2display[] = [1, lang('Fax') . ':', $client_data['fax']];
        $data2display[] = [1, lang('Zip') . ':', $client_data['post_code']];
        $data2display[] = [1, lang('city') . ':', $client_data['city']];
        $data2display[] = [1, lang('Address') . ':', nl2br($client_data['address'])];
        $data2display[] = [1, lang('E-mail') . ':', $client_data['email']];
        $data2display[] = [1, lang('Internet') . ':', (isset($client_data['url']) ? $client_data['url'] : '')];
        $data2display[] = [1, lang('Birthday') . ':', $client_data['birthday'] != '' ? DateHelper::convertMysql2Date($client_data['birthday']) : ''];

        //статистика
        $data2display[] = [0, lang('Statistics')];
        $stats          = $this->r->statistics->getReservationsCountByClientId($client_data['client_id']);
        if (!empty($stats)) {
          $data2display[] = [2, '<b>' . lang('Reservation count', 'clients') . ':</b>'];
          foreach ($stats as $s) {
            $data2display[] = [1, $s[0] . ':', $s[1]];
          }
        } else {
          $data2display[] = [1, '<b>' . lang('Reservation count', 'clients') . ':</b>', ''];
        }
        //$data2display[] = array (2, '<b>Anzahl der Stornierungen:</b>');
        //$data2display[] = array (1, 'Allgemein:', $r->statistics->getRemovedReservationsCountByClientId ($client_data['client_id']));
        $data2display[] = [
          1,
          '<b>' . lang('Cancellation count', 'clients') . ':</b>',
          $this->r->statistics->getRemovedReservationsCountByClientId($client_data['client_id']),
        ];

        $i = 0;
        foreach ($data2display as $field) {
          $class = $i % 2 == 0 ? 'light' : 'dark';

          if ($field[0] == 0) {
            echo "<tr><th colspan=\"2\" class=\"" . $class . "\">" . $field[1] . "</th></tr>\n";
          } elseif ($field[0] == 1) {
            echo "<tr><td nowrap align=\"right\" class=\"" . $class . "\">" . $field[1] . '</td>' . ($field[2]
                ? '<td width="90%" class="' . $class . '">' . $field[2] . '</td>'
                : '<td align="center" width="90%" class="' . $class . '">-</td>') . "</tr>\n";
          } elseif ($field[0] == 2) {
            echo "<tr><td colspan=\"2\" class=\"" . $class . "\">" . $field[1] . "</td></tr>\n";
          }

          $i++;
        }

        echo "</table>\n";

        $this->a = useClass(paths()->enginesDir . 'AccountsEngine', true);

        $reservation2display = $this->getReservation($client_id);
        $this->displayData($reservation2display);

        $removed2display    = [];
        $removedAbo2display = [];
        $this->getRemovedReservation($client_id, $removed2display, $removedAbo2display);
        $this->displayData($removed2display);
        $this->displayData($removedAbo2display);

        $prepayment2display = $this->getAccounts($client_id, $client_data['client_id']);
        $this->displayData($prepayment2display);
        ?>
      </div>
      <script>
        window.resizeTo(1250, 700)
      </script>
      <?
    } else {
      echo lang('Client not found', 'message_error');
    }

    return ob_get_clean();
  }

  protected function displayData($data2display)
  {
    $i   = 0;
    $out = '<table width="300px" border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" class="main" style="height: fit-content;">';
    foreach ($data2display as $field) {
      $class = $i % 2 == 0 ? 'light' : 'dark';

      if ($field[0] == 0) {
        $out .= "<tr><th colspan=\"2\" class=\"" . $class . "\">" . $field[1] . "</th></tr>\n";
      } elseif ($field[0] == 1) {
        $out .= "<tr><td nowrap align=\"right\" class=\"" . $class . "\">" . $field[1] . "</td>" . ($field[2]
            ? '<td width="90%" class="' . $class . '">' . $field[2] . '</td>'
            : '<td align="center" width="90%" class="' . $class . '">-</td>') . "</tr>\n";
      } elseif ($field[0] == 2) {
        $out .= "<tr><td colspan=\"2\" class=\"" . $class . "\">" . $field[1] . "</td></tr>\n";
      }
      $i++;
    }
    $out .= "</table>\n";
    echo $out;
  }
}
