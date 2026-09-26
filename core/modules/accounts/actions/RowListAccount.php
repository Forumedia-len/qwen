<?php

namespace AC\core\modules\accounts\actions;

use Service;
use AC\core\system\helpers\NumberHelper;
use AC\core\system\helpers\ObjectHelper;
use AC\core\system\helpers\TranslateHelper;

class RowListAccount
{

  protected $data = [];

  public function __construct()
  {
    view()->addPathToView('core\modules\accounts\views\\');
  }

  public function getRow($data, $keys)
  {
    $this->data = $data;
    $row        = [];
    foreach ($keys as $key) {
      $itemRow   = ObjectHelper::createObject(['value', 'class', 'id', 'style']);
      $row[$key] = $this->getItem($key, $itemRow);
    }

    return $row;
  }

  protected function getItem($itemName, $ItemRow)
  {
    if (method_exists($this, 'get' . ucfirst($itemName))) {
      return $this->{'get' . ucfirst($itemName)}($ItemRow);
    }
    if (isset($this->data[$itemName])) {
      $ItemRow->value = $this->data[$itemName];
    }

    return $ItemRow;
  }

  protected function getMark($ItemRow)
  {
    $ItemRow->class = 'light';
    $ItemRow->style = 'text-align:center';
    $ItemRow->value = useLayout()->render('checkbox', [
      'field' => ObjectHelper::createObject([
        'name'  => 'account[]',
        'value' => $this->data['account_id']
      ], true)
    ], 'admin');

    return $ItemRow;
  }

  protected function getNumber($ItemRow, $accountPrefixNumber = ACCOUNT_NUMBER)
  {
    $ItemRow->id    = 'numberAccountBlock' . $this->data['account_id'];
    $ItemRow->class = $this->data['execution'] ? 'select' : 'dark';
    $ItemRow->value = config('accountView')->getNumberAccount($this->data['a_number'], $accountPrefixNumber);

    return $ItemRow;
  }

  protected function getDate($ItemRow)
  {
    $ItemRow->class = 'light';
    $ItemRow->value = TranslateHelper::translateMonth(date('n', strtotime($this->data['date_start']))) . ' ' . date(
        'Y',
        strtotime($this->data['date_start'])
      );

    return $ItemRow;
  }

  protected function getTitle($ItemRow)
  {
    $ItemRow->class = 'dark';
    $ItemRow->value = $this->data['name'] . ' ' . $this->data['surname'] . '<br/>'
      . $this->data['address'] . ', ' . $this->data['post_code'] . ' ' . $this->data['city'];

    return $ItemRow;
  }

  protected function getStatus($ItemRow)
  {
    $ItemRow->class = 'light';
    $ItemRow->value = ((!empty($this->data['club_state'])) ? ((($this->data['club_state'] == 1) ? 'NM'
          : ('V' . ($this->data['club_state'] - 1))) . '/ ')
        : '') . $this->data['nds'] . '%';

    return $ItemRow;
  }

  protected function getAmount($ItemRow)
  {
    $ItemRow->class = 'dark';
    $ItemRow->style = 'text-align:right';
    $ItemRow->value = NumberHelper::format($this->data['sum']) . ' ' . CURR_VALUTE;

    return $ItemRow;
  }

  protected function getSepa($ItemRow)
  {
    $ItemRow->class = 'light';
    $ItemRow->style = 'text-align:center;vertical-align:center';
    $imgColor       = 'red';
    if (strlen($this->data['name'] ?? '') > 0 && strlen($this->data['sepa_iban'] ?? '') > 0
      && strlen($this->data['sepa_bic'] ?? '') > 0 && strlen($this->data['sepa_mndtid'] ?? '') > 0
      && strlen($this->data['sepa_dtofsgntr'] ?? '') > 0 && $this->data['sum'] > 0) {
      $imgColor = 'green';
    }

    $ItemRow->value = useLayout()->render('img', [
      'field' => ObjectHelper::createObject([
        'src' => base_url(paths()->getAssetsDir('images/bullet_' . $imgColor . '.gif'))
      ], true)
    ], 'common');

    return $ItemRow;
  }

  protected function getActions($ItemRow, $separator = '</br>')
  {
    $ItemRow->class = 'dark';
    $ItemRow->value = implode($separator, $this->getButtonsAction());

    return $ItemRow;
  }

  protected function getButtonsAction()
  {
    $actions = [];
    $type    = Service::request()->_get('type', module('areas')->useModel()->getFirstActiveType());
    if (defined('USE_ACCOUNT_TEXT_FIELDS') && USE_ACCOUNT_TEXT_FIELDS) {
      $actions['text_field'] = useLayout()->render('link', [
          'field' => ObjectHelper::createObject([
            'href'            => '#',
            'style'           => !empty($this->data['text_fields']) ? 'color:#0bbe2a' : '',
            'class'           => 'btnEdit',
            'value'           => lang('text_fields', 'accounts_view'),
            'otherProperties' => 'onclick="return popup.createWindow(\'get_ajax_data.php?action=getAccountTextForm&account_id=' . $this->data['account_id'] . '&account_type=' . $this->data['account_type'] . '&type=' . $type . '\')"'
          ], true)
        ], 'common');
    }
    $actions['account_view'] = useLayout()->render('link', [
      'field' => ObjectHelper::createObject([
        'href'   => 'accounts_view.php?account=' . $this->data['account_id'] . '&account_type=' . ($this->data['account_type'] + 1) . '&type=' . $type,
        'target' => '_blank',
        'class'  => 'btnView',
        'value'  => lang('View'),
      ], true)
    ], 'common');
    $actions['send_mail']    = useLayout()->render('link', [
      'field' => ObjectHelper::createObject([
        'href'            => 'accounts_view.php?account=' . $this->data['account_id'] . '&account_type=' . ($this->data['account_type'] + 1) . '&action=send',
        'target'          => '_blank',
        'class'           => 'btnMail',
        'style'           => $this->data['send'] == 1 ? 'color:#0712bb' : '',
        'id'              => 'linkSendEmailAccount' . $this->data['account_id'],
        'value'           => lang('Send as email', 'accounts'),
        'otherProperties' => 'onclick="return markExecution(this, \'numberAccountBlock' . $this->data['account_id'] . '\')"'

      ], true)
    ], 'common');

    return $actions;
  }

}