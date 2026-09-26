<?
class WSQLComm
{

    public static $prev_sql = '';
    public static $mysqli   = '';
    public static $error    = '';

    public static function connect()
    {

        $servername   = HOST;
        $username     = USER ;
        $password     = PASSWORD_USER;
        self::$mysqli = new mysqli($servername, $username, $password);
        if (self::$mysqli->connect_error) {
            self::$error="Connection failed: " . self::$mysqli->connect_error;
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
}

WSQLComm::query($sql,true,BD);
echo WSQLComm::error;
?>
