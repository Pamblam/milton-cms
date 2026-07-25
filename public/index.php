<?php

require realpath(dirname(dirname(__FILE__)))."/includes/env.php";

$missing_perms = fi_check_file_app_permissions();
$missing_tables = fi_check_tables();
$missing_user = fi_check_missing_user();
$missing_deps = fi_check_missing_deps();
$missing_node_modules = fi_check_missing_node_modules();

$installed = !empty($config) 
	&& !empty($pdo)
	&& empty($missing_perms) 
	&& empty($missing_tables)
	&& empty($missing_deps)
	&& !$missing_node_modules
	&& !$missing_user;

// The browser installer drives itself over AJAX, sending this header on each
// step so we can return just the inner fragment (or, once setup is complete, a
// JSON signal telling it to reload into the finished app).
$is_installer_ajax = !empty($_SERVER['HTTP_X_MILTON_INSTALLER']);

if($installed){
	if($is_installer_ajax){
		header('Content-Type: application/json');
		echo json_encode(['done' => true]);
		exit;
	}
	if(fi_is_404()) http_response_code(404);
	require fi_resolve_theme_file('index.php');
}else{
	if($is_installer_ajax){
		require fi_resolve_theme_file('installer/installer_body.php');
		exit;
	}
	require fi_resolve_theme_file('installer/installer.php');
}
	