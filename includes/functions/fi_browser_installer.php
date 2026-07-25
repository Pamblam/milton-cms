<?php

function fi_browser_installer($missing_perms, $db_file, $missing_tables, $post, $app_config_file, $server_config_file, $missing_user, $missing_deps, $missing_node_modules){
	$has_errors = false;

	// Ensure Node Modules exist
	echo "<h4>Checking Node Modules...</h4>";
	if($missing_node_modules){
		echo "<p>Please install node dependencies. From the command line, please run:</p>";
		echo fi_installer_codeblock("cd ".APP_ROOT.";\nnpm install;\n", 3);
		echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
		$has_errors = true;
		return;
	}else{
		echo "<p>Node Modules look good 👌</p>";
	}

	// Ensure all filesystem permissions are OK!
	echo "<h4>Checking Permissions...</h4>";
	if(!empty($missing_perms)){
		echo "<p>Milton CMS requires permissions adjustments. From the command line, please run:</p>";
		$perm_cmds = '';
		foreach($missing_perms as $err) $perm_cmds .= $err['solution'].";\n";
		echo fi_installer_codeblock($perm_cmds, count($missing_perms)+1);
		echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
		$has_errors = true;
		return;
	}else{
		echo "<p>Permissions look good 👌</p>";
	}

	// Ensure DB file exists
	echo "<h4>Checking Database Tables...</h4>";
	if(!file_exists($db_file) || empty($pdo)){
		try{
			$pdo = new PDO('sqlite:'.$db_file);
			if(empty($pdo)) throw new Exception("Couldn't create database file.");
		}catch(Exception $e){
			echo "<p>Milton CMS could not create the database file. From the command line, please run:</p>";
			echo fi_installer_codeblock("touch ".$db_file.";\n", 2);
			echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
			$has_errors = true;
			return;
		}
	}
	
	// Iterate thru all the sql files and run them
	if(!empty($missing_tables)){
		foreach($missing_tables as $table){
			$sql = @file_get_contents(APP_ROOT."/database/sql/$table.sql");
			if(false === $sql){
				echo "<p>Can't scan the sql file ($table.sql). Ensure PHP has proper permissions to read it.</p>";
				echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
				$has_errors = true;
				return;
			}

			$sql_statements = explode(";\n", $sql);
			foreach($sql_statements as $sql_statement){
				// Skip blank fragments (e.g. a trailing newline after the final
				// ';'); PDO::exec() throws a ValueError on an empty statement.
				if(trim($sql_statement) === '') continue;
				try{
					$pdo->exec($sql_statement);
					echo "<p>Created table <code>$table</code></p>";
				}catch(PDOException $e){
					echo "<p>($table.sql) Error: ".$e->getMessage()."</p>";
					echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
					$has_errors = true;
					return;
				}
			}

		}
	}else{
		echo "<p>Tables look good 👌</p>";
	}

	// Add missing user
	echo "<h4>Checking User...</h4>";
	$form_errors = [];
	if(isset($post['create_user'])){

		if($post['confirm_password'] !== $post['password']){
			$form_errors[] = "Passwords don't match";
		}
		if(empty($post['username'])){
			$form_errors[] = "No username provided.";
		}
		if(empty($post['display_name'])){
			$form_errors[] = "No display name provided.";
		}
		try{
			if(empty($form_errors)){
				$stmt = $pdo->prepare("INSERT INTO `users` (`username`, `password`, `display_name`) VALUES (?, ?, ?);");
				$stmt->execute([
					$post['username'],
					User::hashPassword($post['password']),
					$post['display_name']
				]);
				// The first user created during installation is granted admin.
				User::addAdmin($pdo, $pdo->lastInsertId());
				$missing_user = false;

			}
		}catch(PDOException $e){
			$form_errors[] = $e->getMessage();
		}
	}

	if($missing_user){
		$has_errors = true;
		echo "<p>There are no users for this install 🚫. Create one.</p>";
		echo "<form method='POST'>";
		if(!empty($form_errors)) echo '<div class="alert alert-danger"><ul class="mb-0"><li>🚫 ' . implode('</li><li>🚫 ', $form_errors) . '</li></ul></div>';
		echo "<div class='mb-3'><label class='form-label'>Username:</label><input class='form-control' name='username' placeholder='Username' /></div>";
		echo "<div class='mb-3'><label class='form-label'>Display Name:</label><input class='form-control' name='display_name' placeholder='Display Name' /></div>";
		echo "<div class='mb-3'><label class='form-label'>Password:</label><input class='form-control' name='password' type='password' placeholder='Password' /></div>";
		echo "<div class='mb-3'><label class='form-label'>Confirm Password:</label><input class='form-control' name='confirm_password' type='password' placeholder='Confirm Password' /></div>";
		echo "<button class='btn btn-primary' type='submit' name='create_user' value=1>Create User</button>";
		echo "</form>";
		return;
	}else{
		echo "<p>Users look good 👌</p>";
	}

	// Checking config files
	echo "<h4>Checking Configuration...</h4>";
	if(!file_exists($app_config_file)){
		echo "<p>App config file doesn't exit 🚫. From the command line, please run:</p>";
		echo fi_installer_codeblock("touch ".$app_config_file.";\n", 2);
		echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
		$has_errors = true;
		return;
	}
	if(!file_exists($server_config_file)){
		echo "<p>Server config file doesn't exit 🚫. From the command line, please run:</p>";
		echo fi_installer_codeblock("touch ".$server_config_file.";\n", 2);
		echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
		$has_errors = true;
		return;
	}

	// App Config
	$form_errors = [];
	if(isset($post['create_app_config'])){
		if(empty($post['app_title'])){
			$form_errors[] = "No app title provided.";
		}
		if(empty($post['app_description'])){
			$form_errors[] = "No app description provided.";
		}
		if(!empty($post['app_og_url']) && !filter_var($post['app_og_url'], FILTER_VALIDATE_URL)){
			$form_errors[] = "Invalid image URL.";
		}
		try{
			if(empty($form_errors)){
				$app_config_obj = [
					'title' => $post['app_title'],
					'desc' => $post['app_description']
				];
				if(!empty($post['app_og_url'])) $app_config_obj['img'] = $post['app_og_url'];
				$res = @file_put_contents($app_config_file, json_encode($app_config_obj, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
				if(false === $res){
					$form_errors[] = "Can't create app config file. Ensure PHP has correct permissions and ownership.";
				}
			}
		}catch(PDOException $e){
			$form_errors[] = $e->getMessage();
		}
	}

	$cfg = false;
	try{
		$cfg = @file_get_contents($app_config_file);
		$cfg = @json_decode($cfg);
	}catch(Exception $e){ $cfg=false; }
	if(empty($cfg)){
		$has_errors = true;
		echo "<p>Blog is not configured 🚫.</p>";
		echo "<form method='POST'>";
		if(!empty($form_errors)) echo '<div class="alert alert-danger"><ul class="mb-0"><li>🚫 ' . implode('</li><li>🚫 ', $form_errors) . '</li></ul></div>';
		echo "<div class='mb-3'><label class='form-label'>Blog Title:</label><input class='form-control' name='app_title' placeholder='App Title' /></div>";
		echo "<div class='mb-3'><label class='form-label'>Blog Description:</label><input class='form-control' name='app_description' placeholder='App Description' /></div>";
		echo "<div class='mb-3'><label class='form-label'>Blog OG Image URL:</label><input class='form-control' name='app_og_url' placeholder='App OG Image URL' /></div>";
		echo "<button class='btn btn-primary' type='submit' name='create_app_config' value=1>Create App Config</button>";
		echo "</form>";
		return;
	}else{
		echo "<p>Blog Config look good 👌</p>";
	}

	// Server config
	$form_errors = [];
	if(isset($post['create_server_config'])){
		if(empty($post['base_url'])){
			$form_errors[] = "No base URL provided.";
		}
		if(empty($post['max_filesize'])){
			$form_errors[] = "No max filesize provided.";
		}
		if(!is_numeric($post['max_filesize'])){
			$form_errors[] = "Invalid max filesize provided.";
		}
		try{
			if(empty($form_errors)){
				$server_config_obj = [
					'base_url' => $post['base_url'],
					'max_upload_size' => $post['max_filesize']
				];
				$res = @file_put_contents($server_config_file, json_encode($server_config_obj, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
				if(false === $res){
					$form_errors[] = "Can't create server config file. Ensure PHP has correct permissions and ownership.";
				}
			}
		}catch(PDOException $e){
			$form_errors[] = $e->getMessage();
		}
	}

	// Server Config
	$cfg = false;
	try{
		$cfg = @file_get_contents($server_config_file);
		$cfg = @json_decode($cfg, true);
	}catch(Exception $e){ $cfg=false; }
	if(empty($cfg)){
		$has_errors = true;
		echo "<p>Server is not configured 🚫.</p>";
		echo "<form method='POST'>";
		if(!empty($form_errors)) echo '<div class="alert alert-danger"><ul class="mb-0"><li>🚫 ' . implode('</li><li>🚫 ', $form_errors) . '</li></ul></div>';
		echo "<div class='mb-3'><label class='form-label'>Base URL:</label><input class='form-control' name='base_url' placeholder='Base URL' value=\"".$_SERVER['REQUEST_URI']."\" /></div>";
		echo "<div class='mb-3'><label class='form-label'>Max Upload File Size:</label><input class='form-control' type='number' name='max_filesize' placeholder='Max Upload File Size' value=\"".fi_file_upload_max_size()."\" /></div>";
		echo "<button class='btn btn-primary' type='submit' name='create_server_config' value=1>Create Server Config</button>";
		echo "</form>";
		return;
	}else{
		echo "<p>Server Config look good 👌</p>";
	}

	// Server config
	$form_errors = [];
	if(isset($post['save_deps'])){
		foreach($post['dep'] as $dep=>$path){
			$cfg[$dep."_path"] = $path;
		}
		try{
			if(empty($form_errors)){
				$res = @file_put_contents($server_config_file, json_encode($cfg, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES));
				if(false === $res){
					$form_errors[] = "Can't create server config file. Ensure PHP has correct permissions and ownership.";
				}
			}
			$missing_deps = fi_check_missing_deps();
		}catch(PDOException $e){
			$form_errors[] = $e->getMessage();
		}
	}

	// Check missing dependencies
	if(!empty($missing_deps)){
		echo "<p>Dependencies not found 🚫.</p>";
		echo "<form method='POST'>";
		if(!empty($form_errors)) echo '<div class="alert alert-danger"><ul class="mb-0"><li>🚫 ' . implode('</li><li>🚫 ', $form_errors) . '</li></ul></div>';
		foreach($missing_deps as $dep){
			echo "<div class='mb-3'><label class='form-label'>Path to <code>$dep</code>:</label><input class='form-control' name='dep[$dep]' /></div>";
		}
		echo "<button class='btn btn-primary' type='submit' name='save_deps' value=1>Save Dependencies</button>";
		echo "</form>";
		return;
	}

	// Build the app
	if(!$has_errors){

		$path_parts = explode('/', $cfg['node_path']);
		array_pop($path_parts);
		$path = implode("/", $path_parts);

		$envPath = '/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin:'.$path;
		$cmd = 'cd ' . escapeshellarg(APP_ROOT) . ' && PATH=' . $envPath . ' npm run build';

		echo "<h4>Checking Configuration...</h4><ul>";
		$cmds = ['Build' => $cmd];
		foreach($cmds as $desc=>$cmd){
			$res = fi_run_cmd($cmd, null, APP_ROOT);
			if($res->exit_status == 0){
				echo "<li>$desc 👌</li>";
			}else{
				$has_errors = true;
				echo "<li>$desc 🚫:".$res->stderr."</li>";
				break;
			}
		}
		echo "</ul>";

		if($has_errors){
			echo "<p>Unable to build, please run this manually in the command line:</p>";
			echo fi_installer_codeblock("cd ".APP_ROOT.";\nnpm run build;\n", 4);
			echo "<button type='button' class='btn btn-primary installer-continue'>Continue Installation</button>";
			return;
		}

		echo "<button type='button' class='btn btn-primary installer-continue'>Continue</button>";
	}
}