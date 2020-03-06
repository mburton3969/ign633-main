	function encodeURL(url){
	url = url.replace(/&/g, '%26'); 
	url = url.replace(/#/g, '%23');
	return url;
}

$(document).ready(function(){
  $('[data-toggle="tooltip"]').tooltip();   
});