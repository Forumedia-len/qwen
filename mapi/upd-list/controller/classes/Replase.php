<?

require_once DIR_BASE."model/model.php";
require_once DIR_BASE."view/view.php";

class CReplase{

	function __construct() 
	{	

	}

	function init()
	{
		MReplase::$host_column     = MRequest::post('host');
		MReplase::$user_column     = MRequest::post('login');
		MReplase::$pass_column     = MRequest::post('pass');
		MReplase::$dir_column      = MRequest::post('path');
		MReplase::$site_column     = MRequest::post('site');
		MReplase::$protocol_column = MRequest::post('protocol');
		MReplase::$bd_column       = MRequest::post('base');
		$chk_base        = MRequest::post('chk_base');
		$chk_site        = MRequest::post('chk_site');
		$id              = MRequest::post('id');
		MBaseList::load(MRequest::post('list'));
		$res             = [];
		$res['error']    = '';
		$res['files']    = '';
		$res['result']   = '';
		$res['messages'] = '';
		$res['downloads']= '';

		if ((isset($id)) && (!empty($chk_base)||!empty($chk_site))) 
		{
			$res = MReplase::replaseFtp($id,$chk_base,$chk_site);
		}

		echo json_encode($res);
	}

}
?>