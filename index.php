<?php 
/**
 * include core framework:
 * 1. dispatch the request to controller class.
 * 2. all models extends ActiveRecord
 * 3. using MicroTpl for template engine.
 */
require_once ('lib/dispatch/src/dispatch.php');
require_once ("lib/ar/ActiveRecord.php");
require_once ("lib/microtpl/MicroTpl.php");
/**
 * add error handles for error code.
 * render the error page, and send HTTP status code too.
 */
error_reporting (0);
error(404, function (){
  render('error', array('message' => 'Page not found.'));
});
error(500, function (){
  render('error', array('message' => 'Internal error.'));
});
/**
 * set error handler and exception handle to trgger the defind error callback.
 */
set_error_handler(function ($errno, $errstr, $file, $line){
  error(500, $errstr. ' in file: '. $file. ' on line'. $line. ' error number:'. $errno);
});
set_exception_handler(function ($e){
	error(500, $e->getMessage());
});
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

config(array(
  //'dispatch.url': 'http://tinymvc.git.vbox', 
  //'dispatch.router' => 'index.php',
  'dispatch.controllers' => 'controllers/',
  'dispatch.views' => 'views',
  'dispatch.models' => 'models/',
  'dispatch.db' => 'sqlite:./demo.sqlite',
  'dispatch.default_route' => '/contact/index',
  //'dispatch.flash_cookie' => '_F',
));
/**
 * configure the database, if no need just comment this line.
 */
ActiveRecord::setDb(new PDO(config('dispatch.db')));
/**
 * this handle is not safe!!!
 * 
 */
/*
on('*', '/f/:func(.*)', function ($func){
  initparams("/f/$func");
  if (!is_callable($func) error('404', 'Page not found');
  call_user_func_array($func, params());
});
*/
/**
 * @param string $prifix optional, for remove the controller and action name 
 */
function initparams($prifix='') {
	// init RESTful params
	if (!(in_array($_SERVER['REQUEST_METHOD'], array('GET', 'POST'))))
    params(request_body());
	// init other params from url, "/:controller/:action/parm1/value1/param2/value2"
	// can add parm1=>value1 and parm2=>value2 into the params Array.
	$values = array();
	$key = '';
	foreach(explode('/', trim(str_replace($prifix, '', path()), '/')) as $i=>$value)
		if ($i%2) $values[$key] = $value; else $key = $value;
	params($values);
}
/**
 * dispatch the request to target action.
 * will trigger error if controller not found or the action can not callable.
 */
on('*', '/:controller/:action(.*)', function ($controller, $action) {
  
  initparams("/$controller/$action");
  $controller.='Controller';
  $callback = array(new $controller,$action);
  if (!is_callable($callback)) error(404, 'Page not found');
  call_user_func_array(array(new $controller,$action), params());
});
/**
 * redirect the request "/controller" to "/controller/index".
 */
on('*', '/:controller', function ($controller){
  redirect("/$controller/index");
});
/**
 * redirect the request "/" to "/index/index".
 */
on('*', '/', function (){
  redirect(($route = config('dispatch.default_route')) ? $route : '/index/index');
});
/**
 * start the main process.
 */
dispatch();
?>