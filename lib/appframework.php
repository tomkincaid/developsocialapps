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

require_once("cache.php");
require_once("curlclient.php");
require_once("facebookapp.php");
require_once("user.php");

$GLOBALS['apptype'] = "canvas";


/*	initilaize an app
	$requireauth = true or false to require authorization
	$additionalpermissions = comma separated list of permissions	*/
function initFacebookApp($requireauth=false,$additionalpermissions=false) {
	
	global $facebookapp, $cache, $db, $user;
	
	// set p3p header, get your own from http://www.w3.org/P3P/
	header('P3P:CP="IDC DSP COR ADM DEVi TAIi PSA PSD IVAi IVDi CONi HIS OUR IND CNT"');
	
	// set app type
	$typecookiename = "apptype_".$GLOBALS['facebookAppId'];
	if (isset($_GET['apptype'])) {
		// type explicitly set in query string
		$GLOBALS['apptype'] = $_GET['apptype'];
	} else if (isset($_REQUEST['signed_request'])) {
		// only canvas apps get a signed request
		$GLOBALS['apptype'] = "canvas";
	} else if (isMobileUseragent()) {
		// mobile
		$GLOBALS['apptype'] = "mobile";
	} else if (isset($_COOKIE[$typecookiename])) {
		// type previously set in cookie, last because can get stuck if using same browser
		$GLOBALS['apptype'] = $_COOKIE[$typecookiename];
	} else {
		// default web
		$GLOBALS['apptype'] = "web";
	}
	
	// set cookie if apptype is different
	if (($GLOBALS['apptype'] != $_COOKIE[$typecookiename]) && ($GLOBALS['apptype'] != "canvas")) {
		setcookie ($typecookiename,$GLOBALS['apptype'],0,"/");
	}
	
	// initialize objects with app type
	initObjects($GLOBALS['apptype']);
	
	// fix for cookies in iframe for safari
	if (isset($_GET['setdefaultcookie'])) {
		// top level page, set default cookie then redirect back to canvas page
		setcookie ('default',"1",0,"/");
		$url = substr($_SERVER['REQUEST_URI'],strrpos($_SERVER['REQUEST_URI'],"/")+1);
		$url = str_replace("setdefaultcookie","defaultcookieset",$url);
		$facebookapp->setType("canvas");
		$url = $facebookapp->getCanvasUrl($url);
		doRedirect($url);
	} else if (($GLOBALS['apptype'] == "canvas") && (stripos($_SERVER['HTTP_USER_AGENT'],"safari") !== false) && (!isset($_COOKIE['default'])) && (!isset($_GET['defaultcookieset']))) {
		// no default cookie, so we need to redirect to top level and set
		$url = $_SERVER['REQUEST_URI'];
		if (strpos($url,"?") === false) $url .= "?";
		else $url .= "&";
		$url .= "setdefaultcookie=1";
		doRedirect($url);
	}
	
	// set permissisons
	if ($additionalpermissions !== false) $GLOBALS['facebookPermissions'] .= ",".$additionalpermissions;
	
	// if auth required, do redirect to facebook dialog
	if ($GLOBALS['apptype'] == "canvas") {
		// for canvas apps, use facebookapp object 
		if ($requireauth) {
			$facebookapp->requireAuthorization($GLOBALS['facebookRedirectPage'], false, $GLOBALS['facebookPermissions']);
			$authorized = true; // can't get here unless app is authorized with permissions
		} else {
			$authorized = $facebookapp->initUserFromReuqest();
		}
	} else {
		// for web and mobile, use user cookie to set facebookapp object
		$authorized = $user->getCookie();
		if ($authorized) {
			if ($user->facebookExpiration < time()) {
				// token expired, remove cookie
				$user->clearCookie();
				$authorized = false;
			} else {
				// use user cookie to set facebookapp object
				$facebookapp->setToken($user->facebookToken,$user->facebookExpiration);
				$facebookapp->setUserId($user->facebookId);
			}
		}
		if ($requireauth) {
			// go back to url for current page
			$success_uri = $_SERVER['SCRIPT_URI']."?".$_SERVER['QUERY_STRING'];
			// use facebookapp object to check for cookie and send to auth dialog
			$facebookapp->requireAuthorization($GLOBALS['webRedirectUrl'], $success_uri, $GLOBALS['facebookPermissions'], $authorized);
		}
	}
	
	// authenticated referrals
	if ($GLOBALS['apptype'] == "canvas") {
		// canvas apps, if authrorized, check for the cookie
		$cookiename = "initialized_".$GLOBALS['facebookAppId']."_".$facebookapp->userId;
		if ($authorized && (stripos($_SERVER['REQUEST_URI'],$GLOBALS['facebookRedirectPage']) === false) && (!isset($_COOKIE[$cookiename]))) {
			// no cookie, check if user is in db
			$initialize = true;
			$sql = "select userid from user where userid='".$db->real_escape_string($facebookapp->userId)."'";
			$result = $db->query($sql);
			if ($row = $result->fetch_assoc()) {
				$initialize = false;
			}
			if ($initialize) {
				// user came from authenticated referral, so send to redirect page to initialize
				$success_uri = substr($_SERVER['REQUEST_URI'],strrpos($_SERVER['REQUEST_URI'],"/")+1); 
				setcookie ("success_uri",$success_uri,0,"/");
				$url = $facebookapp->getCanvasUrl($GLOBALS['facebookRedirectPage']."?code=1"); // need to add code so the page works
				doRedirect($url);
			} else {
				// user is in db, so set cookie
				setcookie ($cookiename,"1",0,"/");
			}
		}
	} else {
		// web and mobile apps will get ?code= from link, send to rediret page
		if (isset($_GET['code']) && (stripos($_SERVER['REQUEST_URI'],$GLOBALS['facebookRedirectPage']) === false) && ($_GET['fb_source'] != "notification")) {
			$successuri = $_SERVER['SCRIPT_URI'];
			$delimiter = "?";
			foreach ($_GET as $key=>$val) {
				if ($key == "code") {
					break;
				} else {
					$successuri .= $delimiter.$key."=".rawurlencode($val);
					$delimiter = "&";
				}
			}
			setcookie ("success_uri",$successuri,0,"/");
			$url = $GLOBALS['webRedirectUrl']."?redirecturi=".rawurlencode($successuri)."&code=".rawurlencode($_GET['code']);
			doRedirect($url);
		}
	}
	
	return $authorized;
}

/* detects mobile browsers */
function isMobileUseragent() {
	$regex = array(	"android",
					"blackberry",
					"(iphone|ipod|ipad)",
					"opera mini",
					"(avantgo|blazer|elaine|hiptop|palm|plucker|xiino)",
					"windows ce; (iemobile|ppc|smartphone)",
					"(kindle|mobile|mmp|midp|o2|pda|pocket|psp|symbian|smartphone|treo|up.browser|up.link|vodafone|wap)",
					"(wap.|.wap)");
	for ($i=0; $i<count($regex); $i++) {
		if(preg_match("/".$regex[$i]."/i",$_SERVER["HTTP_USER_AGENT"])) {
			return true;
		}
	}
	return false;
}

/*	initialize objects needed for app	*/
function initObjects($apptype="canvas") {
	global $facebookapp, $cache, $db, $user;
	$user = new User($GLOBALS['facebookAppId']);
	$facebookapp = new FacebookApp($GLOBALS['facebookAppId'],$GLOBALS['facebookAppSecret'],$GLOBALS['facebookNamespace'],$apptype);
	$cache = new Cache($GLOBALS['facebookNamespace']);
	$db = new mysqli($GLOBALS['dbHost'],$GLOBALS['dbUser'],$GLOBALS['dbPassword'],$GLOBALS['dbName']);
	$db->set_charset('utf8'); // make sure it's utf 8
}

/* closes objects from initObjects */
function closeObjects() {
	global $db;
	$db->close();
}

/*	redirect to ur using javascript	*/
function doRedirect($url) {
	//echo $url;
	echo "<html>\n<body>\n<script>\ntop.location.href='".$url."';\n</script></body></html>";
	exit();
}

function getMobileActiveTab($tabNumber,$currentTab) {
	if  ($tabNumber == $currentTab) return " class='ui-btn-active'";
	else return "";
}

/*	sets a long-lived access token in a cookie and updates the facebookapp object to use this token	*/
function requireLongAccessToken($additionalpermissions=false,$url=false) {
	global $facebookapp;
	
	if ($GLOBALS['apptype'] == "canvas") {

		// only need to check for canvas, web and mobile will get long token in server-side flow
	
		$haslongtoken = false;
		$tokencookie = "long_access_token_".$facebookapp->appId."_".$facebookapp->userId;
		if ($url === false) $url = substr($_SERVER['REQUEST_URI'],strrpos($_SERVER['REQUEST_URI'],"/")+1); 
		
		if (isset($_COOKIE[$tokencookie])) {
			// get long access token from cookie
			if ($facebookapp->setAccessTokenFromString($_COOKIE[$tokencookie])) {
				$haslongtoken = true;
			} else {
				setcookie ($tokencookie,"",-999,"/"); // somethng went wrong, remove cookie
			}
		
		} else if (isset($_GET['code'])) {
			// page has been redirected from server-side flow
			if ($facebookapp->setAccessTokenFromCode($_GET['code'],$facebookapp->getCanvasUrl($url))) {
				// should have long cookie
				$expires = $facebookapp->tokenExpires - time();
				if ($expires > 600000) {
					setcookie ($tokencookie,"access_token=".$facebookapp->token."&expires=".$expires,0,"/"); 
					$haslongtoken = true;
				}
			}
		}
		
		if (!$haslongtoken) {
			// no token yet, so try to get from fbjs
			if ($facebookapp->initUserFromFbjs()) {
				// exchange that token for long token
				if ($facebookapp->exchangeAccessToken()) {
					// should have long token now
					$expires = $facebookapp->tokenExpires - time();
					if ($expires > 600000) {
						setcookie ($tokencookie,"access_token=".$facebookapp->token."&expires=".$expires,0,"/"); 
						$haslongtoken = true;
					}
				}
			}
		}
		
		if (!$haslongtoken) {
			// still don't have token, now try to get from server-side login			
			$scope = $GLOBALS['facebookPermissions'];
			if ($additionalpermissions !== false) $scope .= ",".$additionalpermissions;
			$url = $facebookapp->getAuthUrl($facebookapp->getCanvasUrl($url),$scope);
			doRedirect($url);
		}
		
	}
}

/* 	updates user table
	$apidata=array("column"=>"api_index","email"=>"email") data that needs graph api to "me"
	$additionaldata=array("column"=>"value","name"=>"Tom")	additional data to put in user table 	*/
function updateUserData($apidata=array("email"=>"email"),$additionaldata=array()) {
	global $facebookapp, $db;
	$now = getDbFormattedDate();
	if (count($apidata) > 0) {
		$userinfo = $facebookapp->getGraphObject("me");
		if ($userinfo === false) {
			// something went wrong so clear cookies and go to home page 
			clearAppCookies();
			doRedirect($facebookapp->getCanvasUrl(""));
		} else {
			foreach ($apidata as $column => $index) {
				if (isset($userinfo[$index])) {
					if (($index != "email") || (strpos($userinfo[$index],"@") !== false)) {
						$additionaldata[$column] = $userinfo[$index];
					}
				}
			}
		}
	}
	$sql = "insert into user (userid, token, tokenexpiration, added, active";
	foreach ($additionaldata as $column => $value) $sql .= ", ".$column;
	$sql .= ") values ('".$db->real_escape_string($facebookapp->userId)."', '".$db->real_escape_string($facebookapp->token)."', '".$db->real_escape_string($facebookapp->tokenExpires)."', '".$db->real_escape_string($now)."', '".$db->real_escape_string($now)."'";
	foreach ($additionaldata as $column => $value) $sql .= ", '".$db->real_escape_string($value)."'";
	$sql .= ") on duplicate key update token='".$db->real_escape_string($facebookapp->token)."', tokenexpiration='".$db->real_escape_string($facebookapp->tokenExpires)."', removed='0000-00-00 00:00:00', active='".$db->real_escape_string($now)."'";
	foreach ($additionaldata as $column => $value) $sql .= ", ".$column."='".$db->real_escape_string($value)."'";
	$db->query($sql);
}

/*	returns date formatted for databse	*/
function getDbFormattedDate($time=false) {
	if ($time === false) $time = time();
	return date("Y-m-d H:i:s",$time);
}

/*	clears all cookies used by the app	*/
function clearAppCookies() {
	global $facebookapp;
	setcookie ("long_access_token_".$facebookapp->appId."_".$facebookapp->userId,"",-99999,"/");
	setcookie ("permissions_".$facebookapp->appId."_".$facebookapp->userId,"",-99999,"/");
}

/* 	includes fbjs library
	$status = access to user login status
	$cookie = set facebook signed request cookie
	$xfbml = parse xfbml on page
	$autogrow = set canvas to autogrow	*/
function includeFbjs($status=false,$cookie=false,$xfbml=false,$autogrow=true) { 
?><div id="fb-root"></div>
	<script>
    window.fbAsyncInit = function() {
        FB.init({
            appId	: '<?php echo $GLOBALS['facebookAppId']; ?>',
            status	: <?php echo ($status) ? "true" : "false"; ?>, 
            cookie	: <?php echo ($cookie) ? "true" : "false"; ?>, 
            xfbml	: <?php echo ($xfbml) ? "true" : "false"; ?>, 
        });
		<?php if ($autogrow) echo "FB.Canvas.setAutoGrow();" // set canvas height to content automatically ?>
    };
    (function(d){
        var js, id = 'facebook-jssdk', ref = d.getElementsByTagName('script')[0];
        if (d.getElementById(id)) {return;}
        js = d.createElement('script'); js.id = id; js.async = true;
        js.src = "//connect.facebook.net/en_US/all.js";
        ref.parentNode.insertBefore(js, ref);
    }(document));
    </script>
<?php
}



?>
