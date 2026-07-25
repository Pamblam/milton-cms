<?php

class SessionController extends ModelController{

	public function __construct($pdo, $response, $id=null) {
		parent::__construct($pdo, $response, $id);
	}

	public function get(){
		$user = $this->getUser();
		if(false == $user){
			$this->response->setData([
				'LoggedIn' => false
			]);
			$this->response->setError("Not logged in", 401)->send();
		}else{
			$this->response->setData([
				'LoggedIn' => true,
				'User' => [
					"id" => $user->get('id'),
					"username" => $user->get('username'),
					"display_name" => $user->get('display_name')
				]
			]);
		}
	}

	public function updateToken(){
		
		// Get the (possibly expired) session
		$session = $this->getSessionFromToken();
		if(empty($session)) $this->response->setError("Invalid session", 400)->send();

		// Get the user from the session
		$user = User::fromID($this->pdo, $session->get('user_id'));

		// Make sure the session matches the client's IP and UA
		$ua = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
		if($session->get('ip') !== Session::getIP()) $this->response->setError("Invalid IP", 400)->send();
		if($session->get('user_agent') !== $ua) $this->response->setError("Invalid UA", 400)->send();

		// Generate a new Session, with a new Token
		$new_token = Session::generateToken();
		$new_session = new Session($this->pdo);
		$new_session->set('ip', Session::getIP());
		$new_session->set('id', $new_token);
		$new_session->set('start_time', time());
		$new_session->set('user_id', $user->get('id'));
		$new_session->set('user_agent', $ua);
		$new_session->save();

		// Delete the old session
		$session->delete();

		$this->response->setHeader("x-auth-token: $new_token");
		$this->response->setData([
			'LoggedIn' => true,
			'User' => [
				"id" => $user->get('id'),
				"username" => $user->get('username'),
				"display_name" => $user->get('display_name')
			]
		]);
	}

	public function post(){
		$user = $this->getUser();
		if($user === false){
			$ip = Session::getIP();

			// Refuse further attempts once this IP has failed too many times
			// within the throttle window (brute-force / credential-stuffing).
			if(LoginThrottle::tooManyAttempts($this->pdo, $ip)){
				$retry = LoginThrottle::retryAfter($this->pdo, $ip);
				$this->response->setHeader("Retry-After: $retry");
				$this->response->setError("Too many login attempts. Try again in ".ceil($retry / 60)." minute(s).", 429)->send();
			}

			if(empty($_POST['username'])) $this->response->setError("Missing parameter: username", 400)->send();
			if(empty($_POST['password'])) $this->response->setError("Missing parameter: password", 400)->send();
			$user = User::fromColumn($this->pdo, 'username', $_POST['username']);
			if(!$user || !$user->verifyPassword($_POST['password'])){
				// Record the failure against this IP before rejecting.
				LoginThrottle::record($this->pdo, $ip, $_POST['username']);
				$this->response->setError("Invalid login", 400)->send();
			}

			// Successful login — clear this IP's failure history.
			LoginThrottle::clear($this->pdo, $ip);

			// do something to create a new session here...
			$new_token = Session::generateToken();
			$this->model_instance->set('ip', Session::getIP());
			$this->model_instance->set('id', $new_token);
			$this->model_instance->set('start_time', time());
			$this->model_instance->set('user_id', $user->get('id'));
			$ua = empty($_SERVER['HTTP_USER_AGENT']) ? '' : $_SERVER['HTTP_USER_AGENT'];
			$this->model_instance->set('user_agent', $ua);
			$res = $this->model_instance->save();
			if(!$res){
				$this->response->setError("Unable to save session. Is DB Writable?", 400)->send();
			}

			// Only allow one active session per user
			$this->model_instance->closeOtherUserSessions();

			$this->response->setHeader("x-auth-token: $new_token");
			$this->response->setData([
				'LoggedIn' => false,
				'User' => [
					"id" => $user->get('id'),
					"username" => $user->get('username'),
					"display_name" => $user->get('display_name')
				]
			]);
		}else{
			$this->response->setData([
				'LoggedIn' => true,
				'User' => [
					"id" => $user->get('id'),
					"username" => $user->get('username'),
					"display_name" => $user->get('display_name')
				]
			]);
		}
		
	}

	public function patch(){
		
	}

	public function delete(){
		$user = $this->getUser();
		if(false == $user){
			$this->response->setData([
				'LoggedIn' => false
			]);
			$this->response->setError("Not logged in", 401)->send();
		}else{
			$success = $this->getSession()->delete();
			$this->response->setData([
				'LoggedIn' => true
			]);
			if(false === $success){
				$this->response->setError("Could not log out", 401)->send();
			}
		}
	}

}