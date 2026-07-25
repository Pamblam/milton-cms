<?php

/**
 * Given the directory to a new version of the software, 
 * merge systems files and database structure with this installation
 */

 require realpath(dirname(dirname(__FILE__)))."/includes/env.php";

// Make sure we have a connection to SQL
if(empty($pdo)){
	echo "Run setup script first.\n";
	exit(1);
}

// Make sure the new version is indicated
if(empty($argv[1])){
	echo "No new version path indiciated.\n";
	exit(1);
}

$NEW_VERSION_BASE = $argv[1];

// Ensure that the new version exists and that we have read access to it
$repo_perms = @fi_ensure_permissions($NEW_VERSION_BASE, ['r', 'e']);
if(!empty($repo_perms)){
	foreach($repo_perms as $perm){
		echo "Error: {$perm['error']}\n";
		echo "Solution: {$perm['error']}\n";
	}
	exit(1);
}

if(!is_dir($NEW_VERSION_BASE)){
	echo "Invalid Milton CMS repo indicated - not a directory\n";
	exit(1);
}


if(!file_exists($NEW_VERSION_BASE."/package.json")){
	echo "Invalid Milton CMS repo indicated - missing package.json\n";
	exit(1);
}

$system_files = recursiveScandir($NEW_VERSION_BASE);
for($i=0; $i<count((array) $system_files); $i++){
	$system_files[$i] = ltrim(substr($system_files[$i], strlen($NEW_VERSION_BASE)), '/');
}

$system_files = array_filter($system_files, function($path){
	$ignore_files_like = [
		'.git',
		'src/themes',
		'public/assets/js/main.js',
		'public/assets/images',
		'config',
		'database',
		'node_modules',
		'package-lock.json',
		'package.json'
	];
	foreach($ignore_files_like as $ig){
		if(0 === strpos($path, $ig)) return false;
	}
	return true;
});

foreach($system_files as $file){

	// Ensure we can read the new file
	$perms = @fi_ensure_permissions($NEW_VERSION_BASE."/".$file, ['r']);
	if(!empty($perms)){
		foreach($repo_perms as $perm){
			echo "Error: {$perm['error']}\n";
			echo "Solution: {$perm['error']}\n";
		}
		exit(1);
	}

	if (!file_exists(dirname(APP_ROOT."/".$file))) {
		mkdir(dirname(APP_ROOT."/".$file), 0777, true);
	}

	$result = file_put_contents(
		APP_ROOT."/".$file, 
		file_get_contents($NEW_VERSION_BASE."/".$file)
	);

	if(false === $result){
		echo "Unable to write new file.\n";
		exit(1);
	}

	echo " - Updated $file\n";
}

// Merge the database structure
$db_files = recursiveScandir($NEW_VERSION_BASE."/database/sql");
for($i=0; $i<count((array) $db_files); $i++){
	$db_files[$i] = ltrim(substr($db_files[$i], strlen($NEW_VERSION_BASE)), '/');
	if(substr($db_files[$i], -4) !== '.sql') continue;
	$sql = @file_get_contents($NEW_VERSION_BASE."/".$db_files[$i]);

	if(file_exists(APP_ROOT."/".$db_files[$i])){
		// compare the files to see if there's a table schange

		$new_columns = [];
		$lines = explode("\n", $sql);
		foreach($lines as $line){
			$line = trim($line, " \n\r\t\v\x00,");
			if(strpos($line, '`') === 0){
				$colname = substr($line, 1, strpos($line, '`', 1)-1);
				$new_columns[$colname] = $line;
			}
		}

		$old_sql = @file_get_contents(APP_ROOT."/".$db_files[$i]);
		if(false === $old_sql){
			echo "Unable to read SQL file: ".APP_ROOT."/".$db_files[$i]."\n";
			exit(1);
		}

		$old_columns = [];
		$lines = explode("\n", $old_sql);
		foreach($lines as $line){
			$line = trim($line, " \n\r\t\v\x00,");
			if(strpos($line, '`') === 0){
				$colname = substr($line, 1, strpos($line, '`', 1)-1);
				$old_columns[$colname] = $line;
			}
		}

		preg_match('/create table[^`]+`([^`]+)`/i', $sql, $matches);
		if(empty($matches[1])){
			echo "Unable to determine table name in ".$NEW_VERSION_BASE."/".$db_files[$i]."\n";
			exit(1);
		}
		$table_name = $matches[1];

		$changed = false;
		foreach($new_columns as $colname=>$coldesc){
			if(empty($old_columns[$colname])){
				$changed = true;
				$alter_sql = "ALTER TABLE `$table_name` ADD COLUMN $coldesc";
				try{
					$pdo->exec($alter_sql);
				}catch(PDOException $e){
					echo "Error: ".$e->getMessage()."\n";
					echo "Can't alter column $table_name.$colname the sql file ($db_files[$i]). Ensure PHP has proper permissions to read it and the database file.\n";
					exit(1);
				}
				echo " - Added column $colname to table $table_name\n";
			}elseif($old_columns[$colname] !== $coldesc){
				// SQLite doesn support changing column type, 
				// but since column types are not rigid we can safely ignore this.
				echo " **Warning: Column type changed: $table_name.$colname - skipping\n";
				$changed = true;
			}
		}

		$result = file_put_contents(APP_ROOT."/".$db_files[$i], $sql);
		if(false === $result){
			echo "Unable to write new file: ".$db_files[$i]."\n";
			exit(1);
		}

		if(!$changed) echo " - $table_name OK\n";



	}else{
		// copy the table and add it to the db
		$result = file_put_contents(APP_ROOT."/".$db_files[$i], $sql);
		if(false === $result){
			echo "Unable to write new file: ".$db_files[$i]."\n";
			exit(1);
		}

		if(false === $sql){
			echo "Can't scan the sql file ($NEW_VERSION_BASE."/".$db_files[$i]). Ensure PHP has proper permissions to read it.\n";
			exit(1);
		}
		$sql_statements = explode(";\n", $sql);
		foreach($sql_statements as $sql_statement){
			if(empty($sql_statement)) continue;
			try{
				$pdo->exec($sql_statement);
			}catch(PDOException $e){
				echo "Error: ".$e->getMessage()."\n";
				echo "Can't import the sql file ($db_files[$i]). Ensure PHP has proper permissions to read it and the database file.\n";
				exit(1);
			}
		}

		echo " - Imported DB file: {$db_files[$i]}\n";
	}
}

// Merge the package.json dependencies
$new_config = json_decode(file_get_contents($NEW_VERSION_BASE."/package.json"), true);
$old_config = json_decode(file_get_contents(APP_ROOT."/package.json"), true);

foreach($new_config['devDependencies'] as $dep=>$ver){
	$old_config['devDependencies'][$dep] = $ver;
}

foreach($new_config['dependencies'] as $dep=>$ver){
	$old_config['dependencies'][$dep] = $ver;
}

foreach($new_config['scripts'] as $dep=>$ver){
	$old_config['scripts'][$dep] = $ver;
}

$old_config['milton-version'] = $new_config['version'];

$result = file_put_contents(
	APP_ROOT."/package.json", 
	json_encode($old_config, JSON_PRETTY_PRINT)
);

if(false === $result){
	echo "Unable to write new package.json file.\n";
	exit(1);
}

function recursiveScandir($dir){
	$files = [];
	$f = scandir($dir);
	foreach($f as $file){
		if(in_array($file, ['.', '..'])) continue;
		$path = "$dir/$file";
		if(is_dir($path)){
			$sub_files = recursiveScandir($path);
			foreach($sub_files as $sub_file) $files[] = $sub_file;
		}else{
			$files[] = $path;
		}
	}
	return $files;
}