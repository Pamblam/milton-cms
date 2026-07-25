<?php

class User extends Model{
	protected static $table_name = "users";
	
	public function __construct($pdo) {
		parent::__construct($pdo);
		$this->columns = [
			"id" => null,
			"username" => null,
			"password" => null,
			"display_name" => null
		];
	}

	/**
	 * Hash a plaintext password with the current best algorithm.
	 * PASSWORD_DEFAULT is bcrypt today and follows PHP forward automatically,
	 * so this is the single source of truth for how passwords are stored.
	 */
	public static function hashPassword($plain){
		return password_hash($plain, PASSWORD_DEFAULT);
	}

	/**
	 * Verify a plaintext password against this user's stored hash.
	 * Passwords are always stored as password_hash() hashes; accounts created
	 * before that migration must have their password reset (npm run edit_user).
	 */
	public function verifyPassword($plain){
		$stored = $this->get('password');
		if(!is_string($stored) || $stored === '') return false;
		return password_verify($plain, $stored);
	}

	/** Whether this user is a member of the admins table. */
	public function isAdmin(){
		$id = $this->get('id');
		if(empty($id)) return false;
		return self::userIdIsAdmin($this->pdo, $id);
	}

	/** Whether the given user id is an admin. */
	public static function userIdIsAdmin($pdo, $user_id){
		$stmt = $pdo->prepare("SELECT COUNT(1) FROM `admins` WHERE `user_id` = ?");
		$stmt->execute([intval($user_id)]);
		return intval($stmt->fetchColumn()) > 0;
	}

	/** Grant admin to a user id (no-op if already an admin). */
	public static function addAdmin($pdo, $user_id){
		if(self::userIdIsAdmin($pdo, $user_id)) return true;
		$stmt = $pdo->prepare("INSERT INTO `admins` (`user_id`) VALUES (?)");
		return $stmt->execute([intval($user_id)]);
	}

	/** Revoke admin from a user id. */
	public static function removeAdmin($pdo, $user_id){
		$stmt = $pdo->prepare("DELETE FROM `admins` WHERE `user_id` = ?");
		return $stmt->execute([intval($user_id)]);
	}

	/** How many admins currently exist. */
	public static function adminCount($pdo){
		return intval($pdo->query("SELECT COUNT(1) FROM `admins`")->fetchColumn());
	}

}