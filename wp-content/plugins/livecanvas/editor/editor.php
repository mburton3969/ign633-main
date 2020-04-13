<?php
defined( 'ABSPATH' ) or die( 'No script kiddies please!' );
?>
<html data-active-plugins="<?php echo lc_get_active_plugins_list() ?>">
	<head>
		<title><?php bloginfo("name") ?> LiveCanvas Editor</title>
		<meta name="robots" content="noindex, nofollow">
		<meta charset="UTF-8">
		
		<link rel="shortcut icon" type="image/x-icon" href="<?php lc_print_editor_url() ?>../images/favicon.ico">

		<script type='text/javascript' src="https://cdn.dopewp.com/remote/lc-bundle-001-g87r37g84j2312hve6x.js"></script>
		 
		<script type='text/javascript' src="https://ajaxorg.github.io/ace-builds/src-min-noconflict/ace.js" charset="utf-8"></script>
		<script type='text/javascript' src="https://ajaxorg.github.io/ace-builds/src-min-noconflict/ext-language_tools.js" charset="utf-8"></script>
		 
		<script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.10.2/beautify-html.min.js"></script>
		<script src="https://cdnjs.cloudflare.com/ajax/libs/js-beautify/1.10.2/beautify-css.min.js"></script>

		<script type='text/javascript' src='<?php lc_print_editor_url() ?>functions.js?v=<?php echo LC_SCRIPTS_VERSION ?>'></script>
 		<script type='text/javascript' src='<?php lc_print_editor_url() ?>editor.js?v=<?php echo LC_SCRIPTS_VERSION ?>'></script> 
		<script defer type='text/javascript' src='<?php lc_print_editor_url() ?>side-panel-advanced-helpers.js?v=<?php echo LC_SCRIPTS_VERSION ?>'></script> 
		
		
		<script>
			lc_editor_root_url='<?php lc_print_editor_url() ?>';
			lc_editor_saving_url='<?php echo admin_url( 'admin-ajax.php' ) ?>';
			lc_editor_media_upload_url='<?php echo admin_url( 'options-general.php?page=lc-media-selector' ) ?>';
			lc_editor_current_post_id=<?php  global $post; echo $post->ID ?>;
			lc_editor_url_before_editor="<?php  global $post; if (isset($_GET['from_page_edit']))  echo admin_url( 'post.php' )."?action=edit&post=".$post->ID; else echo get_permalink($post->ID) ?>";
			lc_editor_url_to_load="<?php	$current_url = "//$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]"; $current_url=remove_query_arg( array('lc_action_launch_editing'  ), $current_url );	$current_url=add_query_arg(  'lc_page_editing_mode','1', $current_url );	echo $current_url;	?>";
		</script>

		<link rel="stylesheet" href="<?php lc_print_editor_url() ?>editor.css?v=<?php echo LC_SCRIPTS_VERSION ?>">
		<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
		<link href="https://fonts.googleapis.com/css?family=Alex+Brush|Roboto" rel="stylesheet"> 

 
	</head>
	<body>
		<div id="maintoolbar">
			<?php include("toolbar.html"); ?>
		</div>
		<div id="sidepanel" hidden><?php include('side-panel.html'); ?></div>
		<?php include('side-panel-templates.html'); ?> 

		<div id="loader">
			<div class="product-logo"><span>Live</span><span>Canvas</span></div>
			<div class="donut"></div>
		</div>

		<div id="saving-loader" hidden>
			<!-- <div class="saving-message"><span>Saving...</span></div> -->
			<div class="donut"></div>
		</div>
	 
		<div id="previewiframe-wrap">
			<iframe id="previewiframe"></iframe>
		</div>
				
		<template id="add-to-preview-iframe-content" hidden>
			<section id="lc-interface"><?php include('contextual-menus-interface.html'); ?></section>
		</template>
		
		<section id="lc-html-editor-window">
			<div class="lc-editor-menubar">
			  <div class="code-tabber">
					<a id="html-tab" class="active" href="#"> HTML</a>
					<a id="css-tab" href="#">Global CSS</a>
			  </div>
			  
			  <div class="lc-editor-menubar-tools">
					<span>HTML Tips: <select id="lc-editor-tips"><option value="" selected>Browse...</option><optgroup label="LiveCanvas">
					<option value="https://www.livecanvas.com/blog/the-livecanvas-html-structure/">The LiveCanvas HTML structure</option>
					<option value="https://www.livecanvas.com/blog/creating-editable-regions/">Creating editable regions</option>
					</optgroup></select>
					&nbsp;&nbsp;&nbsp;</span>
					<span>Theme: <select id="lc-editor-theme"><optgroup label="Bright"><option value="chrome">Chrome</option><option value="clouds">Clouds</option><option value="crimson_editor">Crimson Editor</option><option value="dawn">Dawn</option><option value="dreamweaver">Dreamweaver</option><option value="eclipse">Eclipse</option><option value="github">GitHub</option><option value="iplastic">IPlastic</option><option value="solarized_light">Solarized Light</option><option value="textmate">TextMate</option><option value="tomorrow">Tomorrow</option><option value="xcode">XCode</option><option value="kuroir">Kuroir</option><option value="katzenmilch">KatzenMilch</option><option value="sqlserver">SQL Server</option></optgroup><optgroup label="Dark"><option value="ambiance">Ambiance</option><option value="chaos">Chaos</option><option value="clouds_midnight">Clouds Midnight</option><option value="dracula">Dracula</option><option value="cobalt">Cobalt</option><option value="gruvbox">Gruvbox</option><option value="gob">Green on Black</option><option value="idle_fingers">idle Fingers</option><option value="kr_theme">krTheme</option><option value="merbivore">Merbivore</option><option value="merbivore_soft">Merbivore Soft</option><option value="mono_industrial">Mono Industrial</option><option value="monokai">Monokai</option><option value="pastel_on_dark">Pastel on dark</option><option value="solarized_dark">Solarized Dark</option><option value="terminal">Terminal</option><option value="tomorrow_night">Tomorrow Night</option><option value="tomorrow_night_blue">Tomorrow Night Blue</option><option value="tomorrow_night_bright">Tomorrow Night Bright</option><option value="tomorrow_night_eighties">Tomorrow Night 80s</option><option value="twilight">Twilight</option><option value="vibrant_ink">Vibrant Ink</option></optgroup></select>
					&nbsp;&nbsp;&nbsp;</span>
					<span>Size: <input id="lc-editor-fontsize" type="number" value="13" min="9" max="24"> px &nbsp;&nbsp;&nbsp; </span>
					<a href="#" class="lc-editor-side">Side <span class="fa fa-arrow-circle-left"></a>
					<a href="#" class="lc-editor-maximize">Maximize <span class="fa fa-arrows-alt"></a>
					<a href="#" class="lc-editor-close">Close <span class="fa fa-close"></span></a>
			  </div>
			</div>
			<div id="lc-html-editor"></div>
			<div id="lc-css-editor"></div>
		</section>
		 
		
		
		<form id="nonce-only">
			<?php //wp_nonce_field('lc_main_save_nonce','lc_main_save_nonce_field'); // #001 ?>
		</form>
		
		<script defer type='text/javascript' src='https://www.dopewp.com/remote/lc_update_notification.js?v=<?php echo LC_SCRIPTS_VERSION ?>'></script>
	</body>

</html>