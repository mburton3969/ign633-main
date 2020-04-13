<?php

// EXIT IF ACCESSED DIRECTLY.
defined( 'ABSPATH' ) || exit;

///////////////////////// DEMO DUMMY SHORTCODE //////////////////////
add_shortcode( 'lc_dummyshortcode', 'lc_demoshortcode_func' );
function lc_demoshortcode_func(){
	return '
	<div style="width:100%;padding:20px;background:#eee;color:#333;text-align:center">
		<h2>I am a dummy shortcode  </h2>
	</div>
	';
}

///////////////////////////// 'POSTLIST' SHORTCODE TO GET POSTS - a simple wrap for the get_posts function /////////////
function lc_get_posts_func( $atts ){
	//EXTRACT VALUES FROM SHORTCODE CALL
	$get_posts_shortcode_atts=shortcode_atts( array(
			///INPUT
			'posts_per_page'   => 10,
			'offset'           => 0,
			'category'         => '',
			'category_name'    => '',
			'orderby'          => 'date',
			'order'            => 'DESC',
			'include'          => '',
			'exclude'          => '',
			'meta_key'         => '',
			'meta_value'       => '',
			'post_type'        => 'post',
			'post_mime_type'   => '',
			'post_parent'      => '',
			'author'	   		 => '',
			'post_status'      => 'publish',
			'suppress_filters' => true,
			'tax_query' => '', //custom: taxonomy=term_id
			///OUTPUT ////////////
			'output_view' => 'lc_get_posts_default_view',
			'output_wrapper_class' => '',
			'output_number_of_columns' => 3,
			'output_article_class' => '',
			'output_heading_tag' => 'h2',
			'output_hide_elements'  => '',
			'output_excerpt_length' =>45,
			'output_excerpt_text' => '&hellip;',
			'output_featured_image_before' =>'',
			'output_featured_image_format' =>'large',
			'output_featured_image_class' => 'attachment-thumbnail img-responsive alignleft'
     ), $atts );
	 
	extract($get_posts_shortcode_atts);
	
	//CUSTOM TAX QUERY CASE
	if ($tax_query!=""):
		//custom tax case
		$array_tax_query_par=explode("=",$tax_query);
		$get_posts_shortcode_atts= array_merge($get_posts_shortcode_atts,
											  array( 'tax_query' => array(
													array(
													  'taxonomy' => $array_tax_query_par[0], //taxonomy name
													  'field' => 'id',
													  'terms' => $array_tax_query_par[1], //term_id  
													  'include_children' => false
													)
													  )));
	endif; //end custom tax case
	
	//print_r($get_posts_shortcode_atts);return; //for debug
	
	//NOW GET THE POSTS
	$the_posts = get_posts( $get_posts_shortcode_atts );
	
	//CHECK IF NO RESULTS
	if(!$the_posts && current_user_can("administrator")) return "<h2>No results found</h2>"; 
	
	//LAUNCH OUTPUT CALLBACK FUNCTION
	return call_user_func(  $output_view ,$the_posts, $get_posts_shortcode_atts);
}
add_shortcode( 'lc_get_posts', 'lc_get_posts_func' );

 

//TEMPLATING: PLAIN DEFAULT ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function lc_get_posts_default_view($the_posts,$get_posts_shortcode_atts) {
	
	extract($get_posts_shortcode_atts);
	$out='';// INIT
	$output_hide_elements=strtolower($output_hide_elements);
	
	//default is 3 rows
	if($output_number_of_columns==1) $column_classes='col-12'; 
	if($output_number_of_columns==2) $column_classes='col-md-6 col-lg-6';
	if($output_number_of_columns==3) $column_classes='col-md-6 col-lg-4';
	if($output_number_of_columns==4) $column_classes='col-md-6 col-lg-3';
	
	
	foreach ( $the_posts as $the_post ):    //lc_get_posts_default_view($the_post);
		//print_r($the_post);
		$out.="<div class='".$column_classes." mb-3 mb-md-4'>";
		$out.='<article role="article" id="post-'.$the_post->ID.'" class="'.$output_article_class.'">';
		$out.='<header>';
		  
		if ($output_featured_image_before=="1" && strpos( $output_hide_elements,'featuredimage')  === false   ) 
			$out.='<a href="'.get_the_permalink($the_post).'">'.get_the_post_thumbnail($the_post,$output_featured_image_format,array( 'class'	=> $output_featured_image_class )).'</a>';
			   
		if (strpos( $output_hide_elements,'title')  === false   )
			$out.='<'. $output_heading_tag.'> <a href="'.get_the_permalink($the_post).'">'.get_the_title($the_post).'</a>	</'.$output_heading_tag.'>'; 
	   
		if (strpos( $output_hide_elements,'author')  === false  OR strpos( $output_hide_elements,'datetime')  === false  ): 
			$out.='<em>';
			
			if (strpos( $output_hide_elements,'author')  === false   ) $out.=' <span class="text-muted author">'.translate("by", "livecanvas" )." ". get_the_author_meta('user_nicename',$the_post->post_author).',</span>';
				  
			if (strpos( $output_hide_elements,'date')  === false ) $out.=' <time class="text-muted">'.get_the_date('',$the_post).'</time>';
			
			$out.="</em>";
			endif;
			
		$out.='  </header>';
		
		if ($output_featured_image_before!="1" && strpos( $output_hide_elements,'featuredimage')  === false)
			$out.='<a href="'.get_the_permalink($the_post).'">'.get_the_post_thumbnail($the_post,$output_featured_image_format,array( 'class'	=> $output_featured_image_class )).'</a>';
		
		if (strpos( $output_hide_elements,'excerpt')  === false  && $output_excerpt_length !=0  )
			$out.="<p>".   apply_filters( 'NOOO_the_content',  wp_trim_words ( wp_strip_all_tags( ($the_post->post_content)), $output_excerpt_length, $output_excerpt_text ))."</p>"; 
		
		if (strpos( $output_hide_elements,'category')  === false  OR strpos( $output_hide_elements,'comments')  === false    ):
			$out.='  <footer class="text-muted">';
			if (strpos( $output_hide_elements,'category')  === false )
				$out.='<div class="category"><i class="fa fa-folder-open"></i>&nbsp;'.translate('Category', 'livecanvas').': '. get_the_category_list(', ','',$the_post->ID).'</div>';
			if (strpos( $output_hide_elements,'comments')  === false )
				$out.='<div class="comments"><i class="fa fa-comment"></i>&nbsp;'.translate('Comments', 'livecanvas').': '. get_comments(array('post_id' => ($the_post->ID),'count' => true )).'</div>';
			$out.='</footer>';
		endif;
		
		if (strpos( $output_hide_elements,'clearfix')  === false ) $out.='<div class="clearfix"></div>';
		
		$out.='</article>';
		$out.='</div>';
	
   endforeach;
	
   return  "<div class='row ".$output_wrapper_class."'> ".$out."</div>";
}



//TEMPLATING: CARDS ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function lc_get_posts_card_view($the_posts,$get_posts_shortcode_atts) {
	
	extract($get_posts_shortcode_atts);
	$out='';// INIT
	$output_hide_elements=strtolower($output_hide_elements);
	
	//default is 3 rows
	if($output_number_of_columns==1) $column_classes='col-12'; 
	if($output_number_of_columns==2) $column_classes='col-md-6 col-lg-6';
	if($output_number_of_columns==3) $column_classes='col-md-6 col-lg-4';
	if($output_number_of_columns==4) $column_classes='col-md-6 col-lg-3';
	
	foreach ( $the_posts as $the_post ):    
		//print_r($the_post);
		$out.="<div class='".$column_classes." mb-3 mb-md-4'>";
		$out.='<article role="article" id="post-'.$the_post->ID.'" class="card '.$output_article_class.'">';
		
		if ( strpos( $output_hide_elements,'featuredimage')  === false) $out.='<a href="'.get_the_permalink($the_post).'">'.
			'<img alt="" class="'.$output_featured_image_class.'" src="'.get_the_post_thumbnail_url($the_post,$output_featured_image_format).'" '.
			'</a>';
		
		$out.="<div class='card-body'>";
		$out.='<header>';
		
		$out.='<'. $output_heading_tag.'> <a href="'.get_the_permalink($the_post).'">'.get_the_title($the_post).'</a>	</'.$output_heading_tag.'>'; 
	   
		if (strpos( $output_hide_elements,'author')  === false  OR strpos( $output_hide_elements,'datetime')  === false  ): 
			$out.='<em>';
			
			if (strpos( $output_hide_elements,'author')  === false   ) $out.=' <span class="text-muted author">'.translate("by", "livecanvas" )." ". get_the_author_meta('user_nicename',$the_post->post_author).',</span>';
				  
			if (strpos( $output_hide_elements,'date')  === false ) $out.=' <time class="text-muted">'.get_the_date('',$the_post).'</time>';
			
			$out.="</em>";
			endif;
			
		$out.='  </header>';
	 
		if (strpos( $output_hide_elements,'excerpt')  === false  && $output_excerpt_length !=0  )
			$out.="<p>".   apply_filters( 'NOOO_the_content',  wp_trim_words ( wp_strip_all_tags( ($the_post->post_content)), $output_excerpt_length, $output_excerpt_text ))."</p>"; 
		$out.='  </div>'; //close card body
		
 
		if (strpos( $output_hide_elements,'category')  === false  OR strpos( $output_hide_elements,'comments')  === false    ):
			$out.='<div class="card-footer">  <footer class="text-muted">';
			if (strpos( $output_hide_elements,'category')  === false )
				$out.='<div class="category"><i class="fa fa-folder-open"></i>&nbsp;'.translate('Category', 'livecanvas').': '. get_the_category_list(', ','',$the_post->ID).'</div>';
			if (strpos( $output_hide_elements,'comments')  === false )
				$out.='<div class="comments"><i class="fa fa-comment"></i>&nbsp;'.translate('Comments', 'livecanvas').': '. get_comments(array('post_id' => ($the_post->ID),'count' => true )).'</div>';
			$out.='</footer></div>';
		endif;
 
 
		$out.='</article>';
		$out.='</div>';
	
   endforeach;
	
   return  "<div class='row ".$output_wrapper_class."'> ".$out."</div>";
}



/*
    
//TEMPLATING: CAROUSEL ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function lc_get_posts_carousel_view($the_posts,$get_posts_shortcode_atts) {
	global $post;
	$post_backup=$post;
	$lc_carousel_index=rand(0,1000);
	extract($get_posts_shortcode_atts); 
	$output_hide_elements=strtolower($output_hide_elements);
	
	ob_start();
	?> 
	<section id="lc-carousel-index-<?php echo $lc_carousel_index; ?>" class="carousel slide" data-ride="carousel">
		
		<!-- Indicators -->
		<ol class="carousel-indicators">
			<?php
			$carousel_item_count=0;
			foreach ( $the_posts as $post ): ?>
				 <li data-target="#lc-carousel-index-<?php echo $lc_carousel_index; ?>" data-slide-to="<?php echo $carousel_item_count ?>" <?php if ($carousel_item_count==0): ?>class="active" <?php endif ?>></li>
				<?php $carousel_item_count++;
			endforeach ?>
		</ol>
		
		<!-- Wrapper for slides -->
		<div class="carousel-inner" role="listbox">
			<?php
			$carousel_item_count=0;
			foreach ( $the_posts as $post ):
				setup_postdata( $post );
				$carousel_item_count++;
				$image_url_array = wp_get_attachment_image_src( get_post_thumbnail_id(get_the_ID()), $output_featured_image_format);
				?>
				<div id="post_<?php the_ID()?>" class="item <?php if ($carousel_item_count==1) echo "active "; ?><?php echo $output_article_class ?>">
					<img src="<?php echo $image_url_array[0] ?>" alt="<?php echo esc_attr(get_the_title()); ?>" >																
					<div class="carousel-caption">
						
						<?php if (strpos( $output_hide_elements,'title')  === false   ): ?>
						<<?php echo $output_heading_tag ?>><a href="<?php the_permalink(); ?>"><?php the_title()?></a></<?php echo $output_heading_tag ?>>
						<?php endif ?>
						
						<?php if (strpos( $output_hide_elements,'author')  === false  OR strpos( $output_hide_elements,'datetime')  === false  ): ?>
						<h4> 
						  <em>
							<?php if (strpos( $output_hide_elements,'author')  === false   ): ?>
							<span class="text-muted author"><?php _e('By', 'bbe'); echo " "; the_author() ?>,</span>
							<?php endif ?>
							 <?php if (strpos( $output_hide_elements,'datetime')  === false   ): ?>
							<time  class="text-muted" datetime="<?php the_time('d-m-Y')?>"><?php the_time('jS F Y') ?></time>
							<?php endif ?>
						  </em>
						</h4>
						<?php if (strpos( $output_hide_elements,'excerpt')  === false  && $output_excerpt_length !=0 ): ?>
								<div class="excerpt"><?php the_excerpt() ?></div>
								<?php endif ?>
						<?php endif ?>
					
					</div> <!-- close carousel caption -->
				</div> <!-- close item -->
				<?php
			endforeach;
			?>
		</div>
		
		
		<!-- Controls -->
		<a class="left carousel-control" href="#lc-carousel-index-<?php echo $lc_carousel_index; ?>" role="button" data-slide="prev">
		  <span class="glyphicon glyphicon-chevron-left" aria-hidden="true"></span>
		  <span class="sr-only">Previous</span>
		</a>
		<a class="right carousel-control" href="#lc-carousel-index-<?php echo $lc_carousel_index; ?>" role="button" data-slide="next">
		  <span class="glyphicon glyphicon-chevron-right" aria-hidden="true"></span>
		  <span class="sr-only">Next</span>
		</a>
		
	</section>

	
	<?php
	$out =   ob_get_contents();
	ob_end_clean();
	wp_reset_postdata();
	$post=$post_backup;
	return $out;
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
*/

/*

////SHORTCODE LC NEWSMIXER

add_shortcode( 'lc_newsmixer', 'lc_newsmixer_func' );

function lc_newsmixer_func($atts) {
	
	$the_shortcode_atts=shortcode_atts( array(
			'cats'   => "", //category IDs,comma separated
			'cat_query_args' =>"",
			'heading_postfix'   => "",
			 
     ), $atts );
	 
	extract($the_shortcode_atts);
	
	if($cats==""){
		//If no 'cats' parameter is supplied, list all categories
		$categories = get_categories( $cat_query_args );
		$cats="";
		foreach($categories as $c) $cats .= $c->term_taxonomy_id.",";
	}
	 
	$array_cats=explode(',',$cats);
	 
	$html="";
	$attributes=$atts;
	foreach ($array_cats as $cat_id):
	
		$attributes['category']=$cat_id;
		$html.= ' <div class="page-header "> <h1>  '.get_cat_name($cat_id).' <span class="text-muted">  '.$heading_postfix.' </span></h1>   </div> ';
		$html.= lc_get_posts_func($attributes);
		  
		 
	endforeach;
	return "<div class='lc-newsmixer'>". $html."</div>";
}
		
		
		
//////////////////////////////////////////////


////SHORTCODE LC NEWSMIXER CUSTOM TAX

add_shortcode( 'lc_newsmixer_tax', 'lc_newsmixer_tax_func' );

function lc_newsmixer_tax_func($atts) {
	
	$the_shortcode_atts=shortcode_atts( array(
			'tax_name'   => "",
			'term_ids' =>"",
			'heading_postfix'   => "",
			 
     ), $atts );
	 
	extract($the_shortcode_atts);
	
	if($term_ids==""){
		//If no 'term_ids' parameter is supplied, list all terms in tax
		$terms = get_terms( array( 'taxonomy' => $tax_name,  'hide_empty' => false,) );
		$term_ids="";
		foreach($terms as $t) $term_ids .= $t->term_taxonomy_id.",";
	 
	}
	 
	$array_terms=explode(',',$term_ids);
	 
	$html="";
	$attributes=$atts;
	foreach ($array_terms as $term_id):
		 //$html.=$tax_name.'='.$term_id;
		$attributes['tax_query']=$tax_name.'='.$term_id;
		$TermObject = get_term_by( 'id', $term_id ,$tax_name);
		if(!$TermObject) $html.= '<h3>Wrong '.$term_id .' parameter </h3> ';
			else {
			$html.= ' <div class="page-header "> <h1>    '.$TermObject->name.' <span class="text-muted">  '.$heading_postfix.' </span></h1>   </div> ';
			$html.= lc_get_posts_func($attributes);
		}	  
		 
	endforeach;
	return "<div class='lc-newsmixer-tax'>". $html."</div>";
}
		
*/		


/*
///////////////////////// CUSTOM SIDEBARS SHORTCODE /////////////////////
function lc_sidebar_func( $atts ){
 
    $attributes = shortcode_atts( array(
        'id' => 'lc-widgetarea-1',
    ), $atts );
	
	extract($attributes); 
	ob_start();
	
	dynamic_sidebar($id);
	 
	$sidebar_html = ob_get_contents();
	if ($sidebar_html=="") {
		$sidebar_html="<section class='text-center' style='width:100%;padding:20px;background:#efefef';text-align:center>
							<h2>Populate this Widget Area!</h2>
							<blockquote>
								<p>Use the <a class='lc-open-cutomizer-toeditwidgetarea' onFocus=\"javascript:jQuery(this).attr('href',jQuery('#wp-admin-bar-customize a').attr('href'));\" href='#'>theme customizer</a>  
									and go to <code>Widgets</code> >    <code>  ".$id." </code>
								</p>				
							<blockquote>
						</section>";
	}
	ob_end_clean();
	
    return $sidebar_html;
}
add_shortcode( 'lc_widgetsarea', 'lc_sidebar_func' );



*/
		
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/////// 'CATLIST' SHORTCODE TO LIST ELEMENTS FROM CATEGORY - A simple wrap for wp_list_categories /////////////
function lc_get_cats_func( $atts ){
	//EXTRACT VALUES FROM SHORTCODE CALL
	$get_cats_shortcode_atts=shortcode_atts( array(
			'child_of'  => '0',
			'current_category' => '0',
			'depth'  => '0',
			'echo'  => false, //so we return the output in a string instead of printing it
			 // INPUT-RELATED PARAMETERS
			'exclude' => false,
			'exclude_tree' => false, //ADD feed,feed_image,feed_type ?
			'hide_empty' => '1',
			'hide_title_if_empty' => false,
			'hierarchical' => true,
			'order' => 'ASC',
			'orderby' => 'ID',
			'separator' => '<br>',
			'show_count' => 0,
			'show_option_all' =>  false,
			'show_option_none' => 'No categories',
			'style'   => 'list',
			'taxonomy'   => 'category',
			'title_li' => 'Categories',
			'use_desc_for_title'     => 1,
			// OUTPUT-RELATED PARAMETERS
			'output_view' => 'lc_get_cats_default_view',
     ), $atts );
	
	extract($get_cats_shortcode_atts);
	
	//GET THE THING
 
	$the_cats = wp_list_categories( $get_cats_shortcode_atts );
	 
	//LAUNCH OUTPUT CALLBACK FUNCTION
	return call_user_func(  $output_view ,$the_cats, $get_cats_shortcode_atts);
} 

add_shortcode( 'lc_get_cats', 'lc_get_cats_func' );

//////////////// CATLIST: OUTPUT CALLBACKS /////////////////////////
////////////////////////////////////////////////////////////////////

/////////////////CATSLIST OUTPUT DEFAULT CALLBACK //////////////////////
function lc_get_cats_default_view($the_cats,$get_cats_shortcode_atts){
	extract($get_cats_shortcode_atts);
	return "<ul>". $the_cats. "</ul>";
}
/////////////////ADD TO BLOG //////////////////////
function lc_get_cats_custom_view($the_cats,$get_cats_shortcode_atts){
	extract($get_cats_shortcode_atts);
	//PROCESS THINGS ....
	return "<ul>". $the_cats. "</ul>";
}


/////////////////////////////   GET GT BLOCKS   /////////////
add_shortcode( 'lc_get_gt_block', function  ( $atts ){
	//EXTRACT VALUES FROM SHORTCODE CALL
	 
	//NOW GET THE POSTS
	$the_post = get_post( $atts['id'] );
	return $the_post->post_content;
		
});
