<?php
/**
 * @var int    $page
 * @var int    $pages_count
 * @var string $baseUrl
 */

$prevImg = base_url(paths()->getAssetsDir('images/page_previous.gif'));
$nextImg = base_url(paths()->getAssetsDir('images/page_next.gif'));
?>

<?php if ($pages_count > 1): ?>
  <table align="center">
    <tr>
      <?php if ($page > 1): ?>
        <td><a href="<?= $baseUrl ?>/page/<?= $page - 1 ?>"><img src="<?= $prevImg ?>" hspace="2" border="0" alt=""></a></td>
      <?php endif; ?>
      <td>
        <?php for ($i = 1; $i <= $pages_count; $i++): ?>
          <?php if ($i == $page): ?>
            <b><?= $i ?></b>
          <?php else: ?>
            <a href="<?= $baseUrl ?>/page/<?= $i ?>"><?= $i ?></a>
          <?php endif; ?>
        <?php endfor; ?>
      </td>
      <?php if (($page + 1) <= $pages_count): ?>
        <td><a href="<?= $baseUrl ?>/page/<?= $page + 1 ?>"><img src="<?= $nextImg ?>" hspace="2" border="0" alt=""></a></td>
      <?php endif; ?>
    </tr>
  </table>
  <br>
<?php endif; ?>