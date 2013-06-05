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

$authorized = initFacebookApp(true,"read_stream,publish_stream");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Feed</title>
      <meta property="og:url" content="http://www.tomsapps.com/demo/feed.php">
    <meta property="og:title" content="tttt">
    <meta property="og:description" content="dddd">
    <meta property="og:image" content="https://www.tomsapps.com/demo/images/logo.gif">
      <meta property="og:video" content="https://www.tomsapps.com/demo/images/movie.swf">
      <meta property="og:video:type" content="application/x-shockwave-flash">
      <meta property="og:video:width" content="398">
      <meta property="og:video:height" content="224">
    <meta property="og:site_name" content="Tom's Apps Demo">
    <meta property="fb:app_id" content="371610252910187">
</head>
<body>
<h1>Feed Publishing</h1>




<h2>Feed Dialog URL</h2>

<?php
if (isset($_GET['post_id'])) {
	$postinfo = $facebookapp->getGraphObject($_GET['post_id']);
	echo "<pre>Here is what you posted:\n\n";
	print_r($postinfo);
	echo "</pre>";
}
?>

<?php
$imageurl = "http://".$_SERVER['HTTP_HOST'].substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],"/"))."/images/logo.gif";
$paramarray = array(	"redirect_uri"=>$facebookapp->getCanvasUrl('feed.php'), // back to this page
						"link"=>$facebookapp->getCanvasUrl(''),
						"picture"=>$imageurl,
						"name"=>"How to Develop Facebook Applications",
						"caption"=>"{*actor*} is learning how to develop apps",
						"description"=>"Learn how to develop Facebook apps with code samples and an open source PHP library.",
						"properties"=>array(	"1."=>array("text"=>"My Info","href"=>$facebookapp->getCanvasUrl('myinfo.php')),
												"2."=>array("text"=>"My Friends","href"=>$facebookapp->getCanvasUrl('myinfo.php')),
												"3."=>array("text"=>"Publishing","href"=>$facebookapp->getCanvasUrl('feed.php'))),
						"actions"=>array("name"=>"See Demos","link"=>$facebookapp->getCanvasUrl(''))
					);
?>
<p><a href="<?php echo $facebookapp->getFeedDialogUrl($paramarray); ?>" target="_top">Post Something</a></p>

<?php
$paramarray = array(	"redirect_uri"=>$facebookapp->getCanvasUrl('feed.php'), // back to this page
						"link"=>"http://developfacebookapps.com");
?>
<p><a href="<?php echo $facebookapp->getFeedDialogUrl($paramarray); ?>" target="_top">Post with Just Link</a></p>


<h2>Graph API Post</h2>

<?php

if (isset($_POST['message'])) {
	
	// form was posted so post video to friend
	
	// movie needs to be https
	$source = "https://".$_SERVER['HTTP_HOST'].substr($_SERVER['SCRIPT_NAME'],0,strrpos($_SERVER['SCRIPT_NAME'],"/"))."/images/movie.swf";
	
	$paramarray = array(	"message"=>$_POST['message'], 
							"link"=>$facebookapp->getCanvasUrl(''),
							"name"=>"Test Movie",
							"caption"=>"Posted from Tom's Demo App",
							"description"=>"This is an example of a feed post with a Flash movie posted using the Graph API",
							"picture"=>$imageurl, // same as before
							"source"=>$source,
							"actions"=>array("name"=>"See Demos","link"=>$facebookapp->getCanvasUrl(''))
						);
	
	$id = $facebookapp->postToFeed("me",$paramarray);

	if ($id !== false) {
		$postinfo = $facebookapp->getGraphObject($id['id']);
		echo "<pre>Here is what you posted:\n\n";
		print_r($postinfo);
		echo "</pre>";
	}
	
}

?>

<form action="" method="post">
<p>Message: <input type="text" name="message"/></p>
<input type="hidden" name="signed_request" value="<?php echo htmlspecialchars($_REQUEST['signed_request'],ENT_QUOTES); ?>"/>
<p><input type="submit" value="Post"/></p>
</form>

<p><a href="<?php echo $facebookapp->getCanvasUrl(''); ?>" target="_top">Home</a></p>
</body>
</html>
<?php closeObjects(); ?>