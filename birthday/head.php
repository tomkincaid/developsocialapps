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
?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml"><head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title><?php echo $pageTitle." - ".$GLOBALS['appName']; ?></title>

<link rel="stylesheet" type="text/css" href="/common/css/redmond/jquery-ui-1.8.23.custom.css"/>
<link rel="stylesheet" type="text/css" href="/common/styles_common.css" />
<script type="text/javascript" src="/common/js/jquery-1.8.2.min.js"></script>
<script type="text/javascript" src="/common/js/jquery-ui-1.8.23.custom.min.js"></script>

<?php if ($GLOBALS['apptype'] == "canvas") { ?>
	<link rel="stylesheet" type="text/css" href="/common/styles_canvas.css" />
<?php } else { ?>  
    <meta name="viewport" content="width=device-width, initial-scale=1 maximum-scale=1, user-scalable=no" />
    <link rel="stylesheet" type="text/css" href="/common/css/tomsapps.min.css" />
    <link rel="stylesheet" type="text/css" href="/common/css/jquery.mobile.structure-1.2.0.min.css" />
    <link rel="stylesheet" type="text/css" href="/common/styles_mobile.css" />
    <script type="text/javascript" src="/common/js/jquery.mobile-1.2.0.min.js"></script>
<?php } ?>

<script type="text/javascript">

var currentTab = <?php echo $currentTab; // currently active tab, -1 for none ?>;

var canvasUrl = "<?php echo $facebookapp->getCanvasUrl(""); // top canvas url ?>";

var apptype = "<?php echo $GLOBALS['apptype']; ?>";

if (apptype == "canvas") {
	$(document).ready(function() {
		headInit();
	});
} else {
	// remove previous page from dom
	$('div').live('pagehide', function(event, ui) {
		var page = $(event.target);
		page.remove();
	});
	// remove pageinit so it doesn't fire multiple times
	$(document).live('pagebeforeload',function(event,data){
		$(document).unbind('pageinit');
	});
	// this fires every page load
	$(document).live('pageshow',function(event){
		$('a[target="_top"]').removeAttr('target'); // need to remove target so mobile transitions work
	});
}

function headInit() {
	
	// make jquery ui tabs
	$( "#tabs" ).tabs(	{	selected: currentTab,
							// when tab clicked, load page
							select: function(event, ui) {
								switch(ui.index) {
									case 1:
										canvasUrl += "schedule.php";
										break;
									case 2:
										canvasUrl += "help.php";
										break;
								}
								top.location.href = canvasUrl;
								return false;
							} // select
						});
						
}

</script>
</head>
<body>
<?php includeFbjs(); ?>

<?php if ($GLOBALS['apptype'] == "canvas") { ?>

    <div style="float:left;margin-right:10px;width:20px;height:20px;background-color:#eeeeee;"></div><!--this will be an icon-->
    <h2 class='app_name'>Send Birthday Greetings</h2>
    <div class="ad_top"><div style="width:728px;height:90px;background-color:#eeeeee;">728x90 Ad</div></div>
    <div id="tabs">
        <ul>
          <li><a href="#tabs-0">My Friends' Birthdays</a></li>
          <li><a href="#tabs-1">Schedule a Birthday Greeting</a></li>
          <li style="float:right;"><a href="#tabs-2">Help</a></li>
        </ul>
        <div id="tabs-<?php echo ($currentTab >= 0) ? $currentTab : "x\" class=\"ui-tabs-panel"; ?>">
            <div class="ad_right"><div style="width:160px;height:600px;background-color:#eeeeee;">160x600 Ad</div></div>
            <div class="tab_content">

<?php } else { // web or mobile ?>
            
	<div data-role="page" data-theme="a">
    
		<div data-role="header" data-position="fixed" data-theme="b">
        	<h1><?php echo $pageTitle; ?></h1>
            <?php if (!$authorized) echo "<a href='".$facebookapp->getAuthUrl($GLOBALS['webRedirectUrl'],$GLOBALS['facebookPermissions'])."' class='ui-btn-right'>Log In</a>"; ?>
             <?php if ($authorized) echo "<div id='mobileuserbar'>Looged in as ".$user->displayName." &bull; <a href='logout.php'data-ajax='false'>Log Out</a></div>"; ?>
		</div>
       
		<div data-role="content">	
        	
<?php } // end canvas ?>