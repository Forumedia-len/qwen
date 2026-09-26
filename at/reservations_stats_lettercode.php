<?

use AC\core\system\db\Query;

$out = '';

if (isset ($_GET['stock_id'])) {
  $stock_id = (int)$_GET['stock_id'];

  //запрашиваем название типа площадок
  $clients = Query::sqlQuery('select * from ' . Query::tableName('clients') . ' where stock_id LIKE \'%"' . $stock_id . '"%\'');
  if (!empty($clients)) {
    require '../config.php';

    $out .= "<div id=\"main\">";
    $out .= "<div id=\"top\">\n";
    $out .= "<h1>" . config('app')->getProjectTitle() . "</h1>\n";
    $out .= "</div>\n";

    $out .= getClientsDivs($clients);
  } else {
    $out = lang('Clients not presented', 'message_error');
  }
} else {
  $out = lang('Date not presented', 'message_error');
}

$_page['content'][0] = $out;
$_page['key']        = 'reservations_report';
$_page['title']      = 'Statistik';


function getClientsDivs($clients)
{
  $out  = "<div class=\"periods\">\n";
  $rows = "<tr><th>" . lang('Clients') . "</th><th>" . lang('Geburtstag') . "</th></tr>\n";
  foreach ($clients as $p) {
    $rows .= "<tr><td class=\"a\">" . $p['surname'] . ' ' . $p['name'] . "</td><td class=\"p\">" . (!empty($p['birthday']) ? date(
        'd.m.Y',
        strtotime(
          $p['birthday']
        )
      ) : '') . "</td></tr>\n";
  }
  $out .= "<div class=\"periods\">\n";
  $out .= "<table cellspacing=\"0\" class=\"periods\">\n" . $rows;
  $out .= "</table>\n";
  $out .= "</div>\n";
  $out .= "</div>\n";

  return $out;
}
