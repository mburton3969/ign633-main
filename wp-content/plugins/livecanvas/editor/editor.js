/////////////////////////////////////////////////        			
// DOCUMENT READY
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
$(document).ready(function($) {



	//one more check, do we need to restore a local backup?
	/*
	var last_step_html=localStorage.getItem("last_step_html" );
	if (0) if(last_step_html!==null) {
	    swal({
	        title: "A backup session is present",  text: "Should we restore the last saved backup from your browser? Might be a good idea.",   icon: "warning",  buttons: true,  dangerMode: false,
	      })
	    .then((willRestore) => {
	        if (willRestore) {
	          doc.querySelector(selector).outerHTML==last_step_html;
	          updatePreview();
	          //swal("Session restored!", {icon: "success", });
	          
	        } else {
	          //swal("Nothing is being done.");
	        }
	    });
	} 
	*/

	/////////////////////////////INITIALIZE / SETUP THE APP //////////////////////////////////////////////////////////////////

	//SET NETWORK TIMEOUT
	$.ajaxSetup({
		timeout: 45000
	});

	//LOAD PREFERENCES object editorPrefsObj from LOCALSTORAGE
	if (localStorage.getItem("lc_editor_prefs_json") === null) editorPrefsObj = {};
		else editorPrefsObj = JSON.parse(localStorage.getItem("lc_editor_prefs_json"));
		
	//CHECK BROWSER and display a message if user lives in the past  /////////////////////////
	if (!usingChromeBrowser() && !('already_recommended_browser' in editorPrefsObj)) {setEditorPreference("already_recommended_browser", 1); swal("Please use the Google Chrome browser to run LiveCanvas for best results. There is an ever stronger reason to use Chrome as a web developer's main tool: you will see things as most web users do today.");}
	
	//LOAD THE PAGE TO EDIT
	loadURLintoEditor(lc_editor_url_to_load);

	//SIDEBAR BUILD: LOAD READYMADES 
	fetch("https://livecanvas.com/remote/lc-sections-v2b.html")  
		.then(function(response) {
			return response.text();
		}).then(function(page_html) {
			$("#readymade-sections").html(page_html);
		}).catch(function(err) {
			swal("Error " + err + " fetching Readymades");
		});


	//INTERFACE BUILDING: LOAD ICONS
	setTimeout(function() {
		$("#lc-fontawesome-icons").load("?lc_action=load_icons", function() {});
	}, 4000);

	//INTERFACE BUILDING ADD COMMON FIELDS   TO EACH FORM
	$('#sidepanel section form.add-common-form-elements-for-properties-panels').each(function(index, el) {
		$(el).append($("#sidebar-section-form-common-elements-for-properties-panels").html());
	});
	$('#sidepanel section form.add-common-form-elements ').each(function(index, el) {
		$(el).append($("#sidebar-section-form-common-elements").html());
	});

	//INTERFACE BUILDING: copy divs and SELECTs:  
	$(this).find("*[get_content_from]").each(function(index, element) {
		var source_selector = $(element).attr('get_content_from');
		$(element).html($(source_selector).html());
	}); //end each

	/////////////////////////// INIT THE IN-PAGE HTML CODE EDITOR ///////////////////////////
	lc_html_editor = ace.edit("lc-html-editor");
	lc_html_editor.setOptions({
		enableBasicAutocompletion: true, // the editor completes the statement when you hit Ctrl + Space
		enableLiveAutocompletion: true, // the editor completes the statement while you are typing
		showPrintMargin: false, // hides the vertical limiting strip
		highlightActiveLine: false,
		mode: "ace/mode/html",
		wrap: true,
		useSoftTabs: false,
		tabSize: 4,
	});
	
	///SET EDITOR THEME
	if ('editor_theme' in editorPrefsObj) the_editor_theme = editorPrefsObj.editor_theme;
		else the_editor_theme = "cobalt";
	lc_html_editor.setTheme("ace/theme/" + the_editor_theme);
	$("select#lc-editor-theme option[value=" + the_editor_theme + "]").prop('selected', true);

	//SET EDITOR FONTSIZE
	if ('editor_fontsize' in editorPrefsObj) {
		$("#lc-editor-fontsize").val(editorPrefsObj.editor_fontsize);
		document.getElementById('lc-html-editor').style.fontSize = editorPrefsObj.editor_fontsize + 'px';
	}
   
	/////////////////////////// INIT THE IN-PAGE CSS CODE EDITOR ///////////////////////////
	lc_css_editor = ace.edit("lc-css-editor");
	lc_css_editor.setOptions({
		enableBasicAutocompletion: true, // the editor completes the statement when you hit Ctrl + Space
		enableLiveAutocompletion: true, // the editor completes the statement while you are typing
		showPrintMargin: false, // hides the vertical limiting strip
		highlightActiveLine: false,
		mode: "ace/mode/css",
		wrap: true,
		useSoftTabs: false,
		tabSize: 4,
	});

	///SET CSS EDITOR THEME
	if ('editor_theme' in editorPrefsObj) the_css_editor_theme = editorPrefsObj.css_editor_theme;
		else the_css_editor_theme = "chrome";
	lc_css_editor.setTheme("ace/theme/" + the_css_editor_theme);  

 
   

	/////////////////////////// USER ACTIONs TRIGGER REACTIONs //////////////////////////////////////////////////////////////////

	//INIT HTML EDITOR REACTION WHEN EDITED
	lc_html_editor.getSession().on('change', function() {
		if ($("#lc-html-editor-window").attr("prevent_live_update") == "1") return;
		console.log("React to html editor change");
		var selector = $("#lc-html-editor-window").attr("selector");
		var new_html = lc_html_editor.getValue();
		doc.querySelector(selector).innerHTML = new_html;
		//add throttling eventually?
		//if (new_html.includes("<script"))   
		if (new_html.includes("lc-needs-hard-refresh")) {
			updatePreview();
			setTimeout(function() {
				previewFrame.contents().find("html, body").animate({
					scrollTop: previewFrame.contents().find(selector).offset().top
				}, 10, 'linear');
			}, 100);

		} else { updatePreviewSectorial(selector); }
	}); //end onChange

	//INIT CSS EDITOR REACTION WHEN EDITED
	lc_css_editor.getSession().on('change', function() {
		if ($("#lc-css-editor").attr("prevent_live_update") == "1") return;
		console.log("React to css editor change");
		var new_css = lc_css_editor.getValue();
		doc.querySelector("#wp-custom-css").innerHTML = new_css;
		previewFrame.contents().find("#wp-custom-css").html(new_css);
	}); //end onChange
	
	//MAKE CODE EDITORS WINDOW  RESIZABLE
	var theWindow = document.querySelector('#lc-html-editor-window');
	var theBar = document.querySelector('#lc-html-editor-window .lc-editor-menubar');
	var /*startX, */ startY, /*startWidth,*/ startHeight;

	theBar.addEventListener('mousedown', initDragY, false);

	function initDragY(e) {
		startY = e.clientY; //startX = e.clientX;
		startHeight = parseInt(document.defaultView.getComputedStyle(theWindow).height, 10); //startWidth = parseInt(document.defaultView.getComputedStyle(theWindow).width, 10);
		document.documentElement.addEventListener('mousemove', doDragY, false);
		document.documentElement.addEventListener('mouseup', stopDrag, false);
	}

	function doDragY(e) {
		if (e.clientY < 25 || startHeight - e.clientY + startY < 40) return;
		theWindow.style.height = (startHeight - e.clientY + startY) + 'px'; //theWindow.style.width = (startWidth + e.clientX - startX) + 'px';
		lc_html_editor.resize();lc_css_editor.resize(); //console.log(startWidth, e, startX, theWindow.style.width);
	}

	function stopDrag() {
		document.documentElement.removeEventListener('mousemove', doDragY, false);
		document.documentElement.removeEventListener('mouseup', stopDrag, false);
	}
	
	//USER CLICKS CODE EDITOR TABBER: INIT CSS PANEL
	$("body").on("click", "#css-tab", function(e) {
		e.preventDefault();
		$(".code-tabber a.active").removeClass("active");
		$(this).addClass("active");
		$("#lc-html-editor").hide();
		var css = getPageHTML("#wp-custom-css");
		set_css_editor(css); 
		$("#lc-html-editor").hide(); 
		$("#lc-css-editor").show();
		lc_css_editor.resize();
		
		$("select#lc-editor-theme option[value=" + the_css_editor_theme + "]").prop('selected', true);

	
	});
	//USER CLICKS HTML TAB
	$("body").on("click", "#html-tab", function(e) {
		e.preventDefault();
		$(".code-tabber a.active").removeClass("active");
		$(this).addClass("active");
		$("#lc-html-editor").show(); 
		$("#lc-css-editor").hide();
		lc_html_editor.resize();
		
		$("select#lc-editor-theme option[value=" + the_editor_theme + "]").prop('selected', true);
	});
	
	//USER CLICKS ANY LINK IN THE PREVIEW WHEN CODE EDITOR IS OPEN
	$("body").on("click", '#previewiframe-wrap', function(e) {
		if ($('#previewiframe').hasClass("lc-block-pointer-events")) {
			e.preventDefault();
			console.log("Please close the code editor before interacting with the page.");
			//$('#previewiframe').focus();
			$(".lc-editor-close").click();
		}
	});
	//USER OPENS EXTRAS MENU
	$("body").on("click", "#toggle-extras-submenu", function(e) {
		e.preventDefault();
		$("#extras-submenu").slideToggle(100);
		$(this).toggleClass("active-mode");
	});

	//USER CLICKS ANY LINK IN EXTRAS SUBMENU
	$("body").on("click", '#extras-submenu a', function(e) {
		e.preventDefault();
		$('#extras-submenu').slideUp();
		$("#toggle-extras-submenu").removeClass("active-mode");
	});

	//PUSH SIDE PANEL //USELESS NOW
	/*
	$("body").on("click", '.toggle-side-mode', function (e){
	    e.preventDefault(); 
	    $('#previewiframe-wrap').toggleClass("push-aside-preview");
	    $(this).find("i").toggleClass("fa-chevron-circle-left").toggleClass("fa-chevron-circle-right");
	});*/
	/*
	//OPEN PROJECT SETTINGS PANELZ
	$("body").on("click", '.edit-project-settings', function(e) {
		e.preventDefault();
		revealSidePanel("project-settings", 'main#lc-main');     
	});
	*/

	//GO FULLSCREEN
	$("body").on("click", '.go-fullscreen', function(e) {
		e.preventDefault();
		if (document.fullscreenElement) {
			document.exitFullscreen();
		} else {
			document.documentElement.requestFullscreen();
		}
	});


	//USER CLICKS EDIT HTML FROM EXTRAS SUBMENU
	$("body").on("click", '.open-main-html-editor', function(e) {
		e.preventDefault();
		$(".close-sidepanel").click();
		$("#previewiframe").addClass("lc-block-pointer-events");
		$("body").addClass("lc-bottom-editor-is-shown");
		//$(  "main .lc-shortcode-preview").remove();
		var selector = "main#lc-main";
		$("#lc-html-editor-window").attr("selector", selector);
		console.log("open html editor for: " + selector);
		var html = getPageHTML(selector);
		set_html_editor(html);
		$("#lc-html-editor-window").removeClass("lc-opacity-light").fadeIn(100);
		$("#html-tab").click();
		lc_html_editor.focus();
	});
	
	//USER CLICKS EDIT CSS FROM EXTRAS SUBMENU
	$("body").on("click", '.open-main-css-editor', function(e) {
		e.preventDefault();
		$(".open-main-html-editor").click();
		$("#css-tab").click();
		setTimeout(function() { $("#extras-submenu").hide();}, 400);
		
	});
	
	//USER CLICKS EDIT CSS FROM EXTRAS SUBMENU
	$("body").on("click", '.open-editing-history', function(e) {
		e.preventDefault();
		revealSidePanel("history", false);
		
	});
	
	
	//USER CLICKS EXPORT HTML FILE download-static-file
	$("body").on("click", '.download-static-file', function(e) {
		e.preventDefault();
		var the_style="<style>"+getPageHTML("#wp-custom-css")+"</style>";
		//standard from Bootstrap documentation (introduction)
		the_header = '<!doctype html><html lang="en"> <head> <meta charset="utf-8"> <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no"> <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous"> <title>Hello, world!</title> '+the_style+'</head> <body> ';
		var the_footer = ' <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script> <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.7/umd/popper.min.js" integrity="sha384-UO2eT0CpHqdSJQ6hJty5KVphtPhzWj9WO1clHTMGa3JDZwrnQq4sF86dIHNDz0W1" crossorigin="anonymous"></script> <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/js/bootstrap.min.js" integrity="sha384-JjSmVgyd0p3pXB1rRibZUAYoIIy6OrQ6VrjIEaFf/nJGzIxFDsf4x0xIM+B07jRM" crossorigin="anonymous"></script> </body></html>';
		//add FontAwesome
		the_footer = '<link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">' + the_footer;
		download("index.html", the_header + getPageHTML("main#lc-main") + the_footer);
	});

	//MOUSE LEAVES CODE WINDOW: make it translucent
	$("body").on("mouseleave", "#lc-html-editor-window", function() {
		$("#lc-html-editor-window").addClass("lc-opacity-light");
	}); //end function

	//USER CLICKS RESET HTML FROM EXTRAS SUBMENU
	$("body").on("click", '.reset-html-page', function(e) {
		e.preventDefault();
		swal({
				title: "Are you sure?",
				text: "This will delete the whole page content. Are you sure?",
				icon: "warning",
				buttons: true,
				/* dangerMode: true, */
			})
			.then((willDelete) => {
				if (willDelete) {
					$(".lc-editor-close").click();
					$(".close-sidepanel").click();
					setPageHTML("main#lc-main", ""); //setPageHTML("main#lc-main","<section></section>");
					updatePreview();
				}
			});
	});

	////CODE EDITOR WINDOW ux //////////////////////////////////////////////////////
	//Open editor tips
	$("body").on("change", "#lc-editor-tips", function(e) {
		e.preventDefault();
		if ($(this).val() != "") window.open($(this).val());
	});
	//User changes THEME SELECTION
	$("body").on("change", "#lc-editor-theme", function(e) {
		e.preventDefault();
		if ($("#html-tab").hasClass("active")){
			the_editor_theme=$(this).val();
			lc_html_editor.setTheme("ace/theme/" + the_editor_theme);
			setEditorPreference("editor_theme", the_editor_theme);
		} else {
			the_css_editor_theme=$(this).val();
			lc_css_editor.setTheme("ace/theme/" + the_css_editor_theme);
			setEditorPreference("css_editor_theme", the_css_editor_theme);
		}
	});
	//User changes FONT SIZE
	$("body").on("change", "#lc-editor-fontsize", function(e) {
		e.preventDefault();
		document.getElementById('lc-html-editor').style.fontSize = $(this).val() + 'px';
		setEditorPreference("editor_fontsize", $(this).val());
	});
	//USER CLICKS CLOSE CODE EDITOR WINDOW
	$("body").on("click", ".lc-editor-close", function(e) {
		e.preventDefault();
		$("#previewiframe").removeClass("lc-block-pointer-events");
		$("body").removeClass("lc-bottom-editor-is-shown");
		//$(this).closest("section").removeClass("lc-editor-window-maximized");
		lc_html_editor.resize();lc_css_editor.resize();
		$(this).closest("section").hide();
		initialize_contextual_menus();
	});

	//USER CLICKS MAXIMIZE CODE EDITOR WINDOW
	$("body").on("click", ".lc-editor-maximize", function(e) {
		e.preventDefault();
		$(this).closest("section").removeClass("lc-editor-window-sided");
		$(this).closest("section").toggleClass("lc-editor-window-maximized");
		lc_html_editor.resize();lc_css_editor.resize();
	});

	//USER CLICKS SIDE CODE EDITOR WINDOW
	$("body").on("click", ".lc-editor-side", function(e) {
		e.preventDefault();
		$(this).closest("section").removeClass("lc-editor-window-maximized");
		$(this).closest("section").toggleClass("lc-editor-window-sided");
		lc_html_editor.resize();lc_css_editor.resize();
	});

	// ESC KEY CLOSE WINDOWS
	$(document).keyup(function(e) {
		if (e.keyCode == 27) { // esc keycode
			e.preventDefault();
			$(".close-sidepanel").click();
			$(".lc-editor-close").click();
		}
	});

	//RESPONSIVE SWITCH
	$('#responsive-toolbar a').click(function(e) {
		e.preventDefault();
		$('#responsive-toolbar a.active-mode').removeClass("active-mode");
		$(this).addClass("active-mode");
		width_value = $(this).attr("data-width");
		if ($(this).hasClass("add-smartphone-frame")) $("#previewiframe-wrap").addClass("smartphone");
		else $("#previewiframe-wrap").removeClass("smartphone");
		$(this).addClass("active-mode");
		$("#previewiframe").css("width", width_value);

		height_value = $(this).attr("data-height");
		if (height_value === undefined) $("#previewiframe").css("height", "");
		else $("#previewiframe").css("height", height_value);

		//take care of superimposed editing buttons
		//previewFrame.contents().find(".lc-helper-link").remove();
		//setTimeout(add_helper_edit_buttons_to_preview, 1500);

		//hide contextual menu interfaces
		$("#previewiframe").contents().find(".lc-contextual-menu").hide();
	});



	// SAVE Page ////////////////////////////////////////
	$("body").on("click", "#main-save", function(e) {
		e.preventDefault();
		$('#main-save i').attr("class", "fa fa-spinner fa-spin");
		//$("#previewiframe").addClass("lc-block-pointer-events");
		$("#saving-loader").fadeIn(300);
		$.post(
				lc_editor_saving_url, {
					'action': 'lc_save_page',
					'post_id': lc_editor_current_post_id,
					'html_to_save': '\n'+html_beautify(getPageHTML("main#lc-main"), {
										unformatted: ['script', 'style'],
										"indent_size": "1",
										"indent_char": "\t",
									})+'\n',
					'css_to_save': (getPageHTML("#wp-custom-css")),
					'lc_main_save_nonce_field': $("#lc_main_save_nonce_field").val(),
				},
				function(response) {
					//console.log('The server responded: ', response);
					if (response === "Save") {
						//success
						$('#main-save i').attr("class", "fa fa-save");
						$('#main-save').css("color","#3cbf47");
						setTimeout(function(){$('#main-save').css("color",""); }, 2000);
						$("#saving-loader").fadeOut(100);
						original_document_html = getPageHTML();
					} else {
						//(rare) Error!
						swal({
							title: "Saving error (b)",
							icon: "warning",
							text: response
						});
						$('#main-save i').attr("class", "fa fa-save");
						$("#saving-loader").fadeOut(100);
					}

				}
			)
			//.done(function(msg){  })
			.fail(function(xhr, status, error) {
				// (typical, eg unlogged) Error!
				navigator.clipboard.writeText((getPageHTML("main#lc-main")));
				swal({
					title: "Saving error",
					icon: "warning",
					text: error
				});
				$('#main-save i').attr("class", "fa fa-save");
				$("#saving-loader").fadeOut(100);
			});
	}); //end on click



	//CANCEL HTML SAVING     
	$("body").on("click", "#cancel-main-saving", function(e) {
		e.preventDefault();
		if (original_document_html != getPageHTML()) {
			var r = confirm("There are unsaved changes to the page. Exit anyway?");
			if (r === false) return (false);
		}
		window.location.assign(lc_editor_url_before_editor);
	});


	//SAVE WITH COMMAND - S
	$(window).bind('keydown', function(event) {

		if (event.ctrlKey || event.metaKey) {
			switch (String.fromCharCode(event.which).toLowerCase()) {
				case 's':
					event.preventDefault();
					$('#main-save').trigger("click");
					break;
				case 'p':
					event.preventDefault();
					updatePreview();
					break;
			}
		}
	});

	/* *************************** HANDLE CLICKING OF ADD NEW SECTION BUTTON *************************** *///
	$("body").on('click', ".add-new-section", function(e) {
		e.preventDefault();
		$("#sidepanel .close-sidepanel").click();
		console.log("Let's create a new section");
		//previewFrame.contents().find("#lc-add-new-container-section-wrap").hide();
		var newSectionHTML = "<section></section>";
		var lastSection=doc.querySelector("main#lc-main section:last-child"); 
		//INSERT 
		if(!lastSection || lastSection.getAttribute("ID")!=="global-footer") {
			//normal   case:  no magic footer
			console.log("No magic footer detected");
			setPageHTML("main#lc-main", getPageHTML("main#lc-main") + newSectionHTML);
			//update preview
			previewFrame.contents().find("main#lc-main").append(newSectionHTML);
			//updatePreviewSectorial("main#lc-main");
		} else {
			//magic footer case
			console.log("Magic footer detected");
			var footer_code=doc.querySelector("main#lc-main > section#global-footer").outerHTML;
			doc.querySelector("main#lc-main > section#global-footer").remove();
			setPageHTML("main#lc-main", getPageHTML("main#lc-main") + newSectionHTML + footer_code);
			//update preview
			updatePreview();
		}
		//now open the respective panel
		var selector = CSSelector(previewFrame.contents().find("main section:last")[0]); //alert(selector);
		revealSidePanel("sections", selector);
		$(".sidepanel-tabs a:first").click(); //open first tab

		setTimeout(function(){previewFrame.contents().find("html, body").animate({			scrollTop: previewFrame.contents().find(selector).offset().top		}, 500, 'linear'); }, 100);
		

	});


	/* *************************** SIDE PANEL *************************** */
	//HISTORY restore step
	$("body").on("click", "#history-steps li", function(e) {
		e.preventDefault();
		var new_html=$(this).find("template").html();
		setPageHTML("main", new_html);
		
		if (new_html.includes("lc-needs-hard-refresh")) {
			// soft updatePreview()
			previewiframe.srcdoc = doc.querySelector("html").outerHTML;
			previewiframe.onload = enrichPreview();
		
			setTimeout(function() {
				previewFrame.contents().find("html, body").animate({
					scrollTop: previewFrame.contents().find(selector).offset().top
				}, 10, 'linear');
			}, 100);

		} else {
			//soft sectorialupdatePreview
			var selector="main";
			previewiframe.contentWindow.document.body.querySelector(selector).outerHTML = doc.querySelector(selector).outerHTML;
			enrichPreviewSectorial(selector);
		}
		
		
		
		
	});
	
	//MOUSE ENTERS SIDEPANEL: HILIGHT PAGE ELEMENT ////////////////////////
	$("body").on("mouseenter", "#sidepanel section", function() {
		var selector = $(this).attr("selector");
		previewFrame.contents().find(selector).addClass("lc-highlight-currently-editing");
	});
	//MOUSE LEAVES SIDEPANEL: de-HILIGHT PAGE ELEMENT ////////////////////////
	$("body").on("mouseleave", "#sidepanel section", function() {
		var selector = $(this).attr("selector");
		previewFrame.contents().find(selector).removeClass("lc-highlight-currently-editing");
	});

	///CLICK CLOSE PANEL ICON
	$("body").on("click", "#sidepanel .close-sidepanel", function(e) {
		e.preventDefault();
		previewFrame.contents().find(".lc-contextual-menu").fadeOut(500);
		//un-push preview
		$("#previewiframe-wrap").removeClass("push-aside-preview");

		$('#sidepanel').fadeOut();
		//re-show content creation buttons
		//previewFrame.contents().find("#lc-add-new-container-section-wrap").slideDown(300); 
	});

	//TABBER LOGIC eg IMAGES// for UnSplash /wpadmin / svg
	$("body").on("click", "#sidepanel *[data-reveal]", function(e) {
		e.preventDefault();
		var theSection = $(this).closest("section[selector]");
		var selector = $(this).attr("data-reveal");
		if ($(this).hasClass("highlight-button")) { //we have to hide
			$(this).removeClass("highlight-button");
			theSection.find(selector).slideUp();
		} else { //we have to show
			$(this).parent().find(".highlight-button").removeClass("highlight-button");
			$(this).addClass("highlight-button");
			theSection.find(".items-to-reveal > div").hide();
			theSection.find(selector).slideDown();
		}
	});

	//INPUT changes: trigger change in document | attribute values editing
	$("body").on("change", "#sidepanel section *[attribute-name]", function() {
		console.log("attribute values editing");
		var selector = $(this).closest("section").attr("selector");
		var attribute_name = $(this).attr('attribute-name');

		//UNIQUE ID CHECK
		if (attribute_name === "ID" && !!doc.getElementById($(this).val())) {
			swal({
				title: "Already existing ID",
				icon: "warning",
				text: "Please choose another name for this ID."
			});
			return;
		}

		//APPLY THE CHANGE
		if (attribute_name === 'html') setPageHTML(selector, $(this).val());
		else setAttributeValue(selector, attribute_name, $(this).val());

		updatePreviewSectorial(selector);
	});

	//INPUT ZOOMABLE FIELDS: doubleclick and maximize
	$("body").on("contextmenu", "#sidepanel .zoomable", function() {
		$("#sidepanel").addClass("sidepanel-is-maximized");
		return false;
	});

	//INPUT un-maximize
	$("body").on("blur", "#sidepanel input[type=text], #sidepanel textarea", function() {
		$("#sidepanel").removeClass("sidepanel-is-maximized");
	});
   
   //CUSTOM COLOR WIDGET CHANGES
   $("body").on("click", ".custom-color-widget span", function() {
		var selector = $(this).closest("[selector]").attr("selector");
		var elem = doc.querySelector(selector);
		//eliminate all classes in select
		$(this).parent().find("span").each(function(index, element) {
			the_value = $(element).attr("value").trim(); //console.log("Eliminate"+the_value);
			if (the_value !== "") elem.classList.remove(the_value);
		});
		var current_selected_item = $(this).attr("value").trim();
		if (current_selected_item !== "") elem.classList.add(current_selected_item); //console.log("Add class"+current_selected_item);
		$(this).closest("[selector]").find("input[attribute-name=class]").val(elem.classList).change();
      $(this).parent().find("span.active").removeClass("active");
      $(this).addClass("active");
	});
   //  CLICK CUSTOMIZE COLORS
   $("body").on("click", ".customize-colors", function() {
		swal({
				title: "Customizing Bootstrap colors",
				text: "As you may know, this generally requires SASS commmand line tools, in order to manually rebuild the CSS bundle of your theme. \n\nFor a quicker, easy alternative, check out our new CustomStrap 2 Theme - the first theme implementing a SCSS compiler for Bootstrap, directly in the WordPress Customizer.",
				icon: "warning",
				buttons: false,
				/* dangerMode: true, */
		}); 
	});
   
	//SELECT CHANGES: Color / bg / padding > classes  
	$("body").on("change", "#sidepanel section select[target=classes]", function() {
		var selector = $(this).closest("[selector]").attr("selector");
		var elem = doc.querySelector(selector);
		//eliminate all classes in select
		$(this).find("option").each(function(index, element) {
			the_value = $(element).val().trim(); //console.log("Eliminate"+the_value);
			if (the_value !== "") elem.classList.remove(the_value);
		});
		var current_selected_item = $(this).val().trim();
		if (current_selected_item !== "") elem.classList.add(current_selected_item); //console.log("Add class"+current_selected_item);
		$(this).closest("[selector]").find("input[attribute-name=class]").val(elem.classList).change();
	});


	//INLINE STYLE READYMADES 
	$('#sidepanel').on('change', 'select.inline-style-readymades', function(event) {
		event.preventDefault();
		currentValue = $(this).val();
		var theSection = $(this).closest("section[selector]");
		var currentStyle = theSection.find("textarea[attribute-name=style]").val();
		//loop  all items in select
		$(this).find("option").each(function(index, element) {
			the_value = $(element).val();
			//console.log('replace '+the_value+ ' with '+currentValue);
			currentStyle = currentStyle.replace(the_value, currentValue);
		}); //end each
		theSection.find("textarea[attribute-name=style]").val(currentStyle).change();

	}); //end function

	//   fake SELECT inputs //////////////////////////////////////
	//// toggle state
	$("body").on("click", '.ul-to-selection li.first', function() {
		$(this).closest(".ul-to-selection").toggleClass("opened");
	});

	///SHAPE DIVIDERS: CLICK AND APPLY
	$("body").on("click", 'ul#shape_dividers li ', function() {
		if ($(this).hasClass("first")) return;
		var code = $(this).html();
		$(this).closest(".ul-to-selection").find("li.first").html(code);
		//get current area selector eg section)
		var selector = $(this).closest("[selector]").attr("selector");
		//remove the old shape divider if present   
		var elem = doc.querySelector(selector + ' .lc-shape-divider-bottom');
		if (elem) elem.parentNode.removeChild(elem);

		doc.querySelector(selector).innerHTML += code;
		updatePreviewSectorial(selector);

	});

	//////BACKGROUNDS BUILDING ///////////////////
	$('#backgrounds .automatic-library-filler').each(function(index, el) {
		var count;
		for (count = 1; count <= $(el).attr("max"); count++) {
			$(el).append('<li style="' + $(el).attr("the-style").replace(/@id@/g, count) + '"></li>');
		}
	}); //end each

	///BACKGROUNDS: CLICK AND APPLY
	$("body").on("click", "ul#backgrounds li", function() {
		if ($(this).hasClass("first")) return;
		$(this).closest(".ul-to-selection").find("li.first").attr("style", $(this).attr("style"));
		$(this).closest("section").find("textarea[attribute-name=style]").val($(this).attr("style")).change();
	}); //end on click


	///////////BACKGROUND IMAGE
	$("body").on("click", ".open-background-image-panel", function(e) {
		e.preventDefault();
		var selector = $(this).closest("[selector]").attr("selector");
		revealSidePanel("background", selector);
	}); //end on click




	/* *************************** GRID BUILDER: COLUMNS STRUCTURE BUILDING *************************** */

	//HANDLE CLICKING COLUMN SCHEMA BUTTONS: CREATE CONTAINER AND FIRST ROW
	$("body").on("click", "#sidepanel form#grid-builder button[data-rows]", function(e) {
		e.preventDefault(); //$("#sidepanel .close-sidepanel").click();
		var class_prefix = $(this).closest("section").find("[name='row_breakpoint']").val();
		var html_columns = ""; //init variable
		$(this).attr("data-rows").split("-").forEach(function(columnSize) {
			html_columns = html_columns + '<div class="' + class_prefix + columnSize + '">' + '<div class="lc-block"></div><!-- /lc-block -->' + '</div><!-- /col -->';
		});
		//get container width setting
		var container_width = $("input[name=container-width]:checked").val();
		if (container_width == "standard") var the_container_class = "container";
		else var the_container_class = "container-fluid";

		//get title checkbox setting
		if ($('#sidepanel form#grid-builder #add-section-title').prop('checked'))
			var the_intro_row = '<div class="row"><div class="col-md-12">' +
				'<h2 class="display-2 text-center mt-3 mb-0" editable="inline"> Section Title</h2>' +
				'<p class="text-muted h4 text-center mb-5" editable="inline">The subheading text goes here. Explain whats going on in here.</p>' +
				'</div></div>';
		else var the_intro_row = "";

		//define selector for the  ROW:
		var selector = $(this).closest("section").attr("selector");
		var html = '<div class="' + the_container_class + '">' + the_intro_row + '<div class="row">' + html_columns + '</div></div>';
		setPageHTML(selector, html);
		updatePreviewSectorial(selector);
	});

	/////////ADD ANOTHER ROW ///////////////////////

	//HANDLE CLICKING COLUMN SCHEMA BUTTON from content preview
	$("body").on("click", "#sidepanel form.add-row-buttons-wrap button[data-rows]", function(e) {
		console.log("lets add rows");
		e.preventDefault();
		var class_prefix = $(this).closest("section").find("[name='row_breakpoint']").val();
		var html_columns = ""; //init variable
		$(this).attr("data-rows").split("-").forEach(function(columnSize) {
			html_columns = html_columns + '<div class="' + class_prefix + columnSize + '">' + '<div class="lc-block"></div><!-- /lc-block -->' + '</div><!-- /col -->';
		});
		//define selector for the  CONTAINER:
		var selector = $(this).closest("section").attr("selector");
		var html_new = getPageHTML(selector) + ' <div class="row"> ' + html_columns + ' </div> ';
		setPageHTML(selector, html_new); //put columns inside row
		updatePreviewSectorial(selector);
		//$("#sidepanel .close-sidepanel").click();
	});

	/* *************************** SECTIONS / BLOCKS BROWSER / HTML REPLACEMENT / INSTALL *************************** */

	//USER CLICKS BLOCK / SECTION: PUT HTML IN WEBPAGE 
	$("body").on("click", "#sidepanel block", function(e) {
		e.preventDefault();
		//previewFrame.contents().find("#lc-minipreview").hide();
		var selector = $(this).closest("section").attr("selector");
		var new_html = lc_filter_components($(this).closest("block").find("template").html());
		setPageHTML(selector, new_html);
		if (new_html.includes("lc-needs-hard-refresh")) {
			//special case
			updatePreview();
			setTimeout(function() {
				previewFrame.contents().find("html, body").animate({
					scrollTop: previewFrame.contents().find(selector).offset().top
				}, 10, 'linear');
				//previewFrame.contents().find(selector).hide().fadeIn(2000);
			}, 100);

		} else {
			//vanilla case
			updatePreviewSectorial(selector);
			previewFrame.contents().find(selector).hide().fadeIn(400);
		}

	}); //end on click
	
	//USER CLICKS INSERT LIGHT
	$("body").on("click", "#sidepanel block .insert-light", function(e) {
		e.preventDefault();
		$(this).closest("block").click();//insert the section regularly
		var selector = $(this).closest("section").attr("selector");
		setAttributeValue(selector,"class","text-dark bg-light");
		updatePreviewSectorial(selector);
	});
	//USER CLICKS INSERT DARK
	$("body").on("click", "#sidepanel block .insert-dark", function(e) {
		e.preventDefault();
		$(this).closest("block").click();//insert the section regularly
		var selector = $(this).closest("section").attr("selector");
		setAttributeValue(selector,"class","text-light bg-dark");
		updatePreviewSectorial(selector);
	});
	//USER HOVERS DARK LINK
	$("body").on("mouseover", "#sidepanel block .insert-dark", function() {
		$(this).closest("block").find("img").css("filter","grayscale(1) invert(1)");
	});
	//USER un-HOVERS DARK LINK
	$("body").on("mouseout", "#sidepanel block .insert-dark", function() {
		$(this).closest("block").find("img").css("filter","");
	});

	//USER CLICKS a link in BLOCK / SECTION: visit external page
	$("body").on("click", "#sidepanel a", function(e) {
		e.stopPropagation();
	}); //end on click

	/* *************************** SECTIONS / BLOCKS BROWSER : TABBER *************************** */
	//CHANGE ACTIVE TAB
	$("body").on("click", ".sidepanel-tabs a", function(e) {
		e.preventDefault();
		$(this).parent().find(".active").removeClass("active");
		$(this).closest("section").find("form").hide();
		$(this).addClass("active").closest("section").find("#" + $(this).attr("data-show")).show();
		//if($(this).attr("data-show") =="your-custom-sections") $("#lc-your-html-sections").load("?lc_action=load_cpt&cpt_post_type=lc_section", function () { });
		//if($(this).attr("data-show") =="your-custom-blocks")   $("#lc-your-html-blocks").load("?lc_action=load_cpt&cpt_post_type=lc_block", function () { });
	}); //end on click

	//LOADER UTILITY 
	$("body").on("click", "[data-load]", function(e) {
		e.preventDefault();
		if ($(this).attr("data-load") == "custom-html-sections") $("#custom-html-sections").load("?lc_action=load_cpt&cpt_post_type=lc_section", function() {});
		if ($(this).attr("data-load") == "custom-html-blocks") $("#custom-html-blocks").load("?lc_action=load_cpt&cpt_post_type=lc_block", function() {});
	}); //end on click

	/* *************************** BLOCKS BROWSER ACCORDION *************************** */
	//click item additional panel
	$("body").on("click", ".blocks-browser h4", function(e) {
		e.preventDefault(); //alert();
		if ($(this).hasClass("opened")) $(this).removeClass("opened");
		else {
			$(this).closest(".blocks-browser").find("h4.opened").removeClass("opened");
			$(this).addClass("opened");
		}

		$(".blocks-browser block").css("pointer-events", "none");
		$(this).closest(".blocks-browser").find("div").not($(this).next("div")).slideUp('medium');
		$(this).next("div").slideToggle('medium', function() {
			if ($(this).is(':visible'))
				$(this).css('display', 'block');
			$(".blocks-browser block").css("pointer-events", "");
		});
	}); //end on click

	/* *************************** SECTIONS PREVIEW *************************** */
	$("body").on("mouseenter", "#readymade-sections #custom-html-sections block", function() {
		var code = $(this).find("template").html();
		previewFrame.contents().find("#lc-minipreview .lc-minipreview-content").html(code);
		var height = $(this).offset().top - $(document).scrollTop();
		previewFrame.contents().find("#lc-minipreview").css("top", height - 45).show();
	}); //end on hover

	$("body").on("mouseleave", "#readymade-sections #custom-html-sections block", function() {
		previewFrame.contents().find("#lc-minipreview").hide();
	}); //end on hover

 



}); //end document ready

//end file. Wow!