function multithumber_expand(img) {

      var tmp = img.src;
      img.src = img.lowsrc;
      img.lowsrc = tmp;

/*  if(img.src == src1) {
    // img.oldsrc = img.src;
    img.src = src2;
  }
  else {
    // img.src = img.oldsrc;
	img.src = src1;
  }
  // img.style.width = "";
  // img.style.height = "";

  return false; */

}

    if (document.images) {
    }


function toggleHidden(anID, callingElement) {
  el = document.getElementById(anID);
  if (el != null) {
    if (el.style.display == "none") {
      el.style.display = "block";
    }
    else {
      el.style.display = "none";
      callEl = document.getElementById(callingElement);
      if (callEl != null) { callEl.scrollIntoView(); }
    }
  }
}




function thumbWindow(base, mypage, myname, w, h,fit_to_screen, imgtoolbar) {
	var props = '';
	var orig_w = w;
	var scroll = '';
	var winl = (screen.availWidth - w) / 2;
	var wint = (screen.availHeight - h) / 2;
	if (winl < 0) { winl = 0; w = screen.availWidth -6; scroll = 1;}
	if (wint < 0) { wint = 0; h = screen.availHeight - 32; scroll = 1;}
	win = window.open('', 'myThumb', 'height='+h+',width='+w+',top='+wint+',left='+winl+',scrollbars='+scroll+',toolbar=no, menubar=no, resizable=no, scrollbars=no, location=no');
	win.document.open();
	win.document.write('<html><head>');
	win.document.write('<base href="'+base+'" />');
	if (imgtoolbar==0) { win.document.write('<meta http-equiv="imagetoolbar" content="false" />'); }
	win.document.write('<scr' + 'ipt type="text/javascr' + 'ipt" language="JavaScr' + 'ipt">');
  	win.document.write("function click() { window.close(); } ");  // bei click  schliessen
  	win.document.write("document.onmousedown=click ");
  	win.document.write('</scr' + 'ipt>');
	win.document.write('<title>'+myname+'</title></head>');
	win.document.write('<body leftmargin="0" topmargin="0" marginheight="0" marginwidth="0" onBlur="window.close()">');

	if (fit_to_screen) {

		var ns6 = (!document.all && document.getElementById);
		var ie4 = (document.all);
		var ns4 = (document.layers);

		if(ns6||ns4) {
			sbreite = innerWidth - 23;

		}
		else if(ie4) {
			sbreite = document.body.clientWidth - 6;
		}
		if (orig_w>sbreite) { 
			rw = 'width='+sbreite;
		} else {
			rw = '';
		}
		win.document.write('<img src="'+mypage+'" alt="'+myname+'" title="'+myname+'" border="0" '+rw+'\></body></html>');
	} else {
		win.document.write('<img src="'+mypage+'" alt="'+myname+'" title="'+myname+'" border="0" ></body></html>');
	}

	win.document.close();
	if (parseInt(navigator.appVersion) >= 4) { 
		win.window.focus(); 
	}

}