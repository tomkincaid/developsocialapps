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

class CurlClient {

	private $useragent = "my ua"; // set this to your own user agent
	private $cache; // cache object             
	
	public function __construct($namespace="curlclient") {
		$this->cache = new Cache($namespace);
	}
	
	/*  gets or posts request with cachtime 	*/
	public function makeCurlRequest($url,$postarray=false,$cachetimeinhours=false) {
		$return = false;
		if ($cachetimeinhours !== false) {
			$cacheid = $this->getCacheId($url,$postarray); // get id
			$return = $this->cache->getCacheVal($cacheid); // get from cache
		}
		if ($return === false) { 
			// not chaching or not in cache, so make request
			$return = $this->makeRequestWithoutCache($url,$postarray);
			if (($cachetimeinhours !== false) && ($return !== false)) {
				$this->cache->setCacheVal($cacheid,$return,false,"+".$cachetimeinhours." hours"); // save cache
			}
		}
		return $return;
	}
	
	/*  gets a url, use $postarray to make a post, otherwise it will get	*/
	public function makeRequestWithoutCache($url,$postarray=false) {
		$return = false;
		try {
		    $ch = curl_init(); 
			curl_setopt($ch, CURLOPT_URL, $url);
			curl_setopt($ch, CURLOPT_HEADER, false); 
			curl_setopt($ch, CURLOPT_RETURNTRANSFER, true); 
			curl_setopt($ch, CURLOPT_USERAGENT, $this->useragent); 
			if($postarray !== false){ 
				curl_setopt ($ch, CURLOPT_POST, true); 
				curl_setopt ($ch, CURLOPT_POSTFIELDS, $postarray); 
			} 
			$response = curl_exec($ch); 
			$responseInfo = curl_getinfo($ch); 
			curl_close($ch); 
			if ($responseInfo['http_code']==200) { 
				$return = $response; 
			} 
		} catch (Exception $e) {
			$return = false; 
		}
		return $return;
    } 
	
	/*	gets cache id from url and postarry with md5	*/
	public function getCacheId($url,$postarray=false) {
		if ($postargs !== false) $url .= json_encode($postarray);
		return md5($url);
	}	
	
}


?>