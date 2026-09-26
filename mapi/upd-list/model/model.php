<?

require_once DIR_BASE."config.php";

class Model{

	public $classes_path=[];
	public static $model;

	function __construct() 
	{

	$dirs=array_diff(scandir(DIR_BASE.'model/classes'),['.','..']);
	
	foreach($dirs as $file)
	{
		if(is_file(DIR_BASE.'model/classes/'.$file)&&strpos($file, '.php'))
			{
				$this->classes_path[]=DIR_BASE.'model/classes/'.$file;
				require_once DIR_BASE.'model/classes/'.$file;
			}
	}	

	}

	static public function redirect($redirect_url)
	{
header('HTTP/1.1 200 OK');
header('Location: '.BASE_URL.$redirect_url);
	}


}

Model::$model=new Model;
?>