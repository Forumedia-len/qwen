<?php

use AC\core\engines\Engines;
use AC\core\system\helpers\NumberHelper;

class discount_admin
{

  function start()
  {
    $this->e = new Engines();

    switch (Service::request()->_('action')) {
      case 'insert':
        return $this->insert();
        break;
      case 'edit':
        return $this->edit();
        break;
      case 'change':
        return $this->change();
        break;
      case 'remove':
        return $this->remove();
        break;
      default:
        return $this->getList();
    }
  }

  //удалить
  function remove()
  {
    $this->e->discount->removeDiscount((int)$_GET['discount_id']);

    return $this->getList();
  }

  //добавить
  function insert()
  {
    $this->e->discount->insertDiscount(
      (int)$_POST['type'],
      $_POST['title'],
      $_POST['dimension'],
      NumberHelper::float($_POST['retail']),
      NumberHelper::float($_POST['ticket']),
      $_POST['comment']
    );

    return $this->getList();
  }

  //изменить
  function change()
  {
    $this->e->discount->changeDiscount(
      $_POST['title'],
      $_POST['dimension'],
      NumberHelper::float($_POST['retail']),
      NumberHelper::float($_POST['ticket']),
      $_POST['comment'],
      (int)$_GET['discount_id']
    );

    return $this->getList();
  }

  //форма редактирования
  function edit()
  {
    //если новости нет - показываем список
    if ($this->e->discount->getDiscount((int)$_GET['discount_id'], $item)) {
      //форма редактирования
      $out = $this->getFields(1, $item);

      return array($out, '<span class="back"><a href="config_discount.php">'.lang('Back').'</a></span>');
    } else {
      return $this->getList();
    }
  }

  //список
  function getList()
  {
    $out = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out .= '<tr><th colspan="5">'.lang('Customer discounts', 'config_discount').'</th></tr>' . "\n";
    $out .= '<tr><th>'.lang('Title').'</th><th>'.lang('Individual bookings', 'config_discount').'</th><th>'.lang('Abo').'</th><th colspan="2">'.lang('Action').'</th></tr>' . "\n";

    if ($this->e->discount->getDiscounts(0, $discount)) {
      foreach ($discount as $item) {
        $out .= '<tr>' .
          '<td class="dark">' . $item['title'] . '</td>' .
          '<td class="light">' . number_format($item['retail'], 2, ',', " ") . ' ' . ($item['dimension'] == 1 ? '%' : CURR_VALUTE) . '</td>' .
          '<td class="dark">' . number_format($item['ticket'], 2, ',', " ") . ' ' . ($item['dimension'] == 1 ? '%' : CURR_VALUTE) . '</td>';
        $out .= '<td class="light"><a href="config_discount.php?action=edit&discount_id=' . $item['discount_id'] . '" class="btnEdit">'.lang('button_update').'</a></td>';
        $out .= '<td class="light"><a href="config_discount.php?action=remove&discount_id=' . $item['discount_id'] . '" onclick="return ifConfirm (\''.lang('Attention! When deleting a discount rate, it will be reset also for affected customers! Continue?', 'config_discount').'\')" class="btnRemove">'.lang('button_remove').'</a></td>';
        $out .= '</tr>' . "\n";
      }
    }

    $out .= "</table>\n";

    $out_a = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out_a .= '<tr><th colspan="5">'.lang('Abo discounts', 'config_discount').'</th></tr>' . "\n";
    $out_a .= '<tr><th>'.lang('Title').'</th><th>'.lang('Individual bookings', 'config_discount').'</th><th>'.lang('Abo').'</th><th colspan="2">'.lang('Action').'</th></tr>' . "\n";

    if ($this->e->discount->getDiscounts(1, $discount)) {
      foreach ($discount as $item) {
        $out_a .= '<tr>' .
          '<td class="dark">' . $item['title'] . '</td>' .
          '<td class="light">' . number_format($item['retail'], 2, ',', " ") . ' ' . ($item['dimension'] == 1 ? '%' : CURR_VALUTE) . '</td>' .
          '<td class="dark">' . number_format($item['ticket'], 2, ',', " ") . ' ' . ($item['dimension'] == 1 ? '%' : CURR_VALUTE) . '</td>';
        $out_a .= '<td class="light"><a href="config_discount.php?action=edit&discount_id=' . $item['discount_id'] . '" class="btnEdit">'.lang('button_update').'</a></td>';
        $out_a .= '<td class="light"><a href="config_discount.php?action=remove&discount_id=' . $item['discount_id'] . '" onclick="return ifConfirm (\''.lang('Attention! When deleting a discount rate, it will be reset also for affected customers! Continue?', 'config_discount').'\')" class="btnRemove">'.lang('button_remove').'</a></td>';
        $out_a .= '</tr>' . "\n";
      }
    }

    $out_a .= "</table>\n";

    return array($out, $out_a, $this->getFields(0));
  }

  function getFields($mode, $row = array())
  {
    //html
    $out = '<form action="config_discount.php?action=' . ($mode == 0 ? 'insert'
        : 'change&discount_id=' . $row['discount_id']) . '" method="post">' . "\n";

    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= ($mode == 0 ? lang('title_create') : lang('title_update')) ?></th>
      </tr>
      <tr>
        <td class="light"><?= lang('Type')?>:</td>
        <td class="light">
          <?= ($mode == 0 || ($mode && !isset($row['type'])) ? '<input type="radio" name="type" value="0" ' . (!isset($row['type']) ? 'checked'
              : '') . ' onClick="javascript:document.getElementById(\'retail\').readOnly=false;"/>'. lang('Clients') : '') ?>
          <?= ($mode == 0 || (isset($row['type']) && $mode && $row['type'])
            ? '<input type="radio" name="type" value="1" ' . (isset($row['type']) && $row['type'] ? 'checked'
              : '') . ' onClick="javascript:document.getElementById(\'retail\').readOnly=true;"/>' . lang('Abos') : '') ?>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Title')?>:</td>
        <td class="dark"><input type="text" name="title" class="input wide"
                                value="<?= (isset($row['title']) ? str_replace('"', '&amp;', $row['title']) : '') ?>"/></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Discount type', 'config_discount')?>:</td>
        <td class="dark">
          <input type="radio" name="dimension" value="1" <?= (isset($row['dimension']) ? ($row['dimension'] == 1 ? 'checked' : '') : 'checked') ?> />
          %
          <input type="radio" name="dimension" value="2" <?= ((isset($row['dimension']) && $row['dimension'] == 2) ? 'checked'
            : '') ?> /> <?= CURR_VALUTE ?>
        </td>
      </tr>
      <tr>
        <td class="light"><?= lang('Individual bookings', 'config_discount')?>:</td>
        <td class="light"><input type="text" name="retail" class="input" maxlength="6"
                                 value="<?= (isset($row['retail']) ? number_format($row['retail'], 2, ',', "'") : '') ?>"
                                 id="retail" <?= (isset($row['type']) && $row['type'] ? 'readonly' : '') ?>/></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Abo')?>:</td>
        <td class="dark"><input type="text" name="ticket" class="input" maxlength="6"
                                value="<?= (isset($row['ticket']) ? number_format($row['ticket'], 2, ',', "'") : '') ?>"/></td>
      </tr>
      <tr>
        <td class="light"><?= lang('Comment')?>:</td>
        <td class="light"><input type="text" name="comment" class="input wide"
                                 value="<?= (isset($row['comment']) ? str_replace('"', '&amp;', $row['comment']) : '') ?>"/></td>
      </tr>
      <tr>
        <th align="center" colspan="2">
          <input type="submit" value="<?= ($mode == 0 ? lang('button_create') : lang('button_update')) ?>" class="button">
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

$a = new discount_admin;
$_page['content'] = $a->start();
$_page['key'] = 'config_discount';

