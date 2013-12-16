<?php
/**
 * include core framework:
 * 1. dispatch the request to controller class.
 * 2. all models extends ActiveRecord
 * 3. using MicroTpl for template engine.
 */
require_once ('dispatch/src/dispatch.php');
require_once ("ar/ActiveRecord.php");
require_once ("microtpl/MicroTpl.php");

/**
 * helper function to render tamplate using MicroTpl engine.
 */
function t($view, $locals = array(), $layout = 'layout') {
	MicroTpl::render(config('dispatch.views') . DIRECTORY_SEPARATOR . $view. config('dispatch.views.stuff'), 
	$locals, config('dispatch.views') . DIRECTORY_SEPARATOR . $layout. config('dispatch.views.stuff'));
}
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
 * main class.
 * 1. set error hander and exception hander.
 * 2. config the database.
 * 3. add default route.
 * 4. call main function dispatch();
 */
class WebApplication {
	public function __construct($config = 'config.ini') {
		if (is_string($config)) config('source', $config);
		else if (is_array($config)) config($config);
		$this->setErrorHandler();
		$this->setDB();
		$this->setRoutes();
	}
	public function setErrorHandler() {
		/**
		 * add error handles for error code.
		 * render the error page, and send HTTP status code too.
		 */
		error(404, function (){
			t('error', array('message' => 'Page not found.'));
		});
		error(500, function (){
			t('error', array('message' => 'Internal error.'));
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
	}
	/**
	 * configure the database, if no need just comment this line.
	 */	
	public function setDB() {
		if ($db = config('dispatch.db')) ActiveRecord::setDb(new PDO($db));		
	}
	public function setRoutes() {
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
	}
	/**
	 * start the main process.
	 */	
	public function run() {
		dispatch();
	}
}
?>