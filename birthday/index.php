<?php 
/*
Copyright 2012 Thomas Kincaid

Licensed under the Apache License, Version 2.0 (the "License");
you may not use this file except in compliance with the License.
You may obtain a copy of the License at

http://www.apache.org/licenses/LICENSE-2.0

Unless required by applicable law or agreed to in writing, software
distributed under the License is distributed on an "AS IS" BASIS,
WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
See the License for the specific language governing permissions and
limitations under the License.

If you use this code, please give provide a link on your site to
<a href='http://developsocialapps.com'>Uses code from How to Develop Social Facebook Applications</a>
*/ 

require_once("lib.php");

// basic framework to initialize page, get a long-lived token
$authorized = initFacebookApp(false);
requireLongAccessToken();
updateUserData();

// vars for head
$currentTab = 0;
$pageTitle = "Birthdays";

include("head.php");

//$cache->clearCache(strtotime("+1 year"));
?>

<?php if (!$authorized) { ?>

Promo about how great this app is.

<?php } else { //authorized ?>

<style type="text/css">
.section { margin-bottom:20px; }
.date { font-size:1.3em; font-weight:bold; color: #666666; }
.friend { margin-top:20px; min-height:50px; }
.image { width:50px; float:left; margin-right:10px; }
.image img { width:50px; height:50px; border:0px; }
.name { font-weight:bold; }
.message { margin-top:4px; font-size:0.9em; }
</style>

<div class='bottomspace'>Note, you need to use this app at least once per month for your scheduled greetings to work.</div>

<div id="content"><img src="/common/images/ajax-loader.gif"/></div>

<script type="text/javascript">

var signedRequest = "<?php echo (isset($_REQUEST['signed_request'])) ? htmlspecialchars($_REQUEST['signed_request'],ENT_QUOTES) : htmlspecialchars($facebookapp->getSignedRequest(),ENT_QUOTES); ?>";

if (apptype == "canvas") {
	$(document).ready(function() {
		pageInit();
	});
} else {
	$(document).bind('pageinit',function(event) {
		pageInit();
	});
}

function pageInit() {
	
	// get friend data
	var url = "json_friends.php?signed_request="+encodeURIComponent(signedRequest);
	$.getJSON(url, function(data) {
	
		var html = "";
		
		// get today's date
		var date = new Date();
		var today = date.toDateString();
		
		// loop through 60 days
		for (var d=0; d<60; d++) {
			
			// get string formatted dates
			var datestring = date.toDateString(); 
			var fbdate = "";
			var month = date.getMonth()+1;
			if (month < 10) fbdate += "0";
			fbdate += month+"/";
			var day = date.getDate();
			if (day  < 10) fbdate += "0";
			fbdate += day;
		
			// loop through data and find friends with birthday on this day
			var sectionhtml = "";
			for (var i=0; i<data.length; i++) {
				if (data[i].birthday) {
					if (fbdate == data[i].birthday.substr(0,5)) {
						var margin = 60;
						sectionhtml += "<div class='friend'>";
						sectionhtml += "<div class='image'><a href='"+data[i].link+"' target='_blank'><img src='https://graph.facebook.com/"+data[i].id+"/picture'/></a></div>";
						if (data[i].image) {
							margin += 60;
							sectionhtml += "<div class='image'><img src='images/"+data[i].image+".jpg'/></div>";
						}
						sectionhtml += "<div style='margin-left:"+margin+"px;'><div class='name'>"+data[i].name+"</div>";
						if (data[i].image) {
							sectionhtml += "<div class='message'>"+data[i].message+"</div>";
							sectionhtml += "<div class='message' style='font-size:0.9em;'><a href='"+canvasUrl+"schedule.php?friendid="+data[i].id+"&image="+data[i].image+"' target='_top'>Change Greeting</a>";
							 if (apptype == "canvas") sectionhtml += " &bull; ";
							 else sectionhtml += "</div><div class='message' style='font-size:0.9em;'>";
							 
							 sectionhtml += "<a href='"+canvasUrl+"cancel.php?friendid="+data[i].id+"' target='_top'>Cancel Greeting</a></div>";
						} else {
							sectionhtml += "<div class='message' style='font-size:1.1em;'><a href='"+canvasUrl+"schedule.php?friendid="+data[i].id+"' target='_top'>Schedule Greeting</a></div>";
						}
						sectionhtml += "</div></div>";
					}
				}
			}
			
			if (sectionhtml != "") {
				if (datestring == today) datestring = "Today"; // make it clearer for people
				html += "<div class='section'><div class='date'>"+datestring+"</div>"+sectionhtml+"</div>\n";
			}

			date.setTime(date.getTime()+1000*60*60*24); // increment one day
		}
		
		// set content html
		if (html == "") html = "<p>You have no friends with known birthdays in the next 60 days.</p>";
		$("#content").html(html);
		
		if (apptype != "canvas") {
			// make jquery mobile format content
			$("#contentdiv").trigger("create"); 
			$('a[target="_top"]').removeAttr('target');
		}
	
	})

}
	
</script>

<?php } //authorized ?>

<?php
include("foot.php");
closeObjects();
?>