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

initObjects();

if ($facebookapp->initOauthUserFromSignedRequest()) {
	// singed request was good, so now set removed to current datetime
	$sql = "update user set removed='".date("Y-m-d H:i:s")."' where userid=?";
	$stmt = $db->prepare($sql);
	$stmt->bind_param("s", $facebookapp->userId);
	$stmt->execute();
	$stmt->close();
	$db->close();
}

closeObjects();

?>
