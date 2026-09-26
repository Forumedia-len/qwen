<?

require_once DIR_BASE . "model/classes/base_list.php";
require_once DIR_BASE . "model/classes/mysql.php";
require_once DIR_BASE . "model/classes/ftp_php.php";

class MReplase
{

    public static $host_column     = 'ftp_host';
    public static $user_column     = 'ftp_user';
    public static $pass_column     = 'ftp_pass';
    public static $dir_column      = 'ftp_path';
    public static $site_column     = 'site';
    public static $protocol_column = 'protocol';
    public static $bd_column       = 'base';
    public static $bd_host_column  = 'bd_host';
    public static $bd_user_column  = 'bd_user';
    public static $bd_pass_column  = 'bd_pass';
    public static $site_dir        = "site";
    public static $base_dir        = "replase_part";

    public static function prepare($id, $sql)
    {
        MBaseList::load();
        $item = MBaseList::$arr_data[$id];
        foreach ($item as $key => $val) {$sql = str_replace('%' . $key . '%', $val, $sql);}
        return $sql;
    }

    public static function createZip()
    {
        $zip      = new ZipArchive();
        $dir      = DIR_BASE . self::$base_dir;
        $ret      = $zip->open($dir . '/' . self::$site_dir . '.zip', ZipArchive::CREATE | ZipArchive::OVERWRITE);
        $add_path = self::$site_dir;
        function rec_repeat($path, &$zip, &$add_path)
        {
            if (is_dir($path)) {
                $elems = array_diff(scandir($path), array('..', '.'));
                $delta = str_replace(DIR_BASE . 'replase_part/site', '', $path);

                foreach ($elems as $el) {
                    if (is_dir($path . '/' . $el)) {
                        rec_repeat($path . '/' . $el, $zip, $add_path);
                    }
                    if (is_file($path . '/' . $el)) {
                        $zip->addFile($path . '/' . $el, $add_path . $delta . '/' . $el);
                    }
                }
            }
        }
        rec_repeat($dir . '/' . self::$site_dir, $zip, $add_path);
        $zip->close();
    }

    public static function replaseFtp($id, $chk_sql, $chk_files)
    {
        MFTP::$php_base  = 'replasecomm.php';
        MFTP::$dir_base  = 'replase_part';
        MFTP::$downloads = 'replase_part/old';
        MBaseList::load();
        $item  = MBaseList::$arr_data[$id];
        $fname = $item[self::$site_column];

        $comm = '<? $fname="' . $fname . '";
                        define("HOST","' . ((empty($item[self::$bd_host_column])) ? DB_HOST : '') . '");
                        define("USER","' . ((empty($item[self::$bd_user_column])) ? DB_ROOT : '') . '");
                        define("PASSWORD_USER","' . ((empty($item[self::$bd_pass_column])) ? DB_PASSWORD_ROOT : '') . '");
                        define("BD","' . $item[self::$bd_column] . '");';
        $sql = '';
        if ($chk_sql && is_file(DIR_BASE . self::$base_dir . '/' . self::$site_dir . '.sql')) {
            $sql = file_get_contents(DIR_BASE . self::$base_dir . '/' . self::$site_dir . '.sql');
            $sql = self::prepare($id, $sql);
            $comm .= '$sql=\'' . str_replace("'", '"', $sql) . '\';';
        }
        $comm .= 'require_once "replase.php";?>';

        if ($chk_files) {
            self::createZip();
        }

        if ($chk_files && is_file(DIR_BASE . self::$base_dir . '/' . self::$site_dir . '.zip')) {
            $files[] = self::$site_dir . '.zip';
        }

        if (!empty($files) || !empty($sql)) {
            $files[] = 'replase.php';
            $result  = MFTP::sendFiles(
                $item[self::$host_column],
                $item[self::$user_column],
                $item[self::$pass_column],
                $item[self::$dir_column],
                $files,
                $comm,
                $item[self::$site_column],
                $item[self::$protocol_column]);
            return $result;
            //if(is_file(DIR_BASE . self::$base_dir . '/'.self::$site_dir.'.zip'))  unlink(DIR_BASE . self::$base_dir . '/'.self::$site_dir.'.zip');
        }
    }

}
