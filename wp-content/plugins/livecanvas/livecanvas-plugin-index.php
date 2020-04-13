<?php
/*
Plugin Name: LiveCanvas
Description: Build better Web pages. An awesome live HTML editor focused on speed and code quality.
Version: 1.4.0
Author: Dopewp.com
Author URI: https://www.livecanvas.com

*/

// EXIT IF ACCESSED DIRECTLY.
defined( 'ABSPATH' ) || exit;

//DEFINE SCRIPTS VERSION
if (strpos($_SERVER['REQUEST_URI'], '/livecanvas-wp/') !== false)	define("LC_SCRIPTS_VERSION", rand(0, 1000)); else define("LC_SCRIPTS_VERSION", "1.4");

//EXTRAS
include("modules/admin-page-switch.php");
include("modules/plugin-admin-pages.php");
include("modules/optin-extras.php");
include("modules/shortcodes.php");
include("modules/media-selector.php");

//UTILITY
function lc_print_editor_url() { echo plugins_url('/livecanvas/editor/'); }

//COMPATIBILITY LAYER FOR OTHER THEMES. You'll need to manually select the LC template.
$style_parent_theme = wp_get_theme(get_template());
//IF PARENT THEME IS NOT UNDERSTRAP
if ($style_parent_theme->get('Name') != "UnderStrap" && !function_exists("lcta_plugin_is_enabled")) {
	add_action('admin_notices', 'lc_admin_theme_recommend_notice'); 
}

//FUNCTION TO DETERMINE IF POST IN USING LIVECANVAS
function lc_post_is_using_livecanvas($post_id) {
	return (get_post_meta($post_id, '_lc_livecanvas_enabled', true) == '1' OR 'lc_block' === get_post_type() OR 'lc_section' === get_post_type() OR 'lcr_section' === get_post_type() OR 'lc_partial'  === get_post_type()  );
}

function lc_admin_theme_recommend_notice() {
	$screen = get_current_screen();
	if ($screen->base == "theme-install" OR function_exists('lc_theme_is_livecanvas_friendly')) return;
?>
		<div class="notice error is-dismissible" style="padding:10px">
			<img src="<?php	echo plugins_url("/livecanvas/images/lc-logo.png"); ?>" style="width:250px;height: auto";>
			<h2>LiveCanvas requires the Understrap Theme to work</h2>
			 <a class="button button-primary button-hero " href="<?php	echo esc_attr(admin_url("/theme-install.php?search=understrap%20_s")); ?>">Let's install and activate the Theme</a>
		</div> <?php
}

// UTILITY: ALLOW SVG  (4 admins) AND WEBP IMAGE UPLOADS  /////////
add_filter('upload_mimes',function ($mimes){
	 if (current_user_can('administrator')) $mimes['svg'] = 'image/svg+xml';
	 $mimes['webp'] = 'image/webp';
	 return $mimes;
});

/////// CHECK URL ACTIONS IN FRONTEND ////////////////////////////////
add_action('template_redirect', 'lc_check_url_actions');
function lc_check_url_actions() {
	
	//IF THE USER TRIES TO EDIT BUT ITS NOT LOGGED IN, LET HIM LOG IN
	if (!is_user_logged_in() && isset($_GET['lc_action_launch_editing'])) {
		wp_redirect(wp_login_url(add_query_arg(array('lc_action_launch_editing' => '1'), get_permalink())));
		exit;
	}
	
	//FOLLOWING STUFF IS ONLY FOR SUPER ADMINS AND WHEN EDITING IS ENABLED  
	if (!current_user_can("edit_pages")) return;
	
	if (isset($_GET['lc_action_launch_editing']) && $_GET['lc_action_launch_editing'] == "1") {
		include("editor/editor.php");
		die;
	}
	
	if (isset($_GET['lc_action']) && $_GET['lc_action'] == "load_icons") {
		include("editor/icons.html");
		die;
	}
	
	if (isset($_GET['lc_action']) && $_GET['lc_action'] == "load_cpt") { //load your components case
		
		global $post;
		$args    = array(
			'posts_per_page' => 115,
			'post_type' => $_GET['cpt_post_type']
		);
		$myposts = get_posts($args);
		if (!$myposts) { ?>
			<p class="none-yet">None yet</p>
			<?php
		} else
			foreach ($myposts as $post):
			?>
					<block data-id="<?php	echo get_the_ID();?>">
						<h5 class="block-name"><?php echo get_post_field('post_title', get_the_ID(), 'raw');?></h5>
						 <i class="block-description"><?php	echo get_post_field('post_excerpt', get_the_ID(), 'raw');?></i> 
						<template><?php	echo get_post_field('post_content', get_the_ID(), 'raw'); ?></template>
					</block>
			<?php
			endforeach;
			?><a class="add-custom-el-button lc-button" target="_blank" href="<?php	echo admin_url('edit.php?post_type=' . $_GET['cpt_post_type']);?>">Open <?php echo ucfirst(substr($_GET['cpt_post_type'], 3));?>s Archive</a><?php
			die;
	} // end if
	
	
} //end function

/////// CHECK URL ACTIONS IN BACKEND ////////////////////////////////
add_action("admin_init", "lc_check_url_actions_backend");
function lc_check_url_actions_backend() {
	//EDITORS ONLY
	if (!current_user_can("edit_pages")) return;
	
	if (isset($_GET['lc_action_new_page']) && $_GET['lc_action_new_page'] == "1") {
		
		//create new page case
		if (isset($_GET['lc_page_name']))
			$new_page_name = $_GET['lc_page_name'];
		else
			$new_page_name = 'Untitled LiveCanvas Page';
		$post_id = wp_insert_post(array(
			'post_title' => $new_page_name,
			'post_status' => 'draft',
			'post_type' => 'page'
		));
		
		update_post_meta($post_id, '_lc_livecanvas_enabled', 1);
		update_post_meta($post_id, '_wp_page_template', "page-templates/empty.php"); //for understrap
		
		wp_redirect(add_query_arg(array('lc_action_launch_editing' => '1'), get_permalink($post_id)));
		exit;
		
	} //end if
} //end func


////////HIDE TOOLBAR IF EDITING ////////////////////////////
add_action('wp_loaded', 'lc_check_early_actions');
function lc_check_early_actions() {
	if (isset($_GET['lc_page_editing_mode'])) {
		add_filter('show_admin_bar', '__return_false');
		add_filter('edit_post_link', '__return_false');
	}
	
}

////////////PAGE HTML & CSS SAVING /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
add_action('wp_ajax_lc_save_page', 'lc_ajax_save_page_func');

function lc_ajax_save_page_func() {
	
	if (!current_user_can("edit_pages")) return; //Only for those who can
	
	$update_post = array(
		'ID' => $_POST['post_id'],
		'post_content' => (($_POST['html_to_save']))
	);
	
	function lc_tweak_css($css){$css= (stripslashes($css));$css=trim(preg_replace('/\t+/', '', $css));return $css; }
	
	//UPDATE GLOBAL CSS, if it's different from current one
	if (wp_get_custom_css_post() != lc_tweak_css($_POST['css_to_save'])) wp_update_custom_css_post(lc_tweak_css($_POST['css_to_save']));
	
	//UPDATE THE PAGE CONTENT INTO THE DATABASE
	$the_update = wp_update_post($update_post);
	
	//FOR COMPATIBILITY WITH LC ON POSTS: SINCE THE UPDATE WE JUST DONE BEFORE F*CKS UP THE  CHOSEN TEMPLATE FIELD, RESTORE IT
	if ( get_post_type( $_POST['post_id'] ) == 'post' ): // or get_post_type( $_POST['post_id'] ) != 'lcr_section'  
		$current_template = get_post_meta($_POST['post_id'], '_wp_page_template', true);
		//check before if the page is assigned to any possible lc_templates
		if ($current_template == "" OR $current_template == "default")
			update_post_meta($_POST['post_id'], '_wp_page_template', "page-templates/empty.php"); //for understrap 
	endif;
	
	if ($the_update == true) echo "Save"; else echo "Error!";
	wp_die();
	
}



// EDITING TRIGGER LINKS: Place in admin menu bar a link to trigger page editing
add_action('admin_bar_menu', 'lc_add_toolbar_items', 100);
function lc_add_toolbar_items($admin_bar) {
	//check if user has rights to edit,   and that  we are not in editing mode 
	if (!current_user_can("edit_pages") or isset($_GET['lc_action_launch_editing'])) return;
	
	//ADD LINK: NEW LIVECANVAS PAGE LINK
	global $wp_admin_bar;
	$wp_admin_bar->add_node(array(
		'parent' => 'new-content',
		'id' => 'lc-add-new-page',
		'class' => 'ab-item',
		'title' => 'LiveCanvas Page Draft',
		'href' => add_query_arg(array(
			'lc_action_new_page' => '1'
		), get_admin_url()),
		'meta' => array(
			'onclick' => 'var page_name = prompt("New page name", "Untitled LC Page");if (page_name!=null)  window.location = this.getAttribute("href") +"&lc_page_name="+encodeURIComponent(page_name);   return false;'
		)
	));
	
	// ADD LINK: LAUNCH LC EDITING of the page
	if (is_admin())	return; //ONLY IN FRONTEND
	if (!is_single() && !is_page())	return; //ONLY SINGLE POSTS OR PAGES OR CPTs
	if (!lc_post_is_using_livecanvas(get_the_ID()))	return; // the page is not using a LC template
	
	global $wp_admin_bar;
	$wp_admin_bar->add_node(array(
		'id' => 'lc-launch-editing',
		'class' => 'ab-item',
		'title' => '<span id="icon-lc-launch-editing"></span>' . 'Edit with ',
		'href' => add_query_arg(array(
			'lc_action_launch_editing' => '1'
		))
	));
	//OPTIONALLY...
	//$wp_admin_bar->remove_menu('edit');
} //end func



///ADD NEW ELEMENT TO WP-ADMIN LEFT MENU
function lc_add_admin_menu_item() {
	add_pages_page(__('Add LiveCanvas Page'), __('Add LiveCanvas Page'), 'edit_pages', '#lc_click_action_new_page');
}
add_action('admin_menu', 'lc_add_admin_menu_item');

//////////////////ADD JS TO MAKE THAT LINK ACTUALLY WORK /////////////
function lc_add_admin_js() {
?>
	<script>
	document.addEventListener("DOMContentLoaded", function() { 
		document.querySelector("a[href='#lc_click_action_new_page']").addEventListener("click", function(event){
			event.preventDefault();
			document.querySelector("#wp-admin-bar-lc-add-new-page a").click();
		}); //end event click
	});	//end DOMContentLoaded
	</script>
	<?php
} //end func

add_action('admin_head', 'lc_add_admin_js');


/////// ICON IN TOOLBAR STYLING ///////////////////////////////////////////////////
add_action('admin_head', 'lc_print_launch_icon_styles'); // on backend area
add_action('wp_head', 'lc_print_launch_icon_styles'); // on frontend area
function lc_print_launch_icon_styles() {
	if (!is_user_logged_in())
		return;
?>
	<style> 
		#icon-lc-launch-editing:after {			position: relative;			float: right;			content: ' ';			width: 86px;			height: 19px; 			margin-right: 6px;			margin-top: 6px;	margin-left: 4px;			background-size: contain;			background-repeat: no-repeat;			background-image: url('<?php	echo plugins_url("/livecanvas/images/lc-minilogo.png"); ?>'); }
	</style>
	<?php
}


/// HIDE WP ADMIN BAR WHILE EDITING WITH LC
add_action('wp_loaded', 'lc_handle_actions');
function lc_handle_actions() {
	if (!current_user_can("edit_pages") or is_admin()) return;
	global $wp_admin_bar;
	if (isset($_GET['lc_action_launch_editing'])) add_filter('show_admin_bar', '__return_false');
}

/// REMOVE FILTERS FOR EDITING
add_action('wp', 'lc_remove_plugin_filters', PHP_INT_MAX);
function lc_remove_plugin_filters() {
	
	if (current_user_can("edit_pages") && isset($_GET['lc_page_editing_mode'])) {
		remove_all_filters('the_content');
		add_filter('the_content', 'lc_get_main_content');
	}
}

// ADD CSS TO HIDE MENUBAR WHEN EDITING PARTIALS, BLOCKS, SECTION
add_action("wp_head",function(){
	if (/* current_user_can("edit_pages") && isset($_GET['lc_page_editing_mode']) && */ ('lc_block' === get_post_type() OR 'lc_section' === get_post_type() OR 'lc_partial' === get_post_type()) ):
		?> <style>#wrapper-navbar {display: none} </style><?php
	endif;
});


/* CRITICAL PLUS: MAIN CONTENT FILTER  */
add_filter('the_content', 'lc_get_main_content');
function lc_get_main_content($input) {
	 
	//SET A FLAG
	if (current_user_can("edit_pages") && isset($_GET['lc_page_editing_mode']))	$lc_editing_mode = TRUE;	else	$lc_editing_mode = FALSE;
	
	//GET PURE RAW CONTENT
	$page_id = get_queried_object_id();
	if (!lc_post_is_using_livecanvas($page_id))	return $input;
	$html_out = get_post_field('post_content', $page_id, 'raw');
	
	//PASSWORD PROTECTED PAGES
	if ($lc_editing_mode == FALSE && post_password_required()) $html_out = '<div class="lc-container-wrap-passwordform"><div class="container"><div class="row"><div class="col-xs-12 text-center">' . get_the_password_form() . "</div></div></div></div>";
		
	//STRIP OUT USELESS ATTRIBUTES IF NOT EDITING
	if ($lc_editing_mode == FALSE) $html_out=lc_strip_lc_attributes($html_out);
	
	//WRAP ALL in a <MAIN>
	return "<main id='lc-main'>" . $html_out . "</main>";
}

function lc_strip_lc_attributes($html){
	$html = str_replace(' editable="inline"', "", $html);
	$html = str_replace(' editable="rich"', "", $html);
	return $html;
}
/* END of the BLOCK. Shorter than you have thought! Optionally, get also the footer stuff below.  */



//GET FOOTER HTML
function  lc_get_footer($variant){
	if   ('lc_block' === get_post_type() OR 'lc_section' === get_post_type() OR 'lc_partial' === get_post_type()) return "";
	$footer_html = get_post_field( 'post_content', lc_get_partial_postid('is_footer', $variant), 'raw' );
	return  "\n\n\n<footer id='lc-footer'>".do_shortcode(lc_neutralize_section_tags(lc_strip_lc_attributes($footer_html)))."</footer>\n\n\n";
}

function lc_neutralize_section_tags($html){
	$html = str_replace('<section', '<div', $html);
	$html = str_replace('</section>', '</div>', $html);
	return $html;
}
//DECLARE THE FUNCTION TO INTERFACE FOOTER WITH CUSTOMSTRAP
if (lc_get_option_is_set("footerV2") && !function_exists('customstrap_custom_footer')):
	function customstrap_custom_footer($variant=1){echo lc_get_footer($variant); }
endif;

///ADD LC EDITING LINKS TO PAGE LISTING IN THE WP ADMIN////
add_filter('page_row_actions', 'lc_add_action_links', 10, 2);
add_filter('post_row_actions', 'lc_add_action_links', 10, 2);
function lc_add_action_links($actions, $page_object) {
	if ( /* $_GET['post_type']=="lc_block" OR  */ lc_post_is_using_livecanvas($page_object->ID))
		$actions['edit_page_with_lc'] = "<a class='edit_page_with_lc' href='" . esc_url(add_query_arg('lc_action_launch_editing', '1', get_permalink($page_object->ID))) . "'>" . __('Edit with LiveCanvas', 'lc') . "</a>";
	return $actions;
}


/////////

//GET FOOTER CPT ID: check if post exists; if not, create it
function lc_get_partial_postid($field_name, $field_value=1) { 

	$my_posts = get_posts(array('post_type'=> 'lc_partial', 'meta_key' => $field_name, 'meta_value' => $field_value,'numberposts' => 1, 'post_status'    => 'any'));
	if( $my_posts ){
		$footer_ID= $my_posts[0]->ID;
	} else {
		$footer_ID = wp_insert_post(array('post_type' => 'lc_partial','post_title' => ucfirst(substr($field_name,3)), 'post_content' => lc_get_starter_content($field_name), 'post_status' => 'publish'));
		update_post_meta($footer_ID, $field_name, $field_value);
	}
	return $footer_ID;
}

function lc_get_starter_content($field_name){
	if ($field_name=="is_footer") return '
		<section class="bg-secondary text-secondary">
			<div class="container py-4">
				<div class="row py-4">
					<div class="col-12 text-center">
						<div class="lc-block text-white mb-4 text-light">
							<img class="img-fluid" src="https://cdn.dopewp.com/media/svg/undraw-sample/undraw_connected_world_wuay.svg" style="max-height:10vh">
						</div>
						<div class="lc-block text-white text-light">
							<div editable="rich">
								<h5><strong>My Company</strong></h5>33 Loves St, Mayfair, London W1J <br>6RE United Kingdom<p></p>
								<p>Phone: +44 33 55238 25&nbsp;</p>
								<p>Email: info@example.com</p>
							</div>
						</div><!-- /lc-block -->
						<!-- /lc-block -->
					</div>
				</div>
				<div class="row d-flex justify-content-center">
					<div class="lc-block p-1 p-sm-2">
						<a href="#"><i class="fa rounded-circle p-2 p-sm-3 mb-3 bg-light text-dark fa-facebook-square"></i></a>
					</div>
					<div class="lc-block p-1 p-sm-2">
						<a href="#"><i class="fa rounded-circle p-2 p-sm-3 mb-3 bg-light text-dark fa-twitter"></i></a>
					</div>
					<div class="lc-block p-1 p-sm-2">
						<a href="#"><i class="fa rounded-circle p-2 p-sm-3 mb-3 bg-light text-dark fa-instagram"></i></a>
					</div>
					<div class="lc-block p-1 p-sm-2">
						<a href="#"><i class="fa rounded-circle p-2 p-sm-3 mb-3 bg-light text-dark fa-youtube-play"></i></a>
					</div>
					<div class="lc-block p-1 p-sm-2">
						<a href="#"><i class="fa rounded-circle p-2 p-sm-3 mb-3 bg-light text-dark fa-linkedin-square"></i></a>
					</div>
				</div>
			</div>
		</section>
	';
}

//////////// AJAX FETCH OEMBED CODE /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
add_action('wp_ajax_lc_process_oembed', 'lc_process_oembed_func');
function lc_process_oembed_func() {
	if (!current_user_can("edit_pages")) return; //Only for editors
	
	$content = "[embed]" . $_POST['src_url'] . "[/embed]";
	global $post;
	$post->ID = PHP_INT_MAX; //trick to allow content filtering in ajax calls I love you
	remove_filter('the_content', 'wptexturize'); //remve #38
	$embed_code = apply_filters('the_content', $content);
	
	//get the url only
	$embed_code_exploded = explode(' src="', $embed_code);
	$embed_code_exploded = explode('"', $embed_code_exploded[1]);
	echo $embed_code_exploded[0];
	wp_die();
}

//////////// AJAX FETCH SHORTCODE /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
add_action('wp_ajax_lc_process_shortcode', 'lc_process_shortcode_func');
function lc_process_shortcode_func() {
	
	if (!current_user_can("edit_pages")) return; //Only for editors				
	
	global $post;
	$post->ID = PHP_INT_MAX; //trick to allow content filtering in ajax calls I love you
	$input    = stripslashes($_POST['shortcode']);
	$output   = do_shortcode($input);
	
	if ($input == $output)
		$output = "<h2>Unrecognized Shortcode</h2>";
	
	echo $output;
	wp_die();
}




///////////////////// CUSTOM BLOCKS & SECTION CUSTOM POST TYPE REGISTRATION ////////////////////////////////////////////////////////////////////////

function lc_cpts() {
	
	$labels = array(
		'name' => _x('Blocks', 'Post Type General Name', 'text_domain'),
		'singular_name' => _x('Block', 'Post Type Singular Name', 'text_domain'),
		'menu_name' => __('Custom Blocks', 'text_domain'),
		'name_admin_bar' => __('Block', 'text_domain'),
		'archives' => __('Item Archives', 'text_domain'),
		'attributes' => __('Item Attributes', 'text_domain'),
		'parent_item_colon' => __('Parent Item:', 'text_domain'),
		'all_items' => __('All Items', 'text_domain'),
		'add_new_item' => __('Add New Item', 'text_domain'),
		'add_new' => __('Add New', 'text_domain'),
		'new_item' => __('New Item', 'text_domain'),
		'edit_item' => __('Edit Item', 'text_domain'),
		'update_item' => __('Update Item', 'text_domain'),
		'view_item' => __('View Item', 'text_domain'),
		'view_items' => __('View Items', 'text_domain'),
		'search_items' => __('Search Item', 'text_domain'),
		'not_found' => __('Not found', 'text_domain'),
		'not_found_in_trash' => __('Not found in Trash', 'text_domain'),
		'featured_image' => __('Featured Image', 'text_domain'),
		'set_featured_image' => __('Set featured image', 'text_domain'),
		'remove_featured_image' => __('Remove featured image', 'text_domain'),
		'use_featured_image' => __('Use as featured image', 'text_domain'),
		'insert_into_item' => __('Insert into item', 'text_domain'),
		'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
		'items_list' => __('Items list', 'text_domain'),
		'items_list_navigation' => __('Items list navigation', 'text_domain'),
		'filter_items_list' => __('Filter items list', 'text_domain')
	);
	$args   = array(
		'label' => __('Block', 'text_domain'),
		'description' => __('HTML custom blocks', 'text_domain'),
		'labels' => $labels,
		'supports' => array('title','editor','revisions','excerpt'),
		'hierarchical' => false,
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => false,
		'menu_position' => 100,
		'menu_icon' => 'dashicons-welcome-write-blog',
		'show_in_admin_bar' => 0,
		'show_in_nav_menus' => false,
		'can_export' => true,
		'has_archive' => false,
		'exclude_from_search' => true,
		'publicly_queryable' => (current_user_can('administrator')),
		'rewrite' => false,
		'capability_type' => 'page',
		'show_in_rest' => false
	);
	register_post_type('lc_block', $args);
	
	
	
	
	
	
	$labels = array(
		'name' => _x('Sections', 'Post Type General Name', 'text_domain'),
		'singular_name' => _x('Section', 'Post Type Singular Name', 'text_domain'),
		'menu_name' => __('Custom Sections', 'text_domain'),
		'name_admin_bar' => __('Section', 'text_domain'),
		'archives' => __('Item Archives', 'text_domain'),
		'attributes' => __('Item Attributes', 'text_domain'),
		'parent_item_colon' => __('Parent Item:', 'text_domain'),
		'all_items' => __('All Items', 'text_domain'),
		'add_new_item' => __('Add New Item', 'text_domain'),
		'add_new' => __('Add New', 'text_domain'),
		'new_item' => __('New Item', 'text_domain'),
		'edit_item' => __('Edit Item', 'text_domain'),
		'update_item' => __('Update Item', 'text_domain'),
		'view_item' => __('View Item', 'text_domain'),
		'view_items' => __('View Items', 'text_domain'),
		'search_items' => __('Search Item', 'text_domain'),
		'not_found' => __('Not found', 'text_domain'),
		'not_found_in_trash' => __('Not found in Trash', 'text_domain'),
		'featured_image' => __('Featured Image', 'text_domain'),
		'set_featured_image' => __('Set featured image', 'text_domain'),
		'remove_featured_image' => __('Remove featured image', 'text_domain'),
		'use_featured_image' => __('Use as featured image', 'text_domain'),
		'insert_into_item' => __('Insert into item', 'text_domain'),
		'uploaded_to_this_item' => __('Uploaded to this item', 'text_domain'),
		'items_list' => __('Items list', 'text_domain'),
		'items_list_navigation' => __('Items list navigation', 'text_domain'),
		'filter_items_list' => __('Filter items list', 'text_domain')
	);
	$args   = array(
		'label' => __('Section', 'text_domain'),
		'description' => __('HTML custom sections', 'text_domain'),
		'labels' => $labels,
		'supports' => array('title','editor','revisions','excerpt'),
		'hierarchical' => false,
		'public' => false,
		'show_ui' => true,
		'show_in_menu' => false,
		'menu_position' => 100,
		'menu_icon' => 'dashicons-welcome-write-blog',
		'show_in_admin_bar' => 0,
		'show_in_nav_menus' => false,
		'can_export' => true,
		'has_archive' => false,
		'exclude_from_search' => true,
		'publicly_queryable' => (current_user_can('administrator')),
		'rewrite' => false,
		'capability_type' => 'page',
		'show_in_rest' => false
	);
	register_post_type('lc_section', $args);
	
	
	
	
    register_post_type( 'lc_gt_block',
        array(
            'labels' => array(
                'name' => __( 'Gutenberg Blocks' ),
                'singular_name' => __( 'Gutenberg Block' )
            ),
            'has_archive' => false,
			'hierarchical' => false,
            'public' => false,
			'show_ui' => true,
            'show_in_rest' => true,
            'supports' => array('title','editor','revisions'),
			'show_in_menu' => false,
			'menu_position' => 100,
			'menu_icon' => 'dashicons-welcome-write-blog',
			'show_in_admin_bar' => 0,
			'show_in_nav_menus' => false,
			'can_export' => true,
			'has_archive' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => (current_user_can('administrator')),
			'rewrite' => false,
			'capability_type' => 'post',
		
        )
    );
 

    register_post_type( 'lc_partial',
        array(
            'labels' => array(
                'name' => __( 'Template Partials' ),
                'singular_name' => __( 'Template Partial' )
            ),
            'has_archive' => false,
			'hierarchical' => false,
            'public' => false,
			'show_ui' => true,
            'show_in_rest' => false,
            'supports' => array('title','revisions','custom-fields'),
			'show_in_menu' => false,
			'menu_position' => 100,
			'menu_icon' => 'dashicons-welcome-write-blog',
			'show_in_admin_bar' => 0,
			'show_in_nav_menus' => false,
			'can_export' => true,
			'has_archive' => false,
			'exclude_from_search' => true,
			'publicly_queryable' => (current_user_can('administrator')),
			'rewrite' => false,
			'capability_type' => 'page',
		
        )
    );
	
	
	
}
add_action('init', 'lc_cpts', 0);


 

 
 
//FORCE TEMPLATE FOR CPTs BLOCK & SECTION
add_filter( 'template_include', 'lc_force_template' );
function lc_force_template($template){
    
    if ('lc_block' === get_post_type() OR 'lc_section' === get_post_type() OR 'lc_partial' === get_post_type()) {
        $template = get_template_directory().'/page-templates/empty.php';
    }
    // Always return, even if we didn't change anything
    return $template;
}
		



/* //REMOVE CONTENT AUTOP FOR CPTs  */
add_filter('the_content', 'lc_remove_autop_for_posttype', 0);
function lc_remove_autop_for_posttype($content) {
	('lc_block' === get_post_type() OR 'lc_section' === get_post_type() OR 'lc_partial' === get_post_type()) && remove_filter('the_content', 'wpautop');
	return $content;
}


//DISABLE WYSIWYG FOR COMPONENT EDITORS
add_filter('user_can_richedit', 'lc_page_can_richedit');
function lc_page_can_richedit($can) {
	global $post;
	if (@$post->post_type == 'lc_block' OR @$post->post_type == 'lc_section'   )
		return false;
	return $can;
}


// REMOVES MEDIA BUTTONS FROM POST TYPES
add_filter('wp_editor_settings', function($settings) {
	$current_screen = get_current_screen();
	// Post types for which the media buttons should be removed.
	$post_types = array('lc_block',	'lc_section' );
	// Bail out if media buttons should not be removed for the current post type.
	if (!$current_screen || !in_array($current_screen->post_type, $post_types, true)) {	return $settings; }
	$settings['media_buttons'] = false;
	return $settings;
});


// CODEMIRROR FOR CPTs
add_action('admin_enqueue_scripts', function() {
	if ('lc_block' !== get_current_screen()->id && 'lc_section' !== get_current_screen()->id  ) {	return;	}
	// Enqueue code editor and settings for manipulating HTML.
	$settings = wp_enqueue_code_editor(array(	'type' => 'text/html'	));
	// Bail if user disabled CodeMirror.
	if (false === $settings) {	return;	}
	wp_add_inline_script('code-editor', sprintf('jQuery( function() { 
				var lc_editor=wp.codeEditor.initialize( "content", %s );
				lc_editor.codemirror.setSize(null, 700);
			} );', wp_json_encode($settings)));
}); //end add action





//GET ACTIVE PLUGINS LIST
function lc_get_active_plugins_list() {
	$the_list  = "";
	$the_plugs = get_option('active_plugins');
	
	if ($the_plugs)
		foreach ($the_plugs as $key => $value) {
			$string = explode('/', $value); // Folder name will be displayed
			$the_list .= $string[0] . ',';
		}
	
	$the_network_plugs = get_site_option('active_sitewide_plugins');
	
	if ($the_network_plugs)
		foreach ($the_network_plugs as $key => $value) {
			$string = explode('/', $key); // Folder name will be displayed
			$the_list .= $string[0] . ',';
		}
	return $the_list;
}

///////////// HOOK THE CUSTOM INLINE CSS FOR EDITING WITH LC so it's never empty ////////
add_filter("wp_get_custom_css", 'lc_alter_custom_css',100);
function lc_alter_custom_css($css) {
	if (current_user_can("edit_pages") && isset($_GET['lc_page_editing_mode']))		$lc_editing_mode = TRUE;	else		$lc_editing_mode = FALSE;
	if ($lc_editing_mode && $css == "")
		$css .= " "; //ALWAYS NECESSARY WHEN EDITING
	return $css;
}


/////////// AUTOPTIMIZE PATCH //////////////
add_filter('autoptimize_filter_noptimize', 'lc_autoptimize_filter_noptimize_function', 10, 0);
function lc_autoptimize_filter_noptimize_function() {
	if (current_user_can("edit_pages") && (isset($_GET['lc_page_editing_mode']) OR isset($_GET['lc_action_launch_editing'])))
		return true;	else		return false;
}


/////WPROCKET LAZYLOAD PATCH
function lc_deactivate_rocket_lazyload() {
	if (current_user_can("edit_pages") && (isset($_GET['lc_page_editing_mode']) OR isset($_GET['lc_action_launch_editing'])))
		add_filter('do_rocket_lazyload', '__return_false');
}
add_filter('wp', 'lc_deactivate_rocket_lazyload');


//UPDATER
if(lc_get_license_code()):
	require 'modules/plugin-update-checker/plugin-update-checker.php';
	$myUpdateChecker = Puc_v4_Factory::buildUpdateChecker(	'https://update.livecanvas.com/lc-plugin-updater-meta/?license-code='.lc_get_license_code(),	__FILE__,	'livecanvas' );
endif;

