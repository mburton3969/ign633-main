function talert(m){
  var body = document.body;
  var tbox = document.getElementById('toast_box');
  var uid = Math.floor((Math.random() * 100) + 1);
  //Create Toast...
  var d1 = document.createElement('div');
  d1.id = 'toast_'+uid;
  d1.setAttribute('aria-live','polite');
  d1.setAttribute('aria-atomic','true');
  d1.setAttribute('style','position: relative; min-height:200px;');
  
  var d2 = document.createElement('div');
  d2.setAttribute('class','toast');
  d2.setAttribute('style','position:absolute;top:0px;right:0px;');
  
  var d3 = document.createElement('div');
  d3.setAttribute('class','toast-header');
  
  var img = document.createElement('img');
  
  var str = document.createElement('strong');
  str.setAttribute('class','mr-auto');
  str.innerHTML = 'Toolbox Alert';
  
  var s = document.createElement('small');
  s.innerHTML = '';//Usually filled with 'Just Now' or '11 seconds ago'...
  
  var btn = document.createElement('button');
  btn.setAttribute('type','button');
  btn.setAttribute('class','ml-2 mb-1 close');
  btn.setAttribute('data-dismiss','toast');
  btn.setAttribute('aria-label','Close');
  
  var span = document.createElement('span');
  span.setAttribute('aria-hidden','true');
  span.innerHTML = '&times;';
  
  var d4 = document.createElement('div');
  d4.setAttribute('class','toast-body');
  d4.innerHTML = m;
  
  d3.appendChild(img);
  d3.appendChild(str);
  d3.appendChild(s);
  d3.appendChild(btn);
  d2.appendChild(d3);
  d2.appendChild(d4);
  d1.appendChild(d2);
  body.appendChild(d1);
  
  //Show Toast Alert...
  $('#toast_'+uid).toast('show');
  //Set Delayed removal
  setTimeout(function(){
    $('#toast_'+uid).toast('dispose');
  },8000);
  
}

