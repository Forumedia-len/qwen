<?

require_once DIR_BASE . "model/classes/base_list.php";
require_once DIR_BASE . "model/classes/ftp_php.php";

class MBack
{
    public static $sqldir     = 'backup';
    public static $fname      = '';
    public static $create_zip = "create_zip.php";

    public static function getName($site = 'back')
    {
        if (empty(self::$fname)) {self::$fname = str_replace('/', '_', $site) . '_' . date("Y-m-d_H-i-s");}
        return self::$fname;
    }

    public static function getBackBD($id, $bd_column, $site_column)
    {
        MBaseList::load();
        $item = MBaseList::$arr_data[$id];
        exec('mysqldump --user="' . DB_ROOT . '" --password="' . DB_PASSWORD_ROOT . '" --host="' . DB_HOST . '" ' . $item[$bd_column] . ' > ' . DIR_BASE .self::$sqldir.'/' . self::getName($item[$site_column]).'.sql');
        return is_file(DIR_BASE .self::$sqldir.'/' . self::getName($item[$site_column]).'.sql')?(self::getName($item[$site_column]).'.sql'):' ~ ';
    }

    public static function getBackFiles($id,$host_column, $user_column, $pass_column, $dir_column, $site_column,$protocol_column)
    {
        MBaseList::load();
        $item = MBaseList::$arr_data[$id];
        
        $host=$item[$host_column]; 
        $user=$item[$user_column]; 
        $pass=$item[$pass_column]; 
        $dir=$item[$dir_column]; 
        $site=$item[$site_column];
        $protocol=$item[$protocol_column];
        MFTP::$php_base = 'phpbackcomm.php';
        MFTP::$dir_base = 'update_comm';
        $fname          = self::getName($item[$site_column]) . '.zip';
        $php_code       = '<?
        $fname="' . $fname . '";
        require_once "' . self::$create_zip . '";?>';

        ob_start();

        $result['error']    = '';
        $result['files']    = '';
        $result['result']   = '';
        $result['messages'] = '';
        $result['zip_site'] = '~';

        if ($ftp = ftp_connect($host)) {
            if ($fp = ftp_login($ftp, $user, $pass)) {
                if (ftp_pasv($ftp, true)) {
                    if (MFTP::ftpchdir($ftp, trim($dir . '/' . MFTP::$dir_base, '/'))) {
                        // загружаем скрипт на сервер
                        $name = self::$create_zip;
                        if (!empty($fp)) {
                            if (ftp_put($ftp, $name, DIR_BASE . MFTP::$dir_base . '/' . $name, FTP_BINARY)) {
                                $result['files'] .= $name . ', ';
                            };
                        }

                        // выполняем комманду
                        if (!empty(trim($php_code))) {
                            $res = file_put_contents(DIR_BASE . MFTP::$dir_base . '/' . MFTP::$php_base, $php_code);
                            if (ftp_put($ftp, MFTP::$php_base, DIR_BASE . MFTP::$dir_base . '/' . MFTP::$php_base, FTP_BINARY)) {
                                $result['files'] .= MFTP::$php_base;
                            };var_dump($protocol);
                            if (!empty(trim($site))) {
                                $result['result'] = MFTP::phpStart($site,$protocol);
                            } else {
                                $result['result'] = '';
                            }
                        }
                       
                        // выгружаем архив
                        ftp_get($ftp, DIR_BASE . '/' . self::$sqldir . '/' . $fname, $fname, FTP_BINARY);
                        if(is_file(DIR_BASE . '/' . self::$sqldir . '/' . $fname)){$result['zip_site']=$fname;}

                        //удаляем загруженные файлы и директорию
                        if (!empty($result['files'])) {
                            $fl[] = $name; // скрипт
                            $fl[] = $fname; // архив
                            $fl[] = MFTP::$php_base; // комманда
                            foreach ($fl as $item) {
                                ftp_delete($ftp, $item);
                            }
                            if (ftp_chdir($ftp, '..')) {ftp_rmdir($ftp, MFTP::$dir_base);}
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

    public static function getBackSql($id,$host_column, $user_column, $pass_column, $dir_column, $site_column,$bd_column,$protocol_column)
    {
        MBaseList::load();
        $item = MBaseList::$arr_data[$id];
        $host=$item[$host_column]; 
        $user=$item[$user_column]; 
        $pass=$item[$pass_column]; 
        $dir=$item[$dir_column]; 
        $site=$item[$site_column];
        $protocol=$item[$protocol_column];
        MFTP::$php_base = 'sqlbackcomm.php';
        MFTP::$dir_base = 'update_comm';
        $fname          = self::getName($item[$site_column]) . '.sql';
        $php_code       = '<? exec(\'mysqldump --user="' . DB_ROOT . '" --password="' . DB_PASSWORD_ROOT . '" --host="' . DB_HOST . '" ' . $item[$bd_column] . ' > ' .self::getName($item[$site_column]).'.sql\') ?>';

        ob_start();

        $result['error']    = '';
        $result['files']    = '';
        $result['result']   = '';
        $result['messages'] = '';
        $result['dump_sql'] = '~';
        if ($ftp = ftp_connect($host)) {
            if ($fp = ftp_login($ftp, $user, $pass)) {
                if (ftp_pasv($ftp, true)) {
                    if (MFTP::ftpchdir($ftp, trim($dir . '/' . MFTP::$dir_base, '/'))) {
                        
                        // выполняем комманду
                        if (!empty(trim($php_code))) {
                            $res = file_put_contents(DIR_BASE . MFTP::$dir_base . '/' . MFTP::$php_base, $php_code);
                            if (ftp_put($ftp, MFTP::$php_base, DIR_BASE . MFTP::$dir_base . '/' . MFTP::$php_base, FTP_BINARY)) {
                                $result['files'] .= MFTP::$php_base;
                            };
                            if (!empty(trim($site))) {
                                $result['result'] = MFTP::phpStart($site,$protocol);
                            } else {
                                $result['result'] = '';
                            }
                        }
                        // выгружаем архив
                        ftp_get($ftp, DIR_BASE . '/' . self::$sqldir . '/' . $fname, $fname, FTP_BINARY);
                        if(is_file(DIR_BASE . '/' . self::$sqldir . '/' . $fname)){$result['dump_sql']=$fname;}

                        //удаляем загруженные файлы и директорию
                        if (!empty($result['files'])) {
                            $fl[] = $fname; // архив
                            $fl[] = MFTP::$php_base; // комманда
                            foreach ($fl as $item) {
                                ftp_delete($ftp, $item);
                            }
                            if (ftp_chdir($ftp, '..')) {ftp_rmdir($ftp, MFTP::$dir_base);}
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

}
