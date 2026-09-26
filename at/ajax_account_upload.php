<?php
if (isset($_GET['action']) && $_GET['action'] == 'remove' && isset($_GET['upload_file_name'])) {

  $template_path = pathAs(ROOT_PATH . $_GET['upload_file_name']);
  unlink($template_path);
} elseif (isset($_GET['file_name']) && isset($_GET['upload_file_name'])) {
  $template_path = pathAs(ROOT_PATH . $_GET['upload_file_name']);
  $dir = dirname($template_path);
  if (!file_exists($dir)) {
    mkdir($dir, 0755, true);
  }
  $file_name = $_GET['file_name'];
  if (isset($_FILES[$file_name]) && $_FILES[$file_name]['error'] == 0) {
    if (copy($_FILES[$file_name]['tmp_name'], $template_path)) {
      echo 'ok';
    }
  }
}
