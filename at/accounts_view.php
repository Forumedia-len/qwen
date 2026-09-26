<?php

use AC\app\entities\enums\AccountType;
use AC\app\entities\enums\AccountViewType;
use AC\core\engines\AccountsEngine;
use AC\core\engines\AreasEngine;
use AC\core\engines\Engines;
use AC\core\modules\config\models\ConfigModel;
use AC\core\modules\mailing\entities\enums\ModeTemplate;
use AC\core\modules\payment\services\OnlineGatewayService;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\StringHelper;
use AC\core\system\helpers\TimeHelper;
use AC\core\system\helpers\TranslateHelper;
use setasign\Fpdi\Tcpdf\Fpdi;

//useFile('classes/pdf/config/lang/ger.php');
useFile('classes/pdf/tcpdf.php');
//useFile('classes/pdf/fpdi/fpdi.php');
useFile('classes/pdf/fpdi/autoload.php ');

// Создаем шапку и подвал сами
class MYPDF extends Fpdi
{
  public $_tplIdx;
  public $leftMarging;

  //Page header
  public function Header()
  {
    $templateFile = Service::autoloader()->getPathFile(paths()->getAssetsDir('files/main_template.pdf'), 'pdf');
    if ($templateFile) {
      if (null === $this->_tplIdx) {
        $this->setSourceFile($templateFile);
        $this->_tplIdx = $this->importPage(1);
      }
      $this->useTemplate($this->_tplIdx);
    }
  }

  // Page footer
  public function Footer()
  {
    $footer = Service::configDB('account', 'account_view_footer');
    $this->SetY(10);
    // Set font
    $this->SetFont('helvetica', '', 9);
    $this->SetTextColor(0, 0, 0);
    if (!empty($footer)) {
      $this->MultiCell(
        190,
        0,
        $footer,
        false,
        'L',
        false,
        1,
        $this->leftMarging,
        270,
        true,
        0,
        true
      );
    }
  }
}


//Формируем сам счет


$confirmation_delete = false;
$account_type        = (int)Service::request()->_('account_type');
$action              = Service::request()->_('action', 'print');
$accounts_id         = Service::request()->_('account', false);
$account_delete      = Service::request()->_('account_delete', null);
if ($account_delete !== null) {
  $accounts_id[]       = (int)$account_delete;
  $confirmation_delete = true;
}
if ($accounts_id !== false) {
  $accounts_id = (array)$accounts_id;
}
$emails = [];

if ($action == 'sendAll') {
  foreach ($accounts_id as $account_id) {
    $emails[] = mainGeneration((array)$account_id, $account_type, 'send', $confirmation_delete);
  }
} else {
  $emails[] = mainGeneration($accounts_id, $account_type, $action, $confirmation_delete);
}

$out_text = '<div class="message">';

foreach ($emails as $email) {
  if ($email['send'] === false) {
    $out_text .= '<p style="color:red">' . lang(
        'text_email_not_send',
        'accounts_view',
        ['email_number' => '<span style="font-size: 14px">' . $email['number'] . '</span>']
      ) . '</p>';
  } else {
    $out_text .= '<p style="color: green">' . lang(
        'text_email_send',
        'accounts_view',
        [
          'email_number' => '<span style="font-size: 14px">' . $email['number'] . '</span>',
          'email'        => '<span style="font-size: 14px">' . $email['email'] . '</span>',
        ]
      ) . '</p>';
  }
}

$out_text .= '<a href="javascript:window.close()">' . lang('Close', 'accounts_view') . '</a></div>';

//============================================================+
// END OF FILE
//============================================================+


$_page['content'][0] = $out_text;
$_page['key']        = 'accounts_view';
$_page['title']      = 'Beleg';

function mainGeneration($accounts_id, $account_type, $action, $confirmation_delete)
{
  global $l;
  /** @var AccountsEngine $_account */
  $_account = useClass(paths()->enginesDir . 'AccountsEngine', true);
  $r        = new Engines();
  if (is_array($accounts_id)) {
    // create new PDF document
    $pdf              = new MYPDF();
    $pdf->leftMarging = config('accountView')->getLeftMargin();
    $account_config   = ConfigModel::getByType('account');
    // set document information
    $pdf->SetCreator(PDF_CREATOR);
    $pdf->SetAuthor('Forumedia');
    $pdf->SetTitle('account');
    $pdf->SetSubject('Forumedia');
    $pdf->SetKeywords('Forumedia, PDF, active-court');

    // set default header data
//    $pdf->SetHeaderData(PDF_HEADER_LOGO, PDF_HEADER_LOGO_WIDTH, PDF_HEADER_TITLE, PDF_HEADER_STRING);

    // set header and footer fonts
    $pdf->setHeaderFont([PDF_FONT_NAME_MAIN, '', 8]);
    $pdf->setFooterFont([PDF_FONT_NAME_DATA, '', 8]);

    // set default monospaced font
    $pdf->SetDefaultMonospacedFont(PDF_FONT_MONOSPACED);

    //set margins
    $pdf->SetMargins($pdf->leftMarging, 10, 10, false);
    $pdf->SetFooterMargin(0);

    // remove default footer
    $pdf->setPrintHeader(true);

    #$pdf->setPrintFooter(false);
    //set auto page breaks
    $pdf->SetAutoPageBreak(true, ORDER_PAGE_FOOTER);

    //set image scale factor
    $pdf->setImageScale(PDF_IMAGE_SCALE_RATIO);

    //set some language-dependent strings
    $pdf->setLanguageArray($l);
    $pdf->setPageMark();

    // ---------------------------------------------------------

    // set font
    $pdf->SetFont('helvetica', '', 8);
    $footer_text     = "";
    $accountViewType = AccountViewType::tryFrom($account_type);
    $number_prefix   = $accountViewType?->numberPrefix();
    $accounts        = match ($accountViewType) {
      AccountViewType::Individual     => $_account->getAccountsById($accounts_id),
      AccountViewType::Abo,
      AccountViewType::AboFitness,
      AccountViewType::AboLight       => $_account->getAboAccountsById($accounts_id),
      AccountViewType::Special        => $_account->getOtherAccountsById($accounts_id),
      AccountViewType::PrivateAccount => $_account->getPrepaymentAccountsById($accounts_id),
      AccountViewType::MembershipFees => module('membershipFees')
        ->useModel('MembershipFeesAccountModel')
        ->getEngine()
        ->getAccountsById($accounts_id),
      AccountViewType::OnlinePayment  => $_account->getOnlinePaymentAccountsById($accounts_id),
      default                         => false,
    };

    /** @var array|false $accounts */
    if ($accounts) {
      foreach ($accounts as $account_id => $account) {
        $pdf->AddPage();
        $textFields = $account['text_fields'];
        $pdf->SetTopMargin(config('accountView')->marginTop());
        $pdf->SetFont('helvetica', '', 7);
        $pdf->SetTextColor(0, 0, 0);

        $isClientBlockRight = config('accountView')->useShowDataClientRight();
        $clientBlockX = $isClientBlockRight ? 130 : $pdf->leftMarging;
        $companyBlockX = $isClientBlockRight ? $pdf->leftMarging : 130;

        $header = $account_config->account_view_header;
        if (!empty($header)) {
          $pdf->SetFont('helvetica', '', 8);
          $pdf->MultiCell(
            0,
            0,
            $header,
            0,
            'L',
            false,
            1,
            $clientBlockX,
            55
          );
        }

        $pdf->SetFont('helvetica', '', 9);
        $address = $account_config->account_view_address;
        if (!empty($address)) {
          $pdf->writeHTMLCell(0, 0, $companyBlockX, 50, $address, 0, 0, false, true, 'L');
        }

        $pdf->SetFont('helvetica', '', 9);
        $out_user = "\n"
          . ((!empty($account['firm'])) ? ("\n" . $account['firm']) : "")
          . "\n" . $account['name'] . ' ' . $account['surname']
          . "\n" . $account['address']
          . "\n" . $account['post_code'] . ' ' . $account['city'];

        if (config('accountView')->showClientNumberInAddress ?? false) {
          $out_user .= "\n" . 'Mg.Nr.: ' . $account['cl_number'];
        }

        $pdf->MultiCell(
          0,
          0,
          $out_user,
          0,
          'L',
          false,
          1,
          $clientBlockX,
          config('accountView')->verticalPositionClientData()
        );

        if ($textFields && !empty($textFields['checkout_sign'])) {
          $pdf->Ln(1);
          $pdf->writeHTMLCell(
            0,
            0,
            130,
            '',
            '<table align="left" border="0" cellpadding="0" cellspacing="0" style="width:275px"><tr><td><b>' . $_account->getDataAccountTextFields(
              'checkout_sign',
              'title'
            ) . "</b>:</td><td>" . $textFields['checkout_sign'] . '</td></tr></table>'
          );
        }

        $pdf->Ln(config('accountView')->getSpaceBeforeMainText());

        $city = $account_config->account_view_city;
        $out  = '';
        if ($confirmation_delete) {
          if ($ca = $_account->getConfirmationDeleteAccount($account_id)) {
            $out = '<table><tr><td>';
            $out .= '<h1>' . lang('REGISTER', 'accounts_view') . ' ' . config('accountView')->getNumberAccount($ca['account_id'],
                STORNO_ACCOUNT_NUMBER) . '</h1><h2>' . lang('To the invoice',
                'accounts_view') . ' ' . config('accountView')->getNumberAccount($account['a_number'], $number_prefix) . '</h2>';
            $out .= '</td><td>';
            $out .= (!empty($city) ? $city . ', ' : '') . ' ' . date(
                'd.m.Y',
                strtotime($ca['date'])
              );
            $out .= '</td></tr></table>';
          }
        } else {
          if (isset($account['a_date_start'])) {
            $unixdate = strtotime($account['a_date_start']);
          } else {
            $unixdate = strtotime($account['date_start']);
          }
          $out = '<table><tr><td align="left">';
          $out .= '<h1>' . lang('INVOICE', 'accounts_view') . ' ' . config('accountView')->getNumberAccount($account['a_number'],
              $number_prefix) . '</h1>';
          $out .= '</td><td>' . (!empty($city) ? $city . ', ' : '');
          $out .= match ($accountViewType) {
            AccountViewType::Individual => defined('USE_DATE_RESERVATION_ACCOUNT_BY_CREATED') && USE_DATE_RESERVATION_ACCOUNT_BY_CREATED
              ? date('d.m.Y', $unixdate)
              : date(
                'd.m.Y',
                mktime(
                  0,
                  0,
                  0,
                  date('m', $unixdate),
                  (defined('ADMIN_FIRST_DATE_RESERVATION_ACCOUNT') && ADMIN_FIRST_DATE_RESERVATION_ACCOUNT) ? 1 : date('t', $unixdate),
                  date('Y', $unixdate)
                )
              ),
            default                     => date('d.m.Y', $unixdate),
          };

          $out .= '</td></tr></table>';
        }
        if (!(config('accountView')->showClientNumberInAddress ?? false) && !empty($account['cl_number'])) {
          $out .= "\n" . 'Mitglieds Nr.: ' . $account['cl_number'];
        }

        $pdf->writeHTML($out, true, false, 20, false, 'R');

        //расписываем таблицу заказов
        if (is_array($account['reservations'])) {
          $out = lang('text before table', 'accounts_view');
          switch ($accountViewType) {
            case AccountViewType::Individual:
              $out .= getAccountTableData($account['reservations'], (int)$account['nds']);
              break;
            case AccountViewType::Abo:
            case AccountViewType::AboFitness:
              $out .= getAboAccountTableData(
                $account['reservations'],
                (int)$account['nds'],
                $account['price_info'],
                $account['count_game'],
                $account['abo_sum'],
                ($account['info'] ?? false)
              );
              break;
            case AccountViewType::AboLight:
              $out .= getLightAboAccountTableData($account['light_reservations'], (int)$account['nds']);
              break;
            case AccountViewType::Special:
              $out .= getOtherAccountTableData($account['reservations'], (int)$account['nds']);
              break;
            case AccountViewType::PrivateAccount:
              $out .= getPrepaymentAccountTableData(
                $account['reservations'],
                (int)$account['nds'],
                $account['price_info']
              );
              break;
            case AccountViewType::MembershipFees:
              // @todo перенести в отдельный класс
              $out .= module('membershipFees')->useModel('MembershipFeesAccountModel')->getAccountTableData(
                $account['reservations'],
                (int)$account['nds']
              );
              break;
            case AccountViewType::OnlinePayment:
              $out .= getAccountTableData($account['reservations'], (int)$account['nds']);
              break;
            default:
              $out .= '<p><string>' . lang('Error during data transmission', 'accounts_view') . '</string></p>';
              break;
          }

          $pdf->writeHTML($out, true, false, 20, false, 'L');
        }
        $pdf->setLeftMargin($pdf->leftMarging);
        $out = '';
        if ($account_config->account_view_bank_view) {
          $out = '<p style="font-size:10pt;">
			<strong>' . lang('Your bank details', 'accounts_view') . ':</strong><br />
			IBAN: ' . StringHelper::shield(StringHelper::mask($account['sepa_iban'], 4, 2, 'X')) . '<br />
			BIC: ' . StringHelper::shield($account['sepa_bic']) . '<br />
			Bank: ' . StringHelper::shield($account['bank_name']) . '<br />
			SEPA-Referenz: ' . StringHelper::shield($account['bank_sepa_referenz']) . '
			</p>';
        }
        if ($account_config->account_view_mail_view) {
          $email = StringHelper::shield(empty($account['sys_email']) ? $account['email'] : $account['sys_email']);
          $out   .= '<p style="font-size:9pt;">E-Mail: <a href="mailto:' . $email . '">' . $email . '</a></p>';
        }
        $pdf->writeHTML($out, true, false, 20, false, 'L');

        $footer_text = '';
        $pdf->SetFont('helvetica', '', 10);
        if ($text = $_account->getTextBlock($account['text_config'])) {
          $footer_text .= '<p>&nbsp;</p>' . $text['footer'];
        }
        $type = $accountViewType?->accountType();
        if ($type !== null) {
          $footer_text_by_type = lang($type->alias() . '_invoices', 'account_pdf_template_footer_text');
          if ($type === AccountType::PrivateAccount) {
            $footer_text_by_type = match (true) {
              str_starts_with((string)($account['price_info'] ?? ''), 'paypal')        => '<p>' . lang('Payment via PayPal',
                  'accounts_view') . '</p>',
              defined('SHOW_PREPAYMENT_ACCOUNT_TEXT') && !SHOW_PREPAYMENT_ACCOUNT_TEXT => '',
              default                                                                  => $footer_text_by_type
            };
          } elseif ($type === AccountType::OnlinePayment) {
            $footer_text_by_type = getOnlinePaymentTransactionInfo($account['price_info'] ?? null);
          }
          $footer_text .= !empty($footer_text_by_type) ? '<p>&nbsp;</p>' . $footer_text_by_type : '';
        }
        $out = (isset($footer_text) ? $footer_text : '');
        $out .= config('accountView')->additionalTextFooter();

        $pdf->writeHTML($out, true, false, 20, false, 'L');
      }
    }
  }

// reset pointer to the last page
  $pdf->lastPage();

// ---------------------------------------------------------
//Close and output PDF document
  switch ($action) {
    case 'send':
      $file_path = paths()->getTmpFilesDir() . str_replace(['\\', '/'], '|', $r->config['account_attachment']) . '_' . $account_id . '.pdf';
      $pdf->Output($file_path, 'F');
      //непосредственно отправка письма
      $send_error = true;
      if ($accounts) {
        reset($accounts);
        $account_data = current($accounts);
        $email        = empty($account_data['sys_email']) ? $account_data['email'] : $account_data['sys_email'];

        $mailer = Service::mailer();
        $mailer->isHTML(true);
        $mailer->addAttachment($file_path, ($r->config['account_attachment'] ?: 'account') . '.pdf');
        if ($mailer->dispatch($email, ModeTemplate::SERVICE->value, 'account_file_send')) {
          foreach (explode(',', $r->config['mail_for_duplicate_account']) as $emailDuplicate) {
            $mailer->dispatch(trim($emailDuplicate), ModeTemplate::SERVICE->value, 'account_file_send');
          }
          $_account->markSend($accounts_id);

          $out_text   = [
            'send'   => true,
            'email'  => $email,
            'number' => config('accountView')->getNumberAccount($account_data['a_number'], $number_prefix),
          ];
          $send_error = false;
        }
      }

      if ($send_error) {
        $out_text = [
          'send'   => false,
          'email'  => $email,
          'number' => config('accountView')->getNumberAccount($account_data['a_number'], $number_prefix),
        ];
      }
      unlink($file_path);
      break;
    default:
      $pdf->Output('generate_rechnung.pdf', 'I');
  }

  return $out_text;
}

function getShowLight($type)
{
  $e_a = new AreasEngine('');
  $e_a->getAllAreasData($areas);
  $ret = ['light_on' => false, 'heating_on' => false, 'net_on' => false];
  foreach ($areas as $area) {
    if (($type == $area['type_id']) && (true == $area['light_on'])) {
      $ret['light_on'] = true;
    }
    if (($type == $area['type_id']) && (true == $area['heating_on'])) {
      $ret['heating_on'] = true;
    }
    if (($type == $area['type_id']) && (true == $area['net_on'])) {
      $ret['net_on'] = true;
    }
  }

  return $ret;
}

//расписываем таблицу заказов
function getAccountTableData($reservations, $nds)
{
  $type     = Service::request()->_('type', 1);
  $flgLight = getShowLight($type);
  $out      = '<table border="1" bordercolor="black" cellpadding="2">' . "\n";
  $out      .= '<tr>' . "\n";
  $out      .= '<th width="15%"><b>' . lang('Type') . '</b></th>' . "\n";
  $out      .= '<th width="15%"><b>' . lang('Place') . '</b></th>' . "\n";
  $out      .= '<th width="' . (($type == 2) ? 30 : 50) . '%"><b>' . lang('Date') . '/' . lang('Playtime_order', 'accounts_view') . '</b></th>' . "\n";
  if ($type == 2) {
    $out .= '<th width="20%"><b>' . lang('Guest') . '</b></th>' . "\n";
  }
  $out .= '<th width="' . (($flgLight['light_on'] && !DONOT_SHOW_LIGHT) ? 10 : 20) . '%" align="right"><b>' . lang('Amount') . '</b></th>' . "\n";
  if ($flgLight['light_on'] && !DONOT_SHOW_LIGHT) {
    $out .= '<th width="10%" align="right"><b>' . lang('Light') . '</b> </th>' . "\n";
  }
  //$out .= '<th width="10%">Heizung</th>'."\n";
  $out .= '</tr>' . "\n";
  $sum = $sum_heating = $sum_light = 0;
  foreach ($reservations as $a) {
    if ($a['price'] > 0 || $a['light_price'] > 0 || $a['heating_price'] > 0 || $a['net_price'] > 0
      || (config('accountView')->viewNullPrice ?? false)
    ) {
      $out      .= '<tr>' . "\n";
      $sarr     = explode(' - ', $a['type']);
      $sport    = !empty($sarr[1]) ? $sarr[1] : $sarr[0];
      $str_type = (defined('SHOW_ONLY_SPORT') && SHOW_ONLY_SPORT) ? ($sport) : ($a['type']);
      $out      .= '<td width="15%">' . $str_type . '</td>' . "\n";
      $out      .= '<td width="15%">' . $a['area'] . '</td>' . "\n";
      $out      .= '<td width="' . (($type == 2) ? 30 : 50) . '%">' . date('d.m.Y', strtotime($a['date'])) . ' ' . date(
          'H:i',
          strtotime($a['time_start'])
        ) . '-' . date('H:i', strtotime($a['time_finish']))
        . (defined('VIEW_COMMENT_SCHEDULE') && VIEW_COMMENT_SCHEDULE && !empty($a['comment']) ? '<br>' . $a['comment'] : '')
        . '</td>' . "\n";
      $friends  = '';
      if (empty($a['street_friends']) && !empty($a['second'])) {
        $friends = ucwords($a['second']['second_name'] . ' ' . $a['second']['second_surname']);
      }
      if (!empty($a['street_friends'])) {
        foreach ($a['street_friends'] as $friend) {
          if (!empty($friend)) {
            $friends .= $friend['name'] . ', ';
          }
        }
        $friends = trim($friends, ', ');
      }

      if ($type == 2) {
        $out .= '<td width="20%" align="left">' . $friends . '</td>' . "\n";
      }
      $out .= '<td width="' . (($flgLight['light_on'] && !DONOT_SHOW_LIGHT) ? 10 : 20) . '%" align="right">' . number_format(
          $a['price'],
          2,
          ',',
          ' '
        ) . CURR_VALUTE . '</td>' . "\n";
      if (($flgLight['light_on'] && !DONOT_SHOW_LIGHT)) {
        $out .= '<td width="10%" align="right">' . number_format(
            $a['light_price'],
            2,
            ',',
            ' '
          ) . CURR_VALUTE . '</td>' . "\n";
      }
      //$out .= '<td  width="10%" align="right">'.number_format($a['heating_price'], 2, ',', ' ').'' . CURR_VALUTE . '</td>'."\n";
      $out         .= '</tr>' . "\n";
      $sum         += $a['price'];
      $sum_light   += $a['light_price'];
      $sum_heating += $a['heating_price'];
    }
  }
  $out .= '<tr>' . "\n";
  $out .= '<td width="80%" colspan="' . (($flgLight['light_on'] && !DONOT_SHOW_LIGHT) ? 4 : 3) . '" ></td>' . "\n";
  $out .= '<td width="' . (($flgLight['light_on'] && !DONOT_SHOW_LIGHT) ? 10 : 20) . '%" align="right" ><strong>' . number_format(
      $sum,
      2,
      ',',
      ' '
    ) . CURR_VALUTE . '</strong></td>' . "\n";
  if ($flgLight['light_on'] && !DONOT_SHOW_LIGHT) {
    $out .= '<td width="10%" align="right" class="noBorder"><strong>' . number_format(
        $sum_light,
        2,
        ',',
        ' '
      ) . CURR_VALUTE . '</strong></td>' . "\n";
  }
  $out .= '</tr>' . "\n";
  $sum += $sum_light;
  $sum += $sum_heating;
  $out .= '</table>' . "\n";
  $out .= '<p>&nbsp;</p>' . "\n";
  $sum = round($sum, 2);
  $out .= getSumInfo($sum, $nds);

  return $out;
}

//расписываем таблицу заказов (основной)
function getAboAccountTableData($reservations, $nds, $price_info, $count_game, $abo_sum, $info = false)
{
  $e_a = new AreasEngine('');
  $out = '<table border="1" bordercolor="black" cellpadding="2">' . "\n";
  $out .= '<tr>' . "\n";
  $out .= '<th width="5%" align="center">' . lang('Pos') . '</th>' . "\n";
  $out .= '<th width="20%" align="center">' . lang('Sp.place', 'accounts_view') . '</th>' . "\n";
  $out .= '<th width="45%" align="center">' . lang('Time in hours') . '</th>' . "\n";
  if (!ABO_ORDER_HIDE_PRICE) {
    $out .= '<th width="10%" align="center">' . lang('St price', 'accounts_view') . '</th>' . "\n";
    $out .= '<th width="10%" align="center">' . lang('Number', 'accounts_view') . '</th>' . "\n";
    $out .= '<th width="10%" align="center">' . lang('Sum') . '</th>' . "\n";
  }
  $out .= '</tr>' . "\n";

  $sum = $periodSum = 0;
  $i   = 0;


  if (!empty($count_game)) {
    $count_game = unserialize($count_game, ['allowed_classes' => false]);
  }
  if (!empty($price_info)) {
    $price_info = unserialize($price_info, ['allowed_classes' => false]);
  }
  foreach ($reservations as $a) {
    $i++;
    $out .= '<tr>' . "\n";
    $out .= '<td align="center" width="5%" >' . $i . '</td>' . "\n";
    $out .= '<td align="center" width="20%">' . $a['type'] . ' - ' . $a['area'] . "</td>\n";
    $out .= '<td width="45%">';
    if (is_array($a['periods'])) {
      $cg_out = $pr_out = $tot_out = '';
      foreach ($a['periods'] as $p) {
        $outPeriods = [];
        $out        .= '&nbsp;<strong>' . date('d.m.Y', strtotime($p['date_start'])) . ' - ' . date(
            'd.m.Y',
            strtotime($p['date_finish'])
          ) . "</strong><br />\n";
        $cg_out     .= '&nbsp;<br />';
        $pr_out     .= '&nbsp;<br />';
        $tot_out    .= '&nbsp;<br />';
        $periodSum  += $p['price'];
        if (is_array($count_game)) {
          if (isset($count_game[$p['date_start']])) {
            foreach ($count_game[$p['date_start']] as $weekday => $tmp_count_game) {
              foreach ($tmp_count_game as $month => $times) {
                $season = $e_a->getSeasonById(is_numeric($month) ? $e_a->getPeriodByMonth($month) : $e_a->getPeriodByDate($month));
                while ($count = current($times)) {
                  $pr_out_val  = 0;
                  $tot_out_val = 0;
                  if (is_array($price_info)) {
                    $time = key($times);

                    $tmp_price     = 0;
                    $current_price = isset($price_info[$weekday][$month]) ? $price_info[$weekday][$month] : $price_info[$weekday];
                    if (isset($current_price[$time])) {
                      $current_price_time = $current_price[$time];
                      //Цена
                      $tmp_price = isset($current_price_time['net_price'])
                        ? $current_price_time['net_price']
                        : $current_price_time['price'];
                      if (!is_float($current_price_time['price']) || isset($current_price_time['net_price'])) {
                        //Скидка для абонемента
                        if ($current_price_time['discount_ticket_dimension'] == 1) {
                          $ticket_discount = $tmp_price / 100 * $current_price_time['discount_ticket'];
                        } else {
                          $ticket_discount = $current_price_time['discount_ticket'];
                        }

                        //Цена с учетом скидки абонемента
                        $tmp_price = $tmp_price - $ticket_discount;

                        //Цена с учетом наценки для не участников клуба
                        $tmp_price = $tmp_price + $current_price_time['extra'];

                        //Скидка для клиента
                        if ($current_price_time['discount_client_dimension'] == 1) {
                          $client_discount = $tmp_price / 100 * $current_price_time['discount_client'];
                        } else {
                          $client_discount = $current_price_time['discount_client'];
                        }
                        //Цена с учетом скидки клиента
                        $tmp_price = round($tmp_price - $client_discount, 2);
                      }
                    }
                    $pr_out_val  = $tmp_price;
                    $tot_out_val = $tmp_price * $count;
                    $sum         += $tot_out_val;
                  }

                  $str_ind = ((defined('ACCOUNTS_TYPE_VIEW_ABO') && (ACCOUNTS_TYPE_VIEW_ABO) && (strpos($month, '-'))) ? (date(
                        'd.m.Y',
                        strtotime($month)
                      ) . ' ') : '')
                    . TimeHelper::convertTime24($time, false) . ' - ';
                  next($times);
                  $time    = (array_key_exists(key($times), $times)
                    ? key($times)
                    : $a['time_finish']);
                  $str_ind .= TimeHelper::convertTime24($time, false);
                  if (!isset($outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['pr_out'])) {
                    $outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['pr_out'] = 0;
                  }
                  if (!isset($outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['tot_out'])) {
                    $outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['tot_out'] = 0;
                  }
                  if (!isset($outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['cg_out'])) {
                    $outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['cg_out'] = 0;
                  }
                  $outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['pr_out']  = $pr_out_val;
                  $outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['tot_out'] += $tot_out_val;
                  $outPeriods[$season['title']][TranslateHelper::translateWeekday($weekday, true)][$str_ind]['cg_out']  += $count;

                  $time = (array_key_exists(key($times), $times) ? key($times) : $a['time_finish']);
                }
              }
            }
          }
        }
        $out .= '<table width="100%">';
        foreach ($outPeriods as $season_title => $weekdays) {
          $ch_season = $ch_week = '';
          foreach ($weekdays as $week_title => $timess) {
            foreach ($timess as $time_title => $item) {
              $r_season = count($weekdays) * count($timess);
              $r_week   = count($timess);
              $out      .= '<tr>';
              $out      .= '<td width="7">&nbsp;</td>';
              $out      .= (count($outPeriods) > 1 ? ($ch_season !== $season_title
                ? '<td width="100" rowspan="' . $r_season . '">' . $season_title . '</td>' : '') : '');
              $out      .= $ch_week !== $week_title ? '<td width="40" align="center" rowspan="' . $r_week . '">' . $week_title . '</td>' : '';
              $out      .= '<td width="150">' . $time_title . '</td>';
              $out      .= '</tr>';

              $pr_out_txt  = ($abo_sum == 0) ? NumberHelper::valute($item['pr_out']) : "&nbsp;";
              $tot_out_txt = ($abo_sum == 0) ? NumberHelper::valute($item['tot_out']) : "&nbsp;";

              $pr_out  .= '<tr><td>' . ($pr_out_txt) . '</td></tr>' . "\n";
              $tot_out .= '<tr><td>' . ($tot_out_txt) . '</td></tr>' . "\n";
              $cg_out  .= '<tr><td>' . $item['cg_out'] . '</td></tr>' . "\n";

              $ch_season = $season_title;
              $ch_week   = $week_title;
            }
          }
        }
        $out .= '</table>' . "\n";
      }
    }
    if ($abo_sum > 0) {
      $sum = $abo_sum;
    }
    if ($sum == 0) {
      $sum = $periodSum;
    }
    $out .= (isset($info) ? '<b>' . $info . '</b>' : '') . "\n";
    $out .= '</td>' . "\n";
    if (!ABO_ORDER_HIDE_PRICE) {
      $out .= '<td align="center" width="10%">' . (count($outPeriods) == 0 ? $periodSum : $pr_out) . '</td>
             <td align="center" width="10%">' . (count($outPeriods) == 0 ? 1 : $cg_out) . '</td>' . "\n";
      $out .= '<td align="center" width="10%">' . (count($outPeriods) == 0 ? $periodSum : $tot_out) . '</td>' . "\n";
    }
    $out .= '</tr>' . "\n";
  }
  $out .= '</table>' . "\n";
  $out .= '<p>&nbsp;</p>' . "\n";
  $out .= getSumInfo($sum, $nds);

  return $out;
}

//расписываем таблицу заказов
function getLightAboAccountTableData($reservations, $nds)
{
  $out = '<table  border="1" bordercolor="black" cellpadding="2">' . "\n";
  $out .= '<tr>' . "\n";
  $out .= '<th width="10%">' . lang('Pos') . '</th>' . "\n";
  $out .= '<th width="30%">' . lang('Sp.place', 'accounts_view') . '</th>' . "\n";
  $out .= '<th width="10%">' . lang('Options') . '</th>' . "\n";
  $out .= '<th width="30%">' . lang('Time in hours') . '</th>' . "\n";
  $out .= '<th width="20%">' . lang('Sum') . '</th>' . "\n";
  $out .= '</tr>' . "\n";

  $sum = 0;
  $i   = 0;
  foreach ($reservations as $a) {
    $i++;
    $out .= '<tr>' . "\n";
    $out .= '<td width="10%" align="center">' . $i . '</td>' . "\n";
    $out .= '<td width="30%">' . $a['area_type'] . ' ' . $a['area'] . "</td>\n";
    $out .= '<td width="10%">' . ($a['type'] == 1 ? 'Licht' : 'Heizung') . "</td>\n";
    $out .= '<td width="30%">' . date('d.m.Y H:i', strtotime($a['date_start'] . ' ' . $a['time_start'])) . '</td>';
    $out .= '<td width="20%" style="text-align:right">' . NumberHelper::valute($a['price']) . '</td>';
    $out .= '</tr>' . "\n";
    $sum += $a['price'];
  }
  $out .= '</table>' . "\n";
  $out .= '<p>&nbsp;</p>' . "\n";

  $sum = round($sum, 2);
  $out .= getSumInfo($sum, $nds);

  return $out;
}


//расписываем таблицу заказов
function getOtherAccountTableData($reservations, $nds)
{
  $out = '<table border="1" bordercolor="black" cellpadding="2">' . "\n";
  $out .= '<tr>' . "\n";
  $out .= '<th width="10%">' . lang('Position') . '</th>' . "\n";
  $out .= '<th width="50%">' . lang('Position', 'accounts_view') . '</th>' . "\n";
  $out .= '<th width="10%">' . lang('Quantity', 'accounts_view') . '</th>' . "\n";
  $out .= '<th width="15%">' . lang('Unit price', 'accounts_view') . '</th>' . "\n";
  $out .= '<th width="15%">' . lang('Total price') . '</th>' . "\n";
  $out .= '</tr>' . "\n";

  $sum = 0;
  $i   = 0;
  foreach ($reservations as $a) {
    $i++;
    $out .= '<tr>' . "\n";
    $out .= '<td width="10%" align="center">' . $i . '</td>' . "\n";
    $out .= '<td width="50%">' . $a['title'] . '</td>' . "\n";
    $out .= '<td width="10%" align="center">' . str_replace('.', ',', $a['count']) . '</td>' . "\n";
    $out .= '<td width="15%" align="right">' . NumberHelper::valute($a['price']) . '</td>' . "\n";
    $out .= '<td width="15%" align="right">' . NumberHelper::valute(($a['price'] * $a['count'])) . '</td>' . "\n";
    $out .= '</tr>' . "\n";
    $sum += ($a['price'] * $a['count']);
  }
  $out .= '</table>' . "\n";
  $out .= '<p>&nbsp;</p>' . "\n";

  $sum = round($sum, 2);
  $out .= getSumInfo($sum, $nds);

  return $out;
}

//расписываем таблицу заказов
function getPrepaymentAccountTableData($reservations, $nds, $price_info)
{
  $out = '<table border="1" bordercolor="black" cellpadding="2">' . "\n";
  $out .= '<tr>' . "\n";
  $out .= '<th width="50%">' . lang('Position') . '</th>' . "\n";
  $out .= '<th width="50%">' . lang('Invoice amount', 'accounts_view') . '</th>' . "\n";
  $out .= '</tr>' . "\n";

  $sum = 0;
  foreach ($reservations as $a) {
    $out .= '<tr>' . "\n";
    $out .= '<td width="50%" align="left">' . lang(
        'Credit Account Deposit - Please check if the amount has been replenished according to our offers.',
        'accounts_view'
      ) . '</td>' . "\n";
    $out .= '<td width="50%" align="right">' . NumberHelper::valute($a['price']) . '</td>' . "\n";
    $out .= '</tr>' . "\n";
    $sum += $a['price'];
  }
  $out .= '</table>' . "\n";
  $out .= '<p>&nbsp;</p>' . "\n";
  $out .= getSumInfo($sum, $nds);

  return $out;
}

/**
 * Сформировать блок способа оплаты и транзакции для онлайн-счёта.
 */
function getOnlinePaymentTransactionInfo(?string $priceInfo): string
{
  $paymentData = OnlineGatewayService::parseOnlinePaymentInvoicePriceInfo($priceInfo);
  if ($paymentData === null) {
    return '';
  }

  $isPayone         = $paymentData['gateway'] === 'payone';
  $gatewayTitle     = $isPayone
    ? lang('Payment via Payone', 'accounts_view')
    : lang('Payment via PayPal', 'accounts_view');
  $transactionTitle = $isPayone
    ? lang('Payone transaction ID', 'accounts_view')
    : lang('PayPal transaction ID', 'accounts_view');
  $out              = '<p>' . $gatewayTitle;
  if ($isPayone) {
    $out .= '<br />' . lang('Payone reference', 'accounts_view') . ': '
      . StringHelper::shield($paymentData['reference']);
  }
  $out .= '<br />' . $transactionTitle . ': ' . StringHelper::shield($paymentData['transaction_id']);
  if ($paymentData['payment_type'] !== null) {
    $out .= '<br />' . lang('Payment method', 'accounts_view') . ': '
      . StringHelper::shield($paymentData['payment_type']);
  }

  return $out . '</p>';
}

function getSumInfo($sum, $nds)
{
  $out = '<table border="0"><tr><td width="56%">&nbsp;</td><td width="44%">';
  $out .= '<table border="0" width="280">';
  if ((int)Service::configDB('account', 'account_view_nds_view')) {
    $nds_sum = $sum - ($sum / (1 + $nds / 100));
    $out     .= '<tr><td>' . lang('Invoice amount net', 'accounts_view') . '</td><td align="right">'
      . NumberHelper::valute(($sum - $nds_sum)) . '</td></tr>';
    $out     .= '<tr><td>' . lang('VAT', 'accounts_view') . ' ' . $nds . '%</td><td align="right">'
      . (NumberHelper::valute($nds_sum)) . '</td></tr>';
  }
  $out .= '<tr><td><strong>' . lang('Invoice amount', 'accounts_view') . '</strong></td><td align="right"><strong>'
    . NumberHelper::valute($sum) . '</strong></td></tr>';
  $out .= '</table>';
  $out .= '</td></tr></table>';

  return $out;
}
