<?

class MRequest
{
    public static function get($param,$value='')
    {
        return isset($_GET[$param])?(trim($_GET[$param])):$value;
    }

    public static function post($param,$value='')
    {
        return isset($_POST[$param])?(trim($_POST[$param])):$value;
    }

    public static function request($param,$value='')
    {
        return isset($_REQUEST[$param])?(trim($_REQUEST[$param])):$value;
    }

}
