<?

require_once DIR_BASE . "model/classes/ftp_php.php";
require_once DIR_BASE . "model/classes/mysql.php";

class MHome
{
    public static function createPage($current)
    {
        MBaseList::load($current);
        $data['list']=MBaseList::$arr_data;
        $data['tab_list']=MBaseList::getLists($current);
        $data['header']=MBaseList::$header;
        $data['sql']=file_get_contents(DIR_BASE.'sql.txt');
        $data['php']=file_get_contents(DIR_BASE.'php.txt');
        $data['site_sql']=file_get_contents(DIR_BASE.'replase_part/site.sql');
        $data['files']=MFTP::getList();
        return $data;
    }

}
