<?php


use AC\core\modules\areas\models\AreasModel;
use AC\core\modules\text\engines\TextEngine;
use AC\core\system\helpers\ArrayHelper;
use AC\core\system\language\IniFiles;

$_page['key'] = 'config_messages';

class configMessage
{
  /**
   * @var TextEngine
   */
  public    $ini;
  protected $courtType = 'common';
  protected $device    = 'site';

  function __construct()
  {
    $lang               = Service::request()->_('lang', config('lang')->defaultLanguage);
    $this->ini['site']  = new IniFiles(pathAs(paths()?->getLangAppDir($lang . '/message.' . $lang, 'site')));
    $this->ini['touch'] = new IniFiles(pathAs(paths()?->getLangAppDir($lang . '/message.' . $lang, 'touch')));
    $this->courtType    = Service::request()->_('courtType', $this->courtType);
    $this->device       = Service::request()->_('device', $this->device);
  }

  function start()
  {
    switch (Service::request()->_get('action')) {
      case 'edit':
        return $this->edit();
      default:
        return $this->getList();
    }
  }

  function edit()
  {
    $types   = array_merge(
      ['common' => (object)['title' => lang('general', 'config_messages')]],
      module('areas')->useModel('AreasModel')?->selectActiveType()
    );
    $output  = [];
    $section = Service::request()->_get('section');
    $key     = Service::request()->_get('key');
    if (!empty($this->device) && !empty($section) && !empty($key)) {
      if (Service::request()->checkPost('content')) {
        $this->ini[$this->device]->write($section, $key, str_replace(["\n", "\r"], "", Service::request()->_post('content') ? : ' '));
        $this->ini[$this->device]->updateFile();
      }
      $messageItem = Service::structure('messages')->getTree()[$key];

      $variables = isset($messageItem['variables']) ? $messageItem['variables'] : null;
      $value     = $this->ini[$this->device]->read($section, $key, '');
      ob_start();
      ?>
      <h1><?= lang('config_messages_title', 'structure'); ?> ( <?= $types[$this->courtType]->title ?> )</h1>
      <form
        action="config_messages.php?action=edit&section=<?= $section ?>&key=<?= $key ?>&device=<?= $this->device ?>&courtType=<?= $this->courtType ?>"
        method="post" onsubmit="return ifConfirm ()">
        <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide"
               style="width:55%">
          <thead>
          <tr>
            <th><h4><?= $messageItem['name'] ?></h4></th>
            <?= (!empty($variables) ? '<th>' . lang('title_variables', 'config_messages') . '</th>' : '') ?>
          </tr>
          </thead>
          <tbody>
          <tr>
            <td>
              <textarea name="content" class="ckeditor"><?= $value ?></textarea>
            </td>
            <?php if (!empty($variables)) { ?>
              <td>
                <div>
                  <table>
                    <?php foreach ($variables as $variable) { ?>
                      <tr>
                        <td><span style="font-weight: bold"><?= lang('variable_' . $variable, 'config_messages') ?>:</span></td>
                        <td>{{<?= $variable ?>}}</td>
                      </tr>
                    <?php } ?>
                  </table>
                </div>
              </td>
            <?php } ?>
          </tr>
          <tr>
            <th colspan="<?= (!empty($variables) ? 2 : 1) ?>">
              <div style="display: flex;justify-content: center">
                <input type="submit" value="<?= lang('button_save') ?>" class="button">
                <a href="config_messages.php?device=<?= $this->device ?>&courtType=<?= $this->courtType ?>"
                   style="color:black;cursor: default;margin-left: 6px;border: 1px solid #000000;background-color: #FFFFFF;font-size: 13px;margin-top: 3px;height: 18px;padding: 3px 3px 0 3px;display: inline-block;text-decoration: none;"><?= lang(
                    'Back'
                  ) ?></a>
              </div>
            </th>
          </tr>
          </tbody>
        </table>
        <?php if (isset($value)) { ?>
        <?php } ?>
      </form>
      <?php
      $output[] = ob_get_clean();
    } else {
      $this->getList();
    }

    return $output;
  }


  function getList()
  {
    $groupMessages = Service::structure('messages', 'translation')->getTreeByGroup()[$this->device][$this->courtType];
    ob_start();
    ?>
    <h1><?= lang('config_messages_title', 'structure'); ?></h1>
    <table border="0" cellspacing="1" cellpadding="3" bgcolor="#FFFFFF" align="center" class="main wide">
    <caption><?= $this->getLocalMenu() ?></caption>
    <thead>
    <tr>
      <th></th>
      <th><?= lang('Title') ?></th>
      <th width="200px"><?= lang('Photo') ?></th>
      <th width="80px"><?= lang('Action') ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($groupMessages as $group => $messages) {
      foreach ($messages as $key => $item) {
        if ($item['showAccordingToConditions']) {
          ?>
          <tr>
          <?php if (ArrayHelper::keyFirstArray($messages) == $key) { ?>
            <td class="dark" rowspan="<?= count($messages) ?>"><?= lang('title_group_' . $group, 'config_messages') ?></td>
          <?php } ?>
          <td class="dark"><?= $item['name'] ?></td>
          <td class="light">
            <? if (!empty($item['image'])): ?>
              <a href="<?= $item['image'] ?>" target="_blank">
                <img src="<?= $item['image'] ?>" height="50px"/>
              </a>
            <? endif; ?></td>
          <td class="dark">
            <a
              href="config_messages.php?action=edit&section=<?= $item['section'] . '_' . $this->courtType ?>&key=<?= $item['key'] ?>&device=<?= $this->device ?>&courtType=<?= $this->courtType ?>"
              class="btnEdit"><?= lang('button_update') ?></a>
          </td>
          </tr><?
        }
      }
    }
    ?>
    </tbody>
    </table><?php
    $output[] = ob_get_clean();

    return $output;
  }

  public function getLocalMenu()
  {
    ob_start();
    ?>
    <div class="list-type list-type-content">
      <div class="item-type <?= ('common' == $this->courtType ? 'active' : '') ?>">
        <a href="<?= 'config_messages.php?device=' . $this->device . '&courtType=common' ?>">
          <?= lang('general', 'config_messages') ?>
        </a>
      </div>
      <?php foreach (AreasModel::selectActiveType() as $alias => $type) { ?>
        <div class="item-type <?= ($alias == $this->courtType ? 'active' : '') ?>">
          <a href="<?= 'config_messages.php?device=' . $this->device . '&courtType=' . $alias ?>">
            <?= $type->title ?>
          </a>
        </div>
      <?php } ?>
    </div>
    <?php
    return ob_get_clean();
  }

  public function getContentMenu()
  {
    $menu['site'] = [
      'href'   => 'config_messages.php?device=site&courtType=' . $this->courtType,
      'title'  => lang('type_device_site', 'config_messages'),
      'active' => Service::request()->_get('device') == 'site',
    ];
    if (TOUCHSCREEN) {
      $menu['touch'] = [
        'href'   => 'config_messages.php?device=touch&courtType=' . $this->courtType,
        'title'  => lang('type_device_touch', 'config_messages'),
        'active' => Service::request()->_get('device') == 'touch',
      ];
    }

    return [$menu];
  }
}

$a                     = new configMessage;
$_page['content']      = $a->start();
$_page['content_menu'] = $a->getContentMenu();
$_page['js'][]         = 'visualeditor/ckeditor';
