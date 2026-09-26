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
<p>User <?= $username ?>  versuchte auf der Website <?= $host ?> das Passwort mehrmals einzugeben.</p>
<p>Es wurden folgende Daten eingegeben:</p>
<table>
  <tr>
    <th>User</th>
    <th>Passwort</th>
    <th>Eintrittszeit</th>
  </tr>
  <?php foreach ($userLog as $row) : ?>
    <tr>
      <td><?= $row[0] ?></td>
      <td><?= $row[1] ?></td>
      <td><?= $row[2] ?></td>
    </tr>
  <?php endforeach; ?>
</table>

