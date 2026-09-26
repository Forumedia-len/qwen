<?

require_once DIR_BASE."model/model.php";
require_once DIR_BASE."view/view.php";

class CHome{

	function __construct() 
	{	

	}

	function init()
	{
		$data=MHome::createPage(MRequest::request('list','list'));
		View::load('home',$data);
	}

}
?>