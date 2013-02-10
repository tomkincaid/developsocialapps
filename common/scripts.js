// this needs some time otherwise will popup window, name causes problems?
function showFeedDialogWithAction(href,feedname,caption,description,picture,actionname,actionlink) {
	var obj = {		method: 'feed',
					link: href,
          			picture: picture,
          			name: feedname,
          			caption: caption,
          			description: description,
					actions: [{name:actionname,link:actionlink}]
	};
	FB.ui(obj, function(response) {
	});
}



function escapeHtml(unsafe) {
    return unsafe
        .replace(/&/g, "&amp;")
        .replace(/</g, "&lt;")
        .replace(/>/g, "&gt;")
        .replace(/"/g, "&quot;")
        .replace(/'/g, "&#039;");
}

function unescapeHtml(safe) {
	while (safe.indexOf("&amp;") != -1) safe = safe.replace("&amp;","&");
	while (safe.indexOf("&lt;") != -1) safe = safe.replace("&lt;","<");
	while (safe.indexOf("&gt;") != -1) safe = safe.replace("&gt;",">");
	while (safe.indexOf("&quot;") != -1) safe = safe.replace("&quot;",'"');
	while (safe.indexOf("&#039;") != -1) safe = safe.replace("&#039;","'");
	while (safe.indexOf("&apos;") != -1) safe = safe.replace("&apos;","'");
    return safe;
}

function getUnixTime() {
	var d = new Date();
    return Math.round(d.getTime()/1000);
}

function getMonthFromIndex(index) {
	var months = new Array("January","February","March","April","May","June","July","August","September","October","November","December");
	return months[index];
}

function getLocalTimeFromTimestamp(timestamp) {
	var d = new Date(timestamp*1000); // milliseconds
	return getMonthFromIndex(d.getMonth())+" "+d.getDate()+", "+d.getFullYear()+" "+zeroLeadingNumber(d.getHours())+":"+zeroLeadingNumber(d.getMinutes())+":"+zeroLeadingNumber(d.getSeconds());
}

function zeroLeadingNumber(number) {
	var s = new String(number);
	if (number < 10) s = "0"+s;
	return s;
}

function getHighestZIndex() {
	var elements = document.getElementsByTagName("*");
	var highestindex = 0;
	for (var i = 0; i < elements.length - 1; i++) {
		if (parseInt(elements[i].style.zIndex) > highestindex) {
			highestindex = parseInt(elements[i].style.zIndex);
		}
	}
	highestindex++;
	return highestindex;
}

var JSON = JSON || {};  
JSON.stringify = JSON.stringify || function (obj) {  
    var t = typeof (obj);  
    if (t != "object" || obj === null) {  
        // simple data type  
        if (t == "string") obj = '"'+obj+'"';  
        return String(obj);  
    }  
    else {  
        // recurse array or object  
        var n, v, json = [], arr = (obj && obj.constructor == Array);  
        for (n in obj) {  
            v = obj[n]; t = typeof(v);  
            if (t == "string") v = '"'+v+'"';  
            else if (t == "object" && v !== null) v = JSON.stringify(v);  
            json.push((arr ? "" : '"' + n + '":') + String(v));  
        }  
        return (arr ? "[" : "{") + String(json) + (arr ? "]" : "}");  
    }  
};  
JSON.parse = JSON.parse || function (str) {  
    if (str === "") str = '""';  
    eval("var p=" + str + ";");  
    return p;  
};  

function setCookie(c_name,value,exdays) {
	var exdate=new Date();
	exdate.setTime(exdate.getTime() + (60*60*24*1000*exdays)); // milliseconds
	var c_value=escape(value) + ((exdays==null) ? "" : "; expires="+exdate.toUTCString());
	document.cookie=c_name + "=" + c_value;
}

function getCookie(c_name) {
	var i,x,y,ARRcookies=document.cookie.split(";");
	for (i=0;i<ARRcookies.length;i++) {
  		x=ARRcookies[i].substr(0,ARRcookies[i].indexOf("="));
  		y=ARRcookies[i].substr(ARRcookies[i].indexOf("=")+1);
  		x=x.replace(/^\s+|\s+$/g,"");
  		if (x==c_name) {
    		return unescape(y);
    	}
  	}
	return "";
}


