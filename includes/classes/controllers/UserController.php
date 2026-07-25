<?php

class UserController extends ModelController{

	public function __construct($pdo, $response, $id=null) {
		parent::__construct($pdo, $response, $id);
	}

	// Shape a user for the client — never expose the password hash.
	private function userPublic($user){
		return [
			'id' => intval($user->get('id')),
			'username' => $user->get('username'),
			'display_name' => $user->get('display_name'),
			'is_admin' => User::userIdIsAdmin($this->pdo, $user->get('id'))
		];
	}

	public function get(){
		$viewer = $this->getUser();
		if(empty($viewer)) $this->response->setError("Not logged in", 401)->send();
		$viewer_is_admin = $viewer->isAdmin();

		// Single user requested by id
		if(!empty($this->id)){
			if(!$viewer_is_admin && intval($this->id) !== intval($viewer->get('id'))){
				$this->response->setError("Not authorized", 403)->send();
			}
			$target = User::fromID($this->pdo, $this->id);
			if(false === $target) $this->response->setError("User not found", 404)->send();
			$this->response->setData([
				'viewer_is_admin' => $viewer_is_admin,
				'viewer_id' => intval($viewer->get('id')),
				'user' => $this->userPublic($target)
			]);
			return;
		}

		// List — admins see everyone, everyone else sees only themselves.
		// The internal "system" account is never listed or editable.
		$users = [];
		if($viewer_is_admin){
			$rows = $this->pdo->query("SELECT * FROM `users` WHERE `username` != 'system' ORDER BY `username`")->fetchAll(PDO::FETCH_ASSOC);
			foreach($rows as $row){
				$u = new User($this->pdo);
				foreach($row as $k=>$v) $u->set($k, $v);
				$users[] = $this->userPublic($u);
			}
		}else{
			$users[] = $this->userPublic($viewer);
		}

		$this->response->setData([
			'viewer_is_admin' => $viewer_is_admin,
			'viewer_id' => intval($viewer->get('id')),
			'users' => $users
		]);
	}

	public function patch(){
		// Parse the multipart PATCH body (same approach as PostController).
		$_input = [];
		new Stream($_input);
		$_PATCH = empty($_input['post']) ? [] : $_input['post'];

		$viewer = $this->getUser();
		if(empty($viewer)) $this->response->setError("Not logged in", 401)->send();
		$viewer_is_admin = $viewer->isAdmin();

		$target_id = intval($this->id);
		if(empty($target_id)) $this->response->setError("No user id provided", 400)->send();

		// Authorization: admins may edit anyone, others only themselves.
		if(!$viewer_is_admin && $target_id !== intval($viewer->get('id'))){
			$this->response->setError("Not authorized", 403)->send();
		}

		$target = User::fromID($this->pdo, $target_id);
		if(false === $target) $this->response->setError("User not found", 404)->send();
		if($target->get('username') === 'system'){
			$this->response->setError("The system account cannot be edited", 403)->send();
		}

		// Display name (optional)
		if(isset($_PATCH['display_name'])){
			$display_name = trim($_PATCH['display_name']);
			if(strlen($display_name) < 3){
				$this->response->setError("Display name must be at least 3 characters.", 400)->send();
			}
			$target->set('display_name', $display_name);
		}

		// Username (optional) — validate format and uniqueness
		if(isset($_PATCH['username'])){
			$username = trim($_PATCH['username']);
			if(!preg_match("/^[a-zA-Z0-9_]+$/", $username) || strlen($username) < 4 || strlen($username) > 15){
				$this->response->setError("Invalid username. Letters, numbers and underscores only; 4-15 characters.", 400)->send();
			}
			$existing = User::fromColumn($this->pdo, 'username', $username);
			if($existing !== false && intval($existing->get('id')) !== $target_id){
				$this->response->setError("That username is already in use.", 400)->send();
			}
			$target->set('username', $username);
		}

		// Password (optional)
		if(!empty($_PATCH['password'])){
			if(strlen($_PATCH['password']) < 8){
				$this->response->setError("Password must be at least 8 characters.", 400)->send();
			}
			$target->set('password', User::hashPassword($_PATCH['password']));
		}

		$saved = $target->save();
		if(false === $saved){
			$this->response->setError("Unable to update user. Is the DB writable?", 500)->send();
		}

		// Admin flag — only admins may change it.
		if($viewer_is_admin && array_key_exists('is_admin', $_PATCH)){
			$make_admin = $_PATCH['is_admin'] === '1' || $_PATCH['is_admin'] === 'true' || $_PATCH['is_admin'] === 1;
			if($make_admin){
				User::addAdmin($this->pdo, $target_id);
			}else{
				// Never allow removing the final admin — it would lock everyone out.
				if(User::userIdIsAdmin($this->pdo, $target_id) && User::adminCount($this->pdo) <= 1){
					$this->response->setError("You can't remove the last remaining admin.", 400)->send();
				}
				User::removeAdmin($this->pdo, $target_id);
			}
		}

		$this->response->setData([
			'viewer_is_admin' => $viewer_is_admin,
			'viewer_id' => intval($viewer->get('id')),
			'user' => $this->userPublic($target)
		]);
	}

}
