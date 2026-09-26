<?

require_once DIR_BASE . "model/model.php";
require_once DIR_BASE . "view/view.php";

class CBack
{

    public function __construct()
    {

    }

    public function init()
    {
        $host            = MRequest::post('host');
        $user            = MRequest::post('login');
        $pass            = MRequest::post('pass');
        $path            = MRequest::post('path');
        $site            = MRequest::post('site');
        $id              = MRequest::post('id');
        $bd              = MRequest::post('base');
        $chk_base        = MRequest::post('chk_base');
        $chk_site        = MRequest::post('chk_site');
        $protocol        = MRequest::post('protocol');

        MBaseList::load(MRequest::post('list'));
        $res             = [];
        $res['zip_site'] = '~';
        $res['error']    = '';
        $res['files']    = '';
        $res['result']   = '';
        $res['messages'] = '';
        if (!empty($chk_site) && !empty($path) && !empty($host) && !empty($user) && !empty($pass) && (isset($id)) && !empty($site)) {
            $res = MBack::getBackFiles($id, $host, $user, $pass, $path, $site,$protocol);
        }
        $res['dump_sql'] = '~';
        if (!empty($chk_base) && !empty($bd) && !empty($site) && (isset($id)) && !empty($path) && !empty($host) && !empty($user) && !empty($pass)) {
            $res2 = MBack::getBackSql($id, $host, $user, $pass, $path, $site,$bd,$protocol);
        }

        foreach ($res as $key => $val) {
            if (!empty($res2[$key])) {
                $res[$key] .= '<br/>'.$res2[$key];
                $res[$key]=trim($res[$key],'~');
            }
        }
        echo json_encode($res);

    }

}
