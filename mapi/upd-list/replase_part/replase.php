<?
error_reporting(E_ALL);
ini_set('display_errors', true);

class WSQLComm
{

    public static $prev_sql = '';
    public static $mysqli   = '';
    public static $error    = '';

    public static function connect()
    {

        $servername   = HOST;
        $username     = USER;
        $password     = PASSWORD_USER;
        self::$mysqli = new mysqli($servername, $username, $password);
        if (self::$mysqli->connect_error) {
            self::$error = "Connection failed: " . self::$mysqli->connect_error;
        }

    }

    public static function query($query, $type = true, $db = '')
    {
        self::$prev_sql = $query;
        self::connect();
        self::$mysqli->select_db($db);

        $result = self::$mysqli->query($query);

        if (!$result) {
            self::$error = "error: " . self::$mysqli->error . '<br/> sql: ' . self::$prev_sql;
        }
        if (!is_bool($result) && $result) {
            $res_array = [];
            if ($type) {
                while ($row = $result->fetch_assoc()) {
                    $res_array[] = $row;
                }} else {
                while ($row = $result->fetch_row()) {
                    $res_array[] = $row;
                }
            }
            return $res_array;}
        if (is_bool($result) && $result) {return true;}
        return false;
    }

    public static function get_last_error()
    {
        return self::$error;
    }

    public static function ger_result_string($result)
    {
        $mess = 'sql - ';
        if (is_array($result)) {$mess .= print_r($result);}
        if (is_bool($result) && ($result)) {$mess .= 'Ok';}
        if (is_bool($result) && (!$result)) {$mess .= 'error';}
        $mess .= '<br/>' . self::get_last_error();
        return $mess;
    }
}

//замена файлов в каталоге и создание архива
function rec_repeat($path, &$path_base, &$zip, &$add_path)
{
    if (is_dir($path)) {

        $elems = array_diff(scandir($path), array('..', '.'));
        $delta = str_replace($path_base . '/replase_part/site', '', $path);

        foreach ($elems as $el) {
            if (is_dir($path . '/' . $el)) {
                if (is_dir($path_base . $delta . '/' . $el)) {} else {

                    mkdir($path_base . $delta . '/' . $el);
                }
                rec_repeat($path . '/' . $el, $path_base, $zip, $add_path);
            }
            if (is_file($path . '/' . $el)) {
                if (is_file($path_base . $delta . '/' . $el)) {

                    $zip->addFile($path_base . $delta . '/' . $el, $add_path . $delta . '/' . $el);
                }

                copy($path . '/' . $el, $path_base . $delta . '/' . $el);
            }
        }
    }
}
//удаляем распакованное
function del_files($path)
{
    if (is_dir($path)) {

        $elems = array_diff(scandir($path), array('..', '.'));

        foreach ($elems as $el) {
            if (is_dir($path . '/' . $el)) {
                del_files($path . '/' . $el);
            }
            if (is_file($path . '/' . $el)) {

                unlink($path . '/' . $el);
            }
        }
        rmdir($path);
    }
}

ob_start();
$path_site = str_replace('/replase_part/replase.php', '', __FILE__);

$arrPath   = explode('/', trim($path_site));
$last      = array_pop($arrPath);
$fname     = isset($fname) ? $fname : $last;
$zip       = new ZipArchive();
$zip2      = new ZipArchive();
$dir       = str_replace('/replase.php', '', __FILE__);
$add_path  = $fname;
$ret       = $zip->open($dir . '/' . $fname.'.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
if ($ret !== true) {
    printf('Ошибка с кодом %d', $ret);
} else {

    if (is_file($dir . '/site.zip')) {
        $zip2->open($dir . '/site.zip');
        $zip2->extractTo($path_site . '/replase_part');
        $zip2->close();
    }

    if (is_dir($dir . '/site')) {
        rec_repeat($dir . '/site', $path_site, $zip, $add_path);
    }

    $result = '';
    $out='';

    if (!empty($sql)) {
        exec('mysqldump --user="' . USER . '" --password="' . PASSWORD_USER . '" --host="' . HOST . '" ' . BD . ' > ' . $dir . '/' . $fname . '.sql');
        $result = WSQLComm::query($sql, true, BD);
        $out    = WSQLComm::ger_result_string($result);
    }

    if(is_dir($dir . '/site')){
        del_files($dir . '/site');
    }

    $out.= '<br/>' . ob_get_clean();

    echo $out;

    if($zip->count()>0)$zip->close();
}
