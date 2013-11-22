<?php

class indexController {
	function index() {
		echo "Response from index action belongs to index controller.<br /><b>params: </b><br />";
		var_dump(func_get_args());
	}
}