<?php

// This is a PLUGIN TEMPLATE.

// Copy this file to a new name like abc_myplugin.php.  Edit the code, then
// run this file at the command line to produce a plugin for distribution:
// $ php abc_myplugin.php > abc_myplugin-0.1.txt

// Plugin name is optional.  If unset, it will be extracted from the current
// file name. Plugin names should start with a three letter prefix which is
// unique and reserved for each plugin author ("abc" is just an example).
// Uncomment and edit this line to override:
$plugin['name'] = 'cxc_templates';

// Allow raw HTML help, as opposed to Textile.
// 0 = Plugin help is in Textile format, no raw HTML allowed (default).
// 1 = Plugin help is in raw HTML.  Not recommended.
# $plugin['allow_html_help'] = 1;

$plugin['version'] = '0.4.5';
$plugin['author'] = '~cXc~';
$plugin['author_uri'] = 'http://code.google.com/p/cxc-templates/';
$plugin['description'] = 'Template engine for TextPattern 4.5.0 with support for forms, pages, plugins, sections, styles and other template specific assets.';

// Plugin load order:
// The default value of 5 would fit most plugins, while for instance comment
// spam evaluators or URL redirectors would probably want to run earlier
// (1...4) to prepare the environment for everything else that follows.
// Values 6...9 should be considered for plugins which would work late.
// This order is user-overrideable.
$plugin['order'] = '5';

// Plugin 'type' defines where the plugin is loaded
// 0 = public       : only on the public side of the website (default)
// 1 = public+admin : on both the public and admin side
// 2 = library      : only when include_plugin() or require_plugin() is called
// 3 = admin        : only on the admin side
$plugin['type'] = '1';

// Plugin "flags" signal the presence of optional capabilities to the core plugin loader.
// Use an appropriately OR-ed combination of these flags.
// The four high-order bits 0xf000 are available for this plugin's private use
if (!defined('PLUGIN_HAS_PREFS')) define('PLUGIN_HAS_PREFS', 0x0001); // This plugin wants to receive "plugin_prefs.{$plugin['name']}" events
if (!defined('PLUGIN_LIFECYCLE_NOTIFY')) define('PLUGIN_LIFECYCLE_NOTIFY', 0x0002); // This plugin wants to receive "plugin_lifecycle.{$plugin['name']}" events

$plugin['flags'] = '2';

if (!defined('txpinterface'))
        @include_once('zem_tpl.php');

# --- BEGIN PLUGIN CODE ---
 
/*
	PUBLIC PLUGIN CONFIG
	-------------------------------------------------------------------------
*/
	$cxc_templates = array(
		'base_dir'			=>	'tpl',
		'cache_dir'			=>	'tmp',

		'subdir_css'		=>	'style',
		'subdir_forms'		=>	'forms',
		'subdir_pages'		=>	'pages',
		'subdir_plugins'	=>	'plugins',
		'subdir_sections'	=>	'sections',

		'ext_css'			=>	'.css',
		'ext_forms'			=>	'.form',
		'ext_pages'			=>	'.page',
		'ext_plugins'		=>	'.plugin',
		'ext_section'		=>	'.section'
	);

/*
	PLUGIN CODE (no editing below this line, please)
	-------------------------------------------------------------------------
*/
	define('_CXC_TEMPLATES_IMPORT', 1);
	define('_CXC_TEMPLATES_EXPORT', 2);
	$GLOBALS['_CXC_TEMPLATES'] = $cxc_templates;

/*
	PLUGIN CODE::INSTANTIATION
	-------------------------------------------------------------
*/	
	if (@txpinterface == 'admin') {
		$import = 'cxc_templates';
		$import_tab = cxc_templates_gTxt('cxc_tpl_templates_tab');

		add_privs($import, '1,2');
		register_tab('extensions', $import, $import_tab);
		register_callback('cxc_templates', $import);
		register_callback('cxc_tpl_prep', 'plugin_lifecycle.cxc_templates');
	}

/*
	PLUGIN CODE::LIFECYCLE
	-------------------------------------------------------------
*/
	function cxc_tpl_prep($event, $step) {
		global $prefs;
		switch ($step) {
			case 'disabled':
				if (isset($prefs['cxc_tpl_current'])) {
					$prep = safe_delete('txp_prefs','name = "cxc_tpl_current"');
				}
				break;
			case 'deleted':
				if (isset($prefs['cxc_tpl_current'])) {
					$prep = safe_delete('txp_prefs','name = "cxc_tpl_current"');
				}
				break;
		}
	}

/*
	PLUGIN CODE::MAIN CALLBACK
	-------------------------------------------------------------
*/
	function cxc_templates($event, $step='') {
		$GLOBALS['prefs'] = get_prefs();
		global $prefs;
		$template = new cxc_template();

		pagetop(cxc_templates_gTxt('cxc_tpl_process'), '');
		print '
		<style type="text/css">
			.cxc-tpl-boxedup { display: block; width: 450px; }
			.cxc-tpl-success { color: #009900; }
			.cxc-tpl-failure { color: #FF0000; }
			.cxc-tpl-capital { text-transform: capitalize; }
			.cxc-tpl-current { border: medium ridge;float: right; margin: 0 0 0 5px; padding: 1em 1em 0; text-align: center; width: 220px; }
			.cxc-tpl-preview { background: #fff; border: medium ridge; float: right; margin: 0 0 0 10px; padding: 1em 1em 0; text-align: center; width: 220px; }
			.cxc-tpl-default { max-height: 200px; overflow: hidden; }
			.cxc-tpl-padded { border: 1px solid; padding: 2em; }
			.cxc-tpl-smaller { font-size: 80%; }
		</style>

		<script type="text/javascript">
			$(document).ready(function()
			{
			  $(".cxc-tpl-slide-body").hide();
			  $(".cxc-tpl-slide-head").click(function()
			  {
				$(this).next(".cxc-tpl-slide-body").slideToggle(600);
			  });
			});
		</script>

		<table cellpadding="0" cellspacing="0" border="0" id="list" align="center">
			<tr>
				<td>
		';

		if (!isset($prefs['cxc_tpl_current']) && !set_pref('cxc_tpl_current', '', 'publish', 2) && !get_pref('cxc_tpl_current')) {
			print '
				<h1 class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_preferences').'</h1>
				<ul class="results">
					<li>'.cxc_templates_gTxt('cxc_tpl_dbupdate').'</li>
				</ul>
				<br />
			';
		}

		$theme_dir = $prefs['path_to_site']. DIRECTORY_SEPARATOR .$template->_config['base_dir'];
		$cache_dir = $prefs['path_to_site']. DIRECTORY_SEPARATOR .'textpattern'. DIRECTORY_SEPARATOR .$template->_config['cache_dir'];
		if (is_dir($theme_dir) && is_dir($cache_dir)) {

			$theme_index = $theme_dir. DIRECTORY_SEPARATOR .'index.html';
			$cache_index = $cache_dir. DIRECTORY_SEPARATOR .'index.html';
			if (!file_exists($theme_index) || !file_exists($cache_index)) {
				$template->writeIndexFiles($theme_dir);
				$template->writeIndexFiles($cache_dir);
			}
							
			switch ($step) {
				case 'import':
					$import_full = ps('import_full');
					$template->import($import_full, ps('import_dir'));
					$template->writeIndexFiles($theme_dir. DIRECTORY_SEPARATOR .ps('import_dir'));
					print '
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
					break;

				case 'export':
					$dir = ps('export_dir');

					$dir =  str_replace(
								array(' '),
								array('-'),
								$dir
							);
					$template->export($dir);
					$template->writeIndexFiles($theme_dir. DIRECTORY_SEPARATOR .ps('export_dir'));
					print '
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
					break;

				case 'rusure':
					$dir = $theme_dir. DIRECTORY_SEPARATOR .ps('remove_dir');
					if (ps('remove_dir') != 'preimport-data') {
						$tpl_dir = $prefs['path_to_site']. DIRECTORY_SEPARATOR .$template->_config['base_dir']. DIRECTORY_SEPARATOR .ps('remove_dir');
						if (is_dir($tpl_dir)) {
							print '<div class="cxc-tpl-current">';
							$template->cxc_tpl_preview(ps('remove_dir'), $tpl_dir);
							print '</div>';
						}
					}

					print '
						<h1 class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_delete_confirm').'</h1>
						<p>'.cxc_templates_gTxt('cxc_tpl_remove_before').$dir.cxc_templates_gTxt('cxc_tpl_remove_after').'</p>
						'.form(
							graf(''.
								hInput('remove_dir',ps('remove_dir')).' '.
								fInput('submit', 'go', 'Go', 'smallerbox').
								eInput('cxc_templates').sInput('remove')
							)
						).'
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
						';

					break;

				case 'remove':
					$dir = $theme_dir. DIRECTORY_SEPARATOR .ps('remove_dir');
					if (is_dir($dir)) {
						$objects = scandir($dir);
						foreach ($objects as $object) {
							if ($object != '.' && $object != '..') {
								if (is_dir($dir. DIRECTORY_SEPARATOR .$object)) {
									$template->removeDirectory($dir. DIRECTORY_SEPARATOR .$object);
								} else { 
									@unlink($dir. DIRECTORY_SEPARATOR .$object);
								}
							}
						}
						reset($objects);
						@rmdir($dir);
					}
					if (!is_dir($dir)){					
						print '
							<h1 class="cxc-tpl-success"><span class="cxc-tpl-capital">'.str_replace('_', ' ', ps('remove_dir')).'</span> '.cxc_templates_gTxt('cxc_tpl_removed').'</h1>
							<p>'.cxc_templates_gTxt('cxc_tpl_the_capital').str_replace('_', ' ', ps('remove_dir')).cxc_templates_gTxt('cxc_tpl_removed_from').$template->_config['base_dir'].cxc_templates_gTxt('cxc_tpl_removed_dir').'</p>
						';
					} else {
						print '
							<h1 class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_remove_failed').'</h1>
							<p>'.cxc_templates_gTxt('cxc_tpl_the_capital').str_replace('_', ' ', ps('remove_dir')).cxc_templates_gTxt('cxc_tpl_not_removed').'</p>
						';
					}
					print '
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
					break;

				case 'importZip':
					$adv_live = ps('adv_live');
					$adv_root = ps('adv_root');
					$import_full = ps('import_full');
					$tpl_alist = scandir($theme_dir);
					$rel_temp_dir = $template->_config['cache_dir']. DIRECTORY_SEPARATOR . $_FILES['file']['name'];															
					move_uploaded_file($_FILES['file']['tmp_name'],$rel_temp_dir);
					$template->importZip($adv_live, $adv_root, $rel_temp_dir,$_FILES['file']['name']);
					$tpl_blist = scandir($theme_dir);
					$newtpl = array_merge(array_diff($tpl_blist,$tpl_alist));
					if ($adv_live){
						if ($newtpl != '' && count($newtpl) == 1) {
							$template->import($import_full, $newtpl[0]);
							$template->writeIndexFiles($theme_dir. DIRECTORY_SEPARATOR .$newtpl[0]);
						} else {
							print '
								<h1>'.cxc_templates_gTxt('cxc_tpl_template_import').' <span class="cxc-tpl-capital">'.str_replace('_', ' ', str_replace('.zip', '', $_FILES['file']['name'])).'</span></h1>
								<ul class="results">
									<li>'.cxc_templates_gTxt('cxc_tpl_import_failed_before').str_replace(' ', '-', str_replace('.zip', '', $_FILES['file']['name'])).cxc_templates_gTxt('cxc_tpl_import_failed_after').'</li>
								</ul>
								<br />
								<p>'.cxc_templates_gTxt('cxc_tpl_upload_import_fail').'</p>
							';
						}
					}
					print '
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
					break;

				case 'docs':
					$template->cxc_tpl_docs($prefs['cxc_tpl_current']);
					print '
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
					break;

				case 'downzip':
					$zipdir	= ps('zip_dir');
					$stripz	= $prefs['path_to_site']. DIRECTORY_SEPARATOR .$template->_config['base_dir']. DIRECTORY_SEPARATOR;
					$template->writeIndexFiles($theme_dir. DIRECTORY_SEPARATOR .$zipdir);
					$template->cxc_tpl_downzip($theme_dir. DIRECTORY_SEPARATOR .$zipdir, $zipdir.'.zip', $stripz);
					print '
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
					break;

				default:
					$importlist = $template->getTemplateList();
					$php_modules = array_map('strtolower', get_loaded_extensions());

					if (!empty($prefs['cxc_tpl_current']) && $prefs['cxc_tpl_current'] != 'preimport-data') {
						$tpl_dir = $prefs['path_to_site']. DIRECTORY_SEPARATOR .$template->_config['base_dir']. DIRECTORY_SEPARATOR .$prefs['cxc_tpl_current'];
						if (is_dir($tpl_dir)) {
							print '<div class="cxc-tpl-current">';
							$template->cxc_tpl_current($tpl_dir);
							print '</div>';
						}
					}

					if (empty($importlist) || $importlist == '') {
						print '
							<h1>'.cxc_templates_gTxt('cxc_tpl_import_template').'</h1>
							<p class="cxc-tpl-boxedup">'.cxc_templates_gTxt('cxc_tpl_none_before').$template->_config['base_dir'].cxc_templates_gTxt('cxc_tpl_none_after').'</p>
							<span class="cxc-tpl-slide-head">
								'.form(
									graf(''.
										checkbox('show_alt', 'show_alt', '0', '', 'show_alt').' '.cxc_templates_gTxt('cxc_tpl_alternate_dir').' &lt;/&gt;')
								).'
							</span>
							<div class="cxc-tpl-slide-body">
								<p class="cxc-tpl-boxedup">'.cxc_templates_gTxt('cxc_tpl_alt_adjust').'</p>
								<p class="cxc-tpl-boxedup">'.cxc_templates_gTxt('cxc_tpl_alt_note').'</p>
							</div>
						';
					} else {
						print '
							<h1>'.cxc_templates_gTxt('cxc_tpl_import_template').'</h1>
						'.form(
							graf(''.cxc_templates_gTxt('cxc_tpl_import_which').' <br />'.
								selectInput('import_dir', $importlist, '', 1).' <br />'.
								checkbox('import_full', 'import_full', '0', '', 'import_full').' '.cxc_templates_gTxt('cxc_tpl_import_safe_mode').' <br />'.
								fInput('submit', 'go', 'Go', 'smallerbox').
								eInput('cxc_templates').sInput('import')
							)
						);
					}

					print '
						<h1>'.cxc_templates_gTxt('cxc_tpl_export_template').'</h1>	
					'.form(
						graf(''.cxc_templates_gTxt('cxc_tpl_export_name').' <br />'.
							fInput('text', 'export_dir', '').
							fInput('submit', 'go', 'Go', 'smallerbox').
							eInput('cxc_templates').sInput('export')
						)
					);

					if (!empty($importlist) && !$importlist == '') {
						print '
							<h1>'.cxc_templates_gTxt('cxc_tpl_delete_template').'</h1>
						'.form(
							graf(''.
								selectInput('remove_dir', $importlist, '', 1).' '.
								fInput('submit', 'go', 'Go', 'smallerbox').
								eInput('cxc_templates').sInput('rusure')
							)
						);
					}

					if (!empty($importlist) && !$importlist == '' && in_array('zlib', $php_modules)) {
						print '
							<h1>'.cxc_templates_gTxt('cxc_tpl_zip_template').'</h1>
						'.form(
							graf(''.
								selectInput('zip_dir', $importlist, '', 1).' '.
								fInput('submit', 'go', 'Go', 'smallerbox').
								eInput('cxc_templates').sInput('downzip')
							)
						);
					}

					print '
						<h1>'.cxc_templates_gTxt('cxc_tpl_upload_template').'</h1>
					';
					if (in_array('zlib', $php_modules)) {
						print '
						'.form(
							graf(''.cxc_templates_gTxt('cxc_tpl_upload_select').' <br />'.
								fInput('file', 'file', '', '', '', '',50,'','file').
								eInput('cxc_templates').sInput('importZip').' <br />'.
								checkbox('adv_live', 'adv_live', '1', '', 'adv_live').' '.cxc_templates_gTxt('cxc_tpl_upload_import').' <br />'.
								checkbox('import_full', 'import_full', '0', '', 'import_full').' '.cxc_templates_gTxt('cxc_tpl_import_safe_mode').' <br />
								<span class="cxc-tpl-slide-head cxc-tpl-boxedup"><a id="upload-advanced-options">'.cxc_templates_gTxt('cxc_tpl_advanced_options').'</a> &lt;/&gt;</span>
								<span class="cxc-tpl-slide-body cxc-tpl-boxedup">'.
									checkbox('adv_root', 'adv_root', '0', '', 'adv_root').' '.cxc_templates_gTxt('cxc_tpl_root_install').' <br />
									<strong>'.cxc_templates_gTxt('cxc_tpl_advanced_note').'</em>
								</span>'.
								fInput('submit', 'go', 'Go', 'smallerbox','','')
							), '', '', 'post', '', str_replace('\\', '', '\" enctype=\"multipart/form-data'), ''
						);
					} else {
						print '
						<span class="cxc-tpl-slide-head cxc-tpl-boxedup"><a id="upload-advanced-options">'.cxc_templates_gTxt('cxc_tpl_feature_unavailable').'</a> &lt;/&gt;</span>
						<span class="cxc-tpl-slide-body cxc-tpl-boxedup">'.cxc_templates_gTxt('cxc_tpl_zlib_enabled_text').'</span>
						';
					}

					break;
			}
		} else {
			$error = false;
			if (!is_dir($theme_dir) && !mkdir($theme_dir, 0777)) { $error = true; }
			if (is_dir($theme_dir) && !is_writable($theme_dir) && !chmod($theme_dir, 0777)) { $error = true; }
			if (!is_dir($cache_dir) && !mkdir($cache_dir, 0777)) { $error = true; }
			if (is_dir($cache_dir) && !is_writable($cache_dir) && !chmod($cache_dir, 0777)) { $error = true; }
			if (!$error) { // no errors, let’s do your thing
				print '
					<h1 class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_req_dir_created').'</h1>
					<p><a href="index.php?event=cxc_templates">'.cxc_templates_gTxt('cxc_tpl_reload_click').'</a> '.cxc_templates_gTxt('cxc_tpl_reload_display').'</p>
				';
			} else {
				if (!is_dir($theme_dir) && !is_dir($cache_dir)) {
					print '
						<h1 class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_req_dir_missing').'</h1>
						<p>'.cxc_templates_gTxt('cxc_tpl_req_dir_this').$template->_config['base_dir'].cxc_templates_gTxt('cxc_tpl_req_dir_theme').$template->_config['cache_dir'].cxc_templates_gTxt('cxc_tpl_req_dir_cache').$theme_dir.cxc_templates_gTxt('cxc_tpl_req_dir_or').$cache_dir.cxc_templates_gTxt('cxc_tpl_req_dir_auto').'</p>
						<p>'.cxc_templates_gTxt('cxc_tpl_req_dir_manual').'</p>
<pre><code>    mkdir '.$template->_config['base_dir'].'
    chmod 777 '.$template->_config['base_dir'].'
</code></pre>
						<p>'.cxc_templates_gTxt('cxc_tpl_req_dir_after').'</p>
						<p>'.cxc_templates_gTxt('cxc_tpl_add_security').$theme_dir.cxc_templates_gTxt('cxc_tpl_add_empty_dir').'</p>
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
				} else {
					if (!is_dir($theme_dir)){
						$missing_dir = $template->_config['base_dir'];
						$missing_loc = '';
						$is_missing_dir = 'base_dir';
						$is_missing_loc = cxc_templates_gTxt('cxc_tpl_webroot ');
					}else{
						$missing_dir = $template->_config['cache_dir'];
						$missing_loc = DIRECTORY_SEPARATOR .'textpattern'. DIRECTORY_SEPARATOR;
						$is_missing_dir = 'cache_dir';
						$is_missing_loc = 'textpattern';
					}
					print '
						<h1 class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_req_dir_missing').'</h1>
						<p>'.cxc_templates_gTxt('cxc_tpl_req_dir_this').$missing_dir.cxc_templates_gTxt('cxc_tpl_req_directory').$is_missing_loc.cxc_templates_gTxt('cxc_tpl_req_properly').$missing_dir.cxc_templates_gTxt('cxc_tpl_req_dir_auto').'</p>
						<p>'.cxc_templates_gTxt('cxc_tpl_req_manual').'</p>
<pre><code>    mkdir '.$missing_loc.$missing_dir.'
    chmod 777 '.$missing_loc.$missing_dir.'
</code></pre>
						<p>'.cxc_templates_gTxt('cxc_tpl_req_after').$is_missing_dir.cxc_templates_gTxt('cxc_tpl_req_code').'</p>
						<p>'.cxc_templates_gTxt('cxc_tpl_secure_dir').'</p>
						<h2><a href="index.php?event=cxc_templates">&#8617; '.cxc_templates_gTxt('cxc_tpl_return').'</a></h2>
					';
				}
			}
		}

		print "
				</td>
			</tr>
		</table>
		";
	}

	class cxc_template {
		function cxc_template() {
			global $prefs;
			global $_CXC_TEMPLATES;

			$this->_config = $_CXC_TEMPLATES;

		/*
			PRIVATE CONFIG
			------------------------------------------------------
		*/
			$this->_config['root_path']         =   $prefs['path_to_site'];
			$this->_config['full_base_path']    =   sprintf(
														'%s'. DIRECTORY_SEPARATOR .'%s',
														$this->_config['root_path'],
														$this->_config['base_dir']
													);

			$this->_config['error_template']    =   '
				<h1 class="cxc-tpl-failure">%s</h1>
				<p>%s</p>
			';

			$missing_dir_head   = cxc_templates_gTxt('cxc_tpl_req_dir_missing');
			$missing_dir_text   = cxc_templates_gTxt('cxc_tpl_template_dir').'%1\$s'.cxc_templates_gTxt('cxc_tpl_req_dir_auto').' '.cxc_templates_gTxt('cxc_tpl_would_you').'</p><pre><code>    mkdir %1\$s\n    chmod 777 %1\$s</code></pre><p>'.cxc_templates_gTxt('cxc_tpl_should_fix');
			$cant_write_head    = cxc_templates_gTxt('cxc_tpl_not_writable');
			$cant_write_text    = cxc_templates_gTxt('cxc_tpl_chmod_write').'</p><pre><code>    chmod 777 %1\$s</code></pre><p>'.cxc_templates_gTxt('cxc_tpl_problem_fix');
			$cant_read_head     = cxc_templates_gTxt('cxc_tpl_not_readable');
			$cant_read_text     = cxc_templates_gTxt('cxc_tpl_chmod_read').'</p><pre><code>    chmod 777 %%1\$s</code></pre><p>'.cxc_templates_gTxt('cxc_tpl_problem_fix');
			$wrong_file_head	= cxc_templates_gTxt('cxc_tpl_wrong_file_type');
			$wrong_file_text	= cxc_templates_gTxt('cxc_tpl_corrupt_file');

			$this->_config['error_missing_dir'] =   sprintf(
														$this->_config['error_template'],
														$missing_dir_head,
														$missing_dir_text
													);
			$this->_config['error_cant_write']  =   sprintf(
														$this->_config['error_template'],
														$cant_write_head,
														$cant_write_text
													);
			$this->_config['error_cant_read']   =   sprintf(
														$this->_config['error_template'],
														$cant_read_head,
														$cant_read_text
													);
			$this->_config['error_wrong_file']	=	sprintf(
														$this->_config['error_template'],
														$wrong_file_head,
														$wrong_file_text
													);
	
			$this->exportTypes = array(
				'css'		=>	array(
									'ext'       =>  $this->_config['ext_css'],
									'data'      =>  'css',
									'fields'    =>  'name, css',
									'nice_name' =>  'CSS Rules',
									'regex'     =>  '/(.+)'.$this->_config['ext_css'].'/',
									'sql'       =>  '`css` = "%s"',
									'subdir'    =>  $this->_config['subdir_css'],
									'table'     =>  'txp_css',
									'filter'    =>	'1=1'
								),
				'forms'		=>	array(
									'ext'       =>  $this->_config['ext_forms'],
									'data'      =>  'Form',
									'fields'    =>  'name, type, Form',
									'nice_name' =>  'Form Files',
									'regex'     =>  '/(.+)\.(.+)'.$this->_config['ext_forms'].'/',
									'sql'       =>  '`Form` = "%s", `type` = "%s"',
									'subdir'    =>  $this->_config['subdir_forms'],
									'table'     =>  'txp_form',
									'filter'    =>	'1=1'
								),
				'pages'		=>	array(
									'ext'       =>  $this->_config['ext_pages'],
									'data'      =>  'user_html',
									'fields'    =>  'name, user_html',
									'nice_name' =>  'Page Files',
									'regex'     =>  '/(.+)'.$this->_config['ext_pages'].'/',
									'sql'       =>  '`user_html` = "%s"',
									'subdir'    =>  $this->_config['subdir_pages'],
									'table'     =>  'txp_page',
									'filter'    =>	'1=1'
								),
				'plugins'	=>	array(
									'ext'       =>  $this->_config['ext_plugins'],
									'data'      =>  'code',
									'fields'    =>  'name, status, author, author_uri, version, description, help, code, code_restore, code_md5, type',
									'nice_name' =>  'Plugin Files',
									'regex'     =>  '/(.+)\.(.+)'.$this->_config['ext_plugins'].'/',
									'sql'       =>  '`status` = %d, `author` = "%s", `author_uri` = "%s", `version` = "%s", `description` = "%s", `help` = "%s", `code` = "%s", `code_restore` = "%s", `code_md5` = "%s", `type` = %d',
									'subdir'    =>  $this->_config['subdir_plugins'],
									'table'     =>  'txp_plugin',
									'filter'    =>	'`status` = 1'
								),
				'sections'	=>	array(
									'ext'       =>  $this->_config['ext_section'],
									'data'      =>  'section',
									'fields'    =>  'name, page, css, in_rss, on_frontpage, searchable, title',
									'nice_name' =>  'Section Parameters',
									'regex'     =>  '/(.+)'.$this->_config['ext_section'].'/',
									'sql'       =>  '`page` = "%s", `css` = "%s", `in_rss` = "%d", `on_frontpage` = "%d", `searchable` = "%d", `title` = "%s"',
									'subdir'    =>  $this->_config['subdir_sections'],
									'table'     =>  'txp_section',
									'filter'    =>	'1=1'
								)
			);
		}

		function checkdir($dir = '', $type = _CXC_TEMPLATES_EXPORT) {
			/*
				If $type == _EXPORT, then:
					1.  Check to see that /base/path/$dir exists, and is
						writable.  If not, create it.
					2.  Check to see that /base/path/$dir/subdir_* exist,
						and are writable.  If not, create them.

				If $type == _IMPORT, then:
					1.  Check to see that /base/path/$dir exists, and is readable.
					2.  Check to see that /base/path/$dir/subdir_* exist, and are readable.
			*/
			$dir =  sprintf(
						'%s'. DIRECTORY_SEPARATOR .'%s',
						$this->_config['full_base_path'],
						$dir
					);

			$tocheck =  array(
							$dir,
							$dir. DIRECTORY_SEPARATOR .$this->_config['subdir_css'],
							$dir. DIRECTORY_SEPARATOR .$this->_config['subdir_forms'],
							$dir. DIRECTORY_SEPARATOR .$this->_config['subdir_pages'],
							$dir. DIRECTORY_SEPARATOR .$this->_config['subdir_plugins'],
							$dir. DIRECTORY_SEPARATOR .$this->_config['subdir_sections']
						);
			foreach ($tocheck as $curDir) {
				switch ($type) {
					case _CXC_TEMPLATES_IMPORT:
						if (!is_dir($curDir) && !mkdir($curDir, 0777)) {
							echo sprintf($this->_config['error_missing_dir'], $curDir);
							return false;
						}
						if (is_dir($curDir) && !is_readable($curDir)) {
							echo sprintf($this->_config['error_cant_read'], $curDir);
							return false;
						}
						break;

					case _CXC_TEMPLATES_EXPORT:
						if (!is_dir($curDir) && !mkdir($curDir, 0777)) {
								echo sprintf($this->_config['error_missing_dir'], $curDir);
								return false;
						}
						if (is_dir($curDir) && !is_writable($curDir) && !chmod($theme_dir, 0777)) {
							echo sprintf($this->_config['error_cant_write'], $curDir);
							return false;
						}
						break;
				}
			}
			return true;
		}

		function checkdirImportZip() {

			$dir =  $this->_config['full_base_path'];

			if (!is_dir($dir)) {
			   echo sprintf($this->_config['error_missing_dir'], $dir);
			   return false;
			}
			if (!is_readable($dir)) {
			   echo sprintf($this->_config['error_cant_read'], $dir);
			   return false;
			}
			return true;
		}

		function checkFileTypeZip() {
			if ($_FILES['file']['type'] != 'application/zip') {
			   echo sprintf($this->_config['error_wrong_text']);
			   return false;
			}
			return true;
		}

		/*
			EXPORT FUNCTIONS
			----------------------------------------------------------
		*/
		function export($dir = '') {
			if (!$this->checkdir($dir, _CXC_TEMPLATES_EXPORT)) {
				return;
			}

			print '
				<h1 class="cxc-tpl-slide-head"><a id="exporting-details" title="'.cxc_templates_gTxt('cxc_tpl_open_close').'">'.cxc_templates_gTxt('cxc_tpl_export_current').'</a> &lt;/&gt;</h1>
				<div class="cxc-tpl-slide-body">
				<blockquote>
			';

			foreach ($this->exportTypes as $type => $config) {
				print '
					<h1>'.$config['nice_name'].'</h1>
					<ul class="results">
				';

				$rows = safe_rows($config['fields'], $config['table'], $config['filter']);

				foreach ($rows as $row) {
					$filename		=	sprintf(
											'%s'.  DIRECTORY_SEPARATOR  .'%s'.  DIRECTORY_SEPARATOR  .'%s'.  DIRECTORY_SEPARATOR  .'%s%s',
											$this->_config['full_base_path'],
											$dir,
											$config['subdir'],
											$row['name'] . (isset($row['type'])?'.'.$row['type']:''),
											$config['ext']
										);
					$nicefilename	=	sprintf(
											'...'.  DIRECTORY_SEPARATOR  .'%s'.  DIRECTORY_SEPARATOR  .'%s'.  DIRECTORY_SEPARATOR  .'%s%s',
											$dir,
											$config['subdir'],
											$row['name'] . (isset($row['type'])?'.'.$row['type']:''),
											$config['ext']
										);

					$data = '';

					if (isset($row['css'])) {
						$data = $row['css'];
					} elseif ($type=='plugins') {
						$data = base64_encode(serialize($row));
					} elseif ($config['subdir'] != 'sections') {
						$data = $row[$config['data']];
					}

					$f = @fopen($filename, 'w+');
					if ($f) {
						if ($config['subdir'] == 'sections'){
							$this->writeSectionFiles($f,$row);
						} else {
							fwrite($f,$data);
						}
						fclose($f);
						print '
						<li><span class="cxc-tpl-success">'.cxc_templates_gTxt('cxc_tpl_export_success').'</span> '.$config['nice_name'].' \''.$row['name'].'\' to \''.$nicefilename.'\'</li>
						';
					} else {
						print '
						<li><span class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_export_failed').'</span> '.$config['nice_name'].' \''.$row['name'].'\' to \''.$nicefilename.'\'</li>
						';
					}
				}
				print '
					</ul>
				<br />
				';
			}
			print '
				</blockquote>
				</div>
			';
		}

		function writeSectionFiles($f,$row){
			if ($row['name'] == 'default'){
				$name = "name=default\n";
				$page = "page=".$row['page']."\n";
				$css = "css=".$row['css']."\n";
				$in_rss = "in_rss=1\n";
				$on_frontpage = "on_frontpage=1\n";
				$searchable = "searchable=1\n";
				$title = "title=default";
				fwrite($f,$name);
				fwrite($f,$page);
				fwrite($f,$css);
				fwrite($f,$in_rss);
				fwrite($f,$on_frontpage);
				fwrite($f,$searchable);
				fwrite($f,$title);
			}else{
				$name = "name=".$row['name']."\n";
				$page = "page=".$row['page']."\n";
				$css = "css=".$row['css']."\n";
				$in_rss = "in_rss=".$row['in_rss']."\n";
				$on_frontpage = "on_frontpage=".$row['on_frontpage']."\n";
				$searchable = "searchable=".$row['searchable']."\n";
				$title = "title=".$row['title'];
				fwrite($f,$name);
				fwrite($f,$page);
				fwrite($f,$css);
				fwrite($f,$in_rss);
				fwrite($f,$on_frontpage);
				fwrite($f,$searchable);
				fwrite($f,$title);
			}
		}

		/*
			IMPORT FUNCTIONS
			----------------------------------------------------------
		*/
		function getTemplateList() {
			if (!is_readable($this->_config['full_base_path'])) {
				return array();
			}
			$list = '';
			$dir = opendir($this->_config['full_base_path']);

			while(false !== ($filename = readdir($dir))) {
				if (
					is_dir(
						sprintf(
							'%s'.  DIRECTORY_SEPARATOR  .'%s',
							$this->_config['full_base_path'],
							$filename
						)
					) && $filename != '.' && $filename != '..'
				) {
					$list[$filename] = $filename;
				}
			}

			return $list;
		}

		function import($import_full, $dir) {
			if (!$this->checkdir($dir, _CXC_TEMPLATES_IMPORT)) {
				return;
			}
			$basedir =  sprintf(
							'%s'. DIRECTORY_SEPARATOR .'%s',
							$this->_config['full_base_path'],
							$dir
						);
			if (!set_pref('cxc_tpl_current', $dir, 'publish', 2)){
				print '
					<ul class="results">
						<li>'.cxc_templates_gTxt('cxc_tpl_update_unable').' '.str_replace('_', ' ', $dir).' '.cxc_templates_gTxt('cxc_tpl_update_none').'</li>
					</ul>
					<br />
				';
			}
			if (file_exists($basedir. DIRECTORY_SEPARATOR .'README.txt')){
				print '
				<div>'.
					file_get_contents($basedir. DIRECTORY_SEPARATOR .'README.txt').'
				</div>
				<br />
				';
			}

			/*
				Auto export into `preimport-data`
			*/
			print '
				<h1 class="cxc-tpl-slide-head"><a id="processing-backup" title="'.cxc_templates_gTxt('cxc_tpl_open_close').'">'.cxc_templates_gTxt('cxc_tpl_processing').'</a> &lt;/&gt;</h1>
				<div class="cxc-tpl-slide-body">
			';
			$pre_dir = $this->_config['full_base_path']. DIRECTORY_SEPARATOR .'preimport-data';
			if (is_dir($pre_dir)) {
				$objects = scandir($pre_dir);
				foreach ($objects as $object) {
					if ($object != '.' && $object != '..') {
						if (is_dir($pre_dir. DIRECTORY_SEPARATOR .$object)) {
							$this->removeDirectory($pre_dir. DIRECTORY_SEPARATOR .$object);
						} else { 
							@unlink($pre_dir. DIRECTORY_SEPARATOR .$object);
						}
					}
				}
				reset($objects);
				@rmdir($pre_dir);
			}
			if (!is_dir($pre_dir)){					
				print '
					<p>'.cxc_templates_gTxt('cxc_tpl_pre_remove').$this->_config['base_dir'].cxc_templates_gTxt('cxc_tpl_pre_newback').'</p>
				';
			} else {
				print '
					<p>'.cxc_templates_gTxt('cxc_tpl_pre_failed').'</p>
				';
			}
			print '
				<p>'.cxc_templates_gTxt('cxc_tpl_pre_note').'</p>
				</div>
			';

			$this->export('preimport-data');

			print '
				<h1 class="cxc-tpl-slide-head"><a id="importing-details" title="'.cxc_templates_gTxt('cxc_tpl_open_close').'">'.cxc_templates_gTxt('cxc_tpl_template_import').' <span class="cxc-tpl-capital">'.str_replace('_', ' ', $dir).'</span></a> &lt;/&gt;</h1>
				<div class="cxc-tpl-slide-body">
				<blockquote>
			';

			foreach ($this->exportTypes as $type => $config) {
				print '
					<h1>'.$config['nice_name'].'</h1>
					<ul class="results">
				';

				$exportdir =    sprintf(
									'%s'.  DIRECTORY_SEPARATOR  .'%s',
									$basedir,
									$config['subdir']
								);

				$dir	= opendir($exportdir);
				while (false !== ($filename = readdir($dir))) {
					if (preg_match($config['regex'], $filename, $filedata)) {
						$templateName = addslashes($filedata[1]);
						$templateType = (isset($filedata[2]))?$filedata[2]:'';

						$f =    sprintf(
									'%s'.  DIRECTORY_SEPARATOR  .'%s',
									$exportdir,
									$filename
								);

						if ($data = file($f)) {
							if ($type == 'css') {
								$data = doSlash(implode('', $data));
							} elseif ($type == 'plugins') {
								$data = doSlash(unserialize(base64_decode(implode('', $data))));
							} elseif ($type != 'sections') {
								$data = addslashes(implode('', $data));
							}

							if ($type == 'plugins') {
								$rs = safe_row('version, status', $config['table'], 'name="'.$templateName.'"');
								$set = sprintf($config['sql'], $data['status'], $data['author'], $data['author_uri'], $data['version'], $data['description'], $data['help'], $data['code'], $data['code_restore'], $data['code_md5'], $data['type']);
								if ($rs) {
									if ($rs['status'] == 0 || strcasecmp($data['version'], $rs['version']) < 0) {
										$result = safe_update($config['table'], $set, '`name` = "'.$templateName.'"');
									} else {
										$result = 1;
									}
									$success = ($result)?1:0;
								} else {
									$result = safe_insert($config['table'], $set.', `name` = "'.$templateName.'"');
									$success = ($result)?1:0;
								}
							} elseif ($type == 'sections') {
								$rs = safe_row('page, css, in_rss, on_frontpage, searchable, title', $config['table'], 'name="'.$templateName.'"');
								$set = $this->parseSectionFile($config['sql'], $data, $filename, $config['ext']);
								if ($import_full == 0){
									if ($rs) {
										$result = safe_update($config['table'], $set, '`name` = "'.$templateName.'"');
									} else {
										$result = safe_insert($config['table'], $set.', `name` = "'.$templateName.'"');
									}
								} else {
									$result = 1;
								}
								$success = ($result)?1:0;
							} else {
								if (safe_field('name', $config['table'], 'name="'.$templateName.'"')) {
									if ($import_full == 0){
										$result = safe_update($config['table'], sprintf($config['sql'], $data, $templateType), '`name` = "'.$templateName.'"');
									} else {
										$result = 0;
									}
									$success = ($result)?1:0;
								} else {
									$result = safe_insert($config['table'], sprintf($config['sql'], $data, $templateType).', `name` = "'.$templateName.'"');
									$success = ($result)?1:0;
								}
							}
						}

						//$success = true;
						if ($success == 1) {
							print '
						<li><span class="cxc-tpl-success">'.cxc_templates_gTxt('cxc_tpl_import_success').'</span> file "'.$filename.'"</li>
							';
						} else {
							if ($type == 'sections' && $import_full == 1){
								print '
						<li><span class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_import_skipped').'</span> file "'.$filename.'"</li>
								';
							} elseif ($import_full == 1){
								print '
						<li><span class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_import_skipped').'</span> "'.$filename.cxc_templates_gTxt('cxc_tpl_import_present').'</li>
								';
							} else {
								print '
						<li><span class="cxc-tpl-failure">'.cxc_templates_gTxt('cxc_tpl_import_failed').'</span> file "'.$filename.'"</li>
								';
							}
						}
					}
				}

				print '
					</ul>
					<br />
				';
			}
			print '
				</blockquote>
				</div>
			';
			if (file_exists($basedir. DIRECTORY_SEPARATOR .'DESIGNER.txt')){
				print '
				<h1 class="cxc-tpl-slide-head" title="'.cxc_templates_gTxt('cxc_tpl_add_click').'"><a id="additional-info">'.cxc_templates_gTxt('cxc_tpl_add_info').'</a> &lt;/&gt;</h1>
				<div class="cxc-tpl-slide-body">'.
					file_get_contents($basedir. DIRECTORY_SEPARATOR .'DESIGNER.txt').'
				</div>
				';
			}
		}

		function importZip($adv_live, $adv_root, $rel_temp_dir, $fileName) {
			global $prefs;

			if (!$this->checkdirImportZip()) {
					unlink($rel_temp_dir);
				return;
			}

			if (!$adv_root){
				$templates_base_dir = $this->_config['full_base_path'];
			} else {
				$templates_base_dir = $prefs['path_to_site'];
			}
			$full_temp_dir = $this->_config['root_path']. DIRECTORY_SEPARATOR ."textpattern". DIRECTORY_SEPARATOR .$rel_temp_dir;

			print '
				<ul class="results">
			';
			$zip = new cxc_dUnzip2($full_temp_dir);
			if (!empty($zip)) {
				$zip->unzipAll($templates_base_dir);
				$zip->__destroy($full_temp_dir);
				@unlink($full_temp_dir);
				print '<li><span class="cxc-tpl-success">'.cxc_templates_gTxt('cxc_tpl_upload_success').'</span> file "'.$fileName.'"</li>';
			} else {
				$zip->__destroy($full_temp_dir);
				@unlink($full_temp_dir);
				print '<li>'.cxc_templates_gTxt('cxc_tpl_move_fail').$fileName.cxc_templates_gTxt('cxc_tpl_move_temp').'</li>';
			}
			print '
				</ul>
				<br />
			';
		}

		function parseSectionFile($sql,$data,$fname,$ext) {
			$sectionValues = array  (
					// if section title is not within the file, use the filename without the extension
					'title'			=> substr($fname,0,-strlen($ext)),
					'page'			=> 'default',
					'css'			=> 'default',
					'in_rss'		=> 1,
					'on_frontpage'	=> 1,
					'searchable'	=> 1,
				);

			foreach($data as $line) {
				// Split the 'attribute = value' from within the section parameters file.
				// Ignore whitespace surrounding both the attribute and the value.
				// Limit the split to 2 values (just in case the right part contains another '='
				// which is very unlikely, anyway.
				$splitText = split('=',$line,2);
				$sectionParameter = trim($splitText[0]);
				$sectionValues[$sectionParameter] = trim($splitText[1]);
			}

			$sectionLine = sprintf($sql, $sectionValues['page'], $sectionValues['css'], $sectionValues['in_rss'], $sectionValues['on_frontpage'], $sectionValues['searchable'], $sectionValues['title']);
			return $sectionLine;
		}

		/*
			OTHER FUNCTIONS
			----------------------------------------------------------
		*/

		function cxc_tpl_current($tpl_dir){
			global $prefs;

			$tpl_pre = $tpl_dir. DIRECTORY_SEPARATOR .'preview';
			$tpl_alt = str_replace('_',' ',$prefs['cxc_tpl_current']).' '.cxc_templates_gTxt('cxc_tpl_preview_text');
			$readme = $tpl_dir. DIRECTORY_SEPARATOR .'README.txt';
			$design = $tpl_dir. DIRECTORY_SEPARATOR .'DESIGNER.txt';
			
			if (!empty($prefs['cxc_tpl_current']) && is_dir($tpl_dir)){

				if ($img_size = @getimagesize($tpl_pre.'.gif')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$prefs['cxc_tpl_current'].'/preview.gif';
				} elseif ($img_size = @getimagesize($tpl_pre.'.jpg')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$prefs['cxc_tpl_current'].'/preview.jpg';
				} elseif ($img_size = @getimagesize($tpl_pre.'.png')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$prefs['cxc_tpl_current'].'/preview.png';
				}
	
				print '<h2 class="cxc-tpl-capital">'.str_replace('_',' ',$prefs['cxc_tpl_current']).' '.cxc_templates_gTxt('cxc_tpl_template').'</h2>';
				if (isset($tpl_preview)) {
					print '<p class="cxc-tpl-default"><img src="'.$tpl_preview.'" width="200px" height="auto" alt="'.$tpl_alt.'" /></p>';
				} else {
					print '<p class="cxc-tpl-padded">'.cxc_templates_gTxt('cxc_tpl_preview_none').'</p>';
				}
				if (file_exists($readme) || file_exists($design)) {
					print form(''.
							graf(
								fInput('submit', 'go', ''.cxc_templates_gTxt('cxc_tpl_preview_docs').'', 'smallerbox').
								eInput('cxc_templates').sInput('docs')
							)
					);
				} else {
					print '<p class="cxc-tpl-smaller">'.cxc_templates_gTxt('cxc_tpl_preview_note').str_replace('_',' ',$prefs['cxc_tpl_current']).cxc_templates_gTxt('cxc_tpl_preview_last').'</p>';					
				}
			}
		}

		function cxc_tpl_preview($dir, $tpl_dir){
			global $prefs;

			$tpl_pre = $tpl_dir. DIRECTORY_SEPARATOR .'preview';
			$tpl_alt = str_replace('_',' ',$dir).' '.cxc_templates_gTxt('cxc_tpl_preview_text');
			if (is_dir($tpl_dir)){

				if ($img_size = @getimagesize($tpl_pre.'.gif')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$dir.'/preview.gif';
				} elseif ($img_size = @getimagesize($tpl_pre.'.jpg')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$dir.'/preview.jpg';
				} elseif ($img_size = @getimagesize($tpl_pre.'.png')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$dir.'/preview.png';
				}
	
				if ($dir == '') {
					print '
					<h2>'.cxc_templates_gTxt('cxc_tpl_top_level').str_replace('_',' ',$this->_config['base_dir']).cxc_templates_gTxt('cxc_tpl_top_dir').'</h2>
					<p class="cxc-tpl-smaller">'.cxc_templates_gTxt('cxc_tpl_top_remove').'</p>				
					';
				} else {
					print '<h2 class="cxc-tpl-capital">'.str_replace('_',' ',$dir).' '.cxc_templates_gTxt('cxc_tpl_template').'</h2>';
				}
				if (isset($tpl_preview)) {
					print '<p class="cxc-tpl-default"><img src="'.$tpl_preview.'" width="200px" height="auto" alt="'.$tpl_alt.'" /></p>';
				} else {
					print '<p class="cxc-tpl-padded">'.cxc_templates_gTxt('cxc_tpl_preview_none').'</p>';
				}
			}
		}

		function cxc_tpl_docs($tpl_dir){
			global $prefs;

			$basedir =  sprintf(
							'%s'. DIRECTORY_SEPARATOR .'%s',
							$this->_config['full_base_path'],
							$tpl_dir
						);
			$tpl_dir = $prefs['path_to_site']. DIRECTORY_SEPARATOR .$this->_config['base_dir']. DIRECTORY_SEPARATOR .$prefs['cxc_tpl_current'];
			$tpl_pre = $tpl_dir. DIRECTORY_SEPARATOR .'preview';
			$tpl_alt = str_replace('_',' ',$prefs['cxc_tpl_current']).' '.cxc_templates_gTxt('cxc_tpl_preview_text');
			$readme = $basedir. DIRECTORY_SEPARATOR .'README.txt';
			$design = $basedir. DIRECTORY_SEPARATOR .'DESIGNER.txt';

			if (!empty($prefs['cxc_tpl_current']) && $prefs['cxc_tpl_current'] != 'preimport-data') {
				print '
					<div class="cxc-tpl-preview">
				';

				if ($img_size = @getimagesize($tpl_pre.'.gif')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$prefs['cxc_tpl_current'].'/preview.gif';
				} elseif ($img_size = @getimagesize($tpl_pre.'.jpg')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$prefs['cxc_tpl_current'].'/preview.jpg';
				} elseif ($img_size = @getimagesize($tpl_pre.'.png')) {
					$tpl_preview = '../'.$this->_config['base_dir'].'/'.$prefs['cxc_tpl_current'].'/preview.png';
				}
	
				print '<h2 class="cxc-tpl-capital">'.cxc_templates_gTxt('cxc_tpl_preview_img').'</h2>';
				if (isset($tpl_preview)) {
					print '<p><img src="'.$tpl_preview.'" width="200px" height="auto" alt="'.$tpl_alt.'" /></p>';
				} else {
					print '<p class="cxc-tpl-padded">'.cxc_templates_gTxt('cxc_tpl_preview_none').'</p>';
				}
				print '
					<br />
					</div>
				';
			}
			if (file_exists($readme)){
				print '
					<div>'.
						file_get_contents($readme).'
					</div>
					<br />
				';
			}
			if (file_exists($design)){
				print '
					<h1>'.cxc_templates_gTxt('cxc_tpl_add_info').'</h1>
					<div>'.
						file_get_contents($design).'
					</div>
					<br />
				';
			}			
		}

		function cxc_tpl_downzip($folder, $to='archive.zip', $basedir) {
			$zip = new cxc_dZip($to, $overwrite=true);
			$php_modules = array_map('strtolower', get_loaded_extensions());
			if (in_array('zlib', $php_modules)) {
				$found = array(rtrim($folder,DIRECTORY_SEPARATOR.'\/'));
				while ($path = each($found)) {
					$path = current($path);
					if (is_dir($path)) {
						foreach (scandir($path) as $subpath) {
							if ($subpath=='.'||$subpath=='..'||substr($subpath,-2)==DIRECTORY_SEPARATOR.'.'||substr($subpath,-3)==DIRECTORY_SEPARATOR.'..') continue;
							$found[] = $path.DIRECTORY_SEPARATOR.$subpath;
						}
					} else {
						$zip->addFile($path, substr($path, strlen($basedir)));
					}
				}
				$zip->save();
				if (file_exists($to)) {
					header ("Content-Type: application/zip");
					header ("Content-Disposition: attachment; filename=$to");
					header ("Pragma: no-cache");
					header ("Expires: 0");
					if (!readfile($to)){
						print cxc_templates_gTxt('cxc_tpl_error_zip_dir');
					}
					if (!unlink($to)) {
						print cxc_templates_gTxt('cxc_tpl_error_zip_remove');
					}
		
					return true;
				} else {
					print cxc_templates_gTxt('cxc_tpl_error_zip_final');
				}
			} else {
				print cxc_templates_gTxt('cxc_tpl_error_zip_failed').' '.$to.' '.cxc_templates_gTxt('cxc_tpl_error_zip_archive');
			}
			return false;
		}

		function removeDirectory($dir) {
			if (is_dir($dir)) {
				$objects = scandir($dir);
				foreach ($objects as $object) {
					if ($object != '.' && $object != '..') {
						if (is_dir($dir. DIRECTORY_SEPARATOR .$object)) {
							$this->removeDirectory($dir.'/'.$object);
						} else { 
							@unlink($dir. DIRECTORY_SEPARATOR .$object);
						}
					}
				}
				reset($objects);
				@rmdir($dir);
			}
		}

		function writeIndexFiles($dir) {
			if (is_dir($dir)) {
				if (!file_exists($dir. DIRECTORY_SEPARATOR .'index.html')) {
					$f = @fopen($dir. DIRECTORY_SEPARATOR .'index.html', 'x+');
					if ($f) {
						fwrite($f,'<html><body bgcolor="#FFFFFF"></body></html>');
						fclose($f);
					}
				}
				$objects = scandir($dir);
				foreach ($objects as $object) {
					if ($object != '.' && $object != '..') {
						if (is_dir($dir. DIRECTORY_SEPARATOR .$object)) {
							$this->writeIndexFiles($dir.'/'.$object);
						}
					}
				}
				reset($objects);
			}
		}
	}

##############################################################
# Class dUnzip2 v2.62
#
#  Author: Alexandre Tedeschi (d)
#  E-Mail: alexandrebr at gmail dot com
#  Londrina - PR / Brazil
#
#  Objective:
#    This class allows programmer to easily unzip files on the fly.
#
#  Requirements:
#    This class requires extension ZLib Enabled. It is default
#    for most site hosts around the world, and for the PHP Win32 dist.
#
#  To do:
#   * Error handling
#   * Write a PHP-Side gzinflate, to completely avoid any external extensions
#   * Write other decompress algorithms
#
#  Methods:
#  * dUnzip2($filename)         - Constructor - Opens $filename
#  * getList([$stopOnFile])     - Retrieve the file list
#  * getExtraInfo($zipfilename) - Retrieve more information about compressed file
#  * getZipInfo([$entry])       - Retrieve ZIP file details.
#  * unzip($zipfilename, [$outfilename, [$applyChmod]]) - Unzip file
#  * unzipAll([$outDir, [$zipDir, [$maintainStructure, [$applyChmod]]]])
#  * close()                    - Close file handler, but keep the list
#  * __destroy()                - Close file handler and release memory
#
#  If you modify this class, or have any ideas to improve it, please contact me!
#  You are allowed to redistribute this class, if you keep my name and contact e-mail on it.
#
#  PLEASE! IF YOU USE THIS CLASS IN ANY OF YOUR PROJECTS, PLEASE LET ME KNOW!
#  If you have problems using it, don't think twice before contacting me!
#
##############################################################

	if(!function_exists('file_put_contents')){
		// If not PHP5, creates a compatible function
		Function file_put_contents($file, $data){
			if($tmp = fopen($file, "w")){
				fwrite($tmp, $data);
				fclose($tmp);
				return true;
			}
			echo "<b>file_put_contents:</b> Cannot create file $file<br>";
			return false;
		}
	}
	
	class cxc_dUnzip2{
		function getVersion(){
			return "2.62";
		}
		// Public
		var $fileName;
		var $lastError;
		var $compressedList; // You will problably use only this one!
		var $centralDirList; // Central dir list... It's a kind of 'extra attributes' for a set of files
		var $endOfCentral;   // End of central dir, contains ZIP Comments
		var $debug;
	
		// Private
		var $fh;
		var $zipSignature = "\x50\x4b\x03\x04"; // local file header signature
		var $dirSignature = "\x50\x4b\x01\x02"; // central dir header signature
		var $dirSignatureE= "\x50\x4b\x05\x06"; // end of central dir signature
	
		// Public
		function cxc_dUnzip2($fileName){
			$this->fileName       = $fileName;
			$this->compressedList =
			$this->centralDirList =
			$this->endOfCentral   = Array();
		}
	
		function getList($stopOnFile=false){
			if(sizeof($this->compressedList)){
				$this->debugMsg(1, "Returning already loaded file list.");
				return $this->compressedList;
			}
	
			// Open file, and set file handler
			$fh = fopen($this->fileName, "r");
			$this->fh = &$fh;
			if(!$fh){
				$this->debugMsg(2, "Failed to load file.");
				return false;
			}
	
			$this->debugMsg(1, "Loading list from 'End of Central Dir' index list...");
			if(!$this->_loadFileListByEOF($fh, $stopOnFile)){
				$this->debugMsg(1, "Failed! Trying to load list looking for signatures...");
				if(!$this->_loadFileListBySignatures($fh, $stopOnFile)){
					$this->debugMsg(1, "Failed! Could not find any valid header.");
					$this->debugMsg(2, "ZIP File is corrupted or empty");
					return false;
				}
			}
	
			if($this->debug){
				#------- Debug compressedList
				$kkk = 0;
				echo "<table border='0' style='font: 11px Verdana; border: 1px solid #000'>";
				foreach($this->compressedList as $fileName=>$item){
					if(!$kkk && $kkk=1){
						echo "<tr style='background: #ADA'>";
						foreach($item as $fieldName=>$value)
							echo "<td>$fieldName</td>";
						echo '</tr>';
					}
					echo "<tr style='background: #CFC'>";
					foreach($item as $fieldName=>$value){
						if($fieldName == 'lastmod_datetime')
							echo "<td title='$fieldName' nowrap='nowrap'>".date("d/m/Y H:i:s", $value)."</td>";
						else
							echo "<td title='$fieldName' nowrap='nowrap'>$value</td>";
					}
					echo "</tr>";
				}
				echo "</table>";
	
				#------- Debug centralDirList
				$kkk = 0;
				if(sizeof($this->centralDirList)){
					echo "<table border='0' style='font: 11px Verdana; border: 1px solid #000'>";
					foreach($this->centralDirList as $fileName=>$item){
						if(!$kkk && $kkk=1){
							echo "<tr style='background: #AAD'>";
							foreach($item as $fieldName=>$value)
								echo "<td>$fieldName</td>";
							echo '</tr>';
						}
						echo "<tr style='background: #CCF'>";
						foreach($item as $fieldName=>$value){
							if($fieldName == 'lastmod_datetime')
								echo "<td title='$fieldName' nowrap='nowrap'>".date("d/m/Y H:i:s", $value)."</td>";
							else
								echo "<td title='$fieldName' nowrap='nowrap'>$value</td>";
						}
						echo "</tr>";
					}
					echo "</table>";
				}
	
				#------- Debug endOfCentral
				$kkk = 0;
				if(sizeof($this->endOfCentral)){
					echo "<table border='0' style='font: 11px Verdana' style='border: 1px solid #000'>";
					echo "<tr style='background: #DAA'><td colspan='2'>dUnzip - End of file</td></tr>";
					foreach($this->endOfCentral as $field=>$value){
						echo "<tr>";
						echo "<td style='background: #FCC'>$field</td>";
						echo "<td style='background: #FDD'>$value</td>";
						echo "</tr>";
					}
					echo "</table>";
				}
			}
	
			return $this->compressedList;
		}
		function getExtraInfo($compressedFileName){
			return
				isset($this->centralDirList[$compressedFileName])?
				$this->centralDirList[$compressedFileName]:
				false;
		}
		function getZipInfo($detail=false){
			return $detail?
				$this->endOfCentral[$detail]:
				$this->endOfCentral;
		}
	
		function unzip($compressedFileName, $targetFileName=false, $applyChmod=0777){
			if(!sizeof($this->compressedList)){
				$this->debugMsg(1, "Trying to unzip before loading file list... Loading it!");
				$this->getList(false, $compressedFileName);
			}
	
			$fdetails = &$this->compressedList[$compressedFileName];
			if(!isset($this->compressedList[$compressedFileName])){
				$this->debugMsg(2, "File '<b>$compressedFileName</b>' is not compressed in the zip.");
				return false;
			}
			if(substr($compressedFileName, -1) == "/"){
				$this->debugMsg(2, "Trying to unzip a folder name '<b>$compressedFileName</b>'.");
				return false;
			}
			if(!$fdetails['uncompressed_size']){
				$this->debugMsg(1, "File '<b>$compressedFileName</b>' is empty.");
				return $targetFileName?
					file_put_contents($targetFileName, ""):
					"";
			}
	
			fseek($this->fh, $fdetails['contents-startOffset']);
			$ret = $this->uncompress(
					fread($this->fh, $fdetails['compressed_size']),
					$fdetails['compression_method'],
					$fdetails['uncompressed_size'],
					$targetFileName
				);
			if($applyChmod && $targetFileName)
				chmod($targetFileName, 0777);
			
			return $ret;
		}
		function unzipAll($targetDir=false, $baseDir="", $maintainStructure=true, $applyChmod=0777){
			if($targetDir === false)
				$targetDir = dirname($_SERVER['SCRIPT_FILENAME'])."/";
			
			$lista = $this->getList();
			if(sizeof($lista)) foreach($lista as $fileName=>$trash){
				$dirname  = dirname($fileName);
				$outDN    = "$targetDir/$dirname";
				
				if(substr($dirname, 0, strlen($baseDir)) != $baseDir)
					continue;
				
				if(!is_dir($outDN) && $maintainStructure){
					$str = "";
					$folders = explode("/", $dirname);
					foreach($folders as $folder){
						$str = $str?"$str/$folder":$folder;
						if(!is_dir("$targetDir/$str")){
							$this->debugMsg(1, "Creating folder: $targetDir/$str");
							mkdir("$targetDir/$str");
							if($applyChmod)
								chmod("$targetDir/$str", $applyChmod);
						}
					}
				}
				if(substr($fileName, -1, 1) == "/")
					continue;
	
				$maintainStructure?
					$this->unzip($fileName, "$targetDir/$fileName", $applyChmod):
					$this->unzip($fileName, "$targetDir/".basename($fileName), $applyChmod);
			}
		}
		
		function close(){     // Free the file resource
			if($this->fh)
				fclose($this->fh);
		}
		function __destroy(){ 
			$this->close();
		}
	
		// Private (you should NOT call these methods):
		function uncompress(&$content, $mode, $uncompressedSize, $targetFileName=false){
			switch($mode){
				case 0:
					// Not compressed
					return $targetFileName?
						file_put_contents($targetFileName, $content):
						$content;
				case 1:
					$this->debugMsg(2, "Shrunk mode is not supported... yet?");
					return false;
				case 2:
				case 3:
				case 4:
				case 5:
					$this->debugMsg(2, "Compression factor ".($mode-1)." is not supported... yet?");
					return false;
				case 6:
					$this->debugMsg(2, "Implode is not supported... yet?");
					return false;
				case 7:
					$this->debugMsg(2, "Tokenizing compression algorithm is not supported... yet?");
					return false;
				case 8:
					// Deflate
					return $targetFileName?
						file_put_contents($targetFileName, gzinflate($content, $uncompressedSize)):
						gzinflate($content, $uncompressedSize);
				case 9:
					$this->debugMsg(2, "Enhanced Deflating is not supported... yet?");
					return false;
				case 10:
					$this->debugMsg(2, "PKWARE Date Compression Library Impoloding is not supported... yet?");
					return false;
			   case 12:
				   // Bzip2
				   return $targetFileName?
					   file_put_contents($targetFileName, bzdecompress($content)):
					   bzdecompress($content);
				case 18:
					$this->debugMsg(2, "IBM TERSE is not supported... yet?");
					return false;
				default:
					$this->debugMsg(2, "Unknown uncompress method: $mode");
					return false;
			}
		}
		function debugMsg($level, $string){
			if($this->debug){
				if($level == 1)
					echo "<b style='color: #777'>dUnzip2:</b> $string<br>";
				
				if($level == 2)
					echo "<b style='color: #F00'>dUnzip2:</b> $string<br>";
			}
			$this->lastError = $string;
		}
		function getLastError(){
			return $this->lastError;
		}
	
		function _loadFileListByEOF(&$fh, $stopOnFile=false){
			// Check if there's a valid Central Dir signature.
			// Let's consider a file comment smaller than 1024 characters...
			// Actually, it length can be 65536.. But we're not going to support it.
	
			for($x = 0; $x < 1024; $x++){
				fseek($fh, -22-$x, SEEK_END);
	
				$signature = fread($fh, 4);
				if($signature == $this->dirSignatureE){
					// If found EOF Central Dir
					$eodir['disk_number_this']   = unpack("v", fread($fh, 2)); // number of this disk
					$eodir['disk_number']        = unpack("v", fread($fh, 2)); // number of the disk with the start of the central directory
					$eodir['total_entries_this'] = unpack("v", fread($fh, 2)); // total number of entries in the central dir on this disk
					$eodir['total_entries']      = unpack("v", fread($fh, 2)); // total number of entries in
					$eodir['size_of_cd']         = unpack("V", fread($fh, 4)); // size of the central directory
					$eodir['offset_start_cd']    = unpack("V", fread($fh, 4)); // offset of start of central directory with respect to the starting disk number
					$zipFileCommentLenght        = unpack("v", fread($fh, 2)); // zipfile comment length
					$eodir['zipfile_comment']    = $zipFileCommentLenght[1]?fread($fh, $zipFileCommentLenght[1]):''; // zipfile comment
					$this->endOfCentral = Array(
						'disk_number_this'=>$eodir['disk_number_this'][1],
						'disk_number'=>$eodir['disk_number'][1],
						'total_entries_this'=>$eodir['total_entries_this'][1],
						'total_entries'=>$eodir['total_entries'][1],
						'size_of_cd'=>$eodir['size_of_cd'][1],
						'offset_start_cd'=>$eodir['offset_start_cd'][1],
						'zipfile_comment'=>$eodir['zipfile_comment'],
					);
	
					// Then, load file list
					fseek($fh, $this->endOfCentral['offset_start_cd']);
					$signature = fread($fh, 4);
	
					while($signature == $this->dirSignature){
						$dir['version_madeby']      = unpack("v", fread($fh, 2)); // version made by
						$dir['version_needed']      = unpack("v", fread($fh, 2)); // version needed to extract
						$dir['general_bit_flag']    = unpack("v", fread($fh, 2)); // general purpose bit flag
						$dir['compression_method']  = unpack("v", fread($fh, 2)); // compression method
						$dir['lastmod_time']        = unpack("v", fread($fh, 2)); // last mod file time
						$dir['lastmod_date']        = unpack("v", fread($fh, 2)); // last mod file date
						$dir['crc-32']              = fread($fh, 4);              // crc-32
						$dir['compressed_size']     = unpack("V", fread($fh, 4)); // compressed size
						$dir['uncompressed_size']   = unpack("V", fread($fh, 4)); // uncompressed size
						$fileNameLength             = unpack("v", fread($fh, 2)); // filename length
						$extraFieldLength           = unpack("v", fread($fh, 2)); // extra field length
						$fileCommentLength          = unpack("v", fread($fh, 2)); // file comment length
						$dir['disk_number_start']   = unpack("v", fread($fh, 2)); // disk number start
						$dir['internal_attributes'] = unpack("v", fread($fh, 2)); // internal file attributes-byte1
						$dir['external_attributes1']= unpack("v", fread($fh, 2)); // external file attributes-byte2
						$dir['external_attributes2']= unpack("v", fread($fh, 2)); // external file attributes
						$dir['relative_offset']     = unpack("V", fread($fh, 4)); // relative offset of local header
						$dir['file_name']           = fread($fh, $fileNameLength[1]);                             // filename
						$dir['extra_field']         = $extraFieldLength[1] ?fread($fh, $extraFieldLength[1]) :''; // extra field
						$dir['file_comment']        = $fileCommentLength[1]?fread($fh, $fileCommentLength[1]):''; // file comment
	
						// Convert the date and time, from MS-DOS format to UNIX Timestamp
						$BINlastmod_date = str_pad(decbin($dir['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
						$BINlastmod_time = str_pad(decbin($dir['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);
						$lastmod_dateY = bindec(substr($BINlastmod_date,  0, 7))+1980;
						$lastmod_dateM = bindec(substr($BINlastmod_date,  7, 4));
						$lastmod_dateD = bindec(substr($BINlastmod_date, 11, 5));
						$lastmod_timeH = bindec(substr($BINlastmod_time,   0, 5));
						$lastmod_timeM = bindec(substr($BINlastmod_time,   5, 6));
						$lastmod_timeS = bindec(substr($BINlastmod_time,  11, 5));
	
						// Some protection agains attacks...
						if(!$dir['file_name'] = $this->_protect($dir['file_name']))
							continue;
	
						$this->centralDirList[$dir['file_name']] = Array(
							'version_madeby'=>$dir['version_madeby'][1],
							'version_needed'=>$dir['version_needed'][1],
							'general_bit_flag'=>str_pad(decbin($dir['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
							'compression_method'=>$dir['compression_method'][1],
							'lastmod_datetime'  =>mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY),
							'crc-32'            =>str_pad(dechex(ord($dir['crc-32'][3])), 2, '0', STR_PAD_LEFT).
												  str_pad(dechex(ord($dir['crc-32'][2])), 2, '0', STR_PAD_LEFT).
												  str_pad(dechex(ord($dir['crc-32'][1])), 2, '0', STR_PAD_LEFT).
												  str_pad(dechex(ord($dir['crc-32'][0])), 2, '0', STR_PAD_LEFT),
							'compressed_size'=>$dir['compressed_size'][1],
							'uncompressed_size'=>$dir['uncompressed_size'][1],
							'disk_number_start'=>$dir['disk_number_start'][1],
							'internal_attributes'=>$dir['internal_attributes'][1],
							'external_attributes1'=>$dir['external_attributes1'][1],
							'external_attributes2'=>$dir['external_attributes2'][1],
							'relative_offset'=>$dir['relative_offset'][1],
							'file_name'=>$dir['file_name'],
							'extra_field'=>$dir['extra_field'],
							'file_comment'=>$dir['file_comment'],
						);
						$signature = fread($fh, 4);
					}
	
					// If loaded centralDirs, then try to identify the offsetPosition of the compressed data.
					if($this->centralDirList) foreach($this->centralDirList as $filename=>$details){
						$i = $this->_getFileHeaderInformation($fh, $details['relative_offset']);
						$this->compressedList[$filename]['file_name']          = $filename;
						$this->compressedList[$filename]['compression_method'] = $details['compression_method'];
						$this->compressedList[$filename]['version_needed']     = $details['version_needed'];
						$this->compressedList[$filename]['lastmod_datetime']   = $details['lastmod_datetime'];
						$this->compressedList[$filename]['crc-32']             = $details['crc-32'];
						$this->compressedList[$filename]['compressed_size']    = $details['compressed_size'];
						$this->compressedList[$filename]['uncompressed_size']  = $details['uncompressed_size'];
						$this->compressedList[$filename]['lastmod_datetime']   = $details['lastmod_datetime'];
						$this->compressedList[$filename]['extra_field']        = $i['extra_field'];
						$this->compressedList[$filename]['contents-startOffset']=$i['contents-startOffset'];
						if(strtolower($stopOnFile) == strtolower($filename))
							break;
					}
					return true;
				}
			}
			return false;
		}
		function _loadFileListBySignatures(&$fh, $stopOnFile=false){
			fseek($fh, 0);
			
			$return = false;
			for(;;){
				$details = $this->_getFileHeaderInformation($fh);
				if(!$details){
					$this->debugMsg(1, "Invalid signature. Trying to verify if is old style Data Descriptor...");
					fseek($fh, 12 - 4, SEEK_CUR); // 12: Data descriptor - 4: Signature (that will be read again)
					$details = $this->_getFileHeaderInformation($fh);
				}
				if(!$details){
					$this->debugMsg(1, "Still invalid signature. Probably reached the end of the file.");
					break;
				}
				$filename = $details['file_name'];
				$this->compressedList[$filename] = $details;
				$return = true;
				if(strtolower($stopOnFile) == strtolower($filename))
					break;
			}
			
			return $return;
		}
		function _getFileHeaderInformation(&$fh, $startOffset=false){
			if($startOffset !== false)
				fseek($fh, $startOffset);
			
			$signature = fread($fh, 4);
			if($signature == $this->zipSignature){
				# $this->debugMsg(1, "Zip Signature!");
				
				// Get information about the zipped file
				$file['version_needed']     = unpack("v", fread($fh, 2)); // version needed to extract
				$file['general_bit_flag']   = unpack("v", fread($fh, 2)); // general purpose bit flag
				$file['compression_method'] = unpack("v", fread($fh, 2)); // compression method
				$file['lastmod_time']       = unpack("v", fread($fh, 2)); // last mod file time
				$file['lastmod_date']       = unpack("v", fread($fh, 2)); // last mod file date
				$file['crc-32']             = fread($fh, 4);              // crc-32
				$file['compressed_size']    = unpack("V", fread($fh, 4)); // compressed size
				$file['uncompressed_size']  = unpack("V", fread($fh, 4)); // uncompressed size
				$fileNameLength             = unpack("v", fread($fh, 2)); // filename length
				$extraFieldLength           = unpack("v", fread($fh, 2)); // extra field length
				$file['file_name']          = fread($fh, $fileNameLength[1]); // filename
				$file['extra_field']        = $extraFieldLength[1]?fread($fh, $extraFieldLength[1]):''; // extra field
				$file['contents-startOffset']= ftell($fh);
				
				// Bypass the whole compressed contents, and look for the next file
				fseek($fh, $file['compressed_size'][1], SEEK_CUR);
				
				// Convert the date and time, from MS-DOS format to UNIX Timestamp
				$BINlastmod_date = str_pad(decbin($file['lastmod_date'][1]), 16, '0', STR_PAD_LEFT);
				$BINlastmod_time = str_pad(decbin($file['lastmod_time'][1]), 16, '0', STR_PAD_LEFT);
				$lastmod_dateY = bindec(substr($BINlastmod_date,  0, 7))+1980;
				$lastmod_dateM = bindec(substr($BINlastmod_date,  7, 4));
				$lastmod_dateD = bindec(substr($BINlastmod_date, 11, 5));
				$lastmod_timeH = bindec(substr($BINlastmod_time,   0, 5));
				$lastmod_timeM = bindec(substr($BINlastmod_time,   5, 6));
				$lastmod_timeS = bindec(substr($BINlastmod_time,  11, 5));
				
				// Some protection agains attacks...
				if(!$file['file_name'] = $this->_protect($file['file_name']))
					return;
				
				// Mount file table
				$i = Array(
					'file_name'         =>$file['file_name'],
					'compression_method'=>$file['compression_method'][1],
					'version_needed'    =>$file['version_needed'][1],
					'lastmod_datetime'  =>mktime($lastmod_timeH, $lastmod_timeM, $lastmod_timeS, $lastmod_dateM, $lastmod_dateD, $lastmod_dateY),
					'crc-32'            =>str_pad(dechex(ord($file['crc-32'][3])), 2, '0', STR_PAD_LEFT).
										  str_pad(dechex(ord($file['crc-32'][2])), 2, '0', STR_PAD_LEFT).
										  str_pad(dechex(ord($file['crc-32'][1])), 2, '0', STR_PAD_LEFT).
										  str_pad(dechex(ord($file['crc-32'][0])), 2, '0', STR_PAD_LEFT),
					'compressed_size'   =>$file['compressed_size'][1],
					'uncompressed_size' =>$file['uncompressed_size'][1],
					'extra_field'       =>$file['extra_field'],
					'general_bit_flag'  =>str_pad(decbin($file['general_bit_flag'][1]), 8, '0', STR_PAD_LEFT),
					'contents-startOffset'=>$file['contents-startOffset']
				);
				return $i;
			}
			return false;
		}
		
		function _protect($fullPath){
			// Known hack-attacks (filename like):
			//   /home/usr
			//   ../../home/usr
			//   folder/../../../home/usr
			//   sample/(x0)../home/usr
			
			$fullPath = strtr($fullPath, ":*<>|\"\x0\\", "......./");
			while($fullPath[0] == "/")
				$fullPath = substr($fullPath, 1);
			
			if(substr($fullPath, -1) == "/"){
				$base     = '';
				$fullPath = substr($fullPath, 0, -1);
			}
			else{
				$base     = basename($fullPath);
				$fullPath = dirname($fullPath);
			}
			
			$parts   = explode("/", $fullPath);
			$lastIdx = false;
			foreach($parts as $idx=>$part){
				if($part == ".")
					unset($parts[$idx]);
				elseif($part == ".."){
					unset($parts[$idx]);
					if($lastIdx !== false){
						unset($parts[$lastIdx]);
					}
				}
				elseif($part === ''){
					unset($parts[$idx]);
				}
				else{
					$lastIdx = $idx;
				}
			}
	
			$fullPath = sizeof($parts)?implode("/", $parts)."/":"";
			return $fullPath.$base;
		}
	}
	
	class cxc_dZip{
		var $filename;
		var $overwrite;
		
		var $zipSignature = "\x50\x4b\x03\x04"; // local file header signature
		var $dirSignature = "\x50\x4b\x01\x02"; // central dir header signature
		var $dirSignatureE= "\x50\x4b\x05\x06"; // end of central dir signature
		var $files_count  = 0;
		var $fh;
		
		function cxc_dZip($filename, $overwrite=true){
			$this->filename  = $filename;
			$this->overwrite = $overwrite;
		}
		function addDir($dirname, $fileComments=''){
			if(substr($dirname, -1) != '/')
				$dirname .= '/';
			$this->addFile(false, $dirname, $fileComments);
		}
		function addFile($filename, $cfilename, $fileComments='', $data=false){
			if(!($fh = &$this->fh))
				$fh = fopen($this->filename, $this->overwrite?'wb':'a+b');
			
			// $filename can be a local file OR the data wich will be compressed
			if(substr($cfilename, -1)=='/'){
				$details['uncsize'] = 0;
				$data = '';
			}
			elseif(file_exists($filename)){
				$details['uncsize'] = filesize($filename);
				$data = file_get_contents($filename);
			}
			elseif($filename){
				echo "<b>Cannot add $filename. File not found</b><br>";
				return false;
			}
			else{
				$details['uncsize'] = strlen($filename);
				// DATA is given.. use it! :|
			}
	
			// if data to compress is too small, just store it
			if($details['uncsize'] < 256){
				$details['comsize'] = $details['uncsize'];
				$details['vneeded'] = 10;
				$details['cmethod'] = 0;
				$zdata = &$data;
			}
			else{ // otherwise, compress it
				$zdata = gzcompress($data);
				$zdata = substr(substr($zdata, 0, strlen($zdata) - 4), 2); // fix crc bug (thanks to Eric Mueller)
				$details['comsize'] = strlen($zdata);
				$details['vneeded'] = 10;
				$details['cmethod'] = 8;
			}
			
			$details['bitflag'] = 0;
			$details['crc_32']  = crc32($data);
			
			// Convert date and time to DOS Format, and set then
			$lastmod_timeS  = str_pad(decbin(date('s')>=32?date('s')-32:date('s')), 5, '0', STR_PAD_LEFT);
			$lastmod_timeM  = str_pad(decbin(date('i')), 6, '0', STR_PAD_LEFT);
			$lastmod_timeH  = str_pad(decbin(date('H')), 5, '0', STR_PAD_LEFT);
			$lastmod_dateD  = str_pad(decbin(date('d')), 5, '0', STR_PAD_LEFT);
			$lastmod_dateM  = str_pad(decbin(date('m')), 4, '0', STR_PAD_LEFT);
			$lastmod_dateY  = str_pad(decbin(date('Y')-1980), 7, '0', STR_PAD_LEFT);
			
			# echo "ModTime: $lastmod_timeS-$lastmod_timeM-$lastmod_timeH (".date("s H H").")\n";
			# echo "ModDate: $lastmod_dateD-$lastmod_dateM-$lastmod_dateY (".date("d m Y").")\n";
			$details['modtime'] = bindec("$lastmod_timeH$lastmod_timeM$lastmod_timeS");
			$details['moddate'] = bindec("$lastmod_dateY$lastmod_dateM$lastmod_dateD");
			
			$details['offset'] = ftell($fh);
			fwrite($fh, $this->zipSignature);
			fwrite($fh, pack('s', $details['vneeded'])); // version_needed
			fwrite($fh, pack('s', $details['bitflag'])); // general_bit_flag
			fwrite($fh, pack('s', $details['cmethod'])); // compression_method
			fwrite($fh, pack('s', $details['modtime'])); // lastmod_time
			fwrite($fh, pack('s', $details['moddate'])); // lastmod_date
			fwrite($fh, pack('V', $details['crc_32']));  // crc-32
			fwrite($fh, pack('I', $details['comsize'])); // compressed_size
			fwrite($fh, pack('I', $details['uncsize'])); // uncompressed_size
			fwrite($fh, pack('s', strlen($cfilename)));   // file_name_length
			fwrite($fh, pack('s', 0));  // extra_field_length
			fwrite($fh, $cfilename);    // file_name
			// ignoring extra_field
			fwrite($fh, $zdata);
			
			// Append it to central dir
			$details['external_attributes']  = (substr($cfilename, -1)=='/'&&!$zdata)?16:32; // Directory or file name
			$details['comments']             = $fileComments;
			$this->appendCentralDir($cfilename, $details);
			$this->files_count++;
		}
		function setExtra($filename, $property, $value){
			$this->centraldirs[$filename][$property] = $value;
		}
		function save($zipComments=''){
			if(!($fh = &$this->fh))
				$fh = fopen($this->filename, $this->overwrite?'w':'a+');
			
			$cdrec = "";
			foreach($this->centraldirs as $filename=>$cd){
				$cdrec .= $this->dirSignature;
				$cdrec .= "\x0\x0";                  // version made by
				$cdrec .= pack('v', $cd['vneeded']); // version needed to extract
				$cdrec .= "\x0\x0";                  // general bit flag
				$cdrec .= pack('v', $cd['cmethod']); // compression method
				$cdrec .= pack('v', $cd['modtime']); // lastmod time
				$cdrec .= pack('v', $cd['moddate']); // lastmod date
				$cdrec .= pack('V', $cd['crc_32']);  // crc32
				$cdrec .= pack('V', $cd['comsize']); // compressed filesize
				$cdrec .= pack('V', $cd['uncsize']); // uncompressed filesize
				$cdrec .= pack('v', strlen($filename)); // file comment length
				$cdrec .= pack('v', 0);                // extra field length
				$cdrec .= pack('v', strlen($cd['comments'])); // file comment length
				$cdrec .= pack('v', 0); // disk number start
				$cdrec .= pack('v', 0); // internal file attributes
				$cdrec .= pack('V', $cd['external_attributes']); // internal file attributes
				$cdrec .= pack('V', $cd['offset']); // relative offset of local header
				$cdrec .= $filename;
				$cdrec .= $cd['comments'];
			}
			$before_cd = ftell($fh);
			fwrite($fh, $cdrec);
			
			// end of central dir
			fwrite($fh, $this->dirSignatureE);
			fwrite($fh, pack('v', 0)); // number of this disk
			fwrite($fh, pack('v', 0)); // number of the disk with the start of the central directory
			fwrite($fh, pack('v', $this->files_count)); // total # of entries "on this disk" 
			fwrite($fh, pack('v', $this->files_count)); // total # of entries overall 
			fwrite($fh, pack('V', strlen($cdrec)));     // size of central dir 
			fwrite($fh, pack('V', $before_cd));         // offset to start of central dir
			fwrite($fh, pack('v', strlen($zipComments))); // .zip file comment length
			fwrite($fh, $zipComments);
			
			fclose($fh);
		}
		
		// Private
		function appendCentralDir($filename,$properties){
			$this->centraldirs[$filename] = $properties;
		}
	} 

/*
	PLUGIN CODE::LANGUAGE SUPPORT = cxc_templates_gTxt('')
	-------------------------------------------------------------
*/
	function cxc_templates_gTxt($what, $atts = array()) {
		$lang = array(
			'cxc_tpl_templates_tab' => 'Templates',
			'cxc_tpl_process' => 'Process Templates',
			'cxc_tpl_preferences' => 'Plugin Preferences',
			'cxc_tpl_dbupdate' => '<span class="cxc-tpl-failure">Database update failed</span> entry for current template will be unavailable.',
			'cxc_tpl_return' => 'Click here to return to the template manager.',
			'cxc_tpl_delete_confirm' => 'Delete Directory Confirmation',
			'cxc_tpl_remove_before' => 'This will completely remove the "<strong>',
			'cxc_tpl_remove_after' => '</strong>" directory from your site, click "<strong>GO</strong>" to continue or use the link below to return to the template manager.',
			'cxc_tpl_removed' => 'Template Removed',
			'cxc_tpl_the_capital' => 'The <span class="cxc-tpl-capital"><strong>',
			'cxc_tpl_removed_from' => '</strong></span> template files have been successfully removed from the "<strong>',
			'cxc_tpl_removed_dir' => '</strong>" directory.',
			'cxc_tpl_remove_failed' => 'Unable to Remove Template',
			'cxc_tpl_not_removed' => '</span> template directory was not removed, this might be due to the server configuration of your host and removal of templates may need to be done manually',
			'cxc_tpl_template_import' => 'Template Import:',
			'cxc_tpl_import_failed_before' => '<span class="cxc-tpl-failure">Failed importing</span> the \'<span class="cxc-tpl-capital">',
			'cxc_tpl_import_failed_after' => '</span>\' template files',
			'cxc_tpl_upload_import_fail' => 'It was not possible to import the uploaded template because the Zip file contained more than one template or it was already present in the templates directory.',
			'cxc_tpl_import_template' => 'Import Template',
			'cxc_tpl_none_before' => 'There are no templates installed in the \'<strong>',
			'cxc_tpl_none_after' => '</strong>\' directory, please clone your current template or upload a new one.',
			'cxc_tpl_alternate_dir' => 'Use Alternate Template Directory (<em class="cxc-tpl-failure">not recommended</em>)',
			'cxc_tpl_alt_adjust' => 'You will need to adjust the location to be used as the template directory by modifying <code>$cxc_templates[\'base_dir\']</code> in the plugin\'s code. After you have adjusted the plugin\'s code it will try to automatically create the chosen directory for you if not already present in your webroot.',
			'cxc_tpl_alt_note' => '<strong>Note:</strong> this could affect template assets and result in broken links to css, images, JS and other template files so it is recommended you use the default or select the directory used by the templates designer.',
			'cxc_tpl_import_which' => 'Which template would you like to import?',
			'cxc_tpl_import_safe_mode' => 'Use Import Safe Mode (<em class="cxc-tpl-failure">non-destructive</em>)',
			'cxc_tpl_export_template' => 'Export Template',
			'cxc_tpl_export_name' => 'Choose a name for the exported template.',
			'cxc_tpl_delete_template' => 'Delete Template',
			'cxc_tpl_zip_template' => 'Zip Project Folder',
			'cxc_tpl_upload_template' => 'Upload Template',
			'cxc_tpl_upload_select' => 'Please select the template you would like to upload.',
			'cxc_tpl_upload_import' => 'Import Uploaded Template',
			'cxc_tpl_advanced_options' => 'Advanced Options',
			'cxc_tpl_root_install' => 'Web Root Installation (<em class="cxc-tpl-failure">not recommended</em>)',
			'cxc_tpl_advanced_note' => 'Note:</strong> <em>do not use unless required or you know what you are doing!',
			'cxc_tpl_feature_unavailable' => 'Feature Unavailable',
			'cxc_tpl_zlib_enabled_text' => 'If you are seeing this error it means your host compiled version of PHP does not include the ZLib modules. This feature requires the extension ZLib to be enabled, it is compiled by default for most site hosts around the world, and for the PHP Win32 distributions. If this is unavailable you should try speaking to your hosts to have it enabled, if they are not able to provide this for you, I recommend considering a new host. If you choose to remain with them you will have to unzip locally and FTP templates into your template directory, sorry for the inconvenience.',
			'cxc_tpl_req_dir_created' => 'Required Directories Created',
			'cxc_tpl_reload_click' => 'Click here to reload this page',
			'cxc_tpl_reload_display' => 'and display the template manager.',
			'cxc_tpl_req_dir_missing' => 'Required Directories Missing',
			'cxc_tpl_req_dir_this' => 'This plugin requires the `<strong>',
			'cxc_tpl_req_dir_theme' => '</strong>` directory to be located in the webroot for it to function properly and the `<strong>',
			'cxc_tpl_req_dir_cache' => '</strong>` directory to be in the textpattern directory, either `<strong>',
			'cxc_tpl_req_dir_or' => '</strong>` directory or `<strong>',
			'cxc_tpl_req_dir_auto' => '</strong>` directory does not exist, and could not be automatically created.',
			'cxc_tpl_req_dir_manual' => 'Please create these directories manually using your FTP client, hosting control panel or by running something like ...',
			'cxc_tpl_req_dir_after' => 'After you have created the missing directories, return to (or reload) this page to display the template manager. You could also adjust the plugin\'s directory by modifying <code>$cxc_templates[\'base_dir\']</code> and/or <code>$cxc_templates[\'cache_dir\']</code> in the plugin\'s code.',
			'cxc_tpl_add_security' => 'For additional security you may also want to include empty index.html files or adjust your .htaccess for the `<strong>',
			'cxc_tpl_add_empty_dir' => '</strong>` directory.',
			'cxc_tpl_webroot' => 'webroot',
			'cxc_tpl_req_directory' => '</strong>` directory to be located in the `<strong>',
			'cxc_tpl_req_properly' => '</strong>` directory for it to function properly, the `<strong>',
			'cxc_tpl_req_manual' => 'Please create the directory manually using your FTP client, hosting control panel or by running something like ...',
			'cxc_tpl_req_after' => 'After you have created the missing directory, return to (or reload) this page to display the template manager. You could also adjust the plugin\'s directory by modifying <code>$cxc_templates[\'',
			'cxc_tpl_req_code' => '\']</code> in the plugin\'s code.',
			'cxc_tpl_secure_dir' => 'For additional security you may also want to include empty index.html files or adjust your .htaccess for the directories.',
			'cxc_tpl_template_dir' => 'The template directory `<strong>',
			'cxc_tpl_would_you' => 'Would you mind creating it yourself by running something like ...',
			'cxc_tpl_should_fix' => 'That should fix the issue. You could also adjust the plugin\'s directory by modifying <code>\$cxc_templates[\'base_dir\']</code> in the plugin\'s code.',
			'cxc_tpl_not_writable' => 'Template Directory Not Writable',
			'cxc_tpl_chmod_write' => 'I can\'t seem to write to the template directory \'<strong>%1\$s</strong>\'.  Would you mind running something like ...',
			'cxc_tpl_not_readable' => 'Template Directory Not Readable',
			'cxc_tpl_chmod_read' => 'I can\'t seem to read from the template directory \'<strong>%1\$s</strong>\'.  Would you mind running something like ...',
			'cxc_tpl_problem_fix' => '... to fix the problem?',
			'cxc_tpl_wrong_file_type' => 'Unsupported File Type',
			'cxc_tpl_corrupt_file' => 'The file is either corrupt or is an unsupported file type, only zip files are currently supported by this plugin.',
			'cxc_tpl_open_close' => 'Click here to open/close detailed list of events.',
			'cxc_tpl_export_current' => 'Template Export: Current',
			'cxc_tpl_export_success' => 'Successfully exported',
			'cxc_tpl_export_failed' => 'Failed exporting',
			'cxc_tpl_update_unable' => '<span class="cxc-tpl-failure">Unable to update</span> entry for current template in the database,',
			'cxc_tpl_update_none' => 'template information will be unavailabl6e.',
			'cxc_tpl_processing' => 'Template Processing',
			'cxc_tpl_pre_remove' => 'The `<strong>preimport-data</strong>` template directory has been successfully removed from the `<strong>',
			'cxc_tpl_pre_newback' => '</strong>` directory so a new backup can be written to replace previous template backups.',
			'cxc_tpl_pre_failed' => 'The `<strong>preimport-data</strong>` template directory was not removed, the backup may contain additional files from previous backups. Your current template data will be added to `<strong>preimport-data</strong>` for future use.',
			'cxc_tpl_pre_note' => '<strong>Note:</strong> <em>installing another template will overwrite the current `<strong>preimport-data</strong>` so rename the directory to something else after it is exported to preserve a more permanent backup.</em>',
			'cxc_tpl_import_success' => 'Successfully imported',
			'cxc_tpl_import_skipped' => 'Skipped importing',
			'cxc_tpl_import_present' => '", it was already present.',
			'cxc_tpl_import_failed' => 'Failed importing',
			'cxc_tpl_add_click' => 'Click here to see additional information from the imported templates designer.',
			'cxc_tpl_add_info' => 'Additional Information',
			'cxc_tpl_upload_success' => 'Successfully uploaded',
			'cxc_tpl_move_fail' => '<span class="cxc-tpl-failure">Failed removing </span> file "',
			'cxc_tpl_move_temp' => '" the temporary files.',
			'cxc_tpl_preview_text' => 'Template Preview',
			'cxc_tpl_preview_none' => 'No Preview Image Available',
			'cxc_tpl_preview_docs' => 'Template Documentation',
			'cxc_tpl_preview_note' => '(<em><span class="cxc-tpl-capital">',
			'cxc_tpl_preview_last' => '</span> was the last template imported</em>)',
			'cxc_tpl_top_level' => 'Top Level "<strong>',
			'cxc_tpl_top_dir' => '</strong>" Directory',
			'cxc_tpl_top_remove' => '<strong>Note:</strong> <em class="cxc-tpl-failure">this will remove all templates.</em>',
			'cxc_tpl_template' => 'Template',
			'cxc_tpl_preview_img' => 'Template Preview Image',
			'cxc_tpl_error_zip_dir' => 'Error, there was a problem creating the zip file for the template directory.',
			'cxc_tpl_error_zip_remove' => 'Error, there was a problem removing the zip file after the download.',
			'cxc_tpl_error_zip_final' => 'Error, could not finalise the archive.',
			'cxc_tpl_error_zip_failed' => 'Error, could not create the',
			'cxc_tpl_error_zip_archive' => 'archive file'
		);
		return strtr($lang[$what], $atts);
	} 
# --- END PLUGIN CODE ---
if (0) {
?>
<!--
# --- BEGIN PLUGIN HELP ---
<h1>Import/Export/Remove/Download/Upload Templates as Files</h1>
<p>This plugin creates a new <strong>Templates</strong> tab under <strong>Extensions</strong>, enabling the trivial export of<strong> Forms</strong>, <strong>Pages</strong>, <strong>Plugins</strong>, <strong>Sections</strong>, and <strong>Style</strong> rules to a specified folder for convenient editing, and the subsequent import of new and updated files. Existing template directories, as well as, the $cxc_templates[&#8217;base_dir&#8217;] can be deleted. Please note, the $cxc_templates[&#8217;base_dir&#8217;] will be recreated when the plugin is next accessed. Other features include zip and download of template directories, and a template upload option that will upload and import new templates with a single click.</p>

<h2 class="cxc-tpl-slide-head"><a id="plugin-requirements">Plugin Requirements</a> &lt;/&gt;</h2>
<div class="cxc-tpl-slide-body">
<p>This plugin requires Textpattern <strong>4.5.0</strong> and above.</p>
<p>Regardless of where it&#8217;s been tested, this plugin messes around with your database.</p>
<p><em>Do not use it without backing up your database</em>.</p>
</div>

<h2 class="cxc-tpl-slide-head"><a id="setup-instructions">Setup Instructions</a> &lt;/&gt;</h2>
<div class="cxc-tpl-slide-body">
<p>By default, the plugin looks for the <strong>tmp</strong> and <strong>tpl</strong> directories, the <strong>tpl</strong> directory should be in the webroot with images, rpc, sites, and textpattern directories and the <strong>tmp</strong> directory should be in the textpattern directory. If the directories don&#8217;t exist, the plugin will attempt to create them the first time you access the plugin. This creation will sometimes fail, if that occurs, you&#8217;ll need to create the directories manually, and ensure that the web server has write access.</p>
<p>If your Textpattern root is located at <strong>/users/home/myuser/web/public/</strong>, something similar to the following commands could be used:</p>
<pre><code>cd /users/home/myuser/web/public/
mkdir directory
chmod 777 directory
</code></pre>
<p>Just replace the word &#8217;directory&#8217; in the example above with the directory you need to create.</p>
<p><strong>Note:</strong> <em>if using an alternate template directory you will need to adjust accordingly.</em></p>
</div>

<h2 class="cxc-tpl-slide-head"><a id="usage-instructions">Usage Instructions</a> &lt;/&gt;</h2>
<div class="cxc-tpl-slide-body">
<p><strong>Import Template</strong>&#8211; select a template to import from the dropdown on the <strong>Templates</strong> tab and press <em>Go</em>. Before importing, the plugin will do an export of your currently installed templates to a folder called. If this is not your first install this may overwrite the current template backup located in <strong>preimport-data</strong>.</p>
<p><strong>Safe Mode</strong> &#8211; allows you to import a template with out overwriting any existing database entries. When this setting is enabled the plugin will skip importing the forms, pages, sections and styles if the database already contains an entry with the same name and only import new entries. This setting will usually require additional editing and is turned off by default.</p>
<p><strong>Export Template</strong> &#8211; is achieved by typing in an export name and pressing <em>Go</em>. Keep in mind naming an export the same as an exisiting template directory will overwrite the contents and that the assets folder is not created. This is done for two reasons ...</p>
<ol>
    <li>The system is completely unaware of which template you are using and there isn&#8217;t a meta file to tell the plugin what assets to clone or where to find them.</li>
    <li>Even if the above was added to the plugin, cloned templates would need to edit the exported files to change resource directories used for css, images, and js.</li>
</ol>
<p>... that doesn&#8217;t mean it can&#8217;t be added, only that for now it doesn&#8217;t work that way.</p>
<p><strong>Delete Template</strong> &#8211; select a template to remove from the dropdown on the <strong>Templates</strong> tab and press <em>Go</em>. Follow the instructions on the <strong>Delete Directory Confirmation</strong> page to remove the selected template directory from your site, if no template is selected from the dropdown list the entire templates directory will be removed. If this feature is unable to remove templates it is usually due to the server configuration of your host and removal of templates will need to be done manually.</p>
<p><strong>Zip Project Folder</strong> &#8211; select a template directory to zip from the dropdown on the <strong>Templates</strong> tab and press <em>Go</em>. This will zip the entire template directory and force download of the template, once downloaded you can extract the contents and remove files that are unecessary. This is mostly a feature to help designers share their templates, downloaded zip files must be extracted and rezipped before they can be used with the upload feature. If this feature is unable to zip the template directory it is usually due to the server configuration of your host and downlpad of templates will not be possible<strong></strong>.</p>
<p><strong>Upload Template</strong> &#8211; use the browse button to locate a template zip file you have and press <em>Go</em>. Keep in mind uploading a templates zip file with a template of the same name as an exisiting folder will overwrite the contents of the existing folder. Uploaded templates are extracted to the templates directory and can then be imported using the Import feature of the plugin.</p>
<p><strong>Advanced</strong> &#8211; this area will allow you to do a webroot template installation and is not recommended unless instructed to by the template designer <strong></strong>or you know hwat you are doing. This feature can be used for support files or common files used by designers that must be in the webroot to function properly. When using this method for installation the uploaded zip file will be extracted directly into teh webroot and will overwrite existing files of the same name.</p>
</div>

<h2 class="cxc-tpl-slide-head"><a id="designing-templates">Designing Templates</a> &lt;/&gt;</h2>
<div class="cxc-tpl-slide-body">
<p class="cxc-tpl-slide-head">The following <a id="file-naming-conventions">file naming conventions</a> &lt;/&gt; are recommended to designers:</p>
<div class="cxc-tpl-slide-body preview">
    <p>Default pages, forms and styles should be prefaced with the design’s name.</p>
    <ul>
        <li>Where possible the default page becomes THEME_NAME_default.page and so on ...</li>
        <li><strong>Note:</strong> <em>all core code findings discovered by <a href="">Bert Garcia</a> are still relevant</em>.</li>
    </ul>
    <br />
    <p>Templating file extensions, simple:</p>
    <ul>
        <li>Forms → .form</li>
        <li>Pages → .page</li>
        <li>Plugins → .plugin</li>
        <li>Sections → .section</li>
        <li>Styles → .css  </li>
    </ul>
</div>
<p class="cxc-tpl-slide-head">This is <a id="suggested-folder-structure" title="Click to view example template directory structure">the folder and subfolders structure</a> &lt;/&gt; used for template creation:</p>
<div class="cxc-tpl-slide-body preview">
    <ul>
        <li>tpl<ul>
            <li>THEME_NAME<ul>
                <li>assets<ul>
                    <li>css<ul>
                        <li>additional.css</li>
                    </ul></li>
                    <li>js<ul>
                        <li>additional.js</li>
                    </ul></li>
                    <li>additional.png</li>
                </ul></li>
                <li>forms<ul>
                    <li>default.article.form</li>
                    <li>...</li>
                </ul></li>
                <li>pages<ul>
                    <li>default.page</li>
                    <li>...</li>
                </ul></li>
                <li>plugins<ul>
                    <li>cxc_templates.plugin</li>
                </ul></li>
                <li>sections<ul>
                    <li>default.section</li>
                </ul></li>
                <li>style<ul>
                    <li>default.css</li>
                </ul></li>
            </ul></li>
            <li>DESIGNER.txt</li>
            <li>README.txt</li>
            <li>preview.img</li>
      </ul></li>
    </ul>
</div>
<p>The default templates directory is the &quot;<strong>tpl</strong>&quot; directory, but in the past &quot;_templates&quot; was used and some templates may still require you to use it (or another) directory. I hope everyone will adopt the use of the &quot;<strong>tpl</strong>&quot; directory but I&#8217;m not forcing it on anyone. Please note, it is possible to have and use multiple template directories on a single site, but only templates existing in the directory set as $cxc_templates[&#8217;base_dir&#8217;] will be used to display available templates.</p>
<p>You will need to replace &quot;<strong>THEME_NAME</strong>&quot; with the name of the template you are designing. The name of the template folder should be lower-cased and alpha numeric (can contain hyphens &quot;-&quot; and underscores &quot;_&quot;). Technically it does not have to be lower-cased but it is definitely the standard since asset links are case-sensitive.</p>
<p>The &quot;<strong>assets</strong>&quot; folder is not required nor are the sub-directories, I added them with organization in mind, it is simply a design choice. The concept is that all support files (css, images, js, and other files) could be placed in this folder (or another folder below the <strong>THEME_NAME</strong> directory) instead of requiring an advanced install into the webroot.</p>
<p>The &quot;<strong>forms</strong>&quot; folder contains all forms included with the template, form files not required or part of the template should be removed before sharing your design publicly.</p>
<p>The &quot;<strong>pages</strong>&quot; folder contains all pages included with the template, page files not required or part of the template should be removed before sharing your design publicly.</p>
<p>The &quot;<strong>plugins</strong>&quot; folder contains all plugins included with the template, plugin files not required or part of the template should be removed before sharing your design publicly.</p>
<p>The &quot;<strong>sections</strong>&quot; folder contains all sections included with the template, section files not required or part of the template should be removed before sharing your design publicly.</p>
<p>The &quot;<strong>styles</strong>&quot; folder contains all css style sheets included with the template, css files not required or part of the template should be removed before sharing your design publicly.</p>
<p>The &quot;<strong>DESIGNER.txt</strong>&quot; (<em>optional</em>) file must be located in the templates root directory and can be used by designers to link to their homepage or advertise additional products and services they offer. This file is not required and will not be displayed unless the user clicks on the &quot;<strong>Additional Information</strong> &lt;/&gt;&quot; area located below allow other results. The &quot;<strong>DESIGNER.txt</strong>&quot; file name is case sensitive and may contain simple html markup.</p>
<p>The &quot;<strong>README.txt</strong>&quot; (<em>optional</em>) file must be located in the templates root directory and can be used by designers to display after installation instructions. This file is not required but if present, it will be displayed above all other information during install. The &quot;<strong>README.txt</strong>&quot; file name is case sensitive and may contain simple html markup.</p>
<p>The &quot;<strong>preview.img</strong>&quot; (<em>optional</em>) file must be located in the templates root directory and is an image or logo that can be added to display the template previews. This file is not required but if present, it will be displayed on the right hand side of the template manager after the first import. The file name and acceptable image formats are &quot;<strong>preview.gif</strong>&quot;, &quot;<strong>preview.jpg</strong>&quot; and &quot;<strong>preview.png</strong>&quot; and is case sensitive.</p>
<p><strong>Note:</strong> <em>designers are encouraged to include empty index.html files in all subdirectories of their template to help keep our Textpattern sites secure.</em></p>
</div>

<h2>Plugin Credits</h2>

<p>Adopted for Textpattern 4.5.0 by <a href="http://yauh.de">Stephan Hochhaus</a>.</p>
<p>Plugin code based on a modified version of mem_templates by <a href="http://manfre.net/">Michael Manfre</a> that was released with one of <a href="http://protextthemes.com">Stuart Butcher&#8217;s</a> TXP 4.3.0 templates, which is based off of hcg_templates by <a href="http://txptag.com/">Bert Garcia</a>, which is based off of mcw_templates by <a href="http://mikewest.org/" rel="nofollow">Mike West</a> with additional features introduced to an alternate hcg_templates provided by <a href="http://clueless.com.ar/">Mariano Absatz</a>. </p>
<p>Without code contributions, help and tutoring from <a href="http://zegnat.com/">Martijn van der Ven</a> and <a href="http://stefdawson.com/">Stef Dawson</a>, as well as, the mentioned plugins and contributions from <strong>all</strong> the above this plugin would not have been made possible. </p>
<p><strong>Note:</strong> <em>when </em><strong>&lt;/&gt;</strong><em> is encountered throughout the template manager it denotes information that can be expanded/collapsed to show/hide additional information.</em></p>

<script type="text/javascript">
	$(document).ready(function()
	{
	  $(".cxc-tpl-slide-body").hide();
	  $(".cxc-tpl-slide-head").click(function()
	  {
		$(this).next(".cxc-tpl-slide-body").slideToggle(600);
	  });
	});
</script>
# --- END PLUGIN HELP ---
-->
<?php
}
?>