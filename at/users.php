<?php


use AC\core\engines\usersEngine;
use AC\core\system\helpers\EmailHelper;
use AC\core\system\helpers\PasswordHelper;

class users_admin
{
  protected $rights = array(1 => 'level 1', 2 => 'level 2', 3 => 'level 3');
  /**
   * @var usersEngine
   */
  protected $e;
  protected $messages = [];

  function start()
  {
    $this->e = useClass(paths()->enginesDir . 'UsersEngine', true);

    switch (\Service::request()->_get('action')) {
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

  //добавить
  public function insert()
  {
    if($this->checkDataUser(0, $_POST)) {
      $this->e->insertUser(
        (int)$_POST['rights'],
        $_POST['login'],
        $_POST['password'],
        $_POST['email'],
        $_POST['code'],
        $_POST['name'],
        ($_FILES['image']['error'] == 0 ? $_FILES['image']['tmp_name'] : null)
      );

      return $this->getList();

    }

    return [$this->getFields(0, $_POST), $this->back()];
  }

  //изменить
  function change()
  {
    if($this->checkDataUser(1, array_merge($_POST, $_GET))) {
      $this->e->changeUser(
        (int)$_POST['rights'],
        $_POST['login'],
        $_POST['password'],
        $_POST['email'],
        $_POST['code'],
        $_POST['name'],
        ($_FILES['image']['error'] == 0 ? $_FILES['image']['tmp_name'] : null),
        (int)$_GET['user_id']
      );
      return $this->getList();
    }

    return [$this->getFields(1, array_merge($_GET, $_POST)), $this->back()];
  }

  //удалить
  function remove()
  {
    $this->e->removeUser((int)$_GET['user_id']);

    return $this->getList();
  }

  //форма редактирования
  function edit()
  {
    //если новости нет - показываем список
    if ($this->e->getUser((int)$_GET['user_id'], $item)) {
      unset($item['password']);
      //форма редактирования
      $out = $this->getFields(1, $item);

      return array($out, $this->back());
    } else {
      return $this->getList();
    }
  }

  //список настроек
  function getList()
  {
    $out = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out .= '<tr>
              <th>' . lang('Rights', 'users') . '</th>
              <th>' . lang('Image', 'users') . '</th>
              <th>' . lang('Username', 'users') . '</th>
              <th>' . lang('Name', 'users') . '</th>
              <th>' . lang('Email', 'users') . '</th>
              <th colspan="2">' . lang('Action', 'users') . '</th>
             </tr>' . "\n";

    if ($this->e->getUsers($users)) {
      foreach ($users as $item) {
        if ($item['rights'] > 0) {
          $out .= '<tr>' .
            '<td class="dark">' . $this->rights[$item['rights']] . '</td>' .
            '<td class="light">' . (isset($item['image']) ? '<img src="' . base_url($item['image']) . '" />' : '') . '</td>' .
            '<td class="dark">' . $item['login'] . '</td>' .
            '<td class="light">[' . $item['code'] . '] ' . $item['name'] . '</td>' .
             '<td class="light">'. $item['email'] . '</td>';
          $out .= '<td class="dark">' . ($item['default'] == 1 ? ''
              : '<a href="users.php?action=edit&user_id=' . $item['user_id'] . '" class="btnEdit">' . lang('button_update') . '</a>') . '</td>';
          $out .= '<td class="light">' . ($item['default'] == 1 ? ''
              : '<a href="users.php?action=remove&user_id=' . $item['user_id'] . '" onclick="return ifConfirm ()" class="btnRemove">' . lang('button_remove') . '</a>') . '</td>';
          $out .= '</tr>' . "\n";
        }
      }
    }

    $out .= "</table>\n";

    return array($out, $this->getFields(0), $this->getPageList());
  }

  function getPageList()
  {
    $structure = Service::structure();
    $tree      = $structure->getTree();

    $out_table = array();

    foreach ($tree as $page) {
      if (empty($page['title']) || (isset($page['visible']) && $page['visible'] == false)) {
        continue;
      }
      $page['access']               = (isset($page['access']) ? $page['access'] : 1);
      $out_table[$page['access']][] = $page['title'];
    }

    $out = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide" ><tr>';
    $s   = '';
    foreach ($this->rights as $access => $user) {
      $out .= $s . '<th>' . $user . '</th>';
      $s   = '<th>&nbsp;</th>';
    }
    $out .= '</tr>';
    $out .= '<tr>';
    $s   = '';
    foreach ($this->rights as $access => $user) {
      $out .= $s . '<td class="light">';
      if (isset($out_table[$access])) {
        foreach ($out_table[$access] as $title) {
          $out .= $title . '<br />';
        }
      }
      $out .= '</td>';
      $s   = '<td class="dark" style="font-size:20px;">&#8834;</td>';
    }
    $out .= '</tr>';
    $out .= '</table>';

    return $out;
  }

  function getFields($mode, $row = array())
  {
    $out = '';
    if (!empty($this->messages)) {
      foreach ($this->messages as $message) {
        $out .= '<p style="text-align:center;color: ' . ($message['type'] == 'error' ? 'red' : '#fff') . ' ">' . $message['text'] . '</p>';
      }
    }
    //html
    $out .= '<form action="users.php?action=' . ($mode == 0 ? 'insert'
        : 'change&user_id=' . $row['user_id']) . '" method="post" enctype="multipart/form-data">' . "\n";

    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= ($mode == 0 ? lang('Add a sentence', 'users') : lang('Change a sentence', 'users')) ?></th>
      </tr>
      <tr>
        <td class="dark"><?= lang('Rights', 'users') ?>:</td>
        <td class="dark">
          <select name="rights" class="input wide">
            <?php
            foreach ($this->rights as $rights => $title) {
              echo '<option value="' . $rights . '" ' . ((isset($row['rights']) && $row['rights'] == $rights) ? 'selected'
                  : '') . '>' . $title . '</option>';
            }
            ?>
          </select>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Username', 'users') ?> *:</td>
        <td class="dark"><input type="text" name="login" class="input wide" value="<?= (isset($row['login']) ? $row['login'] : '') ?>" required/></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Password', 'users') ?> *:</td>
        <td class="dark">
          <div style="position: relative;">
            <input type="<?= (!empty($row['password']) ? 'password' : 'text') ?>" name="password" class="input wide" value="<?= (!empty($row['password'])  ? $row['password'] : '') ?>" id="password-input"  <?= (!$mode ? 'required' : '') ?>/>
            <a href="#" class="password-control"
               style="display: inline-block;	width: 24px;	height: 24px;position: relative;top: 6px;">
              <span class="eye" <?= (empty($row['password']) ? 'style="display: none"' : '') ?>>
                <?= useLayout()->render('svg/eye', [], 'common') ?>
              </span>
              <span class="eye eye-not" style="display: none">
                <?= useLayout()->render('svg/eye-not', [], 'common') ?>
              </span>
            </a>
            <a id="password_generate" style="width: 24px;height:24px;display:inline-block; position: relative; top: 6px;cursor: pointer" title="<?= lang('Generate a new password', 'users')?>">
              <?= useLayout()->render('svg/password_generate', [], 'common') ?>
            </a>
          </div>
          <script>
            $(document).ready(function () {
              let inputPassword = $('#password-input');
              let valPassword = $('#password-input').val();
              $(inputPassword).bind('input', function () {
                if ($(inputPassword).attr('type') == 'text' && valPassword == '') {
                  $('.eye').each(function () {
                    $(this).css('display', 'block');
                    $('.eye-not').css('display', 'none')
                  });
                  $(inputPassword).attr('type', 'password');
                  valPassword = $('#password-input').val()
                }
              })
              $('body').on('click', '.password-control', function () {
                if ($(inputPassword).attr('type') == 'password') {
                  $(this).find('.eye').each(function () {
                    $(this).css('display', 'none');
                    $('.eye-not').css('display', 'block')
                  });
                  $(inputPassword).attr('type', 'text');
                } else {
                  $(this).find('.eye').each(function () {
                    $(this).css('display', 'block');
                    $('.eye-not').css('display', 'none')
                  });
                  $(inputPassword).attr('type', 'password');
                }
                $(inputPassword).focus();
                return false;
              });
              document.getElementById('password_generate').addEventListener('click', function () {
                let dataObject = {}
                dataObject.action = 'generatePassword'
                sendAjax('/mapi/api.php', dataObject, function (data) {
                  if(data) {
                    document.getElementById('password-input').setAttribute('value', data)
                  }
                }, 'post', 'json')
              }, false)
            })
          </script>
          <p style="margin: 3px 0 3px; font-size: 12px;font-weight: 300;max-width: 500px;color: #2d2d2d"><?= lang('password_text', 'users') ?></p>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Email', 'users') ?> *:</td>
        <td class="dark"><input type="email" name="email" class="input wide" required value="<?= (isset($row['email']) ? $row['email'] : '') ?>"/></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Indicator', 'users') ?>:</td>
        <td class="dark"><input type="text" name="code" class="input" value="<?= (isset($row['code']) ? $row['code'] : '') ?>"/></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Name', 'users') ?>:</td>
        <td class="dark"><input type="text" name="name" class="input wide" value="<?= (isset($row['name']) ? $row['name'] : '') ?>"/></td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Image', 'users') ?>:</td>
        <td class="dark"><input type="file" name="image" class="input wide"/>
          <?php
          if (isset($row['image'])) {
            $tmp = getimagesize($row['image']);
            echo "<br /><img src=\"" . base_url($row['image']) . "\" vspace=\"7\" style=\"border:1px solid black\"><br>\n";
            echo $tmp[0] . "x" . $tmp[1] . "px, " . ceil(filesize($row['image']) / 1024) . "Kb\n";
          }
          ?>
        </td>
      </tr>
      <tr>
        <th align="center" colspan="2">
          <input type="submit" value="<?= ($mode == 0 ? lang('button_create') : lang('button_update')) ?>" class="button">
          &nbsp;
          <input type="reset" value="<?= lang('button_reset') ?>" class="button">
        </th>
      </tr>
    </table>
    <?
    $out .= ob_get_contents();
    ob_end_clean();

    return $out;
  }

  protected function checkDataUser($mode, $data = [])
  {
    $this->e->getUser($data['user_id'], $dataUser);
    $errors = 0;
    if (empty($data['login']) || (!$mode && empty($data['password'])) || empty($data['email'])) {
      $this->messages[] = ['type' => 'error', 'text' => lang('Incorrect data entry. Please check the data!', 'message_error')];
      $errors++;
    }

    if(!$mode && strlen($data['login']) < 2) {
      $this->messages[] = ['type' => 'error', 'text' => lang('error_attribute_min_value', 'message_error', ['attribute' => lang('Username', 'users'), 'value' => 2] )];
      $errors++;
    }

    if((($mode && !empty($dataUser) && $dataUser['login'] != $data['login']) || !$mode) && !$this->e->checkLoginUnique($data['login'])) {
      $this->messages[] = ['type' => 'error', 'text' => lang('A user with this parameter already exists', 'message_error', ['attribute' => lang('Username', 'users')] )];
      $errors++;
    }

    if((!$mode || !empty($data['password'])) && !PasswordHelper::checkPasswordValidation($data['password'], [], $errorPassword)) {
      if(!empty($errorPassword)) {
        foreach ($errorPassword as $err) {
          $this->messages[] = ['type' => 'error', 'text' => $err];
        }
      }
      $errors++;
    }

    if(!EmailHelper::checkEmail($data['email'])) {
      $this->messages[] = ['type' => 'error', 'text' => lang('error_attribute_type_email', 'message_error', ['attribute' => lang('Email', 'users')] )];
      $errors++;
    }

    if(config('auth')->useEmailUniquenessCheck() && (($mode && !empty($dataUser) && $dataUser['email'] != $data['email']) || !$mode) && !$this->e->checkEmailUnique($data['email'])) {
      $this->messages[] = ['type' => 'error', 'text' => lang('A user with this parameter already exists', 'message_error', ['attribute' => lang('Email', 'users')] )];
      $errors++;
    }

    return !$errors;
  }

  protected function back()
  {
    return '<p style="text-align: center;margin: 0"><a href="users.php">' . lang('Back') . '</a></p>';
  }
}

$a                = new users_admin;
$_page['content'] = $a->start();
$_page['key']     = 'users';

