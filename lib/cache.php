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

class Cache {

	/* 	this is the path to a sqlite database with a single table
		CREATE TABLE "cache" ("id" VARCHAR PRIMARY KEY  NOT NULL , "val" BLOB, "expire" INTEGER)
		if not using caching set this to false	*/
	public $dbpath = "/path/to/cache.sqlite";
	
	/*	if running a cron to clear the cache, set to true	*/
	public $usingcron = false;

	/*	used to cahce multtiple apps, leave blank here	*/
	public $prefix = "";    
	
	/*	sqlite db object */
	public $db;
	
	public function __construct($prefix) {
		$this->prefix = $prefix;
		try {
  			$this->db = new SQLiteDatabase($this->dbpath, 0666, $error);
		} catch(Exception $ex) {
			// something went wrong, can't use caching
			$dbpath = false;
		}
	}
	
	/* 	returns full id with prefix	*/
	public function getId($id) {
		return $this->prefix."_".$id;
	}
	
	/*	gets value from cache or false, optionally decode json	*/
	public function getCacheVal($id,$jsondecode=false) {
		$val = false;
		if ($dbpath !== false) {
			if (!$this->usingcron) $this->clearCache(); // if not using cron, clear the cache on each query
			try {
				$sql = "select val from cache where id='".sqlite_escape_string($this->getId($id))."'";
				if ($result = $this->db->query($sql)) {
					while ($row = $result->fetch(SQLITE_ASSOC)) {
						if ($jsondecode) {
							$val = json_decode($row['val'],true);
						} else {
							$val = $row['val'];
						}
					}
				}
			} catch (Exception $ex) {
				$val = false;
			}
		}
		return $val;
	}
	
	/*	sets val for id in cache, expire is time from now to expire, optionally encode json	*/
	public function setCacheVal($id,$val,$jsonencode=false,$expire="+24 hours") {
		$set = false;
		if ($dbpath !== false) {
			$expiretime = strtotime($expire);
			try {
				if ($jsonencode) $val = json_encode($val);
				$sql = "insert into cache(id,val,expire) values('".sqlite_escape_string($this->getId($id))."','".sqlite_escape_string($val)."',".$expiretime.")";
				@$this->db->query($sql);
				$set = true;
			} catch (Exception $ex) {
				$set = false;
			}
		}
		return $set;
	}
	
	/*	clear an id from cache	*/
	function clearCacheId($id) {
		if ($time === false) $time = time();
		$sql = "delete from cache where id='".sqlite_escape_string($this->getId($id))."'";
		$this->db->query($sql);
	}
	
	/*	clears with expire before time	*/
	function clearCache($time=false) {
		if ($time === false) $time = time();
		$sql = "delete from cache where expire<".$time;
		$this->db->query($sql);
	}
	
}
?>