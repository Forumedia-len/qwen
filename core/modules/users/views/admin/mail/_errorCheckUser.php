<style>
  th {
    background: #0d6efd;
    color: #fff;
  }

  td {
    background: #eee;
    padding: 5px;
  }
</style>
<p>На сайте <?= $host ?> пользователем <?= $username ?> был произведен перебор пароля. </p>
<p>Пробовали ввести такие данные:</p>
<table>
  <tr>
    <th>Пользователь</th>
    <th>Пароль</th>
    <th>Время ввода</th>
  </tr>
  <?php foreach ($userLog as $row) : ?>
    <tr>
      <td><?= $row[0] ?></td>
      <td><?= $row[1] ?></td>
      <td><?= $row[2] ?></td>
    </tr>
  <?php endforeach; ?>
</table>

<table>
  <tr>
    <th>Параметр</th>
    <th>Значение</th>
  </tr>
  <?php foreach ($_SERVER as $key=>$row) : ?>
    <tr>
      <td><?= $key ?></td>
      <td><?= $row ?></td>
    </tr>
  <?php endforeach; ?>
</table>
