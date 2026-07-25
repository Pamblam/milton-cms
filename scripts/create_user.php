<?php

/**
 * Create a new user in the database
 */

require realpath(dirname(dirname(__FILE__)))."/includes/env.php";
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "\n\nCreating users\n";
echo "=================\n";
echo "Creating a new user.";

// Opening the file in 'w' mode truncates it, 
// resetting the exiting database, if there is one
$db_file = APP_ROOT."/database/milton.db";
if(!file_exists($db_file)){
	echo "Database does not exist. Install the app first.\n";
	exit(1);
}

createUser();
$create_another = promptUser("Do you want to create another user? (y/n):");
while($create_another === 'y'){
	createUser();
	$create_another = promptUser("Do you want to create another user? (y/n):");
}

function getNewUsername(){
	global $pdo;
	$username = promptUser("Enter a username (default: admin):");
	if(empty($username)) $username = 'admin';
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

function getNewPassword($username){
	$password = promptUser("Enter a password for user $username (default: password):");
	if(empty($password)) $password = 'password';
	if(strlen($password) < 8){
		echo "Invalid password. Must be at least 8 characters.\n";
		return getNewPassword($username);
	}
	return $password;
}

function getNewDisplayName($username){
	$display_name = promptUser("Enter the real name for user $username (default: Administrator):");
	if(empty($display_name)) $display_name = 'Administrator';
	if(strlen($display_name) < 3){
		echo "Invalid name. Must be at least 3 characters.\n";
		return getNewDisplayName($username);
	}
	return $display_name;
}

function createUser(){
	global $pdo;
	$username = getNewUsername();
	$password = getNewPassword($username);
	$display_name = getNewDisplayName($username);
	try{
		$stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `display_name`) VALUES (?, ?, ?);");
		$stmt->execute([$username, User::hashPassword($password), $display_name]);
	}catch(PDOException $e){
		echo "Error: ".$e->getMessage()."\n";
		echo "Can't create user. Ensure PHP has proper permissions to read the database file.\n";
		exit(1);
	}
	echo "Created user: $username\n";
}



function promptUser($prompt){
	echo "$prompt: ";
	$handle = fopen ("php://stdin","r");
	$line = fgets($handle);
	fclose($handle);
	return trim($line); 
}