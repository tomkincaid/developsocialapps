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

$json = array("success"=>0);

if ($facebookapp->initUserFromReuqest()) {
	
	// got user from signed request
	
	$json['success'] = 1;
	
	$date = date("m/d",strtotime($_GET['date']));
	
	if ($_GET['sendnow'] == 1) {
		
		// send greeting now
		
		$response = postGreeting($_GET['friendid'],$_GET['message'],$_GET['image']);
	
		if ($response === false) $json['success'] = 0; // something went wrong
	
	}
	
	$_GET['message'] = htmlspecialchars($_GET['message'],ENT_QUOTES);

	$sql = "insert into greeting (userid, friendid, message, image, date) values (?,?,?,?,?) on duplicate key update message=?, image=?, date=?";
	$stmt = $db->prepare($sql);
	$stmt->bind_param(	"ssssssss", 
						$facebookapp->userId, 
						$_GET['friendid'], 
						$_GET['message'],
						$_GET['image'],
						$date,
						$_GET['message'],
						$_GET['image'],
						$date);
	$stmt->execute();
	$stmt->close();
	
}

closeObjects();

echo json_encode($json);

?>