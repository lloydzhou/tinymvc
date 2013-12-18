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
	$values = array(); $key = '';
	foreach(explode('/', trim(str_replace($prifix, '', path()), '/')) as $i=>$value)
		if ($i%2) $values[$key] = $value; else $key = $value;
			params($values);

}
function createurl($route, $param = array(), $abslute = false) {
	return str_replace(array('//', '&', '='), '/', site(!$abslute). $route. '/'. http_build_query($param));
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
		error(404, array($this, 'error404'));
		error(500, array($this, 'error500'));
		/**
		 * set error handler and exception handle to trgger the defind error callback.
		 */
		set_error_handler(array($this, 'errorhander'));
		set_exception_handler(array($this, 'exceptionhander'));		
	}
	/**
	 * configure the database, if no need just comment this line.
	 */	
	public function setDB() {
		if ($db = config('dispatch.db')) ActiveRecord::setDb(new PDO($db, config('dispatch.db.username'), config('dispatch.db.password')));		
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
		on('*', '/:controller/:action(.*)', array($this, 'main'));
		/**
		 * redirect the request "/controller" to "/controller/index".
		 */
		on('*', '/:controller', array($this, 'redirectindex'));
		/**
		 * redirect the request "/" to "/index/index".
		 */
		on('*', '/', array($this, 'redirect'));		
	}
	/**
	 * start the main process.
	 */	
	public function run() {
		dispatch();
	}
	/**
	 * helper functions
	 */
	function error404 () {
		t('error', array('message' => 'Page not found.'));
	}
	function error500() {
		t('error', array('message' => 'Internal error.'));
	}
	function errorhander($errno, $errstr, $file, $line) {
		error(500, $errstr. ' in file: '. $file. ' on line'. $line. ' error number:'. $errno);
	}
	function exceptionhander($e) {
		error(500, $e->getMessage());
	}
	function main($controller, $action) {
		initparams("/$controller/$action");
		$controller.='Controller';
		if (!is_callable($callback = array(new $controller,$action))) error(404, 'Page not found');
		call_user_func_array($callback, params());
	}
	function redirectindex($controller){
		redirect(site(true). "/$controller/index");
	}
	function redirect(){
		redirect(site(true). (($route = config('dispatch.route.default')) ? $route : '/index/index'));
	}
}
?>