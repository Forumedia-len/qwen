<?


use AC\core\engines\Engines;
use AC\core\system\helpers\NumberHelper;


class coupon_admin
{
  /**
   * @var Engines
   */
  public $engine;

  function start()
  {
    $this->engine = new Engines();

    switch (Service::request()->_('action')) {
      case 'insert':
        return $this->insert();
        break;
      case 'edit':
        return $this->edit();
        break;
      case 'editCode':
        return $this->editCode();
        break;
      case 'change':
        return $this->change();
        break;
      case 'remove':
        return $this->remove();
      case 'download':
        return $this->download();
        break;
      default:
        return $this->getList();
    }
  }

  //удалить купон
  function remove()
  {
    $auth = Service::auth();

    if ($user = $auth->getUserData()) {
      if ($user['rights'] == 1) {
        $this->engine->coupons->removeCoupon((int)$_GET['coupon_id']);
      } else {
        $this->error = lang('error could not delete the coupon', 'message_error');
      }
    }

    return $this->getList();
  }

  //добавить новость
  function insert()
  {
    $this->engine->coupons->insertCoupon($_POST['title'], NumberHelper::float($_POST['price']),
      (int)$_POST['status']);

    return $this->getList();
  }

  //изменить новость
  function change()
  {
    $this->engine->coupons->changeCoupon((int)$_GET['coupon_id'], $_POST['title'], (int)$_POST['status']);

    return $this->getList();
  }

  //форма редактирования
  function edit()
  {
    //если новости нет - показываем список
    if ($this->engine->coupons->getCoupon((int)$_GET['coupon_id'], $item)) {
      //форма редактирования
      $out = $this->getFields(1, $item);

      return array($out, '<span class="back"><a href="coupon.php">'.lang('Back').'</a></span>');
    } else {
      return $this->getList();
    };
  }

  //форма редактирования
  function editCode()
  {
    if ($this->engine->coupons->getCoupon((int)$_GET['coupon_id'], $item)) {
      if ($codes = $this->engine->coupons->getCouponsCodeList($item['coupon_id'])) {
        $out      = '<form action="coupon.php?action=download" method="post">' . "\n";
        $out      .= '<input type="hidden" name="coupon_id" value="' . $item['coupon_id'] . '" />' . "\n";
        $out      .= '<table cellspacing="1" cellpadding="3" class="main" border="0" align="center">' . "\n";
        $out      .= '	<tr><th colspan="3">'.lang('Export CSV', 'coupon').'</th></tr>' . "\n";
        $out      .= '	<tr><td class="light">'.lang('Type').':</td><td class="light">' . "\n";
        $out      .= '<select name="type">
							<option value="0">'.lang('All').'</option>
							<option value="1">'.lang('active').'</option>
							<option value="2">'.lang('inactive').'</option>
						</select>';
        $out      .= "	</td><tr>\n";
        $out      .= '	<tr><td class="dark" colspan="2" align="center"><input type="submit" value="'.lang('Execute').'" class="button"/></td></tr>' . "\n";
        $out      .= '</table></form>' . "\n";
        $output[] = $out;

        $out = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
        $out .= '<tr><th>'.lang('Code').'</th><th>'.lang('Client').'</th><th>'.lang('Date').'</th></tr>' . "\n";
        foreach ($codes as $item) {
          $out .= '<tr>' .
            '<td class="light"><span class="' . ($item['status'] == 0 ? 'green" ' : 'red"') . '>' . $item['code'] . '</span></td>' .
            '<td class="dark">' . ($item['status'] == 0 ? 'Nein' : $item['client_name'] . ' ' . $item['client_surname']) . '</td>' .
            '<td class="light">' . ($item['status'] == 0 ? '' : date('d.m.Y',
              strtotime($item['actived_date']))) . '</td>';
          $out .= '</tr>' . "\n";
        }
        $out .= "</table>\n";
      } else {
        $out = '<form action="#" method="get">' . "\n";
        $out .= '<script>';
        $out .= 'cp = new coupons(\'get_ajax_data.php?action=generateCouponCode&coupon_id=' . $item['coupon_id'] . '\')';
        $out .= '</script>';
        $out .= '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">';
        $out .= '<tr><th colspan="2">'.lang('Entry', 'coupon').'</th></tr>';
        $out .= '<tr><td class="dark">'.lang('Number', 'coupon').':</td><td class="dark"><input type="text" name="title" id="coupon_count" class="input wide" value="300"/></td></tr>';
        $out .= '<tr style="display:none"><td class="light" colspan="2" align="center"><div style="width:64px; height:44px; padding-top:20px; background:url(images/buttons/big_loader.gif) no-repeat; font-size:20px" id="coupon_counter">0</div></td></tr>';
        $out .= '<tr><th align="center" colspan="2">
							<input type="button" value="'.lang('button_create').'" class="button" onclick="return cp.loadModule()"/>
								</th></tr></table>';
      }

      $output[] = $out;
      $output[] = '<span class="back"><a href="coupon.php">'.lang('Back').'</a></span>';

      return $output;
    } else {
      return $this->getList();
    }
  }

  function download()
  {
    if (isset($_POST['coupon_id']) && isset($_POST['type'])) {
      if ($items = $this->engine->coupons->getFullCoupons($_POST['coupon_id'], (int)$_POST['type'])) {
        $coupon = current($items);
        $out    = $coupon['title'] . ';' . number_format($coupon['price'], 2, ',', ' ') . " ".CURR_VALUTE.";\n\n";
        if ($_POST['type'] == 1) {
          $out .= "Code;;\n";
        } else {
          $out .= "Code;Kunde;Datum\n";
        }
        foreach ($items as $item) {
          if ($_POST['type'] == 1) {
            $a = array($item['code']);
          } else {
            $a = array(
              $item['code'],
              $item['client_name'] . ' ' . $item['client_surname'],
              (empty($item['actived_date']) ? '' : date('d.m.Y', strtotime($item['actived_date'])))
            );
          }

          $out .= join(';', $a) . "\n";
        }
      }
      header("Content-Disposition: attachment; filename=coupon_export.csv");
      header("Content-Type: application/x-force-download; name=\"coupon_export.csv\"");
      echo "\xEF\xBB\xBF" . $out;
      die;
    }

    return $this->getList();
  }

  //список настроек
  function getList()
  {
    $out = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out .= '<tr><th>'.lang('Active').'/'.lang('inactive').'</th><th>'.lang('Title').'</th><th>'.lang('Sum').', '.CURR_VALUTE.'</th><th colspan="3">'.lang('Action').'</th></tr>' . "\n";

    if ($this->engine->coupons->getCoupons($items)) {
      foreach ($items as $item) {
        $out .= '<tr>' .
          '<td class="dark"><span class="' . ($item['status'] == 1 ? 'green"> '. lang('Yes') : 'red"> '. lang('Not')) . '<span></td>' .
          '<td class="light">' . $item['title'] . '</td>' .
          '<td class="dark">' . number_format($item['price'], 2, ',', ' ') . ' '.CURR_VALUTE.'</td>';
        $out .= '<td class="light"><a href="coupon.php?action=editCode&coupon_id=' . $item['coupon_id'] . '" class="btnEdit">'.lang('Coupons', 'coupon').'</a></td>';
        $out .= '<td class="light"><a href="coupon.php?action=edit&coupon_id=' . $item['coupon_id'] . '" class="btnEdit">'.lang('button_update').'</a></td>';
        $out .= '<td class="light"><a href="coupon.php?action=remove&coupon_id=' . $item['coupon_id'] . '" onclick="return ifConfirm ()" class="btnRemove">'.lang('button_remove').'</a></td>';
        $out .= '</tr>' . "\n";
      }
    }
    $out .= "</table>\n";
    if (isset($this->error)) {
      $output[] = $this->error;
    }
    $output[] = $out;
    $output[] = $this->getFields(0);

    return $output;
  }

  function getFields($mode, $row = array())
  {
    //html
    $out = '<form action="coupon.php?action=' . ($mode == 0 ? 'insert' : 'change&coupon_id=' . $row['coupon_id']) . '" method="post">' . "\n";

    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= ($mode == 0 ? lang('title_create') : lang('title_update')) ?></th>
      </tr>
      <tr>
        <td class="dark"><?= lang('Title')?>:</td>
        <td class="dark"><input type="text" name="title" class="input wide"
                                value="<?=( isset($row['title']) ? str_replace('"', '&amp;', $row['title']) :'') ?>"/></td>
      </tr>
      <?
      if ($mode == 0) {
        ?>
        <tr>
          <td class="light"><?= lang('Sum')?>, <?=CURR_VALUTE?>:</td>
          <td class="dark"><input type="text" name="price" class="input wide"
                                  value="<?= (isset($row['price']) ? number_format($row['price'], 2, ',', ' ') : '') ?>"/></td>
        </tr>
        <?
      }
      ?>
      <tr>
        <td class="dark"><?= lang('Active')?>/<?= lang('inactive')?>:</td>
        <td class="dark">
          <input type="checkbox" name="status" value="1" <?= (isset($row['status']) && $row['status'] == 1 ? ' checked' : '') ?> />
        </td>
      </tr>
      <tr>
        <th align="center" colspan="2">
          <input type="submit" value="<?= lang('button_' . ($mode == 0 ? 'create' : 'update')) ?> " class="button">
          &nbsp;
          <input type="reset" value="<?= lang('button_reset')?>" class="button">
        </th>
      </tr>
    </table>
    <?
    $out .= ob_get_contents();
    ob_end_clean();

    return $out;
  }

}

if (GUTHABEN_COUPONS != true) {
  header("Location: " . BASE_HREF . "at/index.php");
}

$a                = new coupon_admin;
$_page['content'] = $a->start();
$_page['key']     = 'coupon';
$_page['js'][]    = 'coupons';

