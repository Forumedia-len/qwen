<?

class MBaseList
{

    // 0-название сайта
    // 1-база данных
    // 2-префикс базы данных
    // 3-ftp
    // 4-ftp пользователь
    // 5-ftp пароль

    public static $list_file_path = DIR_BASE .'csv/'.'list.csv';
    public static $arr_data       = [];
    protected static $flg_load    = false;
    public static $header = [];

    public static function getLists($curr)
    {
        $lists=array_diff(scandir(DIR_BASE . 'csv'), array('..', '.'));
        foreach($lists as &$item)
        {  $temp=[];
            if(is_file(DIR_BASE.'csv/'.$item)&&strpos($item,'.csv')){
            $temp['name']=str_replace('.csv','',$item);
            if($temp['name']==$curr)$temp['current']=true;
            $item=$temp;}
        }
        return $lists; 
    }

    public static function load($list='')
    {
        if (self::$flg_load) {return true;}
        {if(!empty($list)){self::$list_file_path=DIR_BASE .'csv/'.$list.'.csv';}
            //происходит подгрузка
            $CsvString = file_get_contents(self::$list_file_path);
            
            $Data      = str_getcsv($CsvString, "\n"); //parse the rows
            self::$header = str_getcsv($Data[0], ",");
            unset($Data[0]);
            foreach ($Data as $Row) {
                $str = str_getcsv($Row, ",");
                if (!empty($str)) {
                    foreach(self::$header as $hkey=>&$val)
                    { $val=trim($val);
                        $data[$val]= trim($str[$hkey]);}
                    self::$arr_data[]=$data;
                }
            }
            self::$flg_load=true;
        }
    }

    public static function save()
    {
        if (self::$flg_load) {
            //можно сохранять
            $out = '';
            $header=array_flip(self::$header);
            
            $out .= join(',', self::$header) . "\n";

            foreach (self::$arr_data as $str) {
                $data=[];
                foreach($str as $key=>$val){
                    $data[$header[$key]]=$val;
                }
                $out .= join(',', $data) . "\n";
            }
            $content = /*"\xEF\xBB\xBF" .*/ $out;
            return file_put_contents(self::$list_file_path, $content);
        }
    }

    public static function update($index, array $params)
    {
        if(empty(self::$flg_load)){self::load();}
        if (self::$flg_load) {
            if (!empty(self::$arr_data[$index])) {
                foreach (self::$arr_data[$index] as $key => &$str) {
                    if (!empty($params[$key])) {
                        $str = $params[$key];
                    }
                }
            }
            self::$arr_data[$index] = $params;
            return self::save();
        }
        return false;
    }

    public static function insert(array $params)
    {

        if(empty(self::$flg_load)){self::load();}
        if (self::$flg_load) {
            $max = count(self::$header);
            $add = [];
            for ($i = 0; $i < $max; $i++) {
                if (!empty($params[self::$header[$i]])) {
                    $add[self::$header[$i]] = $params[self::$header[$i]];
                } else {
                    $add[self::$header[$i]] = "";
                }}
            self::$arr_data[] = $add;
           return self::save();
        }
        return false;

    }

    public static function delete($index)
    {
        if(empty(self::$flg_load)){self::load();}
        if (self::$flg_load) {
            if (!empty(self::$arr_data[$index])) 
            {
                unset(self::$arr_data[$index]);
                return self::save();
            }
        }
        return false; 
    }

}
