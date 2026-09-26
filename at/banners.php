<?php



class banners_admin
{
  protected $e;

  function start()
  {

    $this->e = useClass(paths()->enginesDir . 'BannersEngine', true);

    if (!isset ($_GET['action'])) {
      return $this->main();
    }

    switch ($_GET['action']) {
      case 'insertBanner':
        return $this->insertBanner();
        break;
      case 'editBanner':
        return $this->editBanner();
        break;
      case 'changeBanner':
        return $this->changeBanner();
        break;
      case 'removeBanner':
        return $this->removeBanner();
        break;
      default:
        return $this->main();
    }
  }

  //список баннеров
  function main()
  {
    return array($this->bannersList(), $this->getBannerFields('insert'));
  }

  //удалить новость
  function removeBanner()
  {
    $this->e->removeBanner((int)$_GET['banner_id']);

    return $this->main();
  }

  //добавить новость
  function insertBanner()
  {
    $this->e->insertBanner(
      $_POST['alt'],
      $_POST['url'],
      (int)$_POST['sort'],
      (int)isset ($_POST['publish']),
      $_FILES['image']['error'] == 0 ? $_FILES['image']['tmp_name'] : null,
      $banner_id
    );

    return $this->main();
  }

  //изменить новость
  function changeBanner()
  {
    $this->e->changeBanner(
      (int)$_POST['banner_id'],
      $_POST['alt'],
      $_POST['url'],
      (int)$_POST['sort'],
      (int)isset ($_POST['publish']),
      $_FILES['image']['error'] == 0 ? $_FILES['image']['tmp_name'] : null
    );

    return $this->main();
  }

  //форма редактирования новости
  function editBanner()
  {

    //если новости нет - показываем список
    if ($this->e->getBanner((int)$_GET['banner_id'], $row)) {
      //форма редактирования
      $tb  = GetThemeBuilder();
      $out = $this->getBannerFields('edit', $row);

      return array($out, $tb->back('banners.php'));
    } else {
      return $this->main();
    }
  }

  //список настроек
  function bannersList()
  {
    $out = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out .= '<tr><th>'.lang('Sequence number').'</th><th width="50%">'.lang('Title').'</th><th>'.lang('Image').'</th><th>'.lang('Publish').'</th><th colspan="2">'.lang('Action').'</th></tr>' . "\n";
    if ($this->e->getBanners($banners, null, null)) {
      foreach ($banners as $item) {
        $out .= '<tr>' .
          '<td class="dark" align="center">' . $item['sort'] . '</td>' .
          '<td class="light">' . $item['alt'] . '</td>' .
          '<td class="dark" align="center">' . ($item['image'] !== null ? lang('Yes') : lang('Not')) . '</td>' .
          '<td class="light" align="center">' . ($item['publish'] == 1 ? lang('Yes') : lang('Not')) . '</td>';
        $out .= '<td class="dark"><a href="banners.php?action=editBanner&banner_id=' . $item['banner_id'] . '" class="btnEdit">'.lang('button_update').'</a></td>';
        $out .= '<td class="dark"><a href="banners.php?action=removeBanner&banner_id=' . $item['banner_id'] . '" onclick="return ifConfirm ()" class="btnRemove">'.lang('button_remove').'</a></td>';
        $out .= '</tr>' . "\n";
      }
    }
    $out .= "</table>\n";

    return $out;
  }

  function getBannerFields($mode, $row = array())
  {
    //html
    $tb  = getThemeBuilder();
    $out = '<form action="banners.php?action=' . ($mode == 'insert' ? 'insertBanner'
        : 'changeBanner') . '" method="post" enctype="multipart/form-data">' . "\n";

    if ($mode == 'edit') {
      $out .= "<input type=\"hidden\" name=\"banner_id\" value=\"" . $row['banner_id'] . "\">\n";
    }
    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main">
      <tr>
        <th colspan="2"><?= ($mode == 'insert' ? lang('title_create') : lang('title_update')) ?></th>
      </tr>
      <tr>
        <td class="dark"><?= lang('Title')?>:</td>
        <td class="dark">
          <input type="text" name="alt" class="input wide" value="<?= (isset($row['alt']) ? str_replace('"', '&amp;', $row['alt']) : '') ?>"/>
        </td>
      </tr>
      <tr>
        <td class="light"><?= lang('URL', 'banners')?>:</td>
        <td class="light">
          <input type="text" name="url" class="input wide" value="<?= isset ($row['url']) ? str_replace('"', '&amp;', $row['url']) : 'http://' ?>"/>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Image')?>:</td>
        <td class="dark">
          <input type="file" name="image" class="input wide"/>
        </td>
      </tr>
      <?
      if ($mode == 'edit' && $row['image'] !== null) {
        echo "	<tr>\n";
        echo "		<td class=\"dark\">&nbsp;</td>\n";
        $tmp = getimagesize($row['image']);
        echo "		<td class=\"dark\"><img src=\"" . base_url($row['image']) . "\" vspace=\"7\" style=\"border:1px solid black\"><br>\n";
        echo $tmp[0] . "x" . $tmp[1] . "px, " . ceil(filesize($row['image']) / 1024) . "Kb</td>\n";
        echo "	</tr>\n";
      }
      ?>
      <tr>
        <td class="light"><?= lang('Sequence number')?>:</td>
        <td class="light">
          <input type="text" name="sort" class="input wide" value="<?= isset ($row['sort']) ? $row['sort'] : 1 ?>"/>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Publish')?>:</td>
        <td class="dark">
          <input type="checkbox" name="publish" value="1"<?= (isset($row['publish']) && $row['publish'] ? ' checked' : '') ?>></textarea>
        </td>
      </tr>
      <tr>
        <th align="center" colspan="2">
          <input type="submit" value="<?= ($mode == 'insert' ? lang('button_create') : lang('button_update')) ?>" class="button">
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

$a                = new \banners_admin;
$_page['content'] = $a->start();
$_page['key']     = 'banners';

