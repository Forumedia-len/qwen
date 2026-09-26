<?

require_once DIR_BASE."model/model.php";
require_once DIR_BASE."view/view.php";

class CList{

	function __construct() 
	{	

	}

	function init()
	{
		MBaseList::load(MRequest::post('list'));
		if(MRequest::post('insert')){
			$data=$_REQUEST;
			if(!empty($data['action'])){unset($data['action']);}
			if(!empty($data['insert'])){unset($data['insert']);}
			echo MBaseList::insert($data);
		}
		if(MRequest::post('update')){
			$id=MRequest::post('item_id');
			$data=$_REQUEST;
			unset($data['item_id']);
			if(!empty($data['action'])){unset($data['action']);}
			if(!empty($data['update'])){unset($data['update']);}
			echo MBaseList::update($id,$data);
		}
		if(MRequest::post('delete')){
			echo MBaseList::delete(MRequest::post('item_id'));
		}
		
	}

}
?>