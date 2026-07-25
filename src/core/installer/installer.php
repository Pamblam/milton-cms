<?php
	// The installer runs before config exists, so we can't use $config->base_url.
	// Derive the app's base path from the (rewritten) script location instead;
	// this resolves the same core assets the theme serves, even mid-install.
	$installer_base = rtrim(str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'])), '/') . '/';
?>
<!doctype html>
<html lang="en">
	<head>
		<meta charset="utf-8">
		<meta name="viewport" content="width=device-width, initial-scale=1">
		<title>Install Milton CMS</title>
		<!-- Same Bootstrap the default theme uses, served via the asset router -->
		<link href="<?php echo htmlspecialchars($installer_base); ?>assets/css/bootstrap.min.css" rel="stylesheet">
		<style>
			body{ background:#f6f7f9; }
			.installer-logo{ display:block; max-width:150px; height:auto; margin:0 auto 1.25rem; }

			.installer-wrap{ position:relative; min-height:60px; }
			.installer-wrap.loading #installer-content{ opacity:.35; pointer-events:none; }
			.installer-spinner{ display:none; position:absolute; inset:0; align-items:center; justify-content:center; }
			.installer-wrap.loading .installer-spinner{ display:flex; }

			.installer-card h4{ margin-top:1.5rem; }

			.code-block{ position:relative; margin:.5rem 0 1rem; }
			.code-textarea{
				border-radius:6px;
				resize:none;
				display:block;
				width:100%;
				border:none;
				background:#1c1c1c;
				color:#f0f0f0;
				font-family:'Courier New', monospace;
				overflow:auto;
				padding:.6rem 2.5rem .6rem .75rem;
			}
			.code-copy{
				position:absolute;
				top:.4rem;
				right:.4rem;
				background:transparent;
				border:none;
				color:#cfcfcf;
				cursor:pointer;
				padding:.25rem;
				border-radius:4px;
				line-height:0;
			}
			.code-copy:hover{ color:#fff; background:rgba(255,255,255,.14); }
			.code-copy.copied{ color:#4ade80; }
		</style>
	</head>
	<body>
		<div class="container" style="max-width: 760px;">
			<div class="installer-card card shadow-sm my-4">
				<div class="card-body p-4 p-md-5">
					<img class="installer-logo" src="<?php echo htmlspecialchars($installer_base); ?>assets/img/milton.png" alt="Milton CMS" />
					<h1 class="h3 mb-4 text-center">Install Milton CMS</h1>
					<div class="installer-wrap">
						<div id="installer-content">
							<?php require fi_resolve_theme_file('installer/installer_body.php'); ?>
						</div>
						<div class="installer-spinner"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading…</span></div></div>
					</div>
				</div>
			</div>
		</div>

		<script>
		(function(){
			const wrap = document.querySelector('.installer-wrap');
			const content = document.getElementById('installer-content');
			const AJAX_HEADER = {'X-Milton-Installer': '1'};

			const setLoading = on => wrap.classList.toggle('loading', !!on);

			// Apply a server response: JSON {done:true} means installation is
			// finished (reload into the app); otherwise it's the next HTML fragment.
			async function apply(res){
				const ct = res.headers.get('content-type') || '';
				if(ct.indexOf('application/json') !== -1){
					const data = await res.json().catch(()=>({}));
					if(data && data.done){ window.location.reload(); return; }
				}
				content.innerHTML = await res.text();
			}

			async function request(opts){
				setLoading(true);
				try{
					const res = await fetch(window.location.href, Object.assign({headers: AJAX_HEADER}, opts));
					await apply(res);
				}catch(e){
					// If the AJAX round-trip fails for any reason, fall back to a
					// full page load so the installer still works.
					window.location.reload();
				}finally{
					setLoading(false);
				}
			}

			// Copy icons and "Continue" buttons (delegated so they keep working
			// after each fragment swap).
			document.addEventListener('click', function(e){
				const copy = e.target.closest('.code-copy');
				if(copy){
					e.preventDefault();
					const ta = document.getElementById(copy.getAttribute('data-copy-target'));
					if(ta && navigator.clipboard){
						navigator.clipboard.writeText(ta.value).then(()=>{
							copy.classList.add('copied');
							setTimeout(()=>copy.classList.remove('copied'), 1500);
						});
					}
					return;
				}

				const cont = e.target.closest('.installer-continue');
				if(cont && content.contains(cont)){
					e.preventDefault();
					request({method:'GET'});
				}
			});

			// Installer forms submit over AJAX.
			document.addEventListener('submit', function(e){
				const form = e.target.closest('form');
				if(!form || !content.contains(form)) return;
				e.preventDefault();
				const fd = new FormData(form);
				// FormData omits the submit button; the installer switches on its
				// name (create_user, create_app_config, …) so add it back.
				if(e.submitter && e.submitter.name){
					fd.append(e.submitter.name, e.submitter.value || '1');
				}
				request({method:'POST', body: fd});
			});
		})();
		</script>
	</body>
</html>
