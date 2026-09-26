<?

require_once DIR_BASE . "model/classes/base_list.php";

function trim_value(&$value)
{
    $value = trim($value,'./');
}

class MFTP
{
    public static $php_base  = 'phpcomm.php';
    public static $dir_base  = 'update_files';
    public static $downloads = 'downloads';
    public static $files     = [];

    public static function getList()
    {
        self::$files = array_diff(scandir(DIR_BASE . self::$dir_base), array('..', '.', self::$php_base));
        return self::$files;
    }

    public static function ftpchdir($ftpcon, $dir)
    {
        if (!empty(trim($dir))) {
            $dirs = explode('/', trim($dir, '/'));
            foreach ($dirs as $part) {

                if (!ftp_chdir($ftpcon, $part)) {
                    if (ftp_mkdir($ftpcon, $part)) {ftp_chdir($ftpcon, $part);} else {return false;}
                }
            }}
        return true;
    }

/**
 * Отправляем комманду на сервер, запускаем её, если получились файлы, то забираем их, удаляем все файлы и временную директорию
 * @param  [type] $host     [description]
 * @param  [type] $user     [description]
 * @param  [type] $pass     [description]
 * @param  [type] $dir      [description]
 * @param  array  $files    [description]
 * @param  string $php_code [description]
 * @param  string $site     [description]
 * @param  [type] $protocol [description]
 * @return [type]           [description]
 */
    public static function sendFiles($host, $user, $pass, $dir, $files = [], $php_code = "", $site = "site", $protocol= 'https')
    {
        ob_start();

        $result['error']    = '';
        $result['files']    = '';
        $result['result']   = '';
        $result['messages'] = '';
        $result['downloads']= '';
        if ($ftp = ftp_connect($host)) {
            if ($fp = ftp_login($ftp, $user, $pass)) {
                if (ftp_pasv($ftp, true)) {
                    if (self::ftpchdir($ftp, trim($dir . '/' . self::$dir_base, '/'))) {
                        // загружаем файлы на сервер
                        foreach ($files as $name) {
                            if (!empty($fp)) {
                                if (ftp_put($ftp, $name, DIR_BASE . self::$dir_base . '/' . $name, FTP_BINARY)) {
                                    $result['files'] .= $name . ', ';
                                };
                            }
                        }
                        // выполняем комманду
                        if (!empty(trim($php_code))) {
                            $res = file_put_contents(DIR_BASE . self::$dir_base . '/' . self::$php_base, $php_code);
                            if (ftp_put($ftp, self::$php_base, DIR_BASE . self::$dir_base . '/' . self::$php_base, FTP_BINARY)) {
                                $result['files'] .= self::$php_base;
                            };
                            if (!empty(trim($site))) {
                                $result['result'] = self::phpStart($site, $protocol);
                            } else {
                                $result['result'] = '';
                            }
                        }
                        //смотрим на различия
                        $files[]   = self::$php_base;
                        $contents  = array_diff(ftp_nlist($ftp,''),['.','..']);
                        $downloads = array_diff($contents, $files);

                        if (!empty($downloads)) {
                            $result['downloads']=implode(',',$downloads);
                            //забираем новые файлы
                            $dir_name = $site . '_' . date('Y-m-d_H-i-s');
                            $result['downloads_dir']=$dir_name;
                            if (!is_dir(DIR_BASE  . self::$downloads . '/' . $dir_name)) 
                                {mkdir(DIR_BASE  . self::$downloads . '/' . $dir_name, 755);}
                            foreach ($downloads as $file) {
                                ftp_get($ftp, DIR_BASE . self::$downloads . '/' . $dir_name . '/' . $file, $file, FTP_BINARY);
                            }}
                        
                        //удаляем загруженные файлы и директорию
                        if (!empty($contents)) {

                            foreach ($contents as $item) {
                                ftp_delete($ftp, $item);
                            }
                            if (ftp_chdir($ftp, '..')) {ftp_rmdir($ftp, self::$dir_base);}
                        }

                        if (!empty($result['files'])) {$result['files'] = trim($result['files'], ', ');}
                    } else {
                        $result['error'] = 'error create dir';}
                } else { $result['error'] = 'error ftp_pasv';}
            } else { $result['error'] = 'error login';}
            ftp_close($ftp);
        } else { $result['error'] = 'error ftp host';}
        $result['messages'] = ob_get_clean();
        return $result;
    }

    public static function phpStart($site, $protocol = 'http')
    {

        return file_get_contents($protocol . "://$site/" . self::$dir_base . "/" . self::$php_base);
    }

}
