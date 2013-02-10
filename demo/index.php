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

$authorized = initFacebookApp(false);

//print_r($_SERVER);

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>Tom's Apps Demo</title>
</head>
<body>
<div id="fb-root"></div>
<script>
  window.fbAsyncInit = function() {
    FB.init({
      appId      : '<?php echo $GLOBALS['facebookAppId']; ?>', // App ID
      status     : true, // check login status
      cookie     : true, // enable cookies to allow the server to access the session
      xfbml      : true  // parse XFBML
    });

    // Additional initialization code here
  };

  // Load the SDK Asynchronously
  (function(d){
     var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
     if (d.getElementById(id)) {return;}
     js = d.createElement('script'); js.id = id; js.async = true;
     js.src = "//connect.facebook.net/en_US/all.js";
     ref.parentNode.insertBefore(js, ref);
   }(document));
</script>
<h1>Tom's Apps Demo</h1>

<?php 
if ($authorized) {
	echo "<p>Logged in as ".$user->displayName." - <a href='logout.php'>Log Out</a></p>";
} else {
	echo "<p><a href='".$facebookapp->getAuthUrl($GLOBALS['webRedirectUrl'],$GLOBALS['facebookPermissions'])."'>Log In with Facebook</a></p>";
}
?>

<p><a href="<?php echo $facebookapp->getCanvasUrl('myinfo.php'); ?>" target="_top">My Info</a></p>
<p><a href="<?php echo $facebookapp->getCanvasUrl('myfriends.php'); ?>" target="_top">My Friends</a></p>
<p><a href="<?php echo $facebookapp->getCanvasUrl('myfriendslocation.php'); ?>" target="_top">My Friends Location</a></p>
<p><a href="<?php echo $facebookapp->getCanvasUrl('feed.php'); ?>" target="_top">Feed Publishing</a></p>
<p><a href="<?php echo $facebookapp->getCanvasUrl('token.php'); ?>" target="_top">Long-Lived Token</a></p>
</body>
</html>
<?php closeObjects(); ?>