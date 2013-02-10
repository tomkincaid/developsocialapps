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
?>

<?php if ($GLOBALS['apptype'] == "canvas") { ?>

            </div><!--tab_content-->
        </div><!--tab-->
    </div><!--tabs-->
    
<?php } else { ?>
            
		</div><!--content-->
      
        <div data-role="footer" data-position="fixed" data-theme="b" class="navbar"><div data-role="navbar" class="navbar"><ul>
            <li><a id="birthdays" href="index.php" data-icon="custom"<?php echo getMobileActiveTab(0,$currentTab); ?>>Friends' Birthdays</a></li>
            <li><a id="send" href="schedule.php" data-icon="custom"<?php echo getMobileActiveTab(1,$currentTab); ?>>Send Greeting</a></li>
        </ul></div></div>
    
    </div><!--page-->
    
    <style type="text/css">	
		.navbar .ui-btn .ui-btn-inner { padding-top: 40px !important; }
		.navbar .ui-btn .ui-icon { width: 32px!important; height: 32px!important; margin-left: -13px !important; box-shadow: none!important; -moz-box-shadow: none!important; -webkit-box-shadow: none!important; -webkit-border-radius: 0 !important; border-radius: 0 !important; }
		#birthdays .ui-icon { background:  url(images/friends.png) 50% 50% no-repeat; background-size: 32px 32px; }
		#send .ui-icon { background:  url(images/gift.png) 50% 50% no-repeat; background-size: 32px 32px;  }
	</style>

<?php } ?>

</body></html>