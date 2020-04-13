<?php
/*
SCSS Compiler interface
*/

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

use ScssPhp\ScssPhp\Compiler; //https://scssphp.github.io/scssphp/docs/

//SOME UTILITIES
//function customstrap_get_uploads_folder_name(){	 $the_wp_get_upload_dir = wp_get_upload_dir();return wp_basename( $the_wp_get_upload_dir['baseurl'] );}
//function customstrap_get_active_scss_filename() {	 $last_uploaded_scss_file_info=get_option('customstrap_active_scss_file_data_'.customstrap_get_active_parent_theme_slug()); if (is_array($last_uploaded_scss_file_info))  return $base_url .'/'.$last_uploaded_scss_file_info['relative_upload_path'];	 else return FALSE;}
add_filter('upload_mimes', 'customstrap_enable_extended_upload_css'); function customstrap_enable_extended_upload_css ( $mime_types =array() ) {  $mime_types['css']  = 'text/css'; return $mime_types;}

function customstrap_get_upload_dir( $param, $subfolder = '' ) {    $upload_dir = wp_upload_dir();    $url = $upload_dir[ $param ];    if ( $param === 'baseurl' && is_ssl() )  $url = str_replace( 'http://', 'https://', $url );return $url . $subfolder; }
function customstrap_get_active_parent_theme_slug(){ $style_parent_theme = wp_get_theme(get_template()); $theme_name = $style_parent_theme->get('Name'); return sanitize_title($theme_name);}

function customstrap_get_font_family_name($font) {	$arr=explode(":",$font); return $arr[0];}
function customstrap_get_font_family_name_nospaces($font) { return str_replace(" ","+",customstrap_get_font_family_name($font)); }


function customstrap_get_compiled_css_url() {
	$base_url=customstrap_get_upload_dir('baseurl');
	$css_bundle_relative_upload_path=get_theme_mod('customstrap_css_bundle_wp_relative_upload_path');
	if ($css_bundle_relative_upload_path!='')  return $base_url .'/'.$css_bundle_relative_upload_path;	 else return FALSE;
}


///ADD NEW ELEMENT TO WP-ADMIN LEFT MENU
//no more necessary as there's automatic detection now
/*
add_action('admin_menu', 'customstrap_add_admin_menu_item');
function customstrap_add_admin_menu_item() {
	add_theme_page(__('Recompile SCSS'), __('Recompile SCSS'), 'install_plugins', 'customstrap_trigger_scss_compiler','customstrap_print_trigger_scss_compiler_page');
}
function customstrap_print_trigger_scss_compiler_page(){
	 ?><style>	.cs-close-compiling-window {display: none} </style>
	 <?php
	 customstrap_generate_css();
}
*/

//CHECK URL PARAMETERS AND REACT ACCORDINGLY
add_action("admin_init","customstrap_test_url_trigger_compiler");
function customstrap_test_url_trigger_compiler(){
	//ADMINS ONLY
	if (!current_user_can("administrator")) return;
	
	if (isset($_GET['cs_compile_scss'])) {		customstrap_generate_css();		die();	}
	
	if (isset($_GET['cs_reset_theme'])) {		remove_theme_mods();	delete_option("customstrap_scss_last_filesmod_timestamp");	wp_die("Theme Options Reset!");	}
	
	if(isset($_GET['cs_show_mods'])){		print_r(get_theme_mods());		wp_die();	}
 
	
}
 
//SET UPLOAD PATH
if (isset($_GET['cs_compile_scss'])) add_filter( 'pre_option_uploads_use_yearmonth_folders', '__return_true'); //so standard folder structure is used for uploads, important for #fix_paths


 
/////FUNCTION TO RECOMPILE THE CSS ///////
function customstrap_generate_css(){
	
	//INITIALIZE COMPILER
	require_once "scssphp/scss.inc.php";
	$scss = new Compiler();
	
	
	try {
		// SET IMPORT PATHS //
		//add parent theme: understrap
		//$scss->setImportPaths('../wp-content/themes/'.customstrap_get_active_parent_theme_slug().'/sass/');	
		$scss->setImportPaths(WP_CONTENT_DIR.'/themes/'.customstrap_get_active_parent_theme_slug().'/sass/');
		
		//ADD OTHER EXTRA PATHS
		//add current: customstrap
		$scss->addImportPath(WP_CONTENT_DIR.'/themes/'.get_option('stylesheet').'/sass/');
		//add extra plugs
		//$scss->addImportPath('../wp-content/plugins/livecanvas-style-packages/libraries/shards-ui-master/src/scss/');
		
		//SET OUTPUT FORMATTING
		$scss->setFormatter('ScssPhp\ScssPhp\Formatter\Crunched');
		
		// ENABLE SOURCE MAP
		//$scss->setSourceMap(Compiler::SOURCE_MAP_INLINE);
		
		//SET SCSS VARIABLES
		$scss->setVariables(customstrap_get_active_scss_variables_array());
		
		$compiled_css = $scss->compile(customstrap_get_active_scss_code());
		
		//echo "COMPILED:". ($compiled_css);die; //FOR DEBUG
	
	} catch (Exception $e) {
		
		echo  "<div style='font-size:20px;font-family:courier;border:1px solid red; padding:15px;margin-top:30px;display:block'>".$e->getMessage()."</div>";
		die();
   }
	
	//SET WPCONTENT RELATIVE PATH
	$array=explode('/',WP_CONTENT_URL);
	$the_root_domain=$array[0].$array[1].$array[2];
	//echo $the_root_domain; //for debug
    $wpcontent_relative_path= str_replace($the_root_domain,"",WP_CONTENT_URL); // should output /app  or /wp-content
    //echo '<br>value:'.($wpcontent_relative_path);// for debug
	
	//FIX PATHS IN COMPILED CSS #fix_paths new
	$compiled_css=str_replace("../",$wpcontent_relative_path."/themes/".customstrap_get_active_parent_theme_slug()."/", $compiled_css);

	//DELETE OLD CSS FILE
	$css_bundle_relative_upload_path=get_theme_mod('customstrap_css_bundle_wp_relative_upload_path');
	if($css_bundle_relative_upload_path!='')  unlink( WP_CONTENT_DIR.'/uploads/'.$css_bundle_relative_upload_path);
	
	
	//SAVE THE COMPILED CSS FILE
	$uploaded= wp_upload_bits( "styles-bundle-".rand(1,100).".css", FALSE, $compiled_css); //maybe here add a version?
	
	if ($uploaded['error']==FALSE) {
		//UPLOAD WAS SUCCESSFUL
		set_theme_mod('customstrap_css_bundle_wp_relative_upload_path',_wp_relative_upload_path( $uploaded['file'] ));
		echo "<br><br><b>Generated File:</b><br><a target='new' href='".customstrap_get_compiled_css_url()."'>".customstrap_get_compiled_css_url()."</a>";
		update_option("customstrap_scss_last_filesmod_timestamp",customstrap_scss_last_filesmod_timestamp());
	 } else {
		//FAILED
		echo  ("<br><br>Error saving CSS file to your uploads directory. ".$uploaded['error']);
	}
	
	echo  " <button class='cs-close-compiling-window'>Click to close window</button>";
		
}

/////FUNCTION TO GET ACTIVE SCSS CODE FROM FILE ///////
function customstrap_get_active_scss_code(){

	//DEFINE FONTS SCSS////////////
	$fonts_parameter=""; 
	$fonts_assignment_code="";
	
	//HEADINGS FONT
	$headings_font=get_theme_mod('customstrap_headings_font');
	if($headings_font!="") {
		$fonts_parameter.= customstrap_get_font_family_name_nospaces($headings_font); 
		$fonts_assignment_code.='$headings-font-family:'.customstrap_get_font_family_name($headings_font).'; 
			';
	}
	
	//BODY FONT
	$body_font=get_theme_mod('customstrap_body_font');
	if($body_font!="") {
		if ($fonts_parameter!="") $fonts_parameter.="|";
		$fonts_parameter.= customstrap_get_font_family_name_nospaces($body_font); 
		$fonts_assignment_code.='$font-family-base:'.customstrap_get_font_family_name($body_font).'; 
			';
	}
	
	//Build combined @import statement for all used Google Fonts
	if ($fonts_parameter!="") $fonts_import_statement='@import url(https://fonts.googleapis.com/css?family='.$fonts_parameter.'); 
		'; else $fonts_import_statement="";
 
	//GRAB SCSS SOURCE FROM LOCAL UNDERSTRAP//////////////////
	$scss_url = WP_CONTENT_URL.'/themes/'.customstrap_get_active_parent_theme_slug().'/sass/theme.scss';
	//$the_scss_code =  file_get_contents($scss_url); //requires more server permissions-legacy
	$response = wp_remote_get( $scss_url ); //more modern way
 
	if ( is_array( $response ) && ! is_wp_error( $response ) ) {
		$headers = $response['headers']; // array of http header lines
		$the_scss_code    = $response['body']; // use the content
	} else die ("Fetch error");
	
	//USE CHILD THEME's CUSTOM VARIABLES.scss IN PLACE OF THE ORIGINAL
	$the_scss_code =  str_replace('theme/theme_variables','theme_variables-custom', $the_scss_code);
	
	//USE CHILD THEME's CUSTOM SCSS IN PLACE OF THE ORIGINAL
	$the_scss_code =  str_replace('theme/theme','theme-custom', $the_scss_code);	
	
	//MAKE BUNDLE ////////////////////////////////	
	$scss_bundle= $fonts_import_statement.$fonts_assignment_code.$the_scss_code;
	echo "<h2>SCSS source:</h2> Pulled from <b>".customstrap_get_active_parent_theme_slug().'/sass/theme.scss'.'</b><br>Filtered to include <b>your own custom files </b> from CustomStrap\'s <b><i>/sass</i></b> folder: <br> <b> _theme_variables-custom.scss</b> and <b> _theme-custom.scss</b> <br><br><code>'.nl2br($the_scss_code).'</code>';
	return $scss_bundle;
}


/////FUNCTION TO GET VARIABLES USED IN CUSTOMIZER /////
function customstrap_get_active_scss_variables_array(){
	$output_array=array();
	foreach(get_theme_mods() as $theme_mod_name => $theme_mod_value):
		
		//check we are treating a scss variable, or skip
		if(substr($theme_mod_name,0,8) != "SCSSvar_") continue;
		
		//skip empty values
		if($theme_mod_value=="") continue;
		
		$variable_name=str_replace("SCSSvar_","$",$theme_mod_name);
		
		//add to output array
		$output_array[$variable_name] = $theme_mod_value;
		
	endforeach;

	return $output_array; 
}


//function to make a timestamp of child theme sass directory
function customstrap_scss_last_filesmod_timestamp () {
	
	$the_directory=WP_CONTENT_DIR.'/themes/'.sanitize_title(wp_get_theme()).'/sass/';
	$files_listing = scandir($the_directory, 1);
	$mod_time_total=0;
	foreach($files_listing as $file_name):
		if ((strpos($file_name, '.scss') !== false) or (strpos($file_name, '.css') !== false)):
			//echo $file_name."<br>";
			$file_stats = stat( $the_directory. $file_name );
			$mod_time_total+= $file_stats['mtime'];
		endif;
	endforeach;
	return $mod_time_total; 
}

//AUTOMATIC REBUILD ON files CHANGE in sass directory
add_action("template_redirect",function(){
	
	if(!is_user_logged_in() OR !current_user_can("administrator") OR isset($_GET['customize_theme']) ) return; //exit if unlogged
	
	//onboarding
	if(get_option("customstrap_scss_last_filesmod_timestamp",0)==0) update_option("customstrap_scss_last_filesmod_timestamp",customstrap_scss_last_filesmod_timestamp());
	
	//DEBUG
	//echo get_option("customstrap_scss_last_filesmod_timestamp",0)."<br>".customstrap_scss_last_filesmod_timestamp();die;
	
	//if timestamps differ, rebuild CSS
	if (get_option("customstrap_scss_last_filesmod_timestamp",0)!=customstrap_scss_last_filesmod_timestamp()) {
		//timestamps are different
		echo "<h1>Some modification has been detected in your sass folder.</h1><h2>Your CSS bundle will be recreated.</h2>";
		customstrap_generate_css();
		wp_die("<h1>Please <a href='#' onClick='location.reload();' >reload the page</a></h1><style>	.cs-close-compiling-window {display: none} </style>");
	}
  
});

 
