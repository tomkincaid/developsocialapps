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

$authorized = initFacebookApp(false);

// vars for head
$currentTab = -1;
$pageTitle = "Login Canceled";

if ((!isset($_GET['error'])) && isset($_GET['code'])) {
	
	// user has auhtorized this app
	
	// if web or mobile, exchange code for token
	if ($GLOBALS['apptype'] != "canvas") {
		if (isset($_GET['redirecturi'])) $redirecturi = $_GET['redirecturi'];
		else $redirecturi = $GLOBALS['webRedirectUrl'];
		$authorized = $facebookapp->setAccessTokenFromCode($_GET['code'],$redirecturi);
	}
	
	if ($authorized) {
		
		// get user's email from graph API
		$email = "";
		$userinfo = $facebookapp->getGraphObject("me");
		
		if ($userinfo !== false) {
			
			if (isset($userinfo['email'])) $email = $userinfo['email'];
			
			if ($GLOBALS['apptype'] != "canvas") {
				// set user object and cookie for web apps
				$user->setFacebook($userinfo['id'], $facebookapp->token, $facebookapp->tokenExpires, $userinfo['first_name']);
				$user->setCookie();
			}
	
			// store in db
			$sql = "insert into user (userid, email, token, tokenexpiration, added, active) values (?,?,?,?,?,?) on duplicate key update email=?, token=?, tokenexpiration=?, removed='0000-00-00 00:00:00', active=?";
			$now = date("Y-m-d H:i:s");
			$stmt = $db->prepare($sql);
			$stmt->bind_param(	"ssssssssss", 
								$facebookapp->userId, 
								$email, 
								$facebookapp->token, 
								$facebookapp->tokenExpires, 
								$now, 
								$now,
								$email, 
								$facebookapp->token, 
								$facebookapp->tokenExpires, 
								$now);
			$stmt->execute();
			$stmt->close();
	
			// go the the page from success_uri cookie
			if (isset($_COOKIE['success_uri'])) $url = $facebookapp->getCanvasUrl($_COOKIE['success_uri']);
			else $url = $facebookapp->getCanvasUrl('');
				
			setcookie ("success_uri","",-99999,"/");
				
			closeObjects();
				
			echo "<html>\n<body>\n<script>\ntop.location.href='".$url."';\n</script></body></html>";
			exit();
			
		}
		
	}
}

include("head.php");

echo "You have canceled the log in.";

include("foot.php");

closeObjects();
?>