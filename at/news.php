<?php

class news_admin
{
  protected $engine;
  function start()
  {
    $this->engine = getEngine('news', false);

    switch (\Service::request()->_get('action')) {
      case 'insertNewsItem':
        return $this->insertNewsItem();
        break;
      case 'editNewsItem':
        return $this->editNewsItem();
        break;
      case 'changeNewsItem':
        return $this->changeNewsItem();
        break;
      case 'removeNewsItem':
        return $this->removeNewsItem();
        break;
      default:
        return $this->getNewsList();
    }
  }

  //список новостей
  function getNewsList()
  {
    return array($this->newsList(), $this->newsInsertForm());
  }

  //удалить новость
  function removeNewsItem()
  {
    $this->engine->removeNewsItemById($_GET['news_id']);

    return $this->getNewsList();
  }

  //добавить новость
  function insertNewsItem()
  {
    $utils = GetEngine('utils');
    $date  = $utils->convertDateEuro2Mysql($_POST['date']);

    $this->engine->insertNewsItem($date, trim($_POST['title']), trim($_POST['content']), $_POST['publish'], $news_id);

    return $this->getNewsList();
  }

  //изменить новость
  function changeNewsItem()
  {
    $utils = GetEngine('utils');
    $date  = $utils->convertDateEuro2Mysql($_POST['date']);

    $this->engine->changeNewsItemById($_POST['news_id'], $date, trim($_POST['title']), trim($_POST['content']), isset($_POST['publish']));

    return $this->getNewsList();
  }

  //форма редактирования новости
  function editNewsItem()
  {
    //если новости нет - показываем список
    if ($this->engine->getNewsItemById($_GET['news_id'], $news_item) == false) {
      return array($this->newsList(), $this->newsInsertForm());
    }

    //форма редактирования
    $out = $this->getNewsItemInputFields(1, $news_item);

    return array($out, '<span class="back"><a href="news.php">'.lang('Back').'</a></span>');
  }

  //список настроек
  function newsList()
  {
    $out = '<table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">' . "\n";
    $out .= '<tr><th>'.lang('Date').'</th><th width="50%">'.lang('Title').'</th><th>'.lang('Publish').'</th><th colspan="2">'.lang('Action').'</th></tr>' . "\n";
    if ($this->engine->getNewsItems($news, [])) {
      foreach ($news as $item) {
        $out .= '<tr>' .
          '<td class="dark" align="center">' . date('d.m.Y', strtotime($item['date'])) . '</td>' .
          '<td class="light">' . $item['title'] . '</td>' .
          '<td class="dark" align="center">' . ($item['publish'] == 1 ? lang('Yes') : lang('Not')) . '</td>';
        $out .= '<td class="light"><a href="news.php?action=editNewsItem&news_id=' . $item['news_id'] . '" class="btnEdit">'.lang('button_update').'</a></td>';
        $out .= '<td class="light"><a href="news.php?action=removeNewsItem&news_id=' . $item['news_id'] . '" onclick="return ifConfirm ()" class="btnRemove">'.lang('button_remove').'</a></td>';
        $out .= '</tr>' . "\n";
      }
    }
    $out .= "</table>\n";

    return $out;
  }

  function newsInsertForm()
  {
    $out = $this->getNewsItemInputFields(0);

    return $out;
  }

  function  getNewsItemInputFields($mode, $row = array())
  {
    if ($mode == 0) {
      $row['date'] = date('d.m.Y');
    } else $row['date'] = date('d.m.Y', strtotime($row['date']));
    //html
    $out = '<form action="news.php?action=' . ($mode == 0 ? 'insertNewsItem' : 'changeNewsItem') . '" method="post">' . "\n";
    if ($mode == 1) {
      $out .= "<input type=\"hidden\" name=\"news_id\" value=\"" . $row['news_id'] . "\">\n";
    }

    ob_start();
    ?>
    <table border="0" cellspacing="1" cellpadding="3" align="center" class="main wide">
      <tr>
        <th colspan="2"><?= ($mode == 0 ? lang('title_create') : lang('title_update')) ?></th>
      </tr>
      <tr>
        <td class="light"><?= lang('Date')?>:</td>
        <td class="light">
          <input type="text" name="date" class="input wide" value="<?= $row['date'] ?>"/>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Title')?>:</td>
        <td class="dark">
          <input type="text" name="title" class="input wide" value="<?= (isset($row['title']) ? str_replace('"', '&amp;', $row['title']) : '') ?>"/>
        </td>
      </tr>
      <tr>
        <td class="light"><?= lang('Content')?>:</td>
        <td class="light">
          <textarea name="content" class="ckeditor input wide"><?= (isset($row['content']) ? str_replace('<', '&lt;', str_replace('>', '&gt;', $row['content'])) : '') ?></textarea>
        </td>
      </tr>
      <tr>
        <td class="dark"><?= lang('Publish')?>:</td>
        <td class="dark">
          <input type="checkbox" name="publish" value="1"<?= (isset($row['publish'] ) && $row['publish'] ? ' checked' : '') ?>></textarea>
        </td>
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

$a                = new news_admin;
$_page['content'] = $a->start();
$_page['key']     = 'news';
$_page['js'][]    = 'visualeditor/ckeditor';

