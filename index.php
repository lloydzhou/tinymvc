<?php 
require_once ('lib/dispatch/src/dispatch.php');
require_once ("lib/idiorm/idiorm.php");
require_once ("lib/helper.php");

config(array(
	//'dispatch.url': 'http://tinymvc.git.vbox', 
	//'dispatch.router' => 'index.php',
	'dispatch.controllers' => 'controllers/',
	'dispatch.views' => 'views',
	'dispatch.models' => 'models/',
	'dispatch.db' => 'sqlite:./demo.sqlite',
	'dispatch.default_route' => '/contact/index'
));
/**
 * configure the database, if no need just comment this line.
 */
 
ORM::configure(config('dispatch.db'));
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
/**
 *
 *
 */
error(404, function (){
	render('index_error', array('message' => 'Page not found.'));
});
error(500, function (){
	render('index_error', array('message' => 'Internal error.'));
});
/**
 * start the main process.
 */
dispatch();
?>