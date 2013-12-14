<?php 
class User extends ActiveRecord{
	public $table = 'user';
	public $primaryKey = 'id';
	public $relations = array(
		'contacts' => array(self::HAS_MANY, 'Contact', 'user_id'),
		'contact' => array(self::HAS_ONE, 'Contact', 'user_id', 'where' => '1', 'order' => 'id desc'),
	);
	public static function authorization($name, $password) {
		session(self::SESSION_KEY, $name);
		return true;
	}
	public static function authorized() {
		return session(self::SESSION_KEY);
	}	
}
