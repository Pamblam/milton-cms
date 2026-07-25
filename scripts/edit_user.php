<?php

/**
 * Edit a user
 */

 require realpath(dirname(dirname(__FILE__)))."/includes/env.php";
 $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
 
echo "\n\nEditing users\n";
echo "=================\n";

// Opening the file in 'w' mode truncates it, 
// resetting the exiting database, if there is one
$db_file = APP_ROOT."/database/milton.db";
if(!file_exists($db_file)){
	echo "Database does not exist. Install the app first.\n";
	exit(1);
}

// Make sure the new version is indicated
if(empty($argv[1])){
	echo "No username or user ID indicated.\n";
	exit(1);
}

$USER_ID = $argv[1];

$user = null;
try{
	$stmt = $pdo->prepare("select * from `users` where `username` = ? OR `id` = ?");
	$stmt->execute([$USER_ID, $USER_ID]);
	$user = $stmt->fetch(PDO::FETCH_ASSOC);
}catch(PDOException $e){
	echo "Error: ".$e->getMessage()."\n";
	echo "Check the `users` table. Ensure PHP has proper permissions to read the database file.\n";
	exit(1);
}

if(empty($user)){
	echo "No user found with that ID or username.\n";
	exit(1);
}

$real_name = getNewDisplayName($user['display_name']);
if(!empty($real_name)) $user['display_name'] = $real_name;

$username = getNewUsername($user['username']);
if(!empty($username)) $user['username'] = $username;

$password = getUpdatedPassword();
if(!empty($password)) $user['password'] = User::hashPassword($password);

try{
	$stmt = $pdo->prepare("UPDATE `users` SET `username` = ?, `password` = ?, `display_name` = ? WHERE `id` = ?;");
	$stmt->execute([$user['username'], $user['password'], $user['display_name'], $user['id']]);
}catch(PDOException $e){
	echo "Error: ".$e->getMessage()."\n";
	echo "Can't update user. Ensure PHP has proper permissions to read the database file.\n";
	exit(1);
}
echo "Updated user: ".$user['username']."\n";

// Optionally add or remove this user from the admins table.
$is_admin = User::userIdIsAdmin($pdo, $user['id']);
$admin_prompt = $is_admin
	? "This user is currently an admin. Remove admin privileges? (y/N)"
	: "This user is not an admin. Grant admin privileges? (y/N)";
$answer = strtolower(promptUser($admin_prompt));
if($answer === 'y'){
	if($is_admin){
		if(User::adminCount($pdo) <= 1){
			echo "Refusing to remove the last remaining admin.\n";
		}else{
			User::removeAdmin($pdo, $user['id']);
			echo "Removed admin privileges from: ".$user['username']."\n";
		}
	}else{
		User::addAdmin($pdo, $user['id']);
		echo "Granted admin privileges to: ".$user['username']."\n";
	}
}

function getNewUsername($username){
	global $pdo;
	$username = promptUser("Enter a username (default: $username)");
	if(empty($username)) return false;
	$valid_username = preg_match("/^[a-zA-Z0-9_]+$/", $username);
	if($valid_username !== 1 || strlen($username) < 4 || strlen($username) > 15){
		echo "Invalid username. May contain only letters, numbers, and underscores. Must be at least 4 characters and no more than 15.\n";
		return getNewUsername();
	}

	// check to see if this user exists already
	try{
		$stmt = $pdo->prepare("select count(1) from `users` where username = ?");
		$stmt->execute([$username]);
		$cnt = intval($stmt->fetchColumn());
	}catch(PDOException $e){
		echo "Error: ".$e->getMessage()."\n";
		echo "Check the `users` table. Ensure PHP has proper permissions to read the database file.\n";
		exit(1);
	}
	if($cnt > 0){
		echo "This username is already in use. Pick another one.\n";
		return getNewUsername();
	}
	return $username;
}

function getNewDisplayName($username){
	$display_name = promptUser("Enter the real name for user this user (default: $username)");
	if(empty($display_name)) return false;
	if(strlen($display_name) < 3){
		echo "Invalid name. Must be at least 3 characters.\n";
		return getNewDisplayName($username);
	}
	return $display_name;
}

function getUpdatedPassword(){
	$password = promptUser("Enter a a new password or leave blank to keep existing password");
	if(empty($password)) return false;
	if(strlen($password) < 8){
		echo "Invalid password. Must be at least 8 characters.\n";
		return getUpdatedPassword();
	}
	return $password;
}

function promptUser($prompt){
	echo "$prompt: ";
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	fclose($handle);
	return trim($line); 
}