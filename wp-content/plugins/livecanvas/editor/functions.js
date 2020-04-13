////////// MAIN BEHAVIORS //////////////////////////
function loadURLintoEditor(url) {
	fetch(url)
		.then(function(response) {
			return response.text();
		}).then(function(page_html) {
			doc = new DOMParser().parseFromString(page_html, 'text/html');
			original_document_html = getPageHTML();
			previewiframe.srcdoc = doc.querySelector("html").outerHTML;
			previewiframe.onload = tryToEnrichPreview();
			saveHistoryStep();
		}).catch(function(err) {
			swal("Error " + err + "fetching URL " + url);
		});
}

function tryToEnrichPreview() {
	console.log("tryToEnrichPreview");
	previewFrameBody = $("#previewiframe").contents().find("body");
	//check iframe is really  loaded and available
	if (previewFrameBody.html() === "" || previewFrameBody.html() === undefined) {
		//not ready yet
		setTimeout(function() {
			console.log("Schedule back");
			tryToEnrichPreview();
		}, 1000);
		return;
	} //end if
	//iframe seems to be ready and accessible
	enrichPreview();
}

function updatePreview() {
	previewiframe.srcdoc = doc.querySelector("html").outerHTML;
	previewiframe.onload = enrichPreview();
	saveHistoryStep();
}

var enrichPreview = debounce(function() {
	console.log("debounced: enrichPreview");
	previewFrame = $("#previewiframe");
	previewFrameBody = previewFrame.contents().find("body"); //dont add main

	//ADD the iframe CSS stylesheet
	previewFrame.contents().find("head").append($("<link/>", {
		rel: "stylesheet",
		href: lc_editor_root_url + "preview-iframe.css",
		type: "text/css"
	})); ///ADD STYLE TO IFRAME HEADER
	// previewFrame.contents().find("head").append($("<link/>",  { rel: "stylesheet", href: "https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css", type: "text/css" })); ///ADD ICON FONT TO IFRAME HEADER

	//ADD contextual menus' HTML INTERFACE ELEMENTS TO IFRAME BODY
	previewFrameBody.append($("#add-to-preview-iframe-content").html());

	//ADD TRIGGER FOR ADDING SECTIONS
	//previewFrame.contents().find("main#lc-main").after($("#lc-add-section-to-page").html());

	//ADD MINIPREVIEW
	previewFrame.contents().find("main#lc-main").after('<div id="lc-minipreview" style="display: none"><div class="lc-minipreview-content"></div></div>'); //#lc-add-section-to-page").html());

	//if there's only an empty section (user has just reset page) hide the section creating button
	//if (  getPageHTML("main#lc-main")=="<section></section>") {
	//    previewFrame.contents().find("#lc-add-new-container-section-wrap").hide();
	//}
	
	//GET BOOTSTRAP COLORS and paint COLOR WIDGETS 
	$(".custom-color-widget").each(function(index, the_widget) { //foreach color widget
		$(the_widget).find("span").each(function(index, span_element) {  //foreach  color element in the widget
			color_name = $(span_element).attr("title").trim().toLowerCase();  //console.log(color_name);
            var color_value=getComputedStyle(previewiframe.contentWindow.document.documentElement).getPropertyValue('--'+color_name);
            $(span_element).css("background",color_value);
		}); //end each 
	}); //end each widget
	
	//CHECK IF IS LC_BLOCK OR LC_SECTION CPT AND HIDE ADDD SECT BUTTON
	if(( previewFrame.contents().find("body").hasClass('lc_section-template'))  || (previewFrame.contents().find("body").hasClass('lc_block-template')) ) {
		$('#primary-tools').hide();
		$('.open-main-html-editor').click();
	}
	
	//INITIALIZE WYSIWYG TOOLBAR BUTTONS 
	init_ww_toolbar_buttons();
	previewiframe.contentDocument.execCommand("DefaultParagraphSeparator", false, "p"); //INIT

	initialize_live_text_editing();
	initialize_contextual_menus();
	initialize_contextual_menu_actions();
	initialize_content_building();

	//PREVENT CLICKING AWAY
	previewFrame.contents().on("click", "a", function(e) {
		e.preventDefault();
		console.log("You cannot navigate the site while editing.");
	});

	//HIDE PRELOADER 
	$("#loader").fadeOut(1000);

	//render shortcodes
	render_shortcodes_in("html");

}, 400);

function updatePreviewSectorial(selector) {
	previewiframe.contentWindow.document.body.querySelector(selector).outerHTML = doc.querySelector(selector).outerHTML;
	enrichPreviewSectorial(selector);
	saveHistoryStep();
}

var enrichPreviewSectorial = debounce(function(selector) {
	console.log("Heavy task: enrichPreviewSectorial " + selector);
	add_helper_attributes_in_preview();
	render_shortcodes_in(selector);
}, 400);

 // HISTORY ///////////
 var saveHistoryStep = debounce(function() {
	var today = new Date();
	$("#history-steps").append("<li> "+today.toLocaleTimeString()+ " "+today.toLocaleDateString()+ "<template>"+getPageHTML("main")+"</template></li>");
	//localStorage.setItem("last_step_html", getPageHTML());    //auto save on localstorage, eventually
}, 2000);

//QUICK DOCUMENT EDITING SUPPORT FUNCTIONS      ///////////////////////////
function getPageHTML(selector) {
	if (selector === undefined) selector = "html";
	return (doc.querySelector(selector).innerHTML);
}

function setPageHTML(selector, newValue) {
	doc.querySelector(selector).innerHTML = newValue;
}

function setPageHTMLOuter(selector, newValue) {
	doc.querySelector(selector).outerHTML = newValue;
}

function getAttributeValue(selector, attribute_name) {
	if (selector === undefined || selector === '') {
		/* console.log("getAttributeValue is called with an undefined selector");*/
		return "";
	}
	return (doc.querySelector(selector).getAttribute(attribute_name));
}

function setAttributeValue(selector, attribute_name, newValue) {
	doc.querySelector(selector).setAttribute(attribute_name, newValue);
}

function setEditorPreference(option_name, option_value) {
	editorPrefsObj[option_name] = option_value;
	editorPreferencesString = JSON.stringify(editorPrefsObj);
	localStorage.setItem("lc_editor_prefs_json", editorPreferencesString);
}


/* ******************* SIDE PANEL  ******************* */
function revealSidePanel(item_type, selector) {

	//hide ux since well be moving the thing
	previewFrame.contents().find(".lc-contextual-menu").fadeOut(500);
	previewFrame.contents().find(".lc-highlight-mainpart").removeClass("lc-highlight-mainpart");
	previewFrame.contents().find(".lc-highlight-container").removeClass("lc-highlight-container");
	previewFrame.contents().find(".lc-highlight-column").removeClass("lc-highlight-column");
	previewFrame.contents().find(".lc-highlight-row").removeClass("lc-highlight-row");
	previewFrame.contents().find(".lc-highlight-block").removeClass("lc-highlight-block");


	$(".nanotoolbar").hide(); //hide other nanobars

	//prepare the panel, hide all subpanels
	$("#sidepanel > section").hide(); // hide other panels

	//set a data attribute to identify the element we're editing
	var sectionSelector = "#sidepanel > section[item-type=" + item_type + "]";
	$(sectionSelector).attr("selector", selector);

	initializeSidePanelSection(sectionSelector); //inits main fields
	$(sectionSelector).show(); //triggers init of other fields

	//move the preview
	$("#previewiframe-wrap").addClass("push-aside-preview");

	$("#sidepanel form").scrollTop(0); //scroll panel to top
	//animate and show the panel
	$("#sidepanel").hide().fadeIn(300); //addClass("slideInLeft")

}

function initializeSidePanelSection(sectionSelector) {

	theSection = $(sectionSelector);
	console.log("Init panel fields for " + theSection.attr("item-type"));
	var selector = theSection.attr("selector");

	//INPUTS: initialize value for text fields /////////
	//foreach input field
	theSection.find("*[attribute-name]").each(function(index, element) {
		var attribute_name = $(element).attr('attribute-name');
		if (attribute_name === 'html') $(element).val(getPageHTML(selector, attribute_name));
		else $(element).val(getAttributeValue(selector, attribute_name));
	}); //end each
    
    //COLOR WIDGETS: highlight active color
	theSection.find(".custom-color-widget").each(function(index, the_widget) { //foreach color widget
		$(the_widget).find("span.active").removeClass("active");
		//foreach  color element in the widget
		var color_assigned=false;
		$(the_widget).find("span").each(function(index, span_element) {
			span_value = $(span_element).attr("value").trim(); //console.log(span_value);
			if (span_value !== "" && doc.querySelector(selector).classList.contains(span_value)) { $(span_element).addClass("active"); color_assigned=true; }
		}); //end each option
		if(!color_assigned) $(the_widget).find("span[value='']").addClass("active"); 
	}); //end each select
    
	///SELECTs: initialize value for select[target=classes]  
	theSection.find("select[target=classes]").each(function(index, select_element) { //foreach select in section
		//apply a default starter option
		$(select_element).find("option:first").prop('selected', true);
		//foreach option in select
		$(select_element).find("option").each(function(index, option_element) {
			option_value = $(option_element).val().trim(); //console.log(option_value);
			if (option_value !== "" && doc.querySelector(selector).classList.contains(option_value)) $(option_element).prop('selected', true);
		}); //end each option

	}); //end each select

	//FAKE SELECTS: close all of them
	theSection.find("ul.ul-to-selection.opened").removeClass("opened");

	//FAKE SELECT BACKGROUNDS: initialize value
	var bg_style = previewFrame.contents().find(selector).css("background"); //doc.querySelector(selector).getAttribute("style");
	theSection.find("ul#backgrounds li.first").attr("style", "background:" + bg_style);

	//CUSTOM INIT FOR SHAPE DIVIDERS: initialize value 
	var bottom_shape_divider_element = doc.querySelector(selector + ' .lc-shape-divider-bottom');
	if (bottom_shape_divider_element) shape_html = bottom_shape_divider_element.outerHTML;
	else shape_html = "";
	theSection.find("ul#shape_dividers li.first").html(shape_html);

	//CUSTOM INIT FOR  IMAGES  
	if (theSection.attr("item-type") == "image") {
		//Update Image Preview
		theSection.find(".preview-image").css("background-image", "url(" + theSection.find("*[attribute-name=src]").val() + ")");
		//check if imgix widget is appropriate
		if (theSection.find("*[attribute-name=src]").val().includes("unsplash.com")) theSection.find(".imgix-fx").show();
		else theSection.find(".imgix-fx").hide();
	}

	//CUSTOM INIT FOR BACKGROUNDS  
	if (theSection.attr("item-type") == "background") {
		var bg_url = "";
		if (previewFrame.contents().find(selector).css("background-image").match(/"([^']+)"/) != null)
			bg_url = previewFrame.contents().find(selector).css("background-image").match(/"([^']+)"/)[1];
		else bg_url = "#";
		//update image preview    
		theSection.find(".preview-image").css("background-image", "url(" + bg_url + ")");
		//update bg url input field
		theSection.find("input[name=background-image]").val(bg_url).attr("data-old-url", bg_url);
		//check if imgix widget is appropriate
		if (bg_url.includes("unsplash.com")) theSection.find(".imgix-fx").show();
		else theSection.find(".imgix-fx").hide();
	}

	//CUSTOM INIT FOR VIDEO BACKGROUND
	if (theSection.attr("item-type") == "video-bg") {
		var video_url = getAttributeValue(selector + " video source", "src");
		theSection.find("input[name='video_mp4_url']").val(video_url);
	}

	//CUSTOM INIT FOR GOOGLE MAP EMBED
	if (theSection.attr("item-type") == "gmap-embed") {
		var iframe_url = getAttributeValue(selector + " iframe", "src");
		var params = lc_parseParams(iframe_url);
		theSection.find("input[name='address']").val(params['q']);
		theSection.find("input[name='zoom']").val(params['z']);
	}

	//CUSTOM INIT FOR SHORTCODES PANEL
	if (theSection.attr("item-type") == "shortcode") {
		//populate shortcode field
		var theShortcode = doc.querySelector(selector).innerHTML;
		theSection.find("*[name=shortcode_text]").val(theShortcode);
	}

	//CUSTOM INIT FOR POSTS LOOP
	if (theSection.attr("item-type") == "posts-loop") {
		var theShortcode = doc.querySelector(selector).innerHTML;
		//loop all input items to initialize input fields
		theSection.find("*[name]").each(function(index, el) {
			fieldName = $(el).attr("name");
			fieldValue = lc_get_parameter_value_from_shortcode(fieldName, theShortcode);
			//console.log("set "+fieldName+" to "+fieldValue);
			if (fieldValue !== "") theSection.find("[name=" + fieldName + "]").val(fieldValue);
		}); //end each    
	}

	//CUSTOM INIT FOR ANIMATIONS
	if (theSection.find("select[name=aos_animation_type]").length != 0) {
		var animation_type = getAttributeValue(selector, "data-aos");
		$('#sidepanel select[name=aos_animation_type] option[value=""]').prop('selected', true); //default
		if (animation_type != "" && $('#sidepanel select[name=aos_animation_type] option[value=' + animation_type + ']').length > 0)
			$('#sidepanel select[name=aos_animation_type] option[value=' + animation_type + ']').prop('selected', true);
	}



} //end function

/* ******************* SHORTCODES ************************* */
function render_shortcode(selector, shortcode) {
	$.post(
		lc_editor_saving_url, {
			'action': 'lc_process_shortcode',
			'shortcode': shortcode,
		},
		function(response) {
			//console.log('The server responded: ', response);
			previewFrame.contents().find(selector).html(response).removeClass("live-shortcode").addClass("lc-rendered-shortcode-wrap");
		}
	);
}

function render_shortcodes_in(selector) {
	doc.querySelector(selector).querySelectorAll(".live-shortcode").forEach((wrap) => {
		render_shortcode(CSSelector(wrap), wrap.innerHTML);
	});
}

/* ******************* PROCEDURES / FUNCTIONS ************************* */

///// SELECTOR GENERATOR /////////////////////////////////
function CSSelector(el) {
	var names = [];
	while (el.parentNode) {
		if (el.nodeName == "MAIN" && el.id == "lc-main") {
			names.unshift(el.nodeName + '#' + el.id);
			break;
		} else {
			if (el === el.ownerDocument.documentElement || el === el.ownerDocument.body) {
				names.unshift(el.tagName);
			} else {
				for (var c = 1, e = el; e.previousElementSibling; e = e.previousElementSibling, c++) {}
				names.unshift(el.tagName + ':nth-child(' + c + ')');
			}
			el = el.parentNode;
		}
	}
	return names.join(' > ');
}

/////////WYSIWYG HARD SANITIZER ////////////////
function sanitize_editable_rich(input) {
	output = input.replace(/<\/?span[^>]*>/g, "");
	output = output.replace(/<\/?div[^>]*>/g, "");
	//output= output.replace(/&nbsp;/g,"");
	output = output.replace(/<b>/g, "<strong>");
	output = output.replace(/<\/b>/g, "</strong>");
	output = output.replace(/<i>/g, "<em>");
	output = output.replace(/<\/i>/g, "</em>");

	output = output.replace(/<\/em><em>/g, "");
	output = output.replace(/<\/strong><strong>/g, "");

	output = output.replace(/<em> <\/em>/g, " ");
	output = output.replace(/<strong> <\/strong>/g, " ");

	return output;
}

///FILTER BLOCKS BEFORE PLACING THEM INSIDE THE PAGE
function lc_filter_components(unfiltered_html_components) {
	var filtered_html = unfiltered_html_components.replace(/@randomid@/g, lc_randomString()); ///substitute random IDs for components
	filtered_html = filtered_html.replace(/@zero_to_ten@/g, Math.floor((Math.random() * 10) + 1)); ///substitute random vars for demo images
	return filtered_html;
}


/* ******************* UTILITY FUNCTIONS ************************* */

//////DEBOUNCE UTILITY  
function debounce(func, wait, immediate) {
	var timeout;
	return function() {
		var context = this,
			args = arguments;
		var later = function() {
			timeout = null;
			if (!immediate) func.apply(context, args);
		};
		var callNow = immediate && !timeout;
		clearTimeout(timeout);
		timeout = setTimeout(later, wait);
		if (callNow) func.apply(context, args);
	};
}


String.prototype.ucwords = function() {
	str = this.toLowerCase();
	return str.replace(/(^([a-zA-Z\p{M}]))|([ -][a-zA-Z\p{M}])/g,
		function($1) {
			return $1.toUpperCase();
		});
};


function lc_randomString() {
	length = 3;
	chars = 'abcdefghijklmnopqrstuvwxyz'; //ABCDEFGHIJKLMNOPQRSTUVWXYZ';
	var result = '';
	for (var i = length; i > 0; --i)
		result += chars[Math.round(Math.random() * (chars.length - 1))];
	return result;
}

function lc_parseParams(str) {

	str = str.split('?')[1]; //eliminate part before ?

	return str.split('&').reduce(function(params, param) {
		var paramSplit = param.split('=').map(function(value) {
			return decodeURIComponent(value.replace('+', ' '));
		});
		params[paramSplit[0]] = paramSplit[1];
		return params;
	}, {});
}


function lc_get_parameter_value_from_shortcode(paramName, theShortcode) {
	theShortcode = theShortcode.replace(/ =/g, '=').replace(/= /g, '=');
	var array1 = theShortcode.split(paramName + '="');
	var significant_part = array1[1];
	if (significant_part === undefined) return "";
	var array2 = significant_part.split('"');
	return array2[0];
}


function getScrollBarWidth() {
	//base case
	if (previewFrameBody.height() <= $(window).height()) return 0;

	var $outer = $('<div>').css({
			visibility: 'hidden',
			width: 100,
			overflow: 'scroll'
		}).appendTo('body'),
		widthWithScroll = $('<div>').css({
			width: '100%'
		}).appendTo($outer).outerWidth();
	$outer.remove();
	return 100 - widthWithScroll;
}

function download(filename, text) {
	var element = document.createElement('a');
	element.setAttribute('href', 'data:text/plain;charset=utf-8,' + encodeURIComponent(text));
	element.setAttribute('download', filename);

	element.style.display = 'none';
	document.body.appendChild(element);

	element.click();

	document.body.removeChild(element);
}

function usingChromeBrowser() {

	var isChromium = window.chrome;
	var winNav = window.navigator;
	var vendorName = winNav.vendor;
	var isOpera = typeof window.opr !== "undefined";
	var isIEedge = winNav.userAgent.indexOf("Edge") > -1;
	var isIOSChrome = winNav.userAgent.match("CriOS");

	if (isIOSChrome) {
		// is Google Chrome on IOS
		return true;
	} else if (
		isChromium !== null &&
		typeof isChromium !== "undefined" &&
		vendorName === "Google Inc." &&
		isOpera === false &&
		isIEedge === false
	) {
		return true;
	} else {
		return false;
	}

}

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////// INITIALIZE LIVE TEXT EDITING  ///////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function init_ww_toolbar_buttons() {

	$('#ww-toolbar a').mousedown(function() {

		//prevent subsequent blur trick
		$("body").addClass("prevent-blur-event-handling-of-editable-fields");

		var command = $(this).data('command');
		console.log("Apply command " + command + " to text");

		if (command == 'display-1' || command == 'display-2' || command == 'display-3' || command == 'display-4') {
			wrapper_tag = "h2";
			node = '<' + wrapper_tag + ' class="' + command + '">' + previewiframe.contentDocument.getSelection().toString() + '</' + wrapper_tag + '>';
			previewiframe.contentDocument.execCommand('insertHTML', false, node);
		}
		if (command == 'h1' || command == 'h2' || command == 'h3' || command == 'h4' || command == 'h5' || command == 'h6' || command == 'p') {
			previewiframe.contentDocument.execCommand('formatBlock', false, command);
		}
		if (command == 'kbd' || command == 'code') {
			node = '<' + command + '>' + previewiframe.contentDocument.getSelection().toString() + '</' + command + '>';
			previewiframe.contentDocument.execCommand('insertHTML', false, node);
		}
		if (command == 'blockquote') {
			node = '<' + command + ' class="' + command + '">' + previewiframe.contentDocument.getSelection().toString() + '</' + command + '>';
			previewiframe.contentDocument.execCommand('insertHTML', false, node);
		}

		if (command == 'forecolor' || command == 'backcolor') {
			previewiframe.contentDocument.execCommand($(this).data('command'), false, $(this).data('value'));
		}

		if (command == 'createlink' || command == 'insertimage') {
			url = prompt('Enter the link here: ', 'https:\/\/cdn.dopewp.com\/media\/architecture\/gal1.jpg  ');
			previewiframe.contentDocument.execCommand($(this).data('command'), false, url);
			add_helpers_to_preview();
		} else previewiframe.contentDocument.execCommand($(this).data('command'), false, null);


		setTimeout(function() {
			$("#previewiframe").contents().find(".lc-last-clicked-editable-element").focus(); //useful so blur event will be triggered if blurred
		}, 200);


	});

} ///end function


function initialize_live_text_editing() {

	//previewiframe.contentDocument.querySelector("h1").style.display = "none"; // WOULD HIDE ANY H1
	//console.log("Start initialize_live_editing function");


	//ON CLICK OF TEXT-EDITABLE ITEMS:
	previewFrameBody.on("click", "[editable=rich],[editable=inline]", function(e) {

		console.log("Clicked editable text");
		e.stopPropagation();
		$(this).attr("contenteditable", "true").focus().addClass("lc-last-clicked-editable-element");
		$(".nanotoolbar").hide();
		$("#ww-toolbar").show();
		$("#sidepanel .close-sidepanel").click(); //$("#sidepanel").hide();
		if ($(this).attr("editable") == "rich") {
			$("#ww-toolbar [data-command]").show();
		}
		if ($(this).attr("editable") == "inline") {
			$("#ww-toolbar [data-command]").hide();
			$("#ww-toolbar [data-suitable='inline']").show();
		}

	}); //end on click


	//ON BLUR OF EDITABLE ITEMS:
	previewFrameBody.on("blur", "[editable=rich],[editable=inline]", function() {

		if ($("body").hasClass("prevent-blur-event-handling-of-editable-fields")) {
			$("body").removeClass("prevent-blur-event-handling-of-editable-fields");
			console.log("Skipping blur event handling");
			return; //stop here execution
		}
		console.log("Blur event. Reapplying content changes on code");
		$(this).removeAttr("contenteditable").removeClass("lc-last-clicked-editable-element");

		$(this).find("*[style]").removeAttr("style"); //kill any inline styling
		$(this).find("*[lc-helper]").removeAttr("lc-helper"); //kill any inline styling
		//$(this).find(".lc-highlight-item").removeClass("lc-highlight-item");         //useless
		var newValue = $(this).html(); //get field content from preview  
		if ($(this).attr("editable") == "rich") newValue = sanitize_editable_rich(newValue); //kill shit like span when deleting

		var selector = CSSelector($(this)[0]); //generate selector
		//setPageHTML( selector,  newValue); //update element's contents in code
		if (selector === "") {
			console.log("Empty selector on blur");
			return;
		} //fix

		doc.querySelector(selector).innerHTML = newValue;
		//alert(newValue);
		// SECTORIAL PREVIEW UPDATE for peace of mind
		updatePreviewSectorial(selector); // or, more efficiently //$(this).html(newValue); //console.log("newval: "+newValue);
		$("#ww-toolbar").hide();

	}); //end on blur


	/* PASTE helper */
	previewFrameBody.on('paste', " *[editable]", function(e) {
		e.preventDefault(); //alert("paste intercept");
		var text = '';
		if (e.clipboardData || e.originalEvent.clipboardData) {
			text = (e.originalEvent || e).clipboardData.getData('text/plain');
		} else if (window.clipboardData) {
			text = window.clipboardData.getData('Text');
		}
		//alert(text);
		var tmp = document.createElement("DIV");
		tmp.innerHTML = text;
		//text=text.replace(/(<([^>]+)>)/ig,"");
		text = tmp.textContent || tmp.innerText;
		text = text.replace(/\n/g, ' ');

		//if (document.queryCommandSupported('insertText')) {
		previewiframe.contentDocument.execCommand('insertText', false, text);
		// } else {
		//   document.execCommand('paste', false, text);
		// }
	});


	//TAKE CARE OF INLINE-EDITABLE NEWLINE  / ENTER KEY  
	previewFrameBody.on("keydown", ' *[editable="inline"]', function(e) {
		if (e.keyCode === 13) {
			previewiframe.contentDocument.execCommand('insertHTML', false, '<br>');
			return false;
		}
	});

	//TAKE CARE OF RICH-EDITABLE when field gets empty
	previewFrameBody.on("keyup", '[editable="rich"]', function() {
		if ($(this).html() === "") {
			$(this).html("<p>Enter some text...</p>");
			previewiframe.contentDocument.execCommand('selectAll', false, null);
		} //CASE EMPTY div, we need to make a paragraph    
	});



} //end init editor func

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////// INITIALIZE CONTEXTUAL  MENUS: PLACING  ///////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////




function add_helper_attributes_in_preview() {
	/* allows some standard items and framework items to be linked to helper windows  */

	//images
	previewFrame.contents().find("body main img").attr("lc-helper", "image");
	//icons
	previewFrame.contents().find("body main i.fa").attr("lc-helper", "icon");
	//buttons
	previewFrame.contents().find("body main .btn").attr("lc-helper", "button");
	//carousels
	//previewFrame.contents().find("body main .carousel").attr("lc-helper","carousel");

}


function initialize_contextual_menus(scope_selector) {

	//FRAMEWORK SETTINGS //////////////
	lc_main_parts_selector = "main > section";
	lc_containers_selector = "main .container,main .container-fluid";
	lc_rows_selector = "main .row";
	lc_columns_selector = "main *[class^='col-'], main *[class*=' col-'],main .col";
	lc_blocks_selector = "main .lc-block";

	//MICRO TESTING JS
	/*
	previewiframe.contentDocument.querySelectorAll(lc_blocks_selector).addEventListener("mouseenter", function( event ) {
	alert('lc_blocks_selector');
	});
	*/

	////////////////////////////// PLACE CONTEXTUAL MENUS WHEN  HOVERING GRID ELEMENTS //////////////////////




	//MOUSE ENTERS PAGE PARTs (SECTIONS)  ////////////////////////
	previewFrameBody.on("mouseenter", lc_main_parts_selector, function() {
		if ($(this).closest(".lc-rendered-shortcode-wrap").length > 0) return; //exit if we're hovering a shortcode
		if ($(this).attr("ID")=="global-footer")
			previewFrame.contents().find("#lc-contextual-menu-mainpart .lc-contextual-title span").text("Footer Section");
				else previewFrame.contents().find("#lc-contextual-menu-mainpart .lc-contextual-title span").text("Section");
		//<i class="fa fa-bars" aria-hidden="true"></i> Section
		//if($(".lc-contextual-window").is(":visible")) return;
		previewFrame.contents().find("#lc-contextual-menu-mainpart .lc-contextual-actions").hide();
		var top = $(this).offset().top; //-previewFrame.contents().scrollTop();
		var left = $(this).offset().left;
		//var right = previewFrame.width() - ($(this).offset().left + $(this).outerWidth())-15;

		var selector = CSSelector($(this)[0]);
		//console.log(selector);
		//var elHeight=previewFrame.contents().find("#lc-contextual-menu-container").outerHeight();
		previewFrame.contents().find("#lc-contextual-menu-mainpart").css({
			'top': top,
			'left': left,
			/* 'right':right */
		}).show().attr("selector", selector);
		previewFrame.contents().find(".lc-highlight-mainpart").removeClass("lc-highlight-mainpart"); //for security
		previewFrame.contents().find(selector).addClass("lc-highlight-mainpart");

		//hl columns new
		//previewFrame.contents().find(selector+" *[class^='col-']").addClass("lc-highlight-column"); //is it useful?


	}); //end function

	//MOUSE LEAVES PAGE PART
	previewFrameBody.on("mouseleave", lc_main_parts_selector, function() {
		//console.log('go out of container');
		var selector = CSSelector($(this)[0]);
		if (previewFrame.contents().find('#lc-contextual-menu-mainpart').is(":hover")) return;
		if (previewFrame.contents().find('#lc-contextual-menu-block').is(":hover")) return;
		previewFrame.contents().find("#lc-contextual-menu-mainpart .lc-contextual-actions").hide();
		previewFrame.contents().find("#lc-contextual-menu-mainpart").hide();

		$(this).removeClass("lc-highlight-mainpart");
		//hl columns new
		previewFrame.contents().find(selector + " *[class^='col-']").removeClass("lc-highlight-column");

	}); //end function




	//MOUSE ENTERS CONTAINER ////////////////////////
	previewFrameBody.on("mouseenter", lc_containers_selector, function() {
		if ($(this).closest(".lc-rendered-shortcode-wrap").length > 0) return; //exit if we're hovering a shortcode
		//if($(".lc-contextual-window").is(":visible")) return;
		previewFrame.contents().find("#lc-contextual-menu-container .lc-contextual-actions").hide();
		var top = $(this).offset().top; //-previewFrame.contents().scrollTop();
		//var left= $(this).offset().left;
		var right = previewFrame.width() - ($(this).offset().left + $(this).outerWidth()) - getScrollBarWidth();

		var selector = CSSelector($(this)[0]);
		//console.log(selector);
		//var elHeight=previewFrame.contents().find("#lc-contextual-menu-container").outerHeight();
		previewFrame.contents().find("#lc-contextual-menu-container").css({
			'top': top,
			/* 'left_NO':left, */ 'right': right
		}).show().attr("selector", selector);
		previewFrame.contents().find(".lc-highlight-container").removeClass("lc-highlight-container"); //for security
		previewFrame.contents().find(selector).addClass("lc-highlight-container");

		//hl columns new
		//previewFrame.contents().find(selector+" *[class^='col-']").addClass("lc-highlight-column"); //is it useful?


	}); //end function

	//MOUSE LEAVES CONTAINER
	previewFrameBody.on("mouseleave", lc_containers_selector, function() {
		//console.log('go out of container');
		var selector = CSSelector($(this)[0]);
		if (previewFrame.contents().find('#lc-contextual-menu-container').is(":hover")) return;
		if (previewFrame.contents().find('#lc-contextual-menu-block').is(":hover")) return;
		previewFrame.contents().find("#lc-contextual-menu-container .lc-contextual-actions").hide();
		previewFrame.contents().find("#lc-contextual-menu-container").hide();

		$(this).removeClass("lc-highlight-container");
		//hl columns new
		previewFrame.contents().find(selector + " *[class^='col-']").removeClass("lc-highlight-column");

	}); //end function

	//MOUSE ENTERS ROW ////////////////////////
	previewFrameBody.on("mouseenter", lc_rows_selector, function() {
		if ($(this).closest(".lc-rendered-shortcode-wrap").length > 0) return; //exit if we're hovering a shortcode
		//if($(".lc-contextual-window").is(":visible")) return;

		previewFrame.contents().find("#lc-contextual-menu-row .lc-contextual-actions").hide();
		var top = $(this).offset().top;
		var left = $(this).offset().left;
		var right = previewFrame.width() - ($(this).offset().left + $(this).outerWidth()) - getScrollBarWidth(); //

		var selector = CSSelector($(this)[0]);
		//console.log(selector);

		var elHeight = previewFrame.contents().find("#lc-contextual-menu-row").outerHeight();
		previewFrame.contents().find("#lc-contextual-menu-row").css({
			'top': top + elHeight,
			'left_NO': left - 1,
			'right': right
		}).show().attr("selector", selector);
		previewFrame.contents().find(selector).addClass("lc-highlight-row");

	}); //end function

	//MOUSE LEAVES ROW
	previewFrameBody.on("mouseleave", lc_rows_selector, function() {
		if (previewFrame.contents().find('#lc-contextual-menu-row').is(":hover")) return;
		if (previewFrame.contents().find('#lc-contextual-menu-block').is(":hover")) return;
		previewFrame.contents().find("#lc-contextual-menu-row .lc-contextual-actions").hide();
		previewFrame.contents().find("#lc-contextual-menu-row").hide();

		$(this).removeClass("lc-highlight-row");
	}); //end function



	//MOUSE ENTERS COLUMN ////////////////////////
	previewFrameBody.on("mouseenter", lc_columns_selector, function() {

		//if($(".lc-contextual-window").is(":visible")) return;
		if ($(this).closest(".lc-rendered-shortcode-wrap").length > 0) return; //exit if we're hovering a shortcode

		previewFrame.contents().find("#lc-contextual-menu-column .lc-contextual-actions").hide();
		var top = $(this).offset().top;
		var left = $(this).offset().left;
		var right = (previewFrame.width() - ($(this).offset().left + $(this).outerWidth())); //

		var selector = CSSelector($(this)[0]);
		//console.log(selector);

		var elHeight = previewFrame.contents().find("#lc-contextual-menu-column").outerHeight();
		previewFrame.contents().find("#lc-contextual-menu-column").css({
			'top': top - elHeight,
			'left': left - 1,
			'right_NO': right
		}).show().attr("selector", selector);
		previewFrame.contents().find(selector).addClass("lc-highlight-column");

	}); //end function

	//MOUSE LEAVES COLUMN
	previewFrameBody.on("mouseleave", lc_columns_selector, function() {
		if (previewFrame.contents().find('#lc-contextual-menu-column').is(":hover")) return;
		if (previewFrame.contents().find('#lc-contextual-menu-block').is(":hover")) return;
		previewFrame.contents().find("#lc-contextual-menu-column .lc-contextual-actions").hide();
		previewFrame.contents().find("#lc-contextual-menu-column").hide();

		$(this).removeClass("lc-highlight-column");
	}); //end function


	//MOUSE ENTERS BLOCK ////////////////////////
	previewFrameBody.on("mouseenter", lc_blocks_selector, function() { //was mouseenter
		if ($(this).closest(".lc-rendered-shortcode-wrap").length > 0) return; //exit if we're hovering a shortcode
		//console.log("mouseenter block");

		//lc_detect_blocks_and_integrate_contextual_menu($(this));
		//if($(".lc-contextual-window").is(":visible")) return;

		previewFrame.contents().find("#lc-contextual-menu-block .lc-contextual-actions").hide();
		var top = $(this).offset().top;
		var left = $(this).offset().left;
		//var right = (previewFrame.width() - ($(this).offset().left + $(this).outerWidth()));

		var selector = CSSelector($(this)[0]);
		//console.log(selector);

		previewFrame.contents().find("#lc-contextual-menu-block").hide().css({
			'top': top,
			'left': left,
			/* 'right_NO':right */
		}).show().attr("selector", selector);
		previewFrame.contents().find(".lc-highlight-block").removeClass("lc-highlight-block"); //for security
		previewFrame.contents().find(selector).addClass("lc-highlight-block");

	}); //end function

	//MOUSE LEAVES BLOCK
	previewFrameBody.on("mouseleave", ".lc-block", function() {
		if (previewFrame.contents().find('#lc-contextual-menu-block').is(":hover")) return;
		previewFrame.contents().find("#lc-contextual-menu-block .lc-contextual-actions").hide();
		previewFrame.contents().find("#lc-contextual-menu-block").hide();

		$(this).removeClass("lc-highlight-block");
	}); //end function

	/*
	//MOUSE ENTERS EDITABLE ITEM ////////////////////////
	previewFrameBody.on("mouseover", "*[lc-helper]", function () { //was mouseenter
	    $(this).addClass("lc-highlight-item");
	    
	}); //end function
	
	//MOUSE LEAVES EDITABLE ITEM that has a HELPER defined
	previewFrameBody.on("mouseleave", "*[lc-helper]", function () {
	    if( previewFrame.contents().find('#lc-contextual-menu-item').is(":hover")) return;
	    previewFrame.contents().find("#lc-contextual-menu-item .lc-contextual-actions").hide();
	    previewFrame.contents().find("#lc-contextual-menu-item").hide();
	    
	    $(this).removeClass("lc-highlight-item");
	}); //end function
	*/

	//TRIGGER  add_helper_attributes_in_preview 
	add_helper_attributes_in_preview();


} //end main function


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////// INITIALIZE CONTEXTUAL MENU ACTIONS  ///////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

function set_html_editor(html) { //quick function to beautify and set the html editor content
	$("#lc-html-editor-window").attr("prevent_live_update", "1");
	lc_html_editor.session.setValue(html_beautify(html, {
		unformatted: ['script', 'style'],
		"indent_size": "1",
		"indent_char": "\t",
	}), 1);
	$("#lc-html-editor-window").removeAttr("prevent_live_update");
}

function set_css_editor(css) { //quick function to beautify and set the css editor content
	$("#lc-css-editor").attr("prevent_live_update", "1");
	lc_css_editor.session.setValue(css_beautify(css, {
		//unformatted: ['script', 'style'],
		"indent_size": "1",
		"indent_char": "\t",
	}), 1);
	$("#lc-css-editor").removeAttr("prevent_live_update");
}


function initialize_contextual_menu_actions() {

	//USER CLICKS CONTEXTUAL BLOCK MENU TITLE: REVEAL SUBMENU
	previewFrame.contents().on("click", ".lc-contextual-title", function(e) {
		e.preventDefault();
		$(this).closest(".lc-contextual-menu").find(".lc-contextual-actions").slideToggle(100);
	}); //end function

	//USER CLICKS ANY SPECIFIC CONTEXTUAL BLOCK MENU ITEM: HIDE CONTEXTUAL MENU  
	previewFrame.contents().on("click", ".lc-contextual-menu ul li a", function(e) {
		e.preventDefault();
		$(this).closest(".lc-contextual-menu").slideUp();
	}); //end function

	//USER CLICKS EDIT HTML 
	previewFrame.contents().find("body").on("click", '.lc-open-html-editor', function(e) {
		e.preventDefault();
		$(".close-sidepanel").click();
		$("#previewiframe").addClass("lc-block-pointer-events");
		$("body").addClass("lc-bottom-editor-is-shown");
		//$("main .lc-shortcode-preview").remove();
		var selector = $(this).closest("[selector]").attr("selector");
		$("#lc-html-editor-window").attr("selector", selector);
		console.log("Open html editor for: " + selector);
		var html = getPageHTML(selector);
		set_html_editor(html);
		$("#lc-html-editor-window").removeClass("lc-opacity-light").fadeIn(100);
		lc_html_editor.focus();
		
		$("#html-tab").click();
	});

	//USER CLICKS ON COPY BLOCK
	previewFrame.contents().find("body").on("click", ".lc-copy-to-clipboard", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		var html = getPageHTML(selector); //console.log("store in clipb:"+html);

		//emergency copy solution for not-chrome browsers
		if (!usingChromeBrowser()) {
			localStorage.setItem("lc_clipboard", html);
			return;
		}

		if (navigator.clipboard == undefined) {
			alert("This requires a secure origin - either HTTPS or localhost");
			return;
		}
		//localStorage.setItem("lc_clipboard", html);
		navigator.clipboard.writeText(html);
	}); //end function copy block

	//USER CLICKS ON PASTE BLOCK
	previewFrame.contents().find("body").on("click", ".lc-paste-from-clipboard", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		//newValue=localStorage.getItem("lc_clipboard");

		//emergency paste solution for not-chrome browsers
		if (!usingChromeBrowser()) {
			var html = localStorage.getItem("lc_clipboard");
			setPageHTML(selector, html);
			updatePreviewSectorial(selector);
			return;
		}

		navigator.clipboard.readText()
			.then(html => {
				if (html === null) {
					alert("Clipboard is Empty");
					return;
				}
				setPageHTML(selector, html);
				updatePreviewSectorial(selector);
			})
			.catch(err => {
				console.error('Failed to read clipboard contents: ', err);
			});
	}); //end function paste block


	///////////CONTAINERS ///////////////////

	//HANDLE CLICKING OF EDIT  CONTAINER PROPERTIES  
	previewFrame.contents().on('click', " .lc-edit-container-properties", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		//alert(selector);
		revealSidePanel("container-properties", selector);
	});

	/*
	//USER CLICKS ON DUPLICATE CONTAINER
	previewFrame.contents().find("body").on("click", ".lc-duplicate-container", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		selector = selector.substring(0, selector.lastIndexOf(">")); //get the selector for the parent: the container's wrap
		var html = doc.querySelector(selector).outerHTML;
		setPageHTMLOuter(selector, html + html);

		selector = selector.substring(0, selector.lastIndexOf(">")); //get the selector for the parent  
		updatePreviewSectorial(selector);

	}); //end function

	*/

	//USER CLICKS ON DELETE  CONTAINER
	previewFrame.contents().find("body").on("click", ".lc-remove-container", function(e) {
		e.preventDefault();
		if (!confirm('Are you sure to delete the selected container?')) return;
		var selector = $(this).closest("[selector]").attr("selector");
		selector = selector.substring(0, selector.lastIndexOf(">")); //get the selector for the parent: the container's wrap
		///we could check here if the selector really refers to a wrap
		setPageHTMLOuter(selector, "");
		updatePreview();
		lc_check_if_content_empty();
	}); //end function  



	//USER CLICKS ON ADD ROW&COLS TO CONTAINER from contextual menu
	previewFrame.contents().on('click', " .lc-container-insert-rowandcols", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		revealSidePanel("add-row", selector);
	});


	//////////////////SECTIONS/////////////////////////////

	//USER CLICKS ON OPEN SECTION LIBRARY / REPLACE SECTION
	previewFrame.contents().find("body").on("click", ".lc-replace-section", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		revealSidePanel("sections", selector);
		$("section[item-type=sections] .sidepanel-tabs a:first").click(); //open first tab 
	}); //end function  


	//HANDLE CLICKING OF EDIT  SECTION PROPERTIES  
	previewFrame.contents().on('click', " .lc-edit-section-properties", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		//alert(selector);
		revealSidePanel("section-properties", selector);
	});

	//////////////ROWS ///////////////////////////////////////
	//USER CLICKS ON ROW PROPERTIES
	previewFrame.contents().find("body").on("click", ".lc-edit-row-properties", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		revealSidePanel("row-properties", selector);
	}); //end function

	//////////////COLUMNS ///////////////////////////////////////

	//USER CLICKS ON COLUMN PROPERTIES
	previewFrame.contents().find("body").on("click", ".lc-edit-column-properties", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		revealSidePanel("column-properties", selector);
	}); //end function

	////////////////////BLOCKS ///////////////////////////

	//USER CLICKS ON REPLACE BLOCK
	previewFrame.contents().find("body").on("click", ".lc-replace-block", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		revealSidePanel("blocks", selector);

	}); //end function  



	//USER CLICKS ON   BLOCK PROPERTIES
	previewFrame.contents().find("body").on("click", ".lc-edit-block-properties", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		revealSidePanel("block-properties", selector);
	}); //end function  

	//USER CLICKS ON DUPLICATE  (GENERAL)  // patched adding  .lc-duplicate-container - and commenting above 1/2020
	previewFrame.contents().find("body").on("click", ".lc-duplicate-section, .lc-duplicate-container, .lc-duplicate-row,.lc-duplicate-block", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		var html = doc.querySelector(selector).outerHTML;
		setPageHTMLOuter(selector, html + html);

		selector = selector.substring(0, selector.lastIndexOf(">")); //get the selector for the parent  
		updatePreviewSectorial(selector);

	}); //end function  

	//USER CLICKS ON DELETE BLOCK/ROW
	previewFrame.contents().find("body").on("click", ".lc-delete-row,.lc-delete-block", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		setPageHTMLOuter(selector, "");

		selector = selector.substring(0, selector.lastIndexOf(">")); //get the selector for the parent  
		updatePreviewSectorial(selector);

	}); //end function  



	//USER CLICKS ON ADD   BLOCK TO COLUMN
	previewFrame.contents().find("body").on("click", ".lc-add-block-to-column", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		setPageHTML(selector, getPageHTML(selector) + '<div class="lc-block"></div><!-- /lc-block -->');
		updatePreviewSectorial(selector);
	}); //end function


	//REODER:: MOVE UP
	previewFrame.contents().find("body").on("click", ".lc-move-up", function(e) {
		e.preventDefault();
		$(".close-sidepanel").click(); //or it's confusing
		var selector = $(this).closest("[selector]").attr("selector");

		if (doc.querySelector(selector).previousElementSibling === null) {
			swal("Element is first already");
			return false;
		}

		var this_element_outer_HTML = doc.querySelector(selector).outerHTML;
		var previous_outer_HTML = doc.querySelector(selector).previousElementSibling.outerHTML;

		doc.querySelector(selector).previousElementSibling.outerHTML = this_element_outer_HTML;
		doc.querySelector(selector).outerHTML = previous_outer_HTML;

		updatePreviewSectorial(CSSelector(doc.querySelector(selector).parentNode));
	}); //end function

	//REODER:: MOVE DOWN
	previewFrame.contents().find("body").on("click", ".lc-move-down", function(e) {
		e.preventDefault();
		$(".close-sidepanel").click(); //or it's confusing
		var selector = $(this).closest("[selector]").attr("selector");

		if (doc.querySelector(selector).nextElementSibling === null) {
			swal("Element is last already");
			return false;
		}

		var this_element_outer_HTML = doc.querySelector(selector).outerHTML;
		var next_outer_HTML = doc.querySelector(selector).nextElementSibling.outerHTML;

		doc.querySelector(selector).nextElementSibling.outerHTML = this_element_outer_HTML;
		doc.querySelector(selector).outerHTML = next_outer_HTML;

		updatePreviewSectorial(CSSelector(doc.querySelector(selector).parentNode));
	}); //end function



	/* USER CLICKS ON "EDIT" LC-HELPER LINKS  IN ITEM CONTEXTUAL MENU */

	previewFrame.contents().find("body").on("click", "*[lc-helper]", function(e) {
		if (e.metaKey) return;

		e.preventDefault();
		e.stopPropagation();

		var item_type = $(this).attr("lc-helper");
		var selector = CSSelector($(this)[0]);
		console.log("open lc helper panel for " + item_type);
		revealSidePanel(item_type, selector);
	});


} //end function


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////// INITIALIZE CONTENT BUILDING ACTIONS  ///////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////


function initialize_content_building() {


	/////////////////////LETS DEFINE SOME ACTION BUTTONS ////////////////////////////////////

	//HANDLE CLICKING OF CHOOSE BLOCK , on dummy new blocks
	previewFrame.contents().on('click', ".lc-block:empty", function(e) {
		e.preventDefault();
		console.log("Let's replace the block's contents");
		var selector = CSSelector($(this).closest(".lc-block")[0]);
		//swal(selector);
		revealSidePanel("blocks", selector);
		//$("section[item-type=blocks] .sidepanel-tabs a:first").click(); //open first tab 
	});
	//HANDLE CLICKING OF CHOOSE SECTION, on dummy new sections
	previewFrame.contents().on('click', "main section:empty", function(e) {
		e.preventDefault();
		console.log("Let's replace the sections's contents");
		var selector = CSSelector($(this).closest("section")[0]);
		//swal(selector);
		revealSidePanel("sections", selector);
	});

} //end function


 