<?
$path = str_replace('/update_comm/create_zip.php', '', __FILE__);

$arrPath   = explode('/', trim($path));
$last      = array_pop($arrPath);
$fname     = isset($fname) ? $fname : $last . '.zip';
$startPath = implode('/', $arrPath);
$zip       = new ZipArchive();
$dir       = str_replace('/create_zip.php', '', __FILE__);
$options   = array('remove_path' => $path, "add_path" => $last);
$ret       = $zip->open($dir . '/' . $fname, ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($ret !== true) 
{
    printf('Ошибка с кодом %d', $ret);
} else {

    function addToZip($listPath, $path, &$zip, $options)
    {
        if (is_dir($path . '/' . $listPath)) {
            $elems = array_diff(scandir($path . '/' . $listPath), array('..', '.', 'update_files', 'test', 'update_comm', '.git','old','alt'));
            foreach ($elems as $el) {
                addToZip($el, $path . '/' . $listPath, $zip, $options);
            }
        } else {
            $zip->addFile($path . '/' . $listPath, $options['add_path'] . str_replace($options['remove_path'], '', $path . '/' . $listPath));
        }
    }
    addToZip($last, $startPath, $zip, $options);
    $zip->close();
}
