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

require_once("../../lib/appframework.php");
require_once("config.php");


/* gets info for all friends icluding birthdays, plus scheduled greetings	*/
function getFriendsAndBirthdays() {
	
	global $facebookapp, $cache, $db;
	
	// get friends with birthdays from cache or API
	$cacheid = "friends_".$facebookapp->userId;
	$friendsAndBirthdays = $cache->getCacheVal($cacheid,true);
	if ($friendsAndBirthdays === false) {
		$friendsAndBirthdays = array();
		$friends = $facebookapp->getGraphObject("me/friends"); 
		if ($friends !== false) {
			$urlarray = array();
			foreach ($friends['data'] as $friend) {
				array_push($urlarray,$friend['id']);
				if (count($urlarray) == 50) {
					$batchrepsonse = $facebookapp->getBatchRequests($urlarray);
					$friendsAndBirthdays = array_merge($friendsAndBirthdays,$batchrepsonse);
					$urlarray = array();
				}
			}
			if (count($urlarray) > 0) {
				$batchrepsonse = $facebookapp->getBatchRequests($urlarray);
				$friendsAndBirthdays = array_merge($friendsAndBirthdays,$batchrepsonse);
			}
			$cache->setCacheVal($cacheid,$friendsAndBirthdays,true,"+1 day");
		}
	}
	
	// create aray of friend ids
	$friendids = array();
	foreach ($friendsAndBirthdays as $freind) {
		array_push($friendids,$freind['id']);
	}
	
	// now get scheduled greetings, and update friend info
	$sql = "select userid, friendid, message, image, date from greeting where userid=?";
	$stmt = $db->prepare($sql);
	$stmt->bind_param("s",$facebookapp->userId);
	$stmt->execute();
	$stmt->bind_result($userid, $friendid, $message, $image, $date);
	while ($stmt->fetch()) {
		$index = array_search($friendid,$friendids);
		if ($index !== false) {
			$friendsAndBirthdays[$index]['message'] = $message;
			$friendsAndBirthdays[$index]['image'] = $image;
			$friendsAndBirthdays[$index]['birthday'] = $date;
		}
	}
	$stmt->close();
	
	return $friendsAndBirthdays;
}


/* 	psots greeting	 */
function postGreeting($friendid,$message,$image) {
	global $facebookapp;
	$paramarray = array(	"message"=>$message, 
							"link"=>$facebookapp->getCanvasUrl(''),
							"name"=>"Birthday Greetings",
							"caption"=>"{*actor*} sent this birthday greeting",
							"description"=>" ", // need a description or it will try to scrape page
							"picture"=>$GLOBALS['callbackUrl']."images/".$image.".jpg",
							"actions"=>array("name"=>"Send Greetings","link"=>$facebookapp->getCanvasUrl(''))
						);
	return $facebookapp->postToFeed($friendid,$paramarray);
}

?>