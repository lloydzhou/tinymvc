<?php 
class Contact extends Model
{
	const TABLE_NAME = 'contact';
	public function __construct($tableName = self::TABLE_NAME, $data = null) {
		parent::__construct($tableName, $data);
	}
}