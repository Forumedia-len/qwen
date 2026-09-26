<?

require_once DIR_BASE . "model/model.php";
require_once DIR_BASE . "view/view.php";

class CAutorize
{

    public function __construct()
    {

    }

    public function init()
    {
        if(isset($_REQUEST['exit'])){
         MAutorize::destroy();   
        }
        if (MAutorize::isAutorize()||MAutorize::create(MRequest::post('user',''),MRequest::post('password',''))) 
        {
            Model::redirect('?action=CHome');

        } else {
            View::load('autorize');
        }

    }

}
