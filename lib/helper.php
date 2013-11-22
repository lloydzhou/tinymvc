<?php 
/**
 * helper function to auto load controllers and models.
 * you can set controllers path buy using config('dispatch.controller', 'controllers');
 * and set models path buy using config('dispatch.model', 'models');
 */
function __autoload($classname) {
	if (file_exists($controller = config('dispatch.controllers'). $classname .".php"))
		require_once($controller);
	elseif (file_exists($model = config('dispatch.models'). $classname .".php"))
		require_once($model);
    if (!class_exists($classname, false)) {
        error(500, "Unable to load class: $classname");
    }
}
/**
 * helper function to get the pathinfo.
 */
function path() {
  static $path;
  if (!$path) {
    $path = parse_url($path ? $path : $_SERVER['REQUEST_URI'], PHP_URL_PATH);
    $root = config('dispatch.router');
    $base = site(true);
    if ($base !== null)
      $path = preg_replace('@^'.preg_quote($base).'@', '', $path);
    if ($root)
      $path = preg_replace('@^/?'.preg_quote(trim($root, '/')).'@i', '', $path);
  }
  return $path;
}
/**
 * @param string $prifix optional, for remove the controller and action name 
 */
function initparams($prifix='') {
  // init TEST params
  params(request_body());
  // init other params from url, "/:controller/:action/parm1/value1/param2/value2"
  // can add parm1=>value1 and parm2=>value2 into the params Array.
  $values = array();
  $key = '';
  foreach(split('/', trim(str_replace($prifix, '', path()), '/')) as $i=>$value)
	if ($i%2) $values[$key] = $value; else $key = $value;
  params($values);
  
}
class Model {

	public $table;
	public $orm = null;

	public function __construct($tableName, $data = null) {
		$this->table = $tableName;
		$this->orm = ORM::for_table($tableName)->create($data);
	}
	public function __call($name, $arguments)
	{
		return call_user_func_array(array($this->orm, $name), $arguments);
	}
	public function __get($name)
	{
		return call_user_func(array($this->orm, '__get'), $name);
	}
	public function __set($name, $value)
	{
		return call_user_func(array($this->orm, '__set'), $name, $value);
	}
	public function insert($data = array())
	{
		return $this->_save($this->orm, $data);
	}
	public function update($data = array())
	{
		return $this->_save($this->orm, $data);
	}
	public function find($id = null)
	{
		$this->orm = ORM::for_table($this->table)->find_one($id);
		return $this;
	}
	public function findAll()
	{
		return ORM::for_table($this->table)->find_many();
	}
	protected function _save($orm, $data)
	{
		foreach($data as $key => $value)
			$orm->$key = $value;
		return $orm->save();
	}
}
