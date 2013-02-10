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

initObjects();

$now = time();

// loop through all scheduled greetings and post

$sql = "select user.userid, user.email, user.token, user.tokenexpiration, greeting.friendid, greeting.message, greeting.image from user, greeting where user.userid=greeting.userid and user.tokenexpiration>0 and greeting.date='".date("m/d")."'";

$result = $db->query($sql);

while ($row = $result->fetch_assoc()) {
	
	
	if ($row['tokenexpiration'] < $now) {
		
		// token expired, so reset in db
		$sql = "update user set token='', tokenexpiration=0 where userid='".$db->real_escape_string($row['userid'])."'";
		$db->query($sql);
		
		// send email
		$msg = "Your Birthday Greetings on Facebook have expired.\n\nTo keep sending birthday greetings, please visit the app at:\n\n".$facebookapp->getCanvasUrl('');
		mail ($row['email'], "Facebook Birthday Greetings Expired", $msg, "From: My Name<me@email.com>");
		
	} else {
		
		// post greeting
		$facebookapp->setToken($row['token']);
		postGreeting($row['friendid'], htmlspecialchars_decode($row['message'],ENT_QUOTES), $row['image']);
	
	}
}

closeObjects();

?>