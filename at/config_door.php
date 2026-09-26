<?


use AC\core\engines\Engines;

class config_door_admin
{

  function start()
  {
    $this->e = new Engines();

    switch ($_GET['action']) {
      case 'change':
        return $this->change();
        break;
      default:
        return $this->getList();
    }
  }

  //изменить
  function change()
  {
    $this->e->setDoorConfig($_POST['door_time_start'], $_POST['door_time_finish']);

    return $this->getList();
  }

  //список настроек
  function getList()
  {
    $out[] = $this->getFields($this->e->config);

    return $out;
  }

  function getFields($row = array())
  {
    //html
    $out = '<form action="config_door.php?action=change" method="post">' . "\n";

    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= lang('title_create')?></th>
      </tr>
      <tr>
        <td class="light"><?= lang('From')?>:</td>
        <td class="light">
          <select name="door_time_start" class="input">
            <?php
            for ($i = 0; $i < 24; $i++) {
              echo '<option value="' . sprintf('%02d', $i) . ':00" ' . ($row['door_time_start'] == sprintf('%02d', $i) . ':00' ? 'selected'
                  : '') . ' >' . sprintf('%02d', $i) . ':00</option>';
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Until')?>:</td>
        <td class="dark">
          <select name="door_time_finish" class="input">
            <?php
            for ($i = 0; $i < 24; $i++) {
              echo '<option value="' . sprintf('%02d', $i) . ':00" ' . ($row['door_time_finish'] == sprintf('%02d', $i) . ':00' ? 'selected'
                  : '') . ' >' . sprintf('%02d', $i) . ':00</option>';
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <th align="center" colspan="2">
          <input type="submit" value="<?= lang('button_create')?>" class="button">
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

$a                = new config_door_admin;
$_page['content'] = $a->start();
$_page['key']     = 'door_monitor';

