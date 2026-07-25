<?php
/**
 * installer_body.php
 * The inner installer fragment (everything inside #installer-content). It is
 * rendered on first paint by installer.php and returned on its own for each
 * AJAX step. It relies on the check variables set up in public/index.php.
 */
fi_browser_installer(
	$missing_perms,
	$db_file,
	$missing_tables,
	$_POST,
	$app_config_file,
	$server_config_file,
	$missing_user,
	$missing_deps,
	$missing_node_modules
);
