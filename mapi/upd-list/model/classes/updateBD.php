<?

require_once DIR_BASE . "model/classes/base_list.php";
require_once DIR_BASE . "model/classes/mysql.php";
require_once DIR_BASE . "model/classes/ftp_php.php";

class MUpdateBD
{

    public static $host_column='ftp_host';
    public static $user_column='ftp_user';
    public static $pass_column='ftp_pass';
    public static $dir_column='ftp_path';
    public static $site_column='site';
    public static $protocol_column='http';
    public static $bd_column='base';
    public static $bd_host_column='bd_host';
    public static $bd_user_column='bd_user';
    public static $bd_pass_column='bd_pass';

    public static function prepare($index,$sql)
    {
        MBaseList::load();
        $item=MBaseList::$arr_data[$index];
        foreach($item as $key=>$val)
            {$sql=str_replace('%'.$key.'%', $val, $sql);}
        return $sql;
    }

    public static function query($index,$sql,$field_base)
    {
        $query_temp = self::prepare($index,$sql);
        MBaseList::load();
        $item=MBaseList::$arr_data[$index];
        $res['query']  = htmlspecialchars($query_temp);
        ob_start();
        $res['result'] = WSQL::query($query_temp, true, $item[$field_base]);
        $output = ob_get_clean();
        if(!empty($output)){$res['result'].='<br/>'.$output;}
        $res['error'] = htmlspecialchars(WSQL::$error);
        return $res;
    }


    public static function queryFtp($id,$sql)
    {
        MFTP::$php_base = 'sqlcomm.php';
        MFTP::$dir_base = 'update_comm';
        MBaseList::load();
        $item=MBaseList::$arr_data[$id];
        $psql=prepare($id,$sql);
        $comm='<?   $sql=\''.str_replace("'",'"',$psql).'\';
                    define("HOST","'.((empty($item[self::$bd_host_column]))?DB_HOST:'').'");
                    define("USER","'.((empty($item[self::$bd_user_column]))?DB_ROOT:'').'");
                    define("PASSWORD_USER","'.((empty($item[self::$bd_pass_column]))?DB_PASSWORD_ROOT:'').'");
                    define("BD","'.$item[self::$bd_column].'");
                    require_once "sqlcomm.php";?>';

        return MFTP::sendFiles(
            $item[self::$host_column], 
            $item[self::$user_column], 
            $item[self::$pass_column], 
            $item[self::$dir_column], 
            ['comm_sql.php'], 
            $comm, 
            $item[self::$site_column],
            $item[self::$protocol_column]);
    }

}
