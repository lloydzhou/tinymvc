<?php 
class Base {
	public function __construct($config = array()) {
		foreach($config as $key => $val) $this->$key = $val;
	}
}
class Expressions extends Base {
	public $source = '';
	public $operator = '';
	public $target = '';
	public function __toString() {
		return $this->source. ' '. $this->operator. ' '. $this->target;
	}
}
class WrapExpressions extends Expressions {
	public $delimiter = ' ';
	public $startWrapper = '(';
	public $endWrapper = ')';
	public function __toString() {
		return $this->startWrapper. implode($this->delimiter, $this->target). $this->endWrapper;
	}
}
class Field extends Base {
	public $name;
	public $rawName;
	public $type;
	public $isPrimaryKey;
	public $isForeignKey;
	public $value;
	public static $operators = array(
		'equal' => '=', 'eq' => '=',
		'notequal' => '<>', 'ne' => '<>',
		'greaterthan' => '>', 'gt' => '>',
		'lessthan' => '', 'lt' => '<',
		'greaterthanorequal' => '>=', 'ge' => '>=',
		'lessthanorequal' => '<=', 'le' => '<=',
		'between' => 'BETWEEN',
		'like' => 'LIKE',
		'in' => 'IN'
	);
	protected function isnull($wrap=false) {
		return new Expressions(array('source'=>$this->name, 'operator'=>'IS', 'target'=>"null"));
	}
	protected function isnotnull($wrap=false) {
		return new Expressions(array('source'=>$this->name, 'operator'=>'IS NOT', 'target'=>"null"));
	}
	public function __call($name, $args) {
		$name = strtolower($name);
		if (method_exists($this,$name)) return call_user_func_array(array($this, $name), $args);
		if (in_array($name, array_keys(self::$operators))) 
			return new Expressions(array('source'=>$this->name, 'operator'=>self::$operators[$name], 
				'target'=>(is_array($args[0]) ? new WrapExpressions(array('startWrapper' =>"('", 'endWrapper' =>"')", 'delimiter' => "', '",'target' => $args[0])) : $args[0])));
	}
}
class BaseTable extends Base {
	protected $expressions = array();
	protected $warpOpen = false;
	public $where;
	public $select;
	public $limit;
	public $order;
	public $from;
	protected $fields = array();
	public $table;
	public $data = array();
	
	public function find() {
		$this->limit = '1';
		$this->getdata($this->getSql());
		return current($this->data);
	}
	public function findAll() {
		return $this->getdata($this->getSql());
	}
	public function getSql() {
		$sqls = array('select', 'insert', 'update', 'delete', 'from', 'where', 'limit', 'order');
		array_walk($sqls, function (&$n, $i, $o){$n = (null !== $o->$n) ? strtoupper($n).' '. $o->$n. ' ' : '';}, $this);
		return implode('', $sqls);		
	}
	public function __get($name) {
		if (isset($this->fields[$name])) return $this->fields[$name];
	}
	// abstract function.
	public function __call($name, $args) {
		if (method_exists($this, $name)) {
			call_user_func_array(array($this, $name), $args);
			return $this;
		}
		$name = strtolower($name);
		$field = $args[0];
		$operator = (is_string(end($args)) && 'or' === strtolower(end($args))) ? 'OR' : 'AND';
		if (isset($this->fields[$field])){
			array_shift($args) && ($exp = call_user_func_array(array($this->fields[$field], $name), $args));
			if ($exp){ 
				if (!$this->wrapOpen)
					$this->_addCondition($exp, $operator);
				else
					$this->_addExpression($exp, $operator);
			}
		}
		return $this;
	}
	protected function wrap($operator = null) {
		if (1 === func_num_args()){
			$this->wrapOpen = false;
			if (count($this->expressions) > 0)
			$this->_addCondition(new WrapExpressions(array('delimiter' => '','target'=>$this->expressions)), 'or' === strtolower($operator) ? 'OR' : 'AND');
			$this->expressions = array();
		} else $this->wrapOpen = true;
	}
	protected function addField($name, $config = array()){
		$this->fields[$name] = new Field(array_merge($config, array('name' => $name)));
	}
	protected function select($fields = '*') {
		$this->select = ($this->select && $fields !== '*'  ? $this->select . ', ': '' ). (is_array($fields) ? implode(', ', $fields) : $fields);
	}
	protected function _addExpression($exp, $operator) {
		if (count($this->expressions) == 0) 
			$this->expressions[] = $exp;
		else 
			$this->expressions[] = new Expressions(array('operator'=>$operator, 'target'=>$exp));
	}
	protected function _addCondition($exp, $operator) {
		if (!$this->where) 
			$this->where = $exp;
		else 
			$this->where = new Expressions(array('source'=>$this->where, 'operator'=>$operator, 'target'=>$exp));	
	}
	public static function forTable($table, $config = array(), $class = 'PDOTable') {
		$instance = new $class (array_merge($config, array('table' => $table, 'from' => $table)));
		foreach($instance->getdata('SHOW FULL COLUMNS FROM '. $table) as $column)
		$instance->addField($column['Field'], array('name' => $column['Field'], 'type' => $column['Type']));
		return $instance;
	}
	
	public function rewind() {return $this->data->rewind();}
	public function current() {return $this->data->current();}
	public function key() {return $this->data->key();}
	public function next() {return $this->data->next();}
	public function valid() {return $this->data->valid();}
	
}
class PDOTable extends BaseTable implements Iterator {
	public static $db = null;
	public $sth = null;
		
	public static function config($dsn, $user = '', $password = '') {
		self::$db = new PDO($dsn, $user, $password);
	}
	public function __call($name, $args) {
		if (method_exists(self::$db, $name)) return $this->sth = call_user_func_array(array(self::$db, $name), $args);
		if (method_exists($this->sth, $name)) return call_user_func_array(array($this->sth, $name), $args);
		return parent::__call($name, $args);
	}
	public function getdata($sql) {
		$this->sth = $this->prepare($sql);
		$this->sth->execute();
		return $this->data = $this->sth->fetchAll(PDO::FETCH_ASSOC);
	}
}
// PRAGMA table_info(tbl)
// SHOW FULL COLUMNS FROM tbl
// 
//$db = new PDO('mysql:host=localhost;dbname=test');
//$stmt = $db->prepare("select * from tbl limit 0");


/*
$e1 = new Expressions(array('source'=>'name', 'operator'=>'=', 'target'=>"'asdaasda'"));
$e2 = new Expressions(array('source'=>'email', 'operator'=>'=', 'target'=>"'asdaasda@sina.com'"));
$e3 = new Expressions(array('source'=>$e1, 'operator'=>'and', 'target'=>$e2));
$e4 = new Expressions(array('source'=>$e1, 'operator'=>'and', 'target'=>$e2));
$e = new Expressions(array('source'=>$e3, 'operator'=>'or', 'target'=>$e4));
echo $e, "\n";

$t = new PDOTable();
$t->addField('name');
$t->addField('id');
echo $t->wrap()->isNull('name')->eq('name', "'lloyd'")->ne('name', "'lloyd'")->wrap(true)->wrap()->in('id', array(1,3,4,2,2))->notNull('id')->wrap('OR')->condition, "\n";
//var_dump($t);
 */
PDOTable::config('mysql:host=localhost;dbname=test');
//var_dump(PDOTable::$db);
$tbl = PDOTable::forTable('tbl');
echo $tbl->isnotnull()->wrap()->ge('id', 1)->wrap('OR')->from, "\n";
//var_dump($tbl);
echo $tbl->select('id, col')->select, "\n";
var_dump($tbl->findAll());
