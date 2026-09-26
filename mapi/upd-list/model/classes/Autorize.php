<?
require_once DIR_BASE . "model/classes/mysql.php";

class MAutorize
{
    public static function isAutorize()
    {
        return !empty($_SESSION['admin_session']);
    }

    public static function create($user, $password)
    {
        if (($user == ADMIN_USER) && (md5($password) == ADMIN_PASSWORD)) {
            $_SESSION['admin_session'] = 'Y';
            return true;
        } else {
            return false;
        }
    }

    public static function destroy()
    {
        if(isset($_SESSION['admin_session']))
            unset($_SESSION['admin_session']);
    }

}
