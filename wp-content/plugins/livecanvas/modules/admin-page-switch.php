<?php

// EXIT IF ACCESSED DIRECTLY.
defined( 'ABSPATH' ) || exit;

/**
 * Add meta box
 *
 * @param post $post The post object
 * @link https://codex.wordpress.org/Plugin_API/Action_Reference/add_meta_boxes
 */
function lc_add_meta_boxes( $post ){
	add_meta_box( 'lca_meta_boxes', __( 'LiveCanvas', 'livecanvas' ), 'lc_build_meta_box', 'page', 'side', 'high' );
	add_meta_box( 'lca_meta_boxes', __( 'LiveCanvas', 'livecanvas' ), 'lc_build_meta_box', 'post', 'side', 'high' );
	//FOR CPTs:
	//determine post type
	if(isset($_GET['post_type'])) $the_post_type=$_GET['post_type']; // for new screen cpt
	if(isset($_GET['post'])) $the_post_type=get_post_type($_GET['post']); // for edit cpt screen
	//add the meta box
	if (isset($the_post_type) && $the_post_type!= 'lc_block' && $the_post_type!= 'lc_gt_block' && $the_post_type!= 'lc_section')
		add_meta_box( 'lca_meta_boxes', __( 'LiveCanvas', 'livecanvas' ), 'lc_build_meta_box', $the_post_type, 'side', 'high' );
	//add the meta box for gt 
	if (isset($the_post_type) && $the_post_type== 'lc_gt_block' )
		add_meta_box( 'lca_meta_boxes', __( 'LiveCanvas', 'livecanvas' ), 'lc_build_meta_box_lc_gt_block', $the_post_type, 'side', 'high' );
	
	//if (isset($currentScreen->id)) add_meta_box( 'lca_meta_boxes', __( 'LiveCanvas', 'livecanvas' ), 'lc_build_meta_box', $currentScreen->id, 'side', 'high' );
}
add_action( 'add_meta_boxes', 'lc_add_meta_boxes' );

 
function lc_build_meta_box( $post ){
	// make sure the form request comes from WordPress
	wp_nonce_field( basename( __FILE__ ), 'lc_meta_box_nonce' );

	// retrieve the _lc_livecanvas_enabled current value
	$current_livecanvas_enabled = get_post_meta( $post->ID, '_lc_livecanvas_enabled', true );
 
	?>
	<div class='inside'>
		<h4><?php _e( 'Enable the LiveCanvas Editor', 'livecanvas' ); ?></h4>
		<p>
			<input type="radio" name="livecanvas_enabled" value="1" <?php checked( $current_livecanvas_enabled, '1' ); ?> /> Yes<br />
			<input type="radio" name="livecanvas_enabled" value="" <?php checked( $current_livecanvas_enabled, '' ); ?> /> No
		</p>
	</div>
	<?php
}

function lc_save_meta_box_data( $post_id ){
	// verify meta box nonce
	if ( !isset( $_POST['lc_meta_box_nonce'] ) || !wp_verify_nonce( $_POST['lc_meta_box_nonce'], basename( __FILE__ ) ) ){		return;	}

	// return if autosave
	if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){ return; }

	// Check the user's permissions.
	if ( ! current_user_can( 'edit_post', $post_id ) ){	return;	}
	
	// If we have some data handle the situation
	if ( isset( $_REQUEST['livecanvas_enabled'] ) ):
		
		
		if ( get_post_meta($post_id, '_lc_livecanvas_enabled', true) !=1 && $_REQUEST['livecanvas_enabled']==1) { ///// CASE LC RADIO SWITCHED FROM OFF TO ON /////////
			
				//set the right template for LC
				update_post_meta( $post_id, '_wp_page_template', "page-templates/empty.php" );
		}
		
		
		if ( get_post_meta($post_id, '_lc_livecanvas_enabled', true) ==1 && $_REQUEST['livecanvas_enabled']!=1) { ///// CASE LC RADIO SWITCHED FROM ON TO OFF /////////
					
				//reset page template
				delete_post_meta( $post_id, '_wp_page_template'  );
		}
		
		//SAVE THE CUSTOM FIELD VALUE
		update_post_meta( $post_id, '_lc_livecanvas_enabled', sanitize_text_field( $_REQUEST['livecanvas_enabled'] ) );
			
		
	endif;
	

}
add_action( 'save_post', 'lc_save_meta_box_data' );


////  _gt_block
function lc_build_meta_box_lc_gt_block( $post ){
	 
	?>
	<div class='inside'>
		<h4><?php _e( 'You can embed this content using the shortcode:', 'livecanvas' ); ?></h4>
		<h2>[lc_get_gt_block id="<?php echo $post->ID; ?>"]</h2>
		 
	</div>
	<?php
}



//////////////////////////////////  SUGGESTION /////////////////////////////////
 
/////SUGGEST IN WP ADMIN TO ENABLE LC TEMPLATES FOR PAGES, DISABLE WYSIWYG WHEN LC TEMPLATES ARE ENABLED
add_action( 'current_screen', 'lc_tweak_wp_interface_page' ); 
function lc_tweak_wp_interface_page() { 
	
	
	$currentScreen = get_current_screen();
	//if( $currentScreen->id !== "page" && $currentScreen->id !== "post" )   return;
	//print_r($currentScreen);die;
	 
	if(isset( $_GET['post'])) $already_using_lc_template=lc_post_is_using_livecanvas($_GET['post']);
						else
							$already_using_lc_template=FALSE;  
	
	if ($already_using_lc_template) { 
		  remove_post_type_support('page', 'editor');
		  remove_post_type_support('post', 'editor');
		  if(isset( $_GET['post'])) remove_post_type_support(get_post_type($_GET['post']), 'editor'); //for saved cpt posts
		  add_action('admin_notices', 'lc_template_admin_notice_using_lc');
    } else {
	 //not using lc template (yet)
	 
	 //if( $currentScreen->id === "page")
	 //if( $currentScreen->id != "post")
	add_action('admin_notices', 'lc_template_admin_notice_not_using_lc_yet');
	}
}



 
function lc_template_admin_notice_not_using_lc_yet(){

	global $post; 
	?>
	 
	<style>
		#wpbody-content .wrap .lc-add-editing-icon {  margin: 10px 0 0 45px;} /*no gut */
		.edit-post-header-toolbar .lc-add-editing-icon {  margin: 0 0 0 45px;} /* gut */
	</style> 
	
	<script>
		function isGutenbergActive() {    return typeof wp !== 'undefined' && typeof wp.blocks !== 'undefined';}
		
		jQuery(document).ready(function($) {
			
			if(isGutenbergActive()) { ///////ONLY FOR GUTENBERG - future useful link: https://github.com/WordPress/gutenberg/issues/17632
				wp.data.subscribe(function () { 
					if (wp.data.select('core/editor').didPostSaveRequestSucceed() && !wp.data.select('core/editor').isAutosavingPost()    ) {  
							console.log("Guten is saving something");
							if ($('input[name=livecanvas_enabled][value=1]').prop("checked") == true ) {
								//LC is enabled!
								if ($("#lc-guten-trigger-editing").length==0) { 
									//button is not there, but its needed: let's append it 
									var lc_button_url="<?php echo get_site_url() ?>?p="+wp.data.select('core/editor').getCurrentPostId()+"&lc_action_launch_editing=1";  
									var lc_button_html="<a id='lc-guten-trigger-editing' class='lc-add-editing-icon button button-primary button-large' href='"+lc_button_url+"' >Edit with LiveCanvas</a>";
									
									$(".edit-post-header-toolbar").append(lc_button_html);
									 
								}
							}	else {
								//LC is not enabled, button is not necessary
								$("#lc-guten-trigger-editing").remove();
								}				 
						
					}
				
			  });  //end subscribe
			} //end if Gutenberg
			else {
				//no gutenberg
			}
			
		});//end document ready
	
	
	</script>
			
		    
	<?php 
 
}
 
function lc_template_admin_notice_using_lc(){
	 ///ADDS THE BUTTON TO LAUNCH LIVECANVAS EDITOR
	 global $post;	 
    ?>
	<script>
		jQuery( document ).ready(function() {		 
				//no guten 
				var lc_button_url="<?php echo esc_url( add_query_arg( array('lc_action_launch_editing'=> '1','from_page_edit' =>'1'), get_permalink($post->ID))) ?>"
				jQuery("#post-body-content").append("<br><a class='lc-add-editing-icon button button-primary button-hero' href='"+lc_button_url+"' >Edit with LiveCanvas</a>");
		});
		 
	</script>
 
	<?php 
 
}

