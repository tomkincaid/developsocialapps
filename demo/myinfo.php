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

$authorized = initFacebookApp(true,"user_about_me,user_likes");

?><!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.1//EN" "http://www.w3.org/TR/xhtml11/DTD/xhtml11.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>My Info</title>
</head>
<body>
<pre>
<?php 

//print_r($_COOKIE);
//print_r($_SERVER);

echo "Hello, user #".$facebookapp->userId.", here is what I know about you:\n\n";
    
$info = $facebookapp->getGraphObject("me"); 
    
print_r($info);

?>
</pre>
<p><a href="<?php echo $facebookapp->getCanvasUrl(''); ?>" target="_top">Home</a></p>
</body>
</html>
<?php closeObjects(); ?>