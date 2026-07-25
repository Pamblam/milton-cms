<?php

class Session extends Model{
	protected static $table_name = "sessions";
	
	public function __construct($pdo) {
		parent::__construct($pdo);
		$this->columns = [
			"id" => null,
			"user_id" => null,
			"start_time" => null,
			"user_agent" => null,
			"ip" => null
		];
	}

	public static function getIP(){
		// Bind the session to REMOTE_ADDR only. The client-supplied
		// HTTP_CLIENT_IP / HTTP_X_FORWARDED_FOR headers were previously trusted,
		// but they are (a) spoofable by the client and (b) unstable behind a
		// proxy/CDN/load-balancer, where X-Forwarded-For can change between
		// requests and silently invalidate an otherwise-valid session. That is
		// a live-only intermittent "not logged in" bug; REMOTE_ADDR is the one
		// value the client cannot forge and stays stable behind a single proxy.
		return empty($_SERVER['REMOTE_ADDR']) ? '' : $_SERVER['REMOTE_ADDR'];
	}

	public static function generateToken(){
		$data = random_bytes(16);
		$data[6] = chr(ord($data[6]) & 0x0f | 0x40);
		$data[8] = chr(ord($data[8]) & 0x3f | 0x80);
		return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
	}

	public function closeOtherUserSessions(){
		if(!empty($this->get('user_id'))){
			$sessions = self::allFromColumn($this->pdo, 'user_id', $this->get('user_id'));
			foreach($sessions as $session){
				if($session->get('id') === $this->get('id')) continue;
				$session->delete();
			}
		}
	}

}