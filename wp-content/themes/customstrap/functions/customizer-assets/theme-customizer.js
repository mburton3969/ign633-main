(function($) {
	

		

	//FUNCTION TO LOOP ALL COLOR WIDGETS AND SHOW CURRENT COLOR grabbing the exposed css variable from page
	function cs_get_page_colors(){
		
		$(".customize-control-color").each(function(index, el) { //foreach color widget
			if (!$(el).find(".customize-control-description").text().includes("$")) return; //skip element if description does not contain a dollar

			color_name = $(el).find(".customize-control-description").text().replace("(", "").replace(")", "").replace("$", "--");
			var color_value = getComputedStyle(document.querySelector("#customize-preview iframe").contentWindow.document.documentElement).getPropertyValue(color_name);

			//console.log(color_name+color_value);

			if (color_value) $(el).css("border-left", "5px solid " + color_value).css("padding-left", "20px");
		}); //end each
		
	}
	
	
	function cs_recompile_css_bundle(){
		
		//SAVE PREVIEW IFRAME SRC
		preview_iframe_src=$("#customize-preview iframe").attr("src");
		if (preview_iframe_src===undefined) preview_iframe_src=$("#customize-preview iframe").attr("data-src");
		//alert(preview_iframe_src); //for debug
		
		//SHOW WINDOW	
		$("#cs-compiling-window").fadeIn();
		$('#cs-loader').show();
		
		//PREPARE URL TO CALL
		var current_url=window.location.href;
		var wpadmin_url = current_url.substring(0, current_url.indexOf('wp-admin/'))+'wp-admin/';
		var recompiling_url=wpadmin_url+"?cs_compile_scss";
		
		$("#cs-recompiling-target").html("Working...");
		//alert("recompiling_url: "+recompiling_url); //FOR DEBUG
		
		//AJAX CALL
		$("#cs-recompiling-target").load(recompiling_url, function() { //when got results,
			console.log("ajax loaded");
			$('#cs-loader').hide();
			//reload preview iframe
			$("#customize-preview iframe").attr("src",preview_iframe_src);
			//upon preview iframe loaded, fetch colors
			$("#customize-preview iframe").on("load",function(){ cs_get_page_colors(); });
		}); //end on loaded
		
		//RESET FLAG
		scss_recompile_is_necessary=false;
			
	} //END FUNCTION
	
	
	
	// WHEN COLORS SECTION IS OPENED
	$("body").on("click", "#accordion-section-colors", function() {
		cs_get_page_colors();
	});
	
	//USER CLICKS GENERATE PALETTE
	$("body").on("click", ".generate-palette", function() {
		var jqxhr = $.getJSON("https://palett.es/API/v1/palette/from/84172b", function(a) {
			console.log(a.results);

		}); //end loaded json ok

		jqxhr.fail(function() {
			alert("Network error. Try later.");
		});


	}); //END ONCLICK

	//WHEN A FONT COMBINATION IS CHOSEN
	$("body").on("change", "#customize-control-customstrap_font_combinations select", function() {
		var value = jQuery(this).val(); //Cabin and Old Standard TT
		var arr = value.split(' and ');
		var font_headings = arr[0];
		var font_body = arr[1];

		if (value === '') {
			font_headings = "";
			font_body = "";
		}

		$('select[data-customize-setting-link="customstrap_headings_font"] option:contains(' + font_headings + '):first').attr('selected', 'selected').change();

		$('select[data-customize-setting-link="customstrap_body_font"] option:contains(' + font_body + '):first').attr('selected', 'selected').change();

		//reset combination select
		//$('#customize-control-customstrap_font_combinations select option:first').attr('selected','selected');

	});

	////////////////////////////////////////// DOCUMENT READY //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	$(document).ready(function() {
		
		//SET DEFAULT
		scss_recompile_is_necessary=false;
				
		//ADD LOADING MESSAGE TO HTML BODY
		var the_loader='<div class="cs-chase">  <div class="cs-chase-dot"></div>  <div class="cs-chase-dot"></div>  <div class="cs-chase-dot"></div>  <div class="cs-chase-dot"></div>  <div class="cs-chase-dot"></div>  <div class="cs-chase-dot"></div></div>';
		var html="<div id='cs-compiling-window' hidden> <span class='cs-closex'>Close X</span> <h1>Rebuilding CSS bundle</h1> <div id='cs-loader'>"+the_loader+"</div> <div id='cs-recompiling-target'></div></div>";
		$("body").append(html);
		
		//ADD COLORS HEADING
		$("#customize-control-SCSSvar_link-hover-color").append("<h2 style='margin-bottom:0'>Bootstrap Colors</h2>");
		
		//LISTEN TO CUSTOMIZER CHANGES
		wp.customize.bind( 'change', function ( setting ) {
			if (setting.id.includes("SCSSvar") || setting.id.includes("font")) scss_recompile_is_necessary=true;
		});
		
		//UPON PUBLISHING CUSTOMIZER CHANGES
		wp.customize.bind('saved', function( /* data */ ) {
			if (scss_recompile_is_necessary)  cs_recompile_css_bundle();

		});
		
		//CLICK TO CLOSE COMPILING WINDOW
		$("body").on("click",".cs-close-compiling-window,.cs-closex",function(){
			$("#cs-compiling-window").fadeOut();
		});

		//ADD COLOR PALETTE GENERATOR
		//var html = "<a href='#' class='generate-palette'>Generate palette from this </a>";
		//$("#customize-control-SCSSvar_primary").prepend(html);
		
		//HELPER FOR TOPBAR DEFAULT
		$("body").on("click","#customize-control-enable_topbar",function(){
			if (!$("#_customize-input-enable_topbar").prop("checked")) return;
			var html_default='<a class="text-reset" href="tel:+1234567890"><i class="fa fa-phone mr-1"></i>  Call us now<span class="d-none d-md-inline">: 1234567890 </span> </a>	<span class="mx-1">   </span>		<a class="text-reset"  href="https://wa.me/1234567890"><i class="fa fa-whatsapp mr-1"></i>  WhatsApp<span class="d-none d-md-inline">: 1234567890 </span> </a>	<span class="mx-1">   </span>		<a class="text-reset" href="mailto:info@yoursite.com"><i class="fa fa-envelope mr-1"></i> Email<span class="d-none d-md-inline">:  info@yoursite.com</span></a>	<span class="mx-1">   </span>		<a class="text-reset" href="https://www.google.com/maps/place/Tour+Eiffel+-+Parc+du+Champ-de-Mars,+75007+Parigi,+Francia/@48.8559324,2.2940058,16z/data=!3m1!4b1!4m5!3m4!1s0x47e6701fecd7f1bb:0xda0b3d0ab838114d!8m2!3d48.8558986!4d2.2980875"><i class="fa fa-map-marker mr-1"></i> Map<span class="d-none d-md-inline">:  Address etc etc</span></a>';
			if ($("#_customize-input-topbar_content").val()=="") $("#_customize-input-topbar_content").val(html_default).change();
		}); 

	}); //end document ready




	/*

	function customstrap_make_customizations_to_customizer(){

	//$("#sub-accordion-section-colors").append("HEELLLOO");

	$('iframe').on('load', function(){
	customstrap_highlight_menu();
	});

	}

	function customstrap_highlight_menu() {

	if($("iframe").contents().find("body").hasClass("archive")) {
	jQuery("li#accordion-section-archives h3").css("background","#ffcc99");
	}

	if($("iframe").contents().find("body").hasClass("single-post")) {
	jQuery("li#accordion-section-singleposts h3").css("background","#ffcc99");
	}




	}

	setTimeout(function(){
	customstrap_make_customizations_to_customizer();

	}, 1000);


	*/
 
 
})(jQuery);