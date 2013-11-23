<?php 
class User extends ORM
{
	const TABLE_NAME = 'user';
	const SESSION_KEY = 'tinymvc-auth';
	public function __construct($tableName = self::TABLE_NAME, $data = null) {
		parent::__construct($tableName, $data);
	}
	public static function authorization($name, $password) {
		session(self::SESSION_KEY, $name);
		return true;
	}
	public static function authorized() {
		return session(self::SESSION_KEY);
	}
}