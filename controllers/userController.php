<?php 
class userController {
	function initTable(){
		ActiveRecord::execute("
			CREATE TABLE IF NOT EXISTS `user` (
				id INTEGER PRIMARY KEY, 
				name TEXT,
				password TEXT,
				profile TEXT,
				salt TEXT
			);"
		);
		redirect(createurl('/user/index'));
	}
	function index() {
		$user = new User();
	    $user_list = $user->findAll();
		$count = count($user_list);
		t('user/index', array('count' => $count, 'user_list' => $user_list));
	}
	function create() {
		$user = new User();
		if (!empty($_POST)) {
			$user->name = params('name');
			$user->password = params('password');
			$user->profile = params('profile');
			if ($user->insert())
			redirect(createurl('/user/index'));
		}
		t('user/create', array('user' => $user));
	}
	function update() {
		$user = $this->_getUser();
		if (!empty($_POST)) {
			$user->name = params('name');
			$user->password = params('password');
			$user->profile = params('profile');
			//var_dump($user);
			if ($user->update())
				redirect(createurl('/user/view/id/'. $user->id));
		}
		t('user/update', array('user' => $user));
	}
	public function delete() {
		$user = $this->_getUser();
		if ($user->delete())
			t('user/delete', array('message' => "Delete User #{$user->id} SUCCESS."));
		else t('user/delete', array('message' => "Fail to delete User #{$user->id}."));
	}
	function view() {
		$user = $this->_getUser();
		t('user/view', array('user' => $user));
	}
	protected function _getUser() {
		if (!($id = params('id')))
			error(404, 'Bad Request.');
		$user = new User;
		if (!($user->find($id)))
			error(404, 'Record not found.');
		return $user;
	}
}