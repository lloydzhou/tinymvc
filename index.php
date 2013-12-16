<?php
error_reporting (0);
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
 * create instance of WebApplication, by using config array.
 * you can using config file like "config.ini".
 * @see more detail on "https://github.com/noodlehaus/dispatch"
 */
(new WebApplication(array(
  //'dispatch.url': 'http://tinymvc.git.vbox', 
  //'dispatch.router' => 'index.php',
  'dispatch.controllers' => 'controllers/',
  'dispatch.views' => 'views',
  'dispatch.views.stuff' => '.html',
  'dispatch.models' => 'models/',
  'dispatch.db' => 'sqlite:./demo.sqlite',
  'dispatch.default_route' => '/contact/index',
  //'dispatch.flash_cookie' => '_F',
)))->run();

?>