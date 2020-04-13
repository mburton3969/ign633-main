<?php
/**
 * The template for displaying the footer.
 *
 * Contains the closing of the #content div and all content after
 *
 * @package understrap
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

get_template_part( 'sidebar-templates/sidebar', 'footerfull' );
 
if (function_exists("customstrap_custom_footer")) customstrap_custom_footer(); else {
	
	$container = get_theme_mod( 'understrap_container_type' );
	?>
	
	<div class="wrapper" id="wrapper-footer">
	
		<div class="<?php echo esc_attr( $container ); ?>">
	
			<div class="row">
	
				<div class="col-md-12">
	
					<footer class="site-footer" id="colophon">
	
						<div class="site-info">
	
							<?php understrap_site_info(); ?>
	
						</div><!-- .site-info -->
	
					</footer><!-- #colophon -->
	
				</div><!--col end -->
	
			</div><!-- row end -->
	
		</div><!-- container end -->
	
	</div><!-- wrapper end -->
	
	<?php } //END ELSE CASE ?>

</div><!-- #page we need this extra closing tag here -->

<?php wp_footer(); ?>

</body>

</html>

