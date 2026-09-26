<?

require_once DIR_BASE."model/model.php";
require_once DIR_BASE."view/view.php";

class CBD{

	function __construct() 
	{	

	}

	function init()
	{
		if(MRequest::post('command_sql')){
			$field_base=MRequest::post('field_base');
			$id=MRequest::post('id');
			$res=MUpdateBD::query($id,MRequest::post('command_sql'),$field_base);
			echo json_encode($res);
		}
		
	}

}
?>