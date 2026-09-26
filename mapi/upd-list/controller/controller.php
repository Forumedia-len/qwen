<?

require_once DIR_BASE . "model/model.php";
require_once DIR_BASE . "view/view.php";

class Controller
{

    public $classes_path = [];

    public static $controller;

    public function __construct()
    {
        $dirs = array_diff(scandir(DIR_BASE . 'controller/classes'),['.','..']);
        foreach ($dirs as $file) {
            if (is_file(DIR_BASE . 'controller/classes/' . $file) && strpos($file, '.php')) {
                $this->classes_path[] = DIR_BASE . 'controller/classes/' . $file;
                require_once DIR_BASE . 'controller/classes/' . $file;
            }
        }
    }

    public function action()
    {
        if (MAutorize::isAutorize()) {
            $class = MRequest::request('action','CHome');
            $view  = MRequest::request('view','');
            $obj = new $class;
            if (!empty($view)) {
                $obj->init($view);} else { $obj->init();}
        } else {
            $obj = new CAutorize;
            $obj->init();
        }
    }

}

//session_start();

Controller::$controller = new Controller;
Controller::$controller->action();
