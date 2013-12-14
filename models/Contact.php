<?php 
class Contact extends ActiveRecord{
	public $table = 'contact';
	public $primaryKey = 'id';
	public $relations = array(
		'user' => array(self::BELONGS_TO, 'User', 'user_id'),
	);
}