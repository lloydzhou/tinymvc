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
	public $delimiter = ', ';
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
class Table extends Base //implements Iterator
{
	protected $sqlExpressions = array('expressions' => array(), 'wrapOPen' => false,
		'select'=>null, 'insert'=>null, 'update'=>' ', 'delete'=>' ', 
		'from'=>null, 'values' => null, 'where'=>null, 'limit'=>null, 'order'=>null);
	protected $fields = array();
	public $table;
	public $data = array();
	
	public static $driver=null;
	public static function config($driver) {
		self::$driver = $driver;
	}
	public function __set($name, $val){
		if (array_key_exists($name, $this->sqlExpressions)) $this->sqlExpressions[$name] = $val;
		if (array_key_exists($name, $this->sqlExpressions)) $this->data[$name] = $val;
	}
	protected function find() {
		$this->limit = '1';
		$this->data = current(self::$driver->getdata($this->getSql(array('select', 'from', 'where', 'limit', 'order'))));
	}
	public function findAll($resetLimit = false) {
		if ($resetLimit) $this->limit = null;
		$this->data = self::$driver->getdata($this->getSql(array('select', 'from', 'where', 'limit', 'order')));
		array_walk($this->data, function (&$n, $i, $o){
			$n = Table::forTable($o->table, array('data' => $n));
		}, $this);
		return $this->data;
	}
	protected function insert($data = null) {
		$data = $data ? $data : $this->data;
		$this->insert = new Expressions(array('operator' => 'into '. $this->table, 'target' => new WrapExpressions(array('delimiter' => ', ', 'target' => array_keys($data)))));
		$this->values = new WrapExpressions(array('delimiter' => ', ', 'target' => array_values($data)));
		self::$driver->getdata($this->getSql(array('insert', 'values')));
		//$this->data
	}
	public function getSql($sqls = array()) {
		array_walk($sqls, function (&$n, $i, $o){$n = (null !== $o->$n) ? strtoupper($n).' '. $o->$n. ' ' : '';}, $this);
		echo 'SQL: '. implode('', $sqls). "\n";
		return implode('', $sqls);		
	}
	public function __get($name) {
		if (isset($this->fields[$name])) return $this->fields[$name];
		if (array_key_exists($name, $this->sqlExpressions)) return $this->sqlExpressions[$name];
	}
	public function __call($name, $args) {
		if (isset(self::$driver->$name)) {
			echo 'call from driver';
			return call_user_func_array(array(self::$driver, $name), $args);
		}
		if (method_exists($this, $name)) 
			call_user_func_array(array($this, $name), $args);
		elseif (isset($args[0]) && $field = $args[0]) {
			$name = strtolower($name);
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
	public static function forTable($table, $config = array()) {
		$instance = new Table (array_merge($config, array('table' => $table, 'from' => $table)));
		foreach(self::$driver->getdata('SHOW FULL COLUMNS FROM '. $table) as $column)
			$instance->addField($column['Field'], array('name' => $column['Field'], 'type' => $column['Type']));
		return $instance;
	}
}
class PDODriver extends Base{
	public $db = null;
	public $sth = null;
		
	public function __call($name, $args) {
		if (method_exists($this->db, $name)) return $this->sth = call_user_func_array(array($this->db, $name), $args);
		if (method_exists($this->sth, $name)) return call_user_func_array(array($this->sth, $name), $args);
	}
	public function __isset($name) {
		return (method_exists($this->db, $name) || method_exists($this->sth, $name));
	}
	public function execsql($sql) {
		
	}
	public function getdata($sql) {
		$this->sth = $this->db->prepare($sql);
		$this->sth->execute();
		$data = $this->sth->fetchAll(PDO::FETCH_ASSOC);
		$this->sth = null;
		return $data;
	}
}
Table::config(new PDODriver(array('db' => new PDO('mysql:host=localhost;dbname=test'))));
$tbl = Table::forTable('tbl');
echo $tbl->isnotnull('id')->wrap()->ge('id', 1)->wrap('OR')->where, "\n";
echo $tbl->select('id, col')->select, "\n";
$tbl->order = 'by id asc';
var_dump($tbl->find());
$tbl->insert(array('id' => 12, 'col' => 100));
foreach($tbl->findAll(true) as $t) var_dump($t);
