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
	'dispatch.db' => 'sqlite:./demo.sqlite'
));

ORM::configure(config('dispatch.db'));
/*
this handle is not safe!!!
on('*', '/f/:func(.*)', function ($func){
  initparams("/f/$func");
  call_user_func_array($func, params());
});
*/
on('*', '/:controller/:action(.*)', function ($controller, $action) {
  initparams("/$controller/$action");
  $controller.='Controller';
  call_user_func_array(array(new $controller,$action), params());
});
on('*', '/:controller', function ($controller){
  redirect("/$controller/index");
});
on('*', '/', function (){
  redirect('/index/index');
});
dispatch();
?>