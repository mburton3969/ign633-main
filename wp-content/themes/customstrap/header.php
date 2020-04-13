<?php
/**
 * The header for our theme.
 *
 * Displays all of the <head> section and everything up till <div id="content">
 *
 * @package understrap
 */
// USEFUL LINKS
// https://medium.com/wdstack/bootstrap-4-custom-navbar-1f6a2da5ed3c
// https://medium.com/coder-grrl/the-guide-to-customising-the-bootstrap-4-navbar-i-wish-id-had-6-months-ago-7bc6ce0e3c71

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

$container = get_theme_mod( 'understrap_container_type' );
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="profile" href="http://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>

<body <?php body_class(); ?>>
<?php do_action( 'wp_body_open' ); ?>

<div class="site" id="page">
	
	<?php if (get_theme_mod("enable_topbar") ) : ?>
	<!-- ******************* The Topbar Area ******************* -->
	<div id="wrapper-topbar" class="py-2 <?php echo get_theme_mod('topbar_bg_color_choice','bg-light') ?> <?php echo get_theme_mod('topbar_text_color_choice','text-dark') ?>">
		<div class="container">
			<div class="row">
				<div id="topbar-content" class="col-md-12 text-left text-center text-md-left small"> <?php echo get_theme_mod('topbar_content') ?>	</div>
			</div>
		</div>
	</div>
	<?php endif; ?>
	<!-- ******************* The Navbar Area ******************* -->
	<div id="wrapper-navbar" itemscope itemtype="http://schema.org/WebSite">

		<a class="skip-link sr-only sr-only-focusable" href="#content"><?php esc_html_e( 'Skip to content', 'customstrap' ); ?></a>

		<nav class="navbar navbar-expand-lg <?php echo get_theme_mod('customstrap_header_navbar_position')." ". get_theme_mod('customstrap_header_navbar_color_scheme','navbar-dark').' '. get_theme_mod('customstrap_header_navbar_color_choice','bg-dark'); ?>">

		<?php if ( 'container' == $container ) : ?>
			<div class="container" >
		<?php endif; ?>
					<div id="logo-tagline-wrap">
						<!-- Your site title as branding in the menu -->
						<?php if ( ! has_custom_logo() ) { ?>
	
							<?php if ( is_front_page() && is_home() ) : ?>
	
								<div class="navbar-brand mb-0 h3"><a rel="home" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" itemprop="url"><?php bloginfo( 'name' ); ?></a></div>
	
							<?php else : ?>
	
								<a class="navbar-brand h3" rel="home" href="<?php echo esc_url( home_url( '/' ) ); ?>" title="<?php echo esc_attr( get_bloginfo( 'name', 'display' ) ); ?>" itemprop="url"><?php bloginfo( 'name' ); ?></a>
	
							<?php endif; ?>
	
	
						<?php } else {
							the_custom_logo();
						} ?><!-- end custom logo -->
	
					
						<?php if (!get_theme_mod('header_disable_tagline')): ?><small id="top-description" class="text-muted d-none d-md-inline-block"><?php bloginfo("description") ?></small><?php endif ?>
					</div>
				<button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarNavDropdown" aria-controls="navbarNavDropdown" aria-expanded="false" aria-label="<?php esc_attr_e( 'Toggle navigation', 'understrap' ); ?>">
					<span class="navbar-toggler-icon"></span>
				</button>

				<!-- The WordPress Menu goes here -->
				<?php wp_nav_menu(
					array(
						'theme_location'  => 'primary',
						'container_class' => 'collapse navbar-collapse',
						'container_id'    => 'navbarNavDropdown',
						'menu_class'      => 'navbar-nav ml-auto',
						'fallback_cb'     => '',
						'menu_id'         => 'main-menu',
						'depth'           => 2,
						'walker'          => new Understrap_WP_Bootstrap_Navwalker(),
					)
				); ?>
			<?php if ( 'container' == $container ) : ?>
			</div><!-- .container -->
			<?php endif; ?>

		</nav><!-- .site-navigation -->

	</div><!-- #wrapper-navbar end -->
