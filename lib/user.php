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

class User {               

	public $displayName = "Anonymous";
	public $facebookId;
	public $facebookToken;
	public $facebookExpiration;
	
	private $cookieName;
	private $cookieExpiration = 2592000; // set expiration in seconds
	private $version = 1; // for forward compatibility
	private $encryptionkey = "somerandomstring"; // for encryption
	
	/* 	object needs a unique id, so apps on same domain don't conflict */
	public function __construct($id="") {
		$this->cookieName = "user_".$id;
	}
	
	/* 	sets display name */
	public function setDisplayName($name) {
		$this->displayName = $name;
	}
	
	/* 	sets facebook info */
	public function setFacebook($id,$token,$expiration,$name=false) {
		$this->facebookId = $id;
		$this->facebookToken = $token;
		$this->facebookExpiration = $expiration;
		if ($name !== false) $this->displayName = $name;
	}
	
	/* 	save data in cookie	*/
	public function setCookie() {
		$jsondata = json_encode(array(	"version"=>$this->version,
										"displayName"=>$this->displayName,
										"facebookId"=>$this->facebookId,
										"facebookToken"=>$this->facebookToken,
										"facebookExpiration"=>$this->facebookExpiration));
		setcookie ($this->cookieName,$this->encrypt($jsondata),time()+$this->cookieExpiration,"/");
	}
	
	/* 	uses cookie to set info	*/
	public function getCookie() {
		$ret = false;
		if (isset($_COOKIE[$this->cookieName])) {
			$data = json_decode($this->decrypt($_COOKIE[$this->cookieName]),true);
			if ($data['version'] == $this->version) {
				$this->displayName = $data['displayName'];
				$this->facebookId = $data['facebookId'];
				$this->facebookToken = $data['facebookToken'];
				$this->facebookExpiration = $data['facebookExpiration'];
				$ret = true;
			}
		}
		return $ret;
	}
	
	/* 	removes the cookie	*/
	public function clearCookie() {
		setcookie ($this->cookieName,"",-99999,"/");
	}
	
	/* 	to encrypt and decrypt text 	*/
	public function encrypt($text) {
		return trim( base64_encode (mcrypt_encrypt (MCRYPT_RIJNDAEL_128, $this->encryptionkey, $text, MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND))));
	}
	public function decrypt($text) {
		return trim( mcrypt_decrypt (MCRYPT_RIJNDAEL_128, $this->encryptionkey, base64_decode($text), MCRYPT_MODE_ECB, mcrypt_create_iv(mcrypt_get_iv_size(MCRYPT_RIJNDAEL_128, MCRYPT_MODE_ECB), MCRYPT_RAND)));
	}
	
}