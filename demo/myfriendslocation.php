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

$authorized = initFacebookApp(true,"friends_location,publish_stream,manage_notifications");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>My Friends Location</title>
</head>
<body onload="showMap();">
<?php 

$cacheid = "friendlocation_".$facebookapp->userId; // create unique cache id

$locationarray = $cache->getCacheVal($cacheid,true); // try to get value from cache

if ($locationarray !== false) {
	
	echo "<p>got friends locations from cache<p>";
	
} else {
	
	echo "<p>no cache, so getting location from API<p>";

	$locationarray = array(); // where we'll store our info
	
	// get all friends and use batch to get their locations
	
	$friends = $facebookapp->getGraphObject("me/friends?limit=100"); 
	
	$urlarray = array();
	
	foreach ($friends['data'] as $friend) {
		
		array_push($urlarray,$friend['id']);
		
		if (count($urlarray) == 50) {
			
			$batchrepsonse = $facebookapp->getBatchRequests($urlarray);
		
			foreach ($batchrepsonse as $friend) {
				
				if (isset($friend['location'])) {
					
					// has location so add to locationsarray
					
					if (isset($locationarray[$friend['location']['id']]['friends'])) {
						
						array_push($locationarray[$friend['location']['id']]['friends'],$friend['name']);
						
					} else {
					
						$locationarray[$friend['location']['id']]['friends'] = array($friend['name']);
					}
				}
				
			}
			
			$urlarray = array();
			
		}
	}
	
	if (count($urlarray) > 0) {
			
		$batchrepsonse = $facebookapp->getBatchRequests($urlarray);
			
		foreach ($batchrepsonse as $friend) {
				
			if (isset($friend['location'])) {
					
				// has location so add to locationsarray
					
				if (isset($locationarray[$friend['location']['id']]['friends'])) {
						
					array_push($locationarray[$friend['location']['id']]['friends'],$friend['name']);
						
				} else {
					
					$locationarray[$friend['location']['id']]['friends'] = array($friend['name']);
				}
			}
			
		}
	}
	
	
	// now loop through locationarray and save info for each location
	
	$urlarray = array();
	
	foreach ($locationarray as $locationid => $info) {
		
		array_push($urlarray,$locationid);
		
		if (count($urlarray) == 50) {
			
			$batchrepsonse = $facebookapp->getBatchRequests($urlarray);
			
			foreach ($batchrepsonse as $location) {
		
				$locationarray[$location['id']]['lat'] = $location['location']['latitude'];
				$locationarray[$location['id']]['lon'] = $location['location']['longitude'];
				$locationarray[$location['id']]['name'] = $location['name'];
			}
			
			$urlarray = array();
			
		}
	}
	
	if (count($urlarray) > 0) {
			
		$batchrepsonse = $facebookapp->getBatchRequests($urlarray);
			
		foreach ($batchrepsonse as $location) {
		
			$locationarray[$location['id']]['lat'] = $location['location']['latitude'];
			$locationarray[$location['id']]['lon'] = $location['location']['longitude'];
			$locationarray[$location['id']]['name'] = $location['name'];
		}
			
	}
	
	// set cache with json encoded array
	$cache->setCacheVal($cacheid,$locationarray,true,"+5 minutes");
}
?>

<div id="map_canvas" style="width:760px;height:380px;"></div>

<script type="text/javascript" src="http://maps.googleapis.com/maps/api/js?sensor=false"></script>

<script type="text/javascript">

var data = <?php echo json_encode($locationarray); ?>; // set data to json value of $locationarray

var markers = new Array(); // keep track of markers

var map; // map object

function showMap() {
	
	// initialize map to show whole world
	var mapOptions = { center: new google.maps.LatLng(0,0), zoom: 1, mapTypeId: google.maps.MapTypeId.TERRAIN };
	map = new google.maps.Map(document.getElementById("map_canvas"), mapOptions);
	
	// loop through data and add a marker for each location
	for (locationid in data) {
		addMarker(data[locationid].lat, data[locationid].lon, data[locationid].name, data[locationid].friends);
	}

}

function addMarker(lat,lon,name,friends) {
	// create a new marker and add it to the map with an info window
	var marker = new google.maps.Marker({ position: new google.maps.LatLng(lat,lon), map: map, title:name });
	var html = "<div style='font-size:11px;'><strong>"+name+"</strong><br/>"+friends.join("<br/>");
	var infowindow = new google.maps.InfoWindow({content: html });
	google.maps.event.addListener(marker, 'click', function() { 
		infowindow.open(map, marker); 
	});
}

</script>

<p><a href="<?php echo $facebookapp->getCanvasUrl(''); ?>" target="_top">Home</a></p>
</body>
</html>
<?php closeObjects(); ?>