<?

class WSQL
{

    public static $prev_sql = '';
    public static $mysqli   = '';
    public static $error    = '';

    public static function connect($is_admin = true)
    {

        $servername   = DB_HOST;
        $username     = !$is_admin ? DB_USER : DB_ROOT;
        $password     = !$is_admin ? DB_PASSWORD_USER : DB_PASSWORD_ROOT;
        self::$mysqli = new mysqli($servername, $username, $password);
        if (DEBUG && self::$mysqli->connect_error) {
            self::$error="Connection failed: " . self::$mysqli->connect_error;
        }

    }

    public static function query($query, $type = true, $db = '')
    {
        self::$prev_sql = $query;
        self::connect();
        if (!empty($db)) {self::$mysqli->select_db($db);} else {self::$mysqli->select_db(DB_DB);}

        $result = self::$mysqli->query($query);

        if (DEBUG && !$result) {
            self::$error = "error: " . self::$mysqli->error . ' sql: ' . self::$prev_sql;
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
