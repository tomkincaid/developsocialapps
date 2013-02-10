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

class FacebookApp {               

	public $appId;
	private $appSecret;
	private $nameSpace;
	public $userId;
	public $token;
	public $tokenExpires;
	private $curlclient; // CurlClient object
	public $appType; // canvas, web, or mobile
	
	// get your own from http://www.w3.org/P3P/, leave blank if setting elsewhere in code
	public $p3p = ''; 
	
	/*	construct object 
		appid, secret, and namespace from app settings	
		type is canvas, web, or mobile */
	public function __construct($id, $secret, $namespace, $type="canvas") {
		$this->appId = $id;
		$this->appSecret = $secret;
		$this->nameSpace = $namespace;
		$this->appType = $type;
		$this->curlclient = new CurlClient();
	}
	
	/* sets token	*/
	public function setToken($token,$expiration=0) {
		$this->token = $token;
		$this->tokenExpires = $expiration;
	}
	
	/* sets token	*/
	public function setUserId($id) {
		$this->userId = $id;
	}
	
	/* sets type	*/
	public function setType($type) {
		$this->appType = $type;
	}
	
	/*  return json data from a graph api object or false 	*/
	function getGraphObject($object,$postarray=false,$cachetimeinhours=false) {
		$return = false;
		$url = $this->getGraphUrl($object);
		$response = $this->curlclient->makeCurlRequest($url,$postarray,$cachetimeinhours);
		if ($repsonse !== false) {
			$return = json_decode($response,true);
			if (isset($return['error'])) {
				$return = false;
				// the request return an error, you can debug here
			}
		}
		return $return;
	}
	
	/*  return json data from a graph api object using paging	
		$object = object to get
		limit = limit parameter for API object
		maxpages = maximum number of pages to get 	*/
	function getGraphObjectWithPaging($object,$limit=5000,$maxpages=10,$cachetimeinhours=false) {
		$data = array();
		$url = $this->getGraphUrl($object,$limit);
		// loop through API calls until maxpages or no paging->next
		while ($maxpages > 0) {
			$response = $this->curlclient->makeCurlRequest($url,false,$cachetimeinhours);
			if ($repsonse === false) {
				// something went wrong
				break;
			} else {
				$jsonarray = json_decode($response,true);
				if (isset($jsonarray['error'])) {
					// something went wrong
					break;
				} else if (isset($jsonarray['data'])) {
					// add current data to data array
					$data = array_merge ($data,$jsonarray['data']);
					if (isset($jsonarray['paging']['next'])) {
						if ($url == $jsonarray['paging']['next']) {
							// for some reason facebook sometimes returns a next url which is the same as we just got, so exit here
							break;
						} else {
							// keep looping
							$url = $jsonarray['paging']['next'];
							$maxpages--;
						}
					} else {
						// no more pages
						break;
					}
				} else { 
					// something went wrong
					break;
				}
			}
		}
		return array("data"=>$data); // using data so it is the same format as other API repsonses
	}
	
	/*	constructs graphs url	*/
	public function getGraphUrl($object,$limit=false,$offset=false) {
		$url = "https://graph.facebook.com/".$object;
		if (strpos($url,"?") === false) $url .= "?";
		else $url .= "&";
		$url .= "access_token=".$this->token;
		if ($limit !== false) $url .= "&limit=".$limit;
		if ($offset !== false) $url .= "&offset=".$offset;
		return $url;
	}
	
	/*	gets batch requests
		$urlarray = array of urls to get
		$methodarray = array of methods, GET or POST, if empty uses GET	*/
	public function getBatchRequests($urlarray,$methodarray=array(),$cachetimeinhours=false) {
		$return = false;
		$url = "https://graph.facebook.com/";
		$batch = array();
		for ($i=0; $i<count($urlarray); $i++) {
			if (!isset($methodarray[$i])) $methodarray[$i] = "GET";
			array_push($batch,array("method"=>$methodarray[$i],"relative_url"=>$urlarray[$i]));
		}
		$postarray = array("access_token"=>$this->token,"batch"=>json_encode($batch)); 
		$response = $this->curlclient->makeCurlRequest($url,$postarray,$cachetimeinhours);
		if ($response !== false) {
			$jsondata = json_decode($response,true);
			if (!isset($jsondata['error'])) {
				$return = array();
				foreach ($jsondata as $data) {
					if ($data['code'] == 200) {
						array_push($return,json_decode($data['body'],true));
					} else {
						array_push($return,false);
					}
				}
			}
		} 
		return $return;
	}
	
	/*	returns all friends	*/
	public function getAllFriends($userid="me",$cachetimeinhours=2) {
		return $this->getGraphObject($userid."/friends?limit=5000",false,$cachetimeinhours);
	}
	
	/*	wrapper in case facebook changes their signed_request	*/
	public function initUserFromReuqest() {
		return $this->initOauthUserFromSignedRequest();
	}
	
	/*  sets userid and token from signed request, return true or false if authorized	*/
	public function initOauthUserFromSignedRequest() {
		$authorized = false;
		if (isset($_REQUEST['signed_request'])) {
			$data = $this->parseSignedRequest($_REQUEST['signed_request']);
			if ($data !== false) {
				if (isset($data['user_id'])) {
					$this->userId = $data['user_id'];
					$this->token = $data['oauth_token'];
					$this->tokenExpires = $data['expires'];
					$authorized = true;
				}
			}
		}
		return $authorized;
	}
	
	/*  require user to authorize and have permissions for page
		redirect_uri = url to return after user has authorized like redirect.php
		success_uri = url to redirect to on successful authorization like mypage.php
		scope = comma separted list of permissions	
		authorized = for web or mobile apps, check outside of method and set	*/
	function requireAuthorization($redirect_uri,$success_uri=false,$scope=false,$authorized=false) {
		if ($this->appType == "canvas") {
			// for canvas apps, check for auth from signed request
			if ($this->initOauthUserFromSignedRequest()) {
				$authorized = true;
			} else {
				$authorized = false;
			}
		}
		if ($authorized) {
			// user is authenticated, so check for permissions
			if (($scope !== false) && (!$this->hasAllPermissions($scope))) {
				$authorized = false;
			}
		}
		if (!$authorized) { 
			// user is either not authorized or doesn't have permissions
			if ($success_uri === false) {
				// if no success_uri use current page, all files for app must be in same directory
				$success_uri = substr($_SERVER['REQUEST_URI'],strrpos($_SERVER['REQUEST_URI'],"/")+1); 
			}
			$this->setCookie ("success_uri",$success_uri,0); // we will use this on the redirect_uri page
			$url = $this->getAuthUrl($this->getCanvasUrl($redirect_uri),$scope);
			echo "<html>\n<body>\n<script>\ntop.location.href='".$url."';\n</script></body></html>";
            exit();
		}
	}
	
	/*	checks to see if has permissions, scope is comma separated list	*/
	public function hasAllPermissions($scope) {
		$return = false;
		$cookiename = "permissions_".$this->appId."_".$this->userId;
		$requiredpermissions = explode(",",$scope);
		// first check cookie
		if (isset($_COOKIE[$cookiename])) {
			$return = true;
			$permissions = json_decode($_COOKIE[$cookiename],true);
			foreach ($requiredpermissions as $perm) {
				if ($permissions['data'][0][$perm] != 1) {
					$return = false;
					break;
				}
			}
		}
		// if didn't have all in cookie, then see if it is in graph	
		if ($return == false) {
			$permissions = $this->getGraphObject("me/permissions");
			if ($permissions !== false) {
				$this->setCookie($cookiename,json_encode($permissions),0);
				$return = true;
				foreach ($requiredpermissions as $perm) {
					if ($permissions['data'][0][$perm] != 1) {
						$return = false;
						break;
					}
				}	
			}
		}
		return $return;
	}
	
	/* 	sets a cookie with p3p headers	*/
	public function setCookie($name,$value,$expires) {
		if ($this->p3p != '') {
			header($this->p3p);
			$this->p3p = '';
		}
		setcookie ($name,$value,$expires,"/"); 
	}

	/*	returns url for oauth authorization
	 	redirect_uri = url to return after user has authorized
		scope = comma separted list of permissions	*/
	public function getAuthUrl($redirect_uri,$scope=false) {
		$url = "https://www.facebook.com/dialog/oauth/?client_id=".$this->appId."&redirect_uri=".rawurlencode($redirect_uri);
		if ($scope !== false) $url .= "&scope=".rawurlencode($scope);
		return $url;
	}
	
	/* returns url to app canvas page, $page like mypage.php?foo=bar	*/
	public function getCanvasUrl($page) {
		if ($this->appType == "canvas") {
			if ($_SERVER['HTTPS'] == "on") $protocol = "https";
			else $protocol = "http";
			return $protocol."://apps.facebook.com/".$this->nameSpace."/".$page;
		} else {
			if ($page == "") {
				// use current directory
				$page = substr($_SERVER['REQUEST_URI'],0,strrpos($_SERVER['REQUEST_URI'],"/")+1); 
			}
			return $page;
		}
	}
	
	/*	parses signed_request parameter and returns data object, returns false if sigs don't match	*/
	public function parseSignedRequest($signed_request) {
		list($encoded_sig, $payload) = explode('.', $signed_request, 2); 
		$data = json_decode(base64_decode(strtr($payload, '-_', '+/')), true);
		$sig = base64_decode(strtr($encoded_sig, '-_', '+/'));
		$expected_sig = hash_hmac('sha256', $payload, $this->appSecret, true);
		if ($sig == $expected_sig) {
			return $data;
    	} else {
			return false;
		}
	}
	
	/* 	return signed request for current object */
	public function getSignedRequest() {
		$data = array(	'algorithm'=>'HMAC-SHA256',
						'issued_at'=>time(),
						'oauth_token'=>$this->token,
						'expires'=>$this->tokenExpires,
						'user_id'=>$this->userId );
		$json = json_encode($data);
		$b64 = str_replace('=', '', strtr(base64_encode($json), '+/', '-_'));
		$raw_sig = hash_hmac('sha256', $b64, $this->appSecret, true);
		$sig = str_replace('=', '', strtr(base64_encode($raw_sig), '+/', '-_'));
		return $sig.'.'.$b64;
	}
	
	/*	exchanges code for token	*/
	public function setAccessTokenFromCode($code,$redirecturi="") {
		$success = false;
		$url = "https://graph.facebook.com/oauth/access_token?client_id=".rawurlencode($this->appId)."&redirect_uri=".rawurlencode($redirecturi)."&client_secret=".rawurlencode($this->appSecret)."&code=".rawurlencode($code);
		$response = $this->curlclient->makeCurlRequest($url);
		if ($response !== false) {
			$success = $this->setAccessTokenFromString($response); 
		}
		return $success;
	}
	
	/*	exchanges short-lived access token for long one	*/
	public function exchangeAccessToken($token=false) {
		$success = false;
		if ($token === false) $token = $this->token;
		$url = "https://graph.facebook.com/oauth/access_token?client_id=".rawurlencode($this->appId)."&client_secret=".rawurlencode($this->appSecret)."&grant_type=fb_exchange_token&fb_exchange_token=".$token;
		$response = $this->curlclient->makeCurlRequest($url);
		if ($response !== false) {
			$success = $this->setAccessTokenFromString($response); 
		}
		return $success;
	}
	
	/*	parses the string format returned by oauth/access_token formatted like access_token=AAAFHD&expires=5181931	*/
	public function setAccessTokenFromString($string) {
		if (strpos($string,"access_token") === false) {
			return false;
		} else {
			$string = str_replace("access_token=","",rawurldecode($string));
			$string = str_replace("expires=","",$string);
			$elements = explode("&",$string);
			$this->token = $elements[0];
			$this->tokenExpires = time() + $elements[1]; // expires is seconds from now
			return true;
		}
	}
	
	/*	tries to use the cookie set by fbjs to init user	*/
	public function initUserFromFbjs() {
		$success = false;
		$fbjscookie = "fbsr_".$this->appId;
		if (isset($_COOKIE[$fbjscookie])) {
			$sr = $this->parseSignedRequest($_COOKIE[$fbjscookie]);
			if ($sr !== false) {
				if ($this->setAccessTokenFromCode($sr['code'])) {
					$this->userId = $sr['user_id'];
					$success = true;
				}
			}
		}
		return $success;
	}
	
	/*	returns url for feed dialog
		$paramarray = array ("link"=>"http:/...","name"=>"My Post"....
		"actions"=>array("name'=>"action name","link"=>"http://...")
		"properties"=>array(array("key1"=>array("text"=>"text1","href"=>"http://..."),"key2"=>array(...))
		for all params see https://developers.facebook.com/docs/reference/dialogs/feed/	*/
	function getFeedDialogUrl($paramarray) {
		$url = "https://www.facebook.com/dialog/feed?app_id=".$this->appId;
		foreach ($paramarray as $key => $value) {
			if (($key == "actions") || ($key == "properties")) {
				$url .= "&".$key."=".rawurlencode(json_encode($value));
			} else {
				$url .= "&".$key."=".rawurlencode($value);
			}
		}
		return $url;
	}
	
	/*	post to users feed
		$userid = id of feed/wall to post to
		$paramarray = array ("link"=>"http:/...","name"=>"My Post"....
		"actions"=>array("name'=>"action name","link"=>"http://...")
		"properties"=>array(array("key1"=>array("text"=>"text1","href"=>"http://..."),"key2"=>array(...))
		for all params see https://developers.facebook.com/docs/reference/dialogs/feed/	
		USERID MUST BE "ME" OR ID OF AUTHENTICATED USER */
	function postToFeed($userid,$paramarray) {
		$resp = false;
		if (($userid == "me") || ($userid == $this->userId)) {
			$object = $userid."/feed";
			$postarray = array("app_id"=>$this->appId);
			foreach ($paramarray as $key => $value) {
				if (($key == "actions") || ($key == "properties")) {
					$postarray[$key] = json_encode($value);
				} else {
					$postarray[$key] = $value;
				}
			}
			$resp = $this->getGraphObject($object,$postarray);
		}
		return $resp;
	}
}
        
?>