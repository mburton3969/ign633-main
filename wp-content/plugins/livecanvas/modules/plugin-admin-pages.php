<?php
// EXIT IF ACCESSED DIRECTLY.
defined( 'ABSPATH' ) || exit;


function lc_get_option_is_set($option_name){
	$lc_settings = get_option('lc_settings');
	return (isset($lc_settings[$option_name]));
}



add_action('admin_menu', 'lc_main_options_page');
function lc_main_options_page(){
	$lc_settings = get_option('lc_settings');
	
	// add top level menu page
	add_menu_page('LiveCanvas - Web Authoring Suite', 'LiveCanvas', 'manage_options', 'livecanvas', 'lc_options_page_func', 'dashicons-heart');
	
	add_submenu_page('livecanvas', // Parent slug
	'License', // Page title
	'License', // Menu title
	'manage_options', // Capability
	'livecanvas_license', // Slug
	'lc_license_page_func'	// Function
	);
	
	add_submenu_page('livecanvas', // Parent slug
	'HTML Blocks', // Page title
	'Blocks', // Menu title
	'manage_options', // Capability
	'edit.php?post_type=lc_block', // Slug
	false
	// Function
	);
	
	if (isset($lc_settings['gtblocks'])) 
	add_submenu_page('livecanvas', // Parent slug
	'Gutenberg Blocks', // Page title
	'Gutenberg Blocks', // Menu title
	'manage_options', // Capability
	'edit.php?post_type=lc_gt_block', // Slug
	false
	// Function
	);
		
	add_submenu_page('livecanvas', // Parent slug
	'Sections', // Page title
	'Sections', // Menu title
	'manage_options', // Capability
	'edit.php?post_type=lc_section', // Slug
	false
	// Function
	);
	
	
	if (isset($_GET['lc_sa'])) 	
	add_submenu_page('livecanvas', // Parent slug
	'Template Partials', // Page title
	'Template Partials', // Menu title
	'manage_options', // Capability
	'edit.php?post_type=lc_partial', // Slug
	false
	// Function
	);
	
}

function lc_admin_menu_active(){
	global $parent_file, $post_type;
	//if ( $post_type == 'CPT' ) {
	$parent_file = 'post';
	//}
				
}
add_action('admin_head', 'lc_admin_menu_active');




function lc_options_page_func(){
	if (!current_user_can('administrator')) return;
	//show current_settings
	//echo "<pre>";  var_dump(get_option('lc_settings'));  echo "</pre>";
	//delete current settings
	//delete_option('lc_settings');die("DELETED");
	
	
	//GET SETTINGS ARRAY FROM DB
	$lc_settings = get_option('lc_settings');
	?>
	<div class="wrap">
		<img src="<?php echo plugins_url("/livecanvas/images/lc-logo.png") ?>" style="width:250px;height: auto";>
		<h1>Welcome to LiveCanvas!</h1>
		<iframe width="560" height="315" src="https://www.youtube.com/embed/EY9PLLogldw" frameborder="0" allow="accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture" allowfullscreen></iframe>
		 <p><br></p>
	
		<a href="#" onclick='document.querySelector("#wp-admin-bar-lc-add-new-page a").click();' class="button large">Create new LiveCanvas Page Draft</a>
		<br><br>
	
		<h2>Optional Extras</h2>
		
		<style>
			table#lc-settings-table {padding:10px 0 30px;}
			table#lc-settings-table th[scope=row] {width:250px;text-align: left}
			table#lc-settings-table tr {line-height: 40px}
		</style>
		
		<form method="post">
			<?php wp_nonce_field('lc_settings_update'); ?>
		   
			<table id="lc-settings-table">
			  
				<tr>
					<th scope="row" >Add Animations</th>
					<td>
						 <label> 	<input name="aos" type="checkbox" value="1" <?php if (isset($lc_settings['aos'])) echo "checked"; ?> > Adds the <b>Animate On Scroll</b> Library <a target="_blank" href="https://livecanvas.com/blog/adding-animations-with-the-aos-library/">Learn more</a></label> 
					</td>
				</tr>
				<tr>
					<th scope="row" > Gutenberg Blocks</th>
					<td>
						 <label> 	<input name="gtblocks" type="checkbox" value="1" <?php if (isset($lc_settings['gtblocks'])) echo "checked"; ?> > Add admin UX to craft custom blocks with Gutenberg (embeddable via Shortcodes) </label> 
					</td>
				</tr>
				<tr>
					<th scope="row"  >Handle Footer  </th>
					<td>
						<label>
							<input name="footerV2" type="checkbox" value="1" <?php if (isset($lc_settings['footerV2'])) echo "checked"; ?> > Use LiveCanvas to design the footer <i style="color:red">(requires CustomStrap 2.5 or later)</i>
							<?php if (isset($lc_settings['footerV2'])): ?>		<a style="margin-left:40px;margin-top:6px" target="_blank" class="button" href="<?php  echo add_query_arg(array('lc_action_launch_editing' => '1'),
																																		get_permalink(    lc_get_partial_postid('is_footer', "1")  ));  ?>">Launch Footer Editor</a>		<?php endif ?>
							
						</label>
					 </td>
				</tr>
				<tr>
					<th scope="row"  > </th>
					<td>
						  <label style="opacity:0.4">    <input name="footer" type="checkbox" value="1" <?php if (isset($lc_settings['footer'])) echo "checked"; ?> > [LEGACY]	Use a #global-footer SECTION in homepage as a global site footer 				</label> 
					</td>
				</tr>	
								
			</table>
			<input class="button-primary" type="submit" name="lc-save-settings" value="Save Settings">
		</form>
	
	</div>
	<?php
}




//OPTIONS SAVING / SUBMIT
add_action('plugins_loaded', function(){
	if (!current_user_can('administrator') OR !is_admin()) return;
	//process eventual submit
	if (isset($_POST['lc-save-settings'])):
					check_admin_referer('lc_settings_update');
					unset($_POST['lc-save-settings']);
					update_option('lc_settings', $_POST, true);
	endif;
	
});
 


function lc_check_license_code($code){
	$response = wp_remote_post( 'https://livecanvas.com/remote/clc/'.$code.'/',array('timeout' => 30, 'method' => 'POST', 'body' =>  "theurl=".get_bloginfo("url")) ); 
	 
	if ( is_array( $response ) && ! is_wp_error( $response ) ) 	return ($response['body']=="OK"); else return FALSE;
}

function lc_license_page_func(){
	if (!current_user_can('administrator')) return;
	
	//process eventual submit
	if (isset($_POST['lc-save-license'])):
					
					check_admin_referer('lc_license_update');

					if ($_POST['license-code']=="" OR lc_check_license_code( $_POST['license-code'])) {
					
						$lc_settings = get_option('lc_settings');
						$lc_settings['license-code']= $_POST['license-code'];
						update_option('lc_settings', $lc_settings, true);
						$feedback_message= "<h2>Updated successfully.</h2>";
						
					}
					 else $feedback_message= "<h2>Invalid license code.</h2>";
	endif;
	
	//show current_settings
	//echo "<pre>";  var_dump(get_option('lc_settings'));  echo "</pre>";
	//delete current settings
	//delete_option('lc_settings');die("DELETED");
	
	
	//GET SETTINGS ARRAY FROM DB
	$lc_settings = get_option('lc_settings');
	?>
	<div class="wrap  ">
		<img src="<?php echo plugins_url("/livecanvas/images/lc-logo.png") ?>" style="width:250px;height: auto";>
		<h1>License</h1>
		<?php if (isset($feedback_message)) echo $feedback_message; else
				if(  !lc_get_license_code()) {  ?>
					<h3>Plugin updates are important to enjoy new features, maximum stability and security. </h3>
					<p> To enable automatic plugin updates, a valid license code is needed. Get it from the <a target="_new" href="https://livecanvas.com/members-area/">members area</a> </p>
					<?php } else { ?>
					
					<?php }	?>
		
		
		<form method="post" style="margin:50px 0; width:400px;font-size:3em; background: #ddd;padding: 20px" >
			<?php wp_nonce_field('lc_license_update'); ?>
		   
			 <input name="license-code" type="text" style="min-width: 100%;" <?php if (isset($lc_settings['license-code'])) echo "value='".esc_attr($lc_settings['license-code'])."'"; ?> placeholder="Paste your license code here..." > 
			 
			<input class="button-primary" type="submit" style="min-width: 100%;" name="lc-save-license" value="Save">
		</form>
	
	
	
	</div>
	<?php
}

function lc_get_license_code(){
	$lc_settings = get_option('lc_settings');
	if(!$lc_settings) return FALSE;
	if(!isset($lc_settings['license-code']))  return FALSE;
	if(strlen($lc_settings['license-code'])<4)  return FALSE;
	return $lc_settings['license-code'];
}

 
