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

// basic framework to initialize page, get a long-lived token, and update the user table
$authorized = initFacebookApp(true,"publish_stream");
requireLongAccessToken();
updateUserData();

// vars for head
$currentTab = 1;
$pageTitle = "Send Greeting";

// max image
$maximage = 2;

$friendsAndBirthdays = getFriendsAndBirthdays(); // this should be cached from home page

// get friend and image if set
$friendid = 0;
$image = 0;
if (isset($_GET['friendid'])) $friendid = $_GET['friendid'];
if (isset($_GET['friendid'])) $image = intval($_GET['image']);

// size of images
if ($GLOBALS['apptype'] == "canvas") $size = 90;
else $size = 40;

include("head.php");
?>

<div id="friendsearch">
<p class="notopspace">Start typing a friend's name and select below:<br/>
<input id="friend" type="text" size="50" maxlength="100" class="inputtext" value="" /></p>
</div>

<div id="greeting" style="display:none;">

<p>Date (select today to send now):<br/>
<input type="text" id="datepicker" size="50" maxlength="100"  class="inputtext"/></p>

<p>Message:<br/>
<input type="text" id="message" size="50" maxlength="255"  class="inputtext"/></p>

<p>Select an image:
<ol id="selectable">
<?php
// just using three images for now
for ($i=0; $i<=$maximage; $i++) {
	echo "<li id='select".$i."'";
	if ($i==$image) echo " class='ui-selected'"; // first one is selected>
	echo "><img src='images/".$i.".jpg'/></li>\n";
}
?>
</ol></p>
	
<div style="clear:left;height:1px;"></div>

<p><button id="submitbutton" onclick="schedule();" style="margin-right:20px;">Schedule a Greeting</button></p>

</div>


<div id="loader" style="display:none;">
<img src="/common/images/ajax-loader.gif" />
</div>


<div id="success" style="display:none;">
<h2 class="notopspace">Your birthday request was scheduled.</h2>
<p><button onclick="reloadPage();">Schedule Another Greeting</button></p>
</div>


<style type="text/css">
#selectable .ui-selectee { border: 1px solid #ffffff; }
#selectable .ui-selected { border: 1px solid #4297D7; }
#selectable { list-style-type: none; margin: 0; padding: 0; }
#selectable li { margin:0px; padding:4px; float:left; width:<?php echo $size; ?>px; height:<?php echo $size; ?>px; border-radius:5px; }
#selectable li img { width:<?php echo $size; ?>px; height:<?php echo $size; ?>px; border:0px; outline:none; cursor:pointer; }
</style>

<script type="text/javascript">

var friendId = "<?php echo htmlspecialchars($friendid,ENT_QUOTES); ?>"; // id of recipient, 0 = use type ahead slector

var selectedImage = <?php echo $image; ?>; // id of image to use, default 0

var isToday = false; // keep track of whether the scheuled date is today or not

var months = ["January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December"]; // array of months javascript Jan is 0 not 1

var signedRequest = "<?php echo (isset($_REQUEST['signed_request'])) ? htmlspecialchars($_REQUEST['signed_request'],ENT_QUOTES) : htmlspecialchars($facebookapp->getSignedRequest(),ENT_QUOTES); ?>";

var friendsAndBirthdays = [<?php 
	// json for the autocomplete and getting birthday
	$comma = "";
	foreach ($friendsAndBirthdays as $friend) {
		$friendarray = array("id"=>$friend['id'],"label"=>$friend['name'],"birthday"=>$friend['birthday']);
		if (isset($friend['image'])) {
			$friendarray['image'] = $friend['image'];
			$friendarray['message'] = $friend['message'];
		}
		echo $comma.json_encode($friendarray); 
		$comma = ",";
	}
?>];


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
	
	// init friend autocomplete 
	$("#friend").autocomplete({ 
		minLength: 1,
		delay:0,
		source: friendsAndBirthdays,
		select: function( event, ui ) {
			friendId = ui.item.id;
			setBirthday(ui.item.birthday);
			$("#greeting").css("display","block");
			$("#friend").blur();
		}
	});
	
	// init datepicker
	$("#datepicker").datepicker({
		dateFormat: "MM d, yy",
		monthNames: months,
		onSelect: function(dateText, inst) {
			var now = new Date();
			var todaysdate = months[now.getMonth()]+" "+now.getDate()+", "+now.getFullYear();
			if (dateText == todaysdate) {
				isToday = true;
				$("#submitbutton .ui-button-text").html("Send Greeting Now");
			} else {
				isToday = false;
				$("#submitbutton .ui-button-text").html("Schedule Greeting");
			}
		}
	});
	
	// init selectable images
	$("#selectable").selectable({
		selected: function(event, ui) {
			// can select multiple, so just use the last one
			$( ".ui-selected", this ).each(function() {
				if ($("#selectable li").index( this ) >= 0) { // sometimes it's -1
					selectedImage = $("#selectable li").index( this );
					$("#select"+selectedImage).removeClass("ui-selected");
				}
			});
			$("#select"+selectedImage).addClass("ui-selected");
		}
	});
	
	// init buttons
	if (apptype == "canvas") $("button").button();
	
	// if friend is set
	if (friendId != "0") {
		for (var i=0; i<friendsAndBirthdays.length; i++) {
			if (friendId == friendsAndBirthdays[i].id) {
				$("#friend").val(friendsAndBirthdays[i].label);
				$("#message").val(friendsAndBirthdays[i].message);
				setBirthday(friendsAndBirthdays[i].birthday);
				$("#greeting").css("display","block");
				break;
			}
		}
	}
	
}


// sets birthday and submit button
function setBirthday(datestring) {
	var dateobject = new Date();
	isToday = false;
	if (datestring == null) {
		// don't know birthday, set to today
		$('<div></div>').html("Birthday is unknown, using today's date.").dialog({
				autoOpen: true,
				title: "Birthday Unknown",
				resizable: false,
				height:'auto',
				width:240,
				modal:true,
				position:[260,300],
				buttons:{Close: function() { $( this ).dialog( "close" ); } }
		});
		isToday = true;
	} else {
		// get date object from string
		var elements = datestring.split("/");
		// can't parse leading 0
		if (elements[0].substr(0,1) == "0") elements[0] = elements[0].substr(1,1); 
		if (elements[1].substr(0,1) == "0") elements[1] = elements[1].substr(1,1);
		var month = parseInt(elements[0]) - 1; // javascript uses 0 for January
		var date = parseInt(elements[1]);
		var year = dateobject.getFullYear();
		if (month < dateobject.getMonth()) {
			year++;
		} else if ((dateobject.getMonth() == month) && (date < dateobject.getDate())) {
			year++;
		} else if ((dateobject.getMonth() == month) && (date == dateobject.getDate())) {
			isToday = true;
		}
		dateobject.setFullYear(year);
		dateobject.setMonth(month);
		dateobject.setDate(date);
	}
	$("#datepicker").datepicker("setDate", dateobject);
	if (isToday) {
		$("#submitbutton .ui-button-text").html("Send Greeting Now");
	} else {
		$("#submitbutton .ui-button-text").html("Schedule Greeting");
	}
}

function schedule() {
	
	// show loader
	$("#friendsearch").css("display","none");
	$("#greeting").css("display","none");
	$("#loader").css("display","block");
	// how to scroll to top? parent.scrollTo(0,0) doesn't work
	
	// submit with ajax
	var url = "json_schedule.php";
	url += "?friendid="+encodeURIComponent(friendId);
	url += "&date="+encodeURIComponent($("#datepicker").val());
	url += "&message="+encodeURIComponent($("#message").val());
	url += "&image="+encodeURIComponent(selectedImage);
	url += "&signed_request="+encodeURIComponent(signedRequest);
	if (isToday) url += "&sendnow=1";

	// submit ajax
	$.getJSON(url, function(data) {
		if (data.success == 1) {
			$("#loader").css("display","none");
			$("#success").css("display","block");
		} else {
			$("#friendsearch").css("display","block");
			$("#greeting").css("display","block");
			$("#loader").css("display","none");
			$('<div></div>').html("There was an error scheduling your greeting.").dialog({
				autoOpen: true,
				title: "Error",
				resizable: false,
				height:'auto',
				width:240,
				modal:true,
				position:[260,300],
				buttons:{Close: function() { $( this ).dialog( "close" ); } }
			});
		}
	});
}


function reloadPage() {
	top.location.href = canvasUrl+"schedule.php";
}



</script>

<?php
include("foot.php");
closeObjects();
?>