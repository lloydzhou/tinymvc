<?php class contactController {	function initTable(){		$db = ORM::get_db();		$db->exec("			CREATE TABLE IF NOT EXISTS contact (				id INTEGER PRIMARY KEY, 				name TEXT, 				email TEXT 			);"		);		redirect('/contact/index');	}	function index() {		$contact = new Contact();	    $count = $contact->count();		$contact_list = $contact->findAll();		render('contact/index', array('count' => $count, 'contact_list' => $contact_list), 'layout');	}	function create() {		if (!empty($_POST)) {			$contact = new Contact();			if ($contact->insert(array('name' => params('name'), 'email' => params('email'))))				redirect('/contact/index');		}		render('contact/create', null, 'layout');	}	function update() {		$contact = $this->_getContact();		if (!empty($_POST)) {			if ($contact->update(array('name' => params('name'), 'email' => params('email'))))				redirect('/contact/view/id/'. $contact->id);		}		render('contact/update', array('contact' => $contact), 'layout');	}	public function delete() {		$contact = $this->_getContact();		if ($contact->delete())			render('contact/delete', array('message' => "Delete Contact #{$contact->id} SUCCESS."));		else render('contact/delete', array('message' => "Fail to delete Contact #{$contact->id}."));	}	function view() {		$contact = $this->_getContact();		render('contact/view', array('contact' => $contact), 'layout');	}	protected function _getContact() {		if (!($id = params('id')))			error(404, 'Bad Request.');		$contact = (new Contact)->find($id);		if (!$contact->orm)			error(404, 'Record not found.');		return $contact;	}}