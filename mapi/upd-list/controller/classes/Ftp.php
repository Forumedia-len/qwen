<?

require_once DIR_BASE . "model/model.php";
require_once DIR_BASE . "view/view.php";

class CFTP
{

    public function __construct()
    {

    }

    public function init()
    {
        $host     = MRequest::post('host');
        $user     = MRequest::post('login');
        $pass     = MRequest::post('pass');
        $path      = MRequest::post('path');
        $site     = MRequest::post('site');
        $files    = $_REQUEST['files'];
        $php_code = MRequest::post('command_php');
        $id       = MRequest::post('id');
        $protocol        = MRequest::post('protocol','http');

        MBaseList::load(MRequest::post('list'));
        if (!empty($host) && !empty($user) && !empty($pass) && (isset($id))) {
            MBaseList::load();
            $item = MBaseList::$arr_data[$id];
            echo json_encode(MFTP::sendFiles($item[$host], 
                $item[$user], 
                $item[$pass], 
                $item[$path], 
                $files, 
                $php_code, 
                $item[$site],
                $item[$protocol]));
        }

    }

}
