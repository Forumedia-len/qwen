<?

require_once DIR_BASE."model/model.php";
require_once DIR_BASE."view/view.php";

class CBase{

	function __construct() 
	{	

	}

	function init($view='home')
	{
		View::load($view);
	}

}
?>