<?php

/**
 * Render a read-only code block with a copy-to-clipboard icon tucked into its
 * top-right corner. Used by the browser installer to present shell commands.
 * The copy behaviour is wired up (via delegation) by the installer shell JS,
 * keyed off the button's data-copy-target attribute.
 */
function fi_installer_codeblock($code, $rows=2){
	$id = 'code-'.substr(md5($code.mt_rand()), 0, 8);
	$esc = htmlspecialchars($code, ENT_QUOTES);
	$rows = intval($rows);
	$copy_svg = '<svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" viewBox="0 0 16 16" aria-hidden="true">'
		. '<path d="M4 1.5H3a2 2 0 0 0-2 2V14a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V3.5a2 2 0 0 0-2-2h-1v1h1a1 1 0 0 1 1 1V14a1 1 0 0 1-1 1H3a1 1 0 0 1-1-1V3.5a1 1 0 0 1 1-1h1z"/>'
		. '<path d="M9.5 1a.5.5 0 0 1 .5.5v1a.5.5 0 0 1-.5.5h-3a.5.5 0 0 1-.5-.5v-1a.5.5 0 0 1 .5-.5zm-3-1A1.5 1.5 0 0 0 5 1.5v1A1.5 1.5 0 0 0 6.5 4h3A1.5 1.5 0 0 0 11 2.5v-1A1.5 1.5 0 0 0 9.5 0z"/>'
		. '</svg>';
	return "<div class='code-block'>"
		. "<textarea id='$id' class='code-textarea' readonly rows='$rows'>$esc</textarea>"
		. "<button type='button' class='code-copy' title='Copy to clipboard' aria-label='Copy to clipboard' data-copy-target='$id'>$copy_svg</button>"
		. "</div>";
}
