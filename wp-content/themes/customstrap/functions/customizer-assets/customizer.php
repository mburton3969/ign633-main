<?php

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

///MAIN SETTING: DECLARE ALL SCSS VARIABLES TO HANDLE IN THE CUSTOMIZER
function customstrap_get_scss_variables_array(){
	return array(
		"colors" => array( // $variable_name => $variable_type

			'$link-color' => 'color',
			'$link-hover-color' => 'color',
			'$primary'=> 'color',
			'$secondary' => 'color',
			'$success' => 'color',
			'$info' => 'color',
			'$warning' => 'color',
			'$danger' => 'color',
			'$light' => 'color',
			'$dark' => 'color',
			),
		//add another section
		"bootstrap-options" => array( // $variable_name => $variable_type

			/*'$enable-rounded' => 'boolean',*/
			'$enable-shadows' => 'boolean',
			'$enable-gradients'=> 'boolean',
			'$enable-responsive-font-sizes' => 'boolean',
			'$font-size-base' => 'text',
			),
		//add another section
		
	);	 
}
 
//ENABLE SELECTIVE REFRESH 
add_theme_support( 'customize-selective-refresh-widgets' );

//ADD HELPER ICONS
function customstrap_register_main_partials( WP_Customize_Manager $wp_customize ) {
 
    // Abort if selective refresh is not available.
    if ( ! isset( $wp_customize->selective_refresh ) ) { return;}
 
	$wp_customize->get_setting( 'blogname' )->transport = 'postMessage';
	$wp_customize->get_setting( 'blogdescription' )->transport = 'postMessage';

	//blogname
    $wp_customize->selective_refresh->add_partial( 'header_site_title', array(
        'selector' => 'a.navbar-brand',
        'settings' => array( 'blogname' ),
        'render_callback' => function() { return get_bloginfo( 'name', 'display' );  },
    ));
	
	//blog description
    $wp_customize->selective_refresh->add_partial( 'header_site_desc', array(
        'selector' => '#top-description',
        'settings' => array( 'blogdescription' ),
        'render_callback' => function() { return get_bloginfo( 'description', 'display' ); },
    ));
	
	//hide tagline
	$wp_customize->selective_refresh->add_partial( 'header_disable_tagline', array(
        'selector' => '#top-description',
        'settings' => array( 'header_disable_tagline' ),
        'render_callback' => function() {if (!get_theme_mod('header_disable_tagline')) return get_bloginfo( 'description', 'display' ); else return "";},
    ));
	
	//MENUS
	$wp_customize->selective_refresh->add_partial( 'header_menu_left', array(
        'selector' => '#navbar .menuwrap-left',
        'settings' => array( 'nav_menu_locations[navbar-left]' ),
          
    ) );
	
	/*
	$wp_customize->selective_refresh->add_partial( 'header_menu_right', array(
        'selector' => '#navbar .menuwrap-right',
        'settings' => array( 'nav_menu_locations[navbar-right]' ),     
    ));
	*/
	//topbar content
	$wp_customize->selective_refresh->add_partial( 'topbar_html_content', array(
        'selector' => '#topbar-content',
        'settings' => array( 'topbar_content' ),
		'render_callback' => function() {
             return get_theme_mod('topbar_content'); 
        },     
    )); 
	//footer text
	$wp_customize->selective_refresh->add_partial( 'footer_ending_text', array(
        'selector' => 'footer.site-footer',
        'settings' => array( 'customstrap_footer_text' ),
		'render_callback' => function() {
             return understrap_site_info();
        },     
    ));
	/*
	//inline css
	$wp_customize->selective_refresh->add_partial( 'customstrap_inline_css', array(
        'selector' => '#customstrap-inline-style',
        'settings' => array( 'customstrap_footer_bgcolor','customstrap_menubar_bgcolor' , 'customstrap_links_color','customstrap_hover_links_color','customstrap_headings_font','customstrap_body_font'  ),
		'render_callback' => function() {
             return customstrap_footer_add_inline_css();
        },
    ));
	*/
	////even if those dont do partial refresh, following code shows the contextual editing pencils 
	//SINGLE: posted on 
	$wp_customize->selective_refresh->add_partial( 'singlepost_entry_meta', array(
        'selector' => '.entry-header .entry-meta',
        'settings' => array( 'singlepost_disable_entry_meta' ),
		'render_callback' => '__return_false'    
    ));
	
	//SINGLE: posted in cats
	$wp_customize->selective_refresh->add_partial( 'singlepost_entry_footer', array(
        'selector' => 'footer.entry-footer',
        'settings' => array( 'singlepost_disable_entry_footer' ),
		'render_callback' => '__return_false'    
    ));
	
	//SINGLE: postnavi
	$wp_customize->selective_refresh->add_partial( 'singlepost_posts_nav', array(
        'selector' => 'nav.post-navigation',
        'settings' => array( 'singlepost_disable_posts_nav' ),
		'render_callback' => '__return_false' 
    ));
	
	//SINGLE: comments
	$wp_customize->selective_refresh->add_partial( 'singlepost_comments', array(
        'selector' => '#comments',
        'settings' => array( 'singlepost_disable_comments' ),
		'render_callback' => '__return_false'    
    ));
     
}
add_action( 'customize_register', 'customstrap_register_main_partials' );

 
//CUSTOM BACKGROUND
$defaults_bg = array(
	'default-color'          => '',	'default-image'          => '',	'default-repeat'         => '',	'default-position-x'     => '',	'default-attachment'     => '',
	'wp-head-callback'       => '_custom_background_cb',	'admin-head-callback'    => '',	'admin-preview-callback' => ''
);
add_theme_support( 'custom-background' );


//CUSTOM BACKGROUND SIZING OPTIONS

function custom_background_size( $wp_customize ) {
 
	// Add your setting.
	$wp_customize->add_setting( 'background-image-size', array(
		'default' => 'cover',
	) );

	// Add your control box.
	$wp_customize->add_control( 'background-image-size', array(
		'label'      => __( 'Background Image Size',"customstrap" ),
		'section'    => 'background_image', 
		'priority'   => 200,
		'type' => 'radio',
		'choices' => array(
			'cover' => __( 'Cover',"customstrap" ),
			'contain' => __( 'Contain' ,"customstrap"),
			'inherit' => __( 'Inherit' ,"customstrap"),
		)
	) );
}

add_action( 'customize_register', 'custom_background_size' );

function custom_background_size_css() {
	if ( ! get_theme_mod( 'background_image' ) )  return;
	$background_size = get_theme_mod( 'background-image-size', 'inherit' );
	echo '<style> body.custom-background { background-size: '.$background_size.'; } </style>';
}

add_action( 'wp_head', 'custom_background_size_css', 999 );


//END CUSTOM BACKGROUND SIZING OPTIONS


	
////////DECLARE ALL THE WIDGETS WE NEED	FOR THE SCSS OPTIONS////////////////////////////////////////////////

add_action("customize_register","customstrap_theme_customize_register_extras");
	
function customstrap_theme_customize_register_extras($wp_customize) {
	
	///ADDD SECTIONS:
	//COLORS is already default
	
	//BOOTSTRAP OPTIONS
	$wp_customize->add_section("bootstrap-options", array(
        "title" => __("Bootstrap Options", "customstrap"),
        "priority" => 50,
    ));
	
	
	//istantiate  all controls needed for customstrap_get_scss_variables_array()
	foreach(customstrap_get_scss_variables_array() as $section_slug => $section_data):
	
		foreach($section_data as $variable_name => $variable_type):
			 
			$variable_slug=str_replace("$","SCSSvar_",$variable_name);
			$variable_pretty_format_name=ucwords(str_replace("-",' ',str_replace("$","",$variable_name)));		
			
			if($variable_type=="color"):
			
				$wp_customize->add_setting(  $variable_slug,  array(
					'default' => '', // Give it a default
					'sanitize_callback' => 'sanitize_hex_color',
					"transport" => "postMessage",
					));
				$wp_customize->add_control(
					new WP_Customize_Color_Control(
					$wp_customize,
					$variable_slug, //give it an ID
					array(
						'label' => __( $variable_pretty_format_name, 'customstrap' ), //set the label to appear in the Customizer
						'description' =>  "(".$variable_name.")",
						'section' => $section_slug, //select the section for it to appear under  
						)
					));	
			endif;
			
			if($variable_type=="boolean"):
 
				$wp_customize->add_setting($variable_slug, array(
					"default" => "",
					"transport" => "postMessage",
				));
				$wp_customize->add_control(new WP_Customize_Control(
					$wp_customize,
					$variable_slug,
					array(
						'label' => __( $variable_pretty_format_name, 'customstrap' ), //set the label to appear in the Customizer
						'description' =>  "(".$variable_name.")",
						'section' => $section_slug, //select the section for it to appear under
						'type' => 'checkbox'
						)
				));
			endif;
			
			if($variable_type=="text"):
 
				$wp_customize->add_setting($variable_slug, array(
					"default" => "",
					"transport" => "postMessage",
					"default" => "1rem",
					//'sanitize_callback' => 'customstrap_sanitize_rem'
				));
				$wp_customize->add_control(new WP_Customize_Control(
					$wp_customize,
					$variable_slug,
					array(
						'label' => __( $variable_pretty_format_name, 'customstrap' ), //set the label to appear in the Customizer
						'description' =>  "(".$variable_name.")",
						'section' => $section_slug, //select the section for it to appear under
						'type' => 'text', 
						)
				));
			endif;
			
		endforeach;
	endforeach;

	//SANITIZE CHECKBOX
	function customstrap_sanitize_checkbox( $input ) {		return ( ( isset( $input ) && true == $input ) ? true : false ); }

	//COLORS: ANDROID CHROME HEADER COLOR
	$wp_customize->add_setting(  'customstrap_header_chrome_color',  array(
	 'default' => '', // Give it a default
	 'transport" => "postMessage',
	 ));
	 $wp_customize->add_control(
	 new WP_Customize_Color_Control(
	 $wp_customize,
	 'customstrap_header_chrome_color', //give it an ID
	 array(
	 'label' => __( 'Header Color in Android Chrome', 'customstrap' ), //set the label to appear in the Customizer
	 'section' => 'colors', //select the section for it to appear under 
		)
	 ));
 
    //TAGLINE: SHOW / HIDE SWITCH
	$wp_customize->add_setting('header_disable_tagline', array(
        'default' => '',
        'transport' => 'postMessage',
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        'header_disable_tagline',
        array(
            'label' => __('Hide Tagline', 'customstrap'),
            'section' => 'title_tagline',  
            'type'     => 'checkbox',
			)
    ));
	
    //   NAVBAR SECTION //////////////////////////////////////////////////////////////////////////////////////////////////////////
	$wp_customize->add_section("nav", array(
        "title" => __("Main Navigation", "customstrap"),
        "priority" => 60,
    ));

	// HEADER NAVBAR CHOICE
	$wp_customize->add_setting("customstrap_header_navbar_position", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_header_navbar_position",
        array(
            'label' => __('Navbar Position', 'customstrap'),
            'section' => 'nav',
            'type'     => 'radio',
			'choices'  => array(
				''  => 'Standard Static Top',
				'fixed-top' => 'Fixed on Top',
				'fixed-bottom'  => 'Fixed on Bottom',
				'd-none'  => 'No Navbar', 
				)
        )
    ));
	
	//HEADERNAVBAR COLOR CHOICE
	$wp_customize->add_setting("customstrap_header_navbar_color_choice", array(
        'default' => 'bg-dark',
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_header_navbar_color_choice",
        array(
            'label' => __('Navbar Background Color', 'customstrap'),
            'section' => 'nav',
            'type'     => 'radio',
			'choices'  => array(
				'bg-primary'	=> 'Primary',	
				'bg-secondary'	=> 'Secondary',	
				'bg-success' 	=> 'Success', 	
				'bg-info' 		=> 'Info', 		
				'bg-warning' 	=> 'Warning', 	
				'bg-danger' 	=> 'Danger', 	
				'bg-light' 	=> 'Light', 	
				'bg-dark' 		=> 'Dark', 		
				'bg-transparent' 		=> 'Transparent' 
				
				
				)
        )
    ));
	
	//HEADERNAVBAR COLOR SCHEME
	$wp_customize->add_setting("customstrap_header_navbar_color_scheme", array(
        'default' => 'navbar-dark',
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_header_navbar_color_scheme",
        array(
            'label' => __('Color Scheme (Menubar links)', 'customstrap'),
            'section' => 'nav',
			'type'     => 'radio',
			'choices'  => array(
				''  => 'Default',
				'navbar-light' => 'Light (Dark links)',
				'navbar-dark' => 'Dark (Light links)', 
				)
        )
    ));
	
	//  TOPBAR SECTION //////////////////////////////////////////////////////////////////////////////////////////////////////////
	$wp_customize->add_section("topbar", array(
        "title" => __("Optional Topbar", "customstrap"),
        "priority" => 60,
    ));
	
	//ENABLE TOPBAR
	$wp_customize->add_setting("enable_topbar", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "enable_topbar",
        array(
            "label" => __("Enable Topbar", "customstrap"),
			"description" => __("Adds before all, at body start", "customstrap"),
            "section" => "topbar", 
            'type'     => 'checkbox',
			)
    ));
	
	//TOPBAR TEXT
	$wp_customize->add_setting("topbar_content", array(
        "default" => "",
        "transport" => "postMessage",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "topbar_content",
        array(
            "label" => __("Topbar Text / HTML", "customstrap"),
            "section" => "topbar",
            'type'     => 'textarea',
        )
    ));
	
	//TOPBAR BG COLOR CHOICE
	$wp_customize->add_setting("topbar_bg_color_choice", array(
        'default' => 'bg-light',
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "topbar_bg_color_choice",
        array(
            'label' => __('Topbar Background Color', 'customstrap'),
            'section' => 'topbar',
            'type'     => 'radio',
			'choices'  => array(
				'bg-primary'	=> 'Primary',	
				'bg-secondary'	=> 'Secondary',	
				'bg-success' 	=> 'Success', 	
				'bg-info' 		=> 'Info', 		
				'bg-warning' 	=> 'Warning', 	
				'bg-danger' 	=> 'Danger', 	
				'bg-light' 	=> 'Light', 	
				'bg-dark' 		=> 'Dark', 		
				'bg-transparent' 		=> 'Transparent'
				)
        )
    ));
	
	//TOPBAR TEXT COLOR CHOICE
	$wp_customize->add_setting("topbar_text_color_choice", array(
        'default' => 'text-dark',
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "topbar_text_color_choice",
        array(
            'label' => __('Topbar Text Color', 'customstrap'),
            'section' => 'topbar',
            'type'     => 'radio',
			'choices'  => array(
				'text-primary'	=> 'Primary',	
				'text-secondary'	=> 'Secondary',	
				'text-success' 	=> 'Success', 	
				'text-info' 		=> 'Info', 		
				'text-warning' 	=> 'Warning', 	
				'text-danger' 	=> 'Danger', 	
				'text-light' 	=> 'Light', 	
				'text-dark' 		=> 'Dark', 		
				)
        )
    ));
	
	
	//FONTS SECTION ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

	$wp_customize->add_section("fonts", array(
        "title" => __("Fonts", "customstrap"),
        "priority" => 50,
    ));
	
	//FONT COMBINATIONS
	$wp_customize->add_setting("customstrap_font_combinations", array(
        "default" => "",
        "transport" => "postMessage",
    ));

	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_font_combinations",
        array(
            "label" => __("Font Combination", "customstrap"),
			'description' => __( 'Check out  <a target="_blank" href="http://fontpair.co/">FontPair</a> for more   inspiration.', 'customstrap' ),
            "section" => "fonts",
            'type'     => 'select',
			'choices'  => array(
				'' => 'Default',
				'Cabin and Old Standard TT' => 'Cabin and Old Standard TT',
				'Fjalla One and Average' => 'Fjalla One and Average',
				'Istok Web and Lora' => 'Istok Web and Lora',
				'Josefin Sans and Playfair Display' => 'Josefin Sans and Playfair Display',
				'Lato and Merriweather' => 'Lato and Merriweather',
				'Montserrat and Cardo' => 'Montserrat and Cardo',
				'Montserrat and Crimson Text' => 'Montserrat and Crimson Text',
				'Montserrat and Domine' => 'Montserrat and Domine',
				'Montserrat and Neuton' => 'Montserrat and Neuton',
				'Montserrat and Playfair Display' => 'Montserrat and Playfair Display',
				'Muli and Playfair Display' => 'Muli and Playfair Display',
				'Nunito and Alegreya' => 'Nunito and Alegreya',
				'Nunito and Lora' => 'Nunito and Lora',
				'Open Sans and Gentium Book Basic' => 'Open Sans and Gentium Book Basic',
				'Oswald and Merriweather' => 'Oswald and Merriweather',
				'Oswald and Quattrocento'=>'Oswald and Quattrocento',
				'PT Sans and PT Serif'=>'PT Sans and PT Serif',
				'Quicksand and EB Garamond'=>'Quicksand and EB Garamond',
				'Raleway and Merriweather'=>'Raleway and Merriweather',
				'Ubuntu and Lora'=>'Ubuntu and Lora',
				
				'Alegreya and Open Sans'=>'Alegreya and Open Sans',
				'Cantata One and Imprima'=>'Cantata One and Imprima',
				'Crete Round and AbeeZee'=>'Crete Round and AbeeZee',
				'Libre Baskerville and Montserrat'=>'Libre Baskerville and Montserrat',
				'Playfair Display and Open Sans'=>'Playfair Display and Open Sans',
				
				'Abel and Ubuntu'=>'Abel and Ubuntu',
				'Didact Gothic and Arimo'=>'Didact Gothic and Arimo',
				'Fjalla One and Cantarell'=>'Fjalla One and Cantarell',
				'Francois One and Lato'=>'Francois One and Lato',
				'Montserrat and Hind'=>'Montserrat and Hind',
				'Oxygen and Source Sans Pro'=>'Oxygen and Source Sans Pro',
				
				'Alfaslab One and Gentium Book'=>'Alfaslab One and Gentium Book',
				'Clicker Script and EB Garamond'=>'Clicker Script and EB Garamond',
				'Dancing Script and Ledger'=>'Dancing Script and Ledger',
				'Nixie One and Ledger'=>'Nixie One and Ledger',
				'Patua One and Lora'=>'Patua One and Lora',
				'Sacramento and Alice'=>'Sacramento and Alice',
				'Walter Turncoat and Kreon'=>'Walter Turncoat and Kreon',
			),
        )
    ));
	
	//HEADING FONTS
	$wp_customize->add_setting("customstrap_headings_font", array(
        "default" => "",
        "transport" => "postMessage",
    ));
	global $customstrap_google_fonts_array;
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_headings_font",
        array(
            "label" => __(" Headings Font ", "customstrap"),
			'description' => __( 'Browse the official <a target="_blank" href="https://fonts.google.com/">Google Fonts</a> page ', 'customstrap' ),
            "section" => "fonts",
            'type'     => 'select',
			'choices'  => $customstrap_google_fonts_array,
        )
    ));
	
	//BODY FONTS
	$wp_customize->add_setting("customstrap_body_font", array(
        "default" => "",
        "transport" => "postMessage",
    ));
	global $customstrap_google_fonts_array;
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_body_font",
        array(
            "label" => __(" Body Font ", "customstrap"),
			'description' => __( 'Browse the official <a target="_blank" href="https://fonts.google.com/">Google Fonts</a> page ', 'customstrap' ),
            "section" => "fonts",
            'type'     => 'select',
			'choices'  => $customstrap_google_fonts_array,
        )
    ));
	
 
	//ADD SECTION FOR FOOTER  //////////////////////////////////////////////////////////////////////////////////////////////////////////
	$wp_customize->add_section("footer", array(
        "title" => __("Footer", "customstrap"),
        "priority" => 100,
    ));
	
	//FOOTER TEXT
	$wp_customize->add_setting("customstrap_footer_text", array(
        "default" => "",
        "transport" => "postMessage",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_footer_text",
        array(
            "label" => __("Footer Text / HTML", "customstrap"),
            "section" => "footer",
            'type'     => 'textarea',
			 
        )
    ));
	
		
	// ADD A SECTION FOR EXTRAS
	$wp_customize->add_section("extras", array(
        "title" => __("Add Code to Header & Footer", "customstrap"),
        "priority" => 160,
    ));

	//ADD HEADER CODE  
	$wp_customize->add_setting("customstrap_header_code", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_header_code",
        array(
            "label" => __("Add code to Header", "customstrap"),
            "section" => "extras",
            'type'     => 'textarea',
			'description' =>'Placed inside the HEAD of the page'
			)
    ));
	
	//ADD FOOTER CODE 
	$wp_customize->add_setting("customstrap_footer_code", array(
        "default" => "",
        "transport" => "refresh",
    ));


	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "customstrap_footer_code",
        array(
            "label" => __("Add code to Footer", "customstrap"),
            "section" => "extras",
            'type'     => 'textarea',
			'description' =>'Placed before closing the BODY of the page'
			)
    ));

	
	// SINGLE POST & ARCHIVES SECTION //////////////////////////////////////////////////////////////////////////////////////////////////////////
	$wp_customize->add_section("singleposts", array(
        "title" => __("Single Post & Archives", "customstrap"),
        "priority" => 160,
    ));
	
	//ENTRY META: POSTED ON / BY 
	$wp_customize->add_setting("singlepost_disable_entry_meta", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "singlepost_disable_entry_meta",
        array(
            "label" => __("Hide Post Date and Author (Posted on)", "customstrap"),
			"description" => __("Publish and reload to see effect", "customstrap"),
            "section" => "singleposts", 
            'type'     => 'checkbox',
			)
    ));
	
	//ENTRY FOOTER: CATEGORIES / TAGS
	$wp_customize->add_setting("singlepost_disable_entry_footer", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "singlepost_disable_entry_footer",
        array(
            "label" => __("Hide Categories and Tags (Posted in)", "customstrap"),
			"description" => __("Publish and reload to see effect", "customstrap"),
            "section" => "singleposts", 
            'type'     => 'checkbox',
			)
    ));
	
	//PAGES NAVIGATION: NEXT / PREV ARTICLE
	$wp_customize->add_setting("singlepost_disable_posts_nav", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "singlepost_disable_posts_nav",
        array(
            "label" => __("Hide Next and Prev Post Links (Single Post Template)", "customstrap"),
			"description" => __("Publish and reload to see effect", "customstrap"),
            "section" => "singleposts", 
            'type'     => 'checkbox',
			)
    ));
	
	//COMMENTS
	$wp_customize->add_setting("singlepost_disable_comments", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "singlepost_disable_comments",
        array(
            "label" => __("Disable Comments", "customstrap"),
			"description" => __("Publish and reload to see effect", "customstrap"),
            "section" => "singleposts", 
            'type'     => 'checkbox',
			)
    ));
	
	//LIGHTBOX
	$wp_customize->add_setting("enable_lightbox", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "enable_lightbox",
        array(
            "label" => __("Enable Lightbox", "customstrap"),
			"description" => __("gLightbox", "customstrap"),
            "section" => "singleposts", 
            'type'     => 'checkbox',
			)
    ));
 
 	//SHARING BUTTONS
	$wp_customize->add_setting("enable_sharing_buttons", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "enable_sharing_buttons",
        array(
            "label" => __("Enable Sharing Buttons", "customstrap"),
			"description" => __("Pure HTML only, zero bloat", "customstrap"),
            "section" => "singleposts", 
            'type'     => 'checkbox',
			)
    ));
	//end single posts ////////////////////////////////////

	/*  .php
	// ADD A SECTION FOR ARCHIVES ///////////////////////////////
	$wp_customize->add_section("archives", array(
        "title" => __("Archive Templates", "customstrap"),
        "priority" => 160,
    ));
	
	//FIELDS
	
	//ARCHIVES_TEMPLATE
	$wp_customize->add_setting("archives_template", array(
        "default" => "",
        "transport" => "refresh",
    ));
	$wp_customize->add_control(new WP_Customize_Control(
        $wp_customize,
        "archives_template",
        array(
            "label" => __("Template", "customstrap"),
            "section" => "archives",
            "settings" => "archives_template",
            'type'     => 'select',
			'choices'  => array(
				''  => 'Standard Blog: List With Sidebar',
				'v2' => 'v2 : Horizontal split with Featured Image',
				'v3' => 'v3 : Simple 3 Columns Grid ',
				'v4' => 'v4 : Masonry Grid',
				 				)
			)
    ));
	
	*/
	
}
 


// ADD CUSTOM JS & CSS TO CUSTOMIZER //////////////////////////////////////////////////////////////////////////////////////////////////////////
function customstrap_customize_enqueue() {
	wp_enqueue_script( 'custom-customize', get_stylesheet_directory_uri() . '/functions/customizer-assets/theme-customizer.js', array( 'jquery', 'customize-controls' ), false, true );
	wp_enqueue_style( 'custom-customize', get_stylesheet_directory_uri() . '/functions/customizer-assets/theme-customizer.css'  );
	
}
add_action( 'customize_controls_enqueue_scripts', 'customstrap_customize_enqueue' );


//ADD BODY CLASSES  //////////////////////////////////////////////////////////////////////////////////////////////////////////
add_filter( 'body_class', 'customstrap_config_body_classes' );
function customstrap_config_body_classes( $classes ) {
	$classes[]="customstrap_header_navbar_position_".get_theme_mod('customstrap_header_navbar_position');
	return $classes;
}

//REMOVE BODY MARGIN-TOP GIVEN BY WORDPRESS ADMIN BAR //////////////////////////////////////////////////////////////////////////////////////////////////////////
add_action('get_header', 'customstrap_filter_head');
function customstrap_filter_head() {
	if (get_theme_mod('customstrap_header_navbar_position')=="fixed-top") remove_action('wp_head', '_admin_bar_bump_cb');
}
