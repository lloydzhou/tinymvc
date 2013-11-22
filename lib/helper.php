<?php 
/**
 * helper function to auto load controllers and models.
 * you can set controllers path buy using config('dispatch.controller', 'controllers');
 * and set models path buy using config('dispatch.model', 'models');
 */
function __autoload($classname) {
	if (file_exists($lib = 'lib'.DIRECTORY_SEPARATOR. $classname .".php"))
		require_once($lib);
	elseif (file_exists($controller = trim(config('dispatch.controllers')). DIRECTORY_SEPARATOR.$classname .".php"))
		require_once($controller);
	elseif (file_exists($model = trim(config('dispatch.models')). DIRECTORY_SEPARATOR. $classname .".php"))
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

