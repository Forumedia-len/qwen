<?php

require '_top.php';
checkSession();

useClass('include\\base\\Request');
useClass('engines\\reservation');

class compare_report
{
  public function start()
  {
    switch ($_GET['action']) {
      case 'loadFileCsv':
        return $this->renderFileCsv();
      case 'compareReservationsWithTmp':
        return $this->compareReservationsWithTmp();
      default:
        return $this->viewForm();
    }
  }

  public function viewForm()
  {
    $out = '';
    ob_start();
    ?>
    <h1>Vergleich der Berichtsdatei mit PayOne</h1>
    <form action="compare_report.php?action=loadFileCsv" method="post" enctype="multipart/form-data">
      <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
        <tr>
          <td class="light">Datei (.csv)</td>
          <td class="light"><input type="file" name="file_csv" class="input wide"/></td>
        </tr>
        <tr>
          <th align="center" colspan="2"><input type="submit" class="button" value="Ver&auml;ndern"></th>
        </tr>
      </table>
    </form>
    <?php
    $out .= ob_get_contents();
    ob_end_clean();

    return [$out, $this->formCompareReservationTmp()];
  }

  public function compareReservationsWithTmp()
  {
    $year             = Request::_get('year');
    $month            = Request::_get('month');
    $engine           = new reservation();
    $mysql_start_date = '2023-02-01';
    $mysql_end_date   = '2023-02-31';
    useClass('engines\\reservationsReports');
    $rr      = new reservationsReports();
    $reports = $rr->getOrders($mysql_start_date, $mysql_end_date);
    $er      = [];
    foreach ($reports as $report) {
      if (!$report['completed']) {
        $reservation_data           = $rr->getReservationData($report['area_id'], $report['start']);
        $reservation_data1          = $rr->getReservationData($report['area_id'], $report['start'], true);
        $er[$report['client_id']][] = [$report, $reservation_data, $reservation_data1];
      }
    }
    Debugs::dvD($er);
  }

  public function renderFileCsv()
  {
    $true_arr         = $false_arr = $incomprehensible_arr = [];
    $mysql_start_date = $mysql_end_date = null;
    $payOneReports    = [];
    if (isset($_FILES['file_csv']) && $_FILES['file_csv']['error'] == 0) {
      $i = 0;
      if ($line = file($_FILES['file_csv']['tmp_name'])) {
        foreach ($line as $data) {
          $data = explode(';', $data);
          if ($i == 0) {
            $alias = array_flip($data);
          } else {
            $unix = strtotime($data[$alias['create_time']]);

            $mysql_start_date = $mysql_start_date == null
              ? date('Y-m-d', $unix)
              : (strtotime($mysql_start_date) > $unix ? date('Y-m-d', $unix) : $mysql_start_date);
            $mysql_end_date   = $mysql_end_date == null
              ? date('Y-m-d', $unix)
              : (strtotime($mysql_end_date) < $unix ? date('Y-m-d', $unix) : $mysql_end_date);

            $payOneReports[date('d.m.Y', $unix)][$data[$alias['reference']]][] = [
              'ordered_date' => date('d.m.Y', $unix),
              'ordered_time' => date('H:i', $unix),
              'surname'      => $data[$alias['surname']],
              'name'         => $data[$alias['name']],
              'price'        => (float)str_replace(',', '.', trim($data[$alias['amount']])),
              'pay_state'    => $data[$alias['reference']],
              'pay_one_type' => $data[$alias['clearingtype']] == 'wlt' ? 'PayPal' : "Card",
            ];
          }
          $i++;
        }
      }
    }
    ksort($payOneReports);
    useClass('engines\\reservationsReports');
    $rr      = new reservationsReports();
    $reports = $rr->getPayOneReports($mysql_start_date, $mysql_end_date);
    ksort($reports);

    foreach ($payOneReports as $day => $pay_states) {
      foreach ($pay_states as $pay_state => $payItems) {
        foreach ($payItems as $key_pay => $payItem) {
          $price = $payItem['price'];
          if (isset($reports[$day][$pay_state])) {
            $tmp_arr = [];
            $type    = '';
            foreach ($reports[$day][$pay_state] as $key => $reportItem) {
              if ($payItem['name'] == $reportItem['name'] && $payItem['surname'] == $reportItem['surname'] && $reportItem['pay_state'] == $pay_state) {
                $price         -= $reportItem['price'];
                $type          = 'name';
                $tmp_arr[$key] = $reportItem;
                unset($reports[$day][$pay_state][$key]);
              } elseif ($reportItem['pay_state'] == $pay_state) {
                $price         -= $reportItem['price'];
                $type          = 'pay_state';
                $tmp_arr[$key] = $reportItem;
                unset($reports[$day][$pay_state][$key]);
              }
            }
            if ($price == 0) {
              $true_arr[$type][$day][] = [
                'pay_item' => $payItem,
                'rep_item' => $tmp_arr,
              ];
            } else {
              $incomprehensible_arr['price_difference'][$day][] = [
                'pay_item' => $payItem,
                'rep_item' => $tmp_arr,
              ];
            }
            unset($payOneReports[$day][$pay_state][$key_pay]);
          } else {
            $ch = false;
            foreach ($reports[$day] as $rep_state => $rep_states) {
              foreach ($rep_states as $key_rep_i => $repItem) {
                if (!$ch && ($payItem['name'] == $repItem['name'] || $payItem['surname'] == $repItem['surname']) && $payItem['price'] == $repItem['price'] && $repItem['instruction'] == 'Guthabenaufladen') {
                  $true_arr['personal_account'][$day][] = [
                    'pay_item' => $payItem,
                    'rep_item' => [$repItem],
                  ];
                  unset($payOneReports[$day][$pay_state][$key_pay]);
                  unset($reports[$day][$rep_state][$key_rep_i]);
                  $ch = true;
                }
              }
              if (empty($reports[$day][$rep_state])) {
                unset($reports[$day][$rep_state]);
              }
            }
          }
        }
        if (empty($payOneReports[$day][$pay_state])) {
          unset($payOneReports[$day][$pay_state]);
        }
        if (empty($reports[$day][$pay_state])) {
          unset($reports[$day][$pay_state]);
        }
      }
      if (empty($payOneReports[$day])) {
        unset($payOneReports[$day]);
      }
      if (empty($reports[$day])) {
        unset($reports[$day]);
      }
    }
    foreach ($payOneReports as $day => $pay_states) {
      foreach ($pay_states as $pay_state => $payItems) {
        foreach ($payItems as $key_pay => $payItem) {
          foreach ($reports as $rep_day => $rep_states) {
            foreach ($rep_states as $rep_state => $repItems) {
              foreach ($repItems as $key_rep => $repItem) {
                if (($payItem['name'] == $repItem['name'] || $payItem['surname'] == $repItem['surname']) || ($payItem['ordered_date'] == $repItem['ordered_date'] && $repItem['instruction'] == 'Guthabenaufladen') && $payItem['price'] == $repItem['price']) {
                  $incomprehensible_arr['def'][$day][] = [
                    'pay_item' => $payItem,
                    'rep_item' => [$repItem],
                  ];
                  unset($payOneReports[$day][$pay_state][$key_pay]);
                  unset($reports[$rep_day][$rep_state][$key_rep]);
                }
              }
              if (empty($reports[$rep_day][$rep_state])) {
                unset($reports[$rep_day][$rep_state]);
              }
            }
            if (empty($reports[$rep_day])) {
              unset($reports[$rep_day]);
            }
          }
        }
        if (empty($payOneReports[$day][$pay_state])) {
          unset($payOneReports[$day][$pay_state]);
        }
        if (empty($reports[$day][$pay_state])) {
          unset($reports[$day][$pay_state]);
        }
      }
      if (empty($payOneReports[$day])) {
        unset($payOneReports[$day]);
      }
      if (empty($reports[$day])) {
        unset($reports[$day]);
      }
    }

    foreach ($payOneReports as $day => $pay_states) {
      foreach ($pay_states as $pay_state => $payItems) {
        foreach ($payItems as $key_pay => $payItem) {
          $tmp_arr = [];
          if (isset($reports[$day])) {
            foreach ($reports[$day] as $rep_state => $repItems) {
              foreach ($repItems as $key_rep => $repItem) {
                $tmp_arr[] = $repItem;
                unset($reports[$day][$rep_state][$key_rep]);
              }
            }
          } else {
            $tmp_arr[] = [
              'ordered_date' => '',
              'ordered_time' => '',
              'surname'      => '',
              'name'         => '',
              'price'        => '',
              'pay_state'    => '',
              'pay_one_type' => '',
            ];
          }
          $incomprehensible_arr['full_def'][$day][] = [
            'pay_item' => $payItem,
            'rep_item' => $tmp_arr,
          ];
          unset($payOneReports[$day][$pay_state][$key_pay]);
        }
        if (empty($payOneReports[$day][$pay_state])) {
          unset($payOneReports[$day][$pay_state]);
        }
        if (empty($reports[$day][$pay_state])) {
          unset($reports[$day][$pay_state]);
        }
      }
      if (empty($payOneReports[$day])) {
        unset($payOneReports[$day]);
      }
      if (empty($reports[$day])) {
        unset($reports[$day]);
      }
    }

    foreach ($reports as $rep_day => $rep_states) {
      $payItem = [
        'ordered_date' => '',
        'ordered_time' => '',
        'surname'      => '',
        'name'         => '',
        'price'        => '',
        'pay_state'    => '',
        'pay_one_type' => '',
      ];
      foreach ($rep_states as $rep_state => $repItems) {
        $incomprehensible_arr['full_def'][$rep_day][] = [
          'pay_item' => $payItem,
          'rep_item' => $repItems,
        ];
        unset($reports[$rep_day][$rep_state]);
      }
      if (empty($reports[$rep_day])) {
        unset($reports[$rep_day]);
      }
    }

    $return_out = [];
    $sum_rep    = $sum_pay = $sumPayOne = $sumRep = $sumPriceDef = 0;

    $return_out[] = $this->generatePriceCoincidenceOfValues(
      $incomprehensible_arr['full_def'],
      'Vollständige Dateninkongruenz',
      $sum_pay,
      $sum_rep
    );
    $this->addSum($sum_rep, $sum_pay, $sumPayOne, $sumRep, $sumPriceDef);
    $return_out[] = $this->generatePriceCoincidenceOfValues(
      $incomprehensible_arr['price_difference'],
      'Preisunterschied',
      $sum_pay,
      $sum_rep
    );
    $this->addSum($sum_rep, $sum_pay, $sumPayOne, $sumRep, $sumPriceDef);

    $return_out[] = $this->generatePriceCoincidenceOfValues(
      $incomprehensible_arr['def'],
      'Datenunterschied',
      $sum_pay,
      $sum_rep
    );
    $this->addSum($sum_rep, $sum_pay, $sumPayOne, $sumRep, $sumPriceDef);
    $return_out[] = $this->generatePriceCoincidenceOfValues(
      $true_arr['pay_state'],
      'Übereinstimmung der Werte gemäß PayOne-Referenz',
      $sum_pay,
      $sum_rep
    );
    $this->addSum($sum_rep, $sum_pay, $sumPayOne, $sumRep, $sumPriceDef);
    $return_out[] = $this->generatePriceCoincidenceOfValues(
      $true_arr['personal_account'],
      'Auffüllung des persönlichen Kontos',
      $sum_pay,
      $sum_rep
    );
    $this->addSum($sum_rep, $sum_pay, $sumPayOne, $sumRep, $sumPriceDef);
    $return_out[] = $this->generatePriceCoincidenceOfValues(
      $true_arr['name'],
      'Zusammentreffen von Werten',
      $sum_pay,
      $sum_rep
    );
    $this->addSum($sum_rep, $sum_pay, $sumPayOne, $sumRep, $sumPriceDef);

    return array_merge(
      $this->viewForm(),
      [
        "
          <table align='center' class='main wide'>
          <tr><th style='font-size: 14px'>Gesamtsumme PayOne Event, €:</th><td style='font-size: 14px'>" . number_format(
          $sumPayOne,
          2,
          ',',
          ''
        ) . "</td></tr>
          <tr><th style='font-size: 14px'>Gesamtsumme Site Event, €:</th><td style='font-size: 14px'>" . number_format(
          $sumRep,
          2,
          ',',
          ''
        ) . "</td></tr>
          <tr><th style='font-size: 14px'>Unterschied, €:</th><td style='font-size: 14px'>" . number_format(
          $sumPriceDef,
          2,
          ',',
          ''
        ) . "</td></tr>
          </table>",
      ],
      $return_out
    );
  }

  public function generatePriceCoincidenceOfValues($coincidence, $title, &$sum_pay, &$sum_rep)
  {
    $out = '';
    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="0" align="center" class="main wide">
      <tr>
        <th colspan="14"><h3><?= $title ?></h3></th>
      </tr>
      <tr>
        <th colspan="14">PayOne Event / Site Event</th>
      </tr>
      <tr>
        <th colspan="2">Datum</th>
        <th colspan="2">Zeit</th>
        <th colspan="2">Familienname</th>
        <th colspan="2">Vorname</th>
        <th colspan="2">Preis, €</th>
        <th colspan="2">PayOne Reference</th>
        <th colspan="2">Zahlungsarten</th>
      </tr>
      <?php
      foreach ($coincidence as $pay_states) {
        foreach ($pay_states as $pay_state) {
          [$pay_item, $rep_item] = [$pay_state['pay_item'], $pay_state['rep_item']];
          ?>
          <tr>
            <?php
            foreach ($pay_item as $key => $value) {
              $rep_out = '<table cellspacing="1">';
              $price   = 0;
              foreach ($rep_item as $item) {
                if ($key == 'price') {
                  $sum_rep += $item[$key];
                  $price   += $item[$key];
                }
                $valItem = $key == 'price' ? number_format($item[$key], 2, ',', '') : $item[$key];
                $rep_out .= '<tr><td>' . $valItem . '</td></tr>';
                $bg      = $value == $item[$key] ? '#95ff95' : '#ffc457';
              }
              if ($key == 'price') {
                $sum_pay += $value;
                $bg      = $value == $price ? '#95ff95' : $bg;
              }
              $rep_out .= '</table>';
              $value   = $key == 'price' ? number_format($value, 2, ',', '') : $value;
              ?>
              <td style="background-color: <?= $bg ?>"><?= $value ?></td>
              <td style="background-color: <?= $bg ?>"><?= $rep_out ?></td>
              <?php
            }
            ?>
          </tr>
          <?php
        }
      }
      ?>
      <tr>
        <td style="font-weight: bold" colspan="8" align="right">Gesamtsumme:</td>
        <td style="font-weight: bold"><?= number_format($sum_pay, 2, ',', '') ?></td>
        <td style="font-weight: bold"><?= number_format($sum_rep, 2, ',', '') ?></td>
        <td style="font-weight: bold" colspan="4">Unterschied: <?= number_format(
            $sum_pay - $sum_rep,
            2,
            ',',
            ''
          ) ?></td>
      </tr>
    </table>
    <?php
    $out .= ob_get_contents();
    ob_end_clean();

    return $out;
  }

  protected function formCompareReservationTmp()
  {
    for ($i = 1; $i <= 31; $i++) {
      $days[sprintf("%02d", $i)] = sprintf("%02d", $i);
    }
    $tb = &getThemeBuilder();
    ob_start();
    ?>
    <form action="" method="get" target="_blank">
      <input type="hidden">
      <table cellspacing="1" cellpadding="3" class="main" border="0">
        <tr>
          <th colspan="2">unvollständige Zahlung</th>
        </tr>
        <tr>
          <td class="light">Von:</td>
          <td class="light">
            <table cellspacing="0" cellpadding="0" border="0">
              <tr>
                <td>
                  <select name="start_year">
                    <?php for ($i = date('Y') - 1; $i <= date('Y'); $i++) { ?>
                      <option value="<?= $i ?>" <?= ($i == date('Y') ? ' selected' : '') ?>><?= $i ?></option>
                    <?php } ?>
                  </select>
                </td>
                <td>
                  <select name="start_month">
                    <?php for ($i = 1; $i <= 12; $i++) { ?>
                      <option value="<?= sprintf("%02d", $i) ?>" <?= ($i == date('m') ? ' selected'
                        : '') ?>><?= sprintf("%02d", $i) ?></option>
                    <?php } ?>
                  </select>
                </td>
                <td>
                  <?= $tb->select('start_day', $days, date('d')) ?>
                </td>
              </tr>
            </table>
          </td>
        <tr>
          <td class="light">Bis:</td>
          <td class="light">
            <table cellspacing="0" cellpadding="0" border="0">
              <tr>
                <td>
                  <select name="end_year">
                    <?php for ($i = date('Y') - 1; $i <= date('Y'); $i++) { ?>
                      <option value="<?= $i ?>" <?= ($i == date('Y') ? ' selected' : '') ?>><?= $i ?></option>
                    <?php } ?>
                  </select>
                </td>
                <td>
                  <select name="end_month">
                    <?php for ($i = 1; $i <= 12; $i++) { ?>
                      <option value="<?= sprintf("%02d", $i) ?>" <?= ($i == date('m') ? ' selected'
                        : '') ?>><?= sprintf("%02d", $i) ?></option>
                    <?php } ?>
                  </select>
                </td>
                <td>
                  <?= $tb->select('end_day', $days, date('d')) ?>
                </td>
              </tr>
            </table>
        </tr>
        <tr>
          <td class="dark" colspan="2" align="center"><input type="submit" value="Ausf&uuml;hren" class="button"/>
          </td>
        </tr>
      </table>
    </form>
    <?php
    return ob_get_clean();
  }

  public function addSum(&$sum_rep, &$sum_pay, &$sumPayOne, &$sumRep, &$sumPriceDef)
  {
    $sumPayOne   += $sum_pay;
    $sumRep      += $sum_rep;
    $sumPriceDef += $sum_pay - $sum_rep;
    $sum_pay     = 0;
    $sum_rep     = 0;
  }

}

$a                = new compare_report();
$_page['content'] = $a->start();
$_page['key']     = 'compare_report';

require '_bottom.php';
?>