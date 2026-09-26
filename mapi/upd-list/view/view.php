<?

require_once DIR_BASE . "model/model.php";

class View
{

    public static function load($view, $data = [])
    {
        require_once DIR_BASE . 'view/header.php';
        require_once DIR_BASE . 'view/views/' . $view . '.php';
        require_once DIR_BASE . 'view/footer.php';
    }

    public static function loadAjax($view, $data = [])
    {
        require_once DIR_BASE . 'view/views/' . $view . '.php';
    }

    public static function error($view, $data = [])
    {
        require_once DIR_BASE . 'view/header.php';
        require_once DIR_BASE . 'view/errors/' . $view . '.php';
        require_once DIR_BASE . 'view/footer.php';
    }

}
