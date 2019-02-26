<?php
  use Expresso\Core\GlobalService;

  /**************************************************************************\
  * eGroupWare API - Translation class for phpgw lang files                  *
  * This file written by Miles Lott <milosch@phpgroupware.org>               *
  * Handles multi-language support using flat files                          *
  * -------------------------------------------------------------------------*
  * This library is part of the eGroupWare API                               *
  * http://www.egroupware.org/api                                            * 
  * ------------------------------------------------------------------------ *
  * This library is free software; you can redistribute it and/or modify it  *
  * under the terms of the GNU Lesser General Public License as published by *
  * the Free Software Foundation; either version 2.1 of the License,         *
  * or any later version.                                                    *
  * This library is distributed in the hope that it will be useful, but      *
  * WITHOUT ANY WARRANTY; without even the implied warranty of               *
  * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.                     *
  * See the GNU Lesser General Public License for more details.              *
  * You should have received a copy of the GNU Lesser General Public License *
  * along with this library; if not, write to the Free Software Foundation,  *
  * Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA            *
  \**************************************************************************/


	// This should be considered experimental.  It works, at the app level.
	// But, for admin and prefs it really slows things down.  See the note
	// in the translate() function.
	// To use, set GlobalService::get('phpgw_info')['server']['translation_system'] = 'file'; in
	// class.translation.inc.php
	class translation
	{
		var $langarray;   // Currently loaded translations
		var $loaded_apps = array(); // Loaded app langs

		/*!
		@function translate
		@abstract Translate phrase to ufser selected lang
		@param $key  phrase to translate
		@param $vars vars sent to lang function, passed to us
		*/
		function translation($appname='phpgwapi',$loadlang='')
		{
			global $lang;
			if($loadlang)
			{
				$lang = $loadlang;
			}
			$this->add_app($appname,$lang);

			if(!isset(GlobalService::get('phpgw_setup')))
			{
				$this->system_charset = GlobalService::get('phpgw_info')['server']['system_charset'];
			}
			else
			{
				GlobalService::get('phpgw')->db->query("SELECT config_value FROM phpgw_config WHERE config_app='phpgwapi' AND config_name='system_charset'",__LINE__,__FILE__);
				if(GlobalService::get('phpgw')->db->next_record())
				{
					$this->system_charset = GlobalService::get('phpgw')->db->f(0);
				}
			}
		}

		function init()
		{
			// post-nuke and php-nuke are using GlobalService::get('lang') too
			// but not as array!
			// this produces very strange results
			if (!is_array(GlobalService::get('lang')))
			{
				GlobalService::get('lang') = array();
			}

			if (GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'])
			{
				$this->userlang = GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'];
			}
			$this->add_app('common');
			if (!count(GlobalService::get('lang')))
			{
				$this->userlang = 'en';
				$this->add_app('common');
			}
			$this->add_app(GlobalService::get('phpgw_info')['flags']['currentapp']);
		}
		/*
		@function charset
		@abstract returns the charset to use (!$lang) or the charset of the lang-files or $lang
		*/
		function charset($lang=False)
		{
			if($lang)
			{
				if(!isset($this->charsets[$lang]))
				{
					GlobalService::get('phpgw')->db->query("SELECT content FROM phpgw_lang WHERE lang='$lang' AND message_id='charset' AND app_name='common'",__LINE__,__FILE__);
					$this->charsets[$lang] = GlobalService::get('phpgw')->db->next_record() ? strtolower(GlobalService::get('phpgw')->db->f(0)) : 'iso-8859-1';
				}
				return $this->charsets[$lang];
			}
			if($this->system_charset)	// do we have a system-charset ==> return it
			{
				return $this->system_charset;
			}
			// if no translations are loaded (system-startup) use a default, else lang('charset')
			return !is_array(GlobalService::get('lang')) ? 'iso-8859-1' : strtolower($this->translate('charset'));
		}

		function isin_array($needle,$haystack)
		{
			while(list($k,$v) = each($haystack))
			{
				if($v == $needle)
				{
					return True;
				}
			}
			return False;
		}

		function translate($key, $vars=False) 
		{
			if(!$this->isin_array(GlobalService::get('phpgw_info')['flags']['currentapp'],$this->loaded_apps) &&
				GlobalService::get('phpgw_info')['flags']['currentapp'] != 'home')
			{
				//echo '<br>loading app "' . GlobalService::get('phpgw_info')['flags']['currentapp'] . '" for the first time.';
				$this->add_app(GlobalService::get('phpgw_info')['flags']['currentapp'],GlobalService::get('lang'));
			}
			elseif(GlobalService::get('phpgw_info')['flags']['currentapp'] == 'admin' ||
				GlobalService::get('phpgw_info')['flags']['currentapp'] == 'preferences')
			{
				// This is done because for these two apps, all langs must be loaded.
				// Note we cannot load for navbar, since it would slow down EVERY page.
				// This is true until all common/admin/prefs langs are in the api file only.
				@ksort(GlobalService::get('phpgw_info')['apps']);
				while(list($x,$app) = @each(GlobalService::get('phpgw_info')['apps']))
				{
					if(!$this->isin_array($app['name'],$this->loaded_apps))
					{
						//echo '<br>loading app "' . $app['name'] . '" for the first time.';
						$this->add_app($app['name'],GlobalService::get('lang'));
					}
				}
			}

			if(!$vars)
			{
				$vars = array();
			}

			$ret = $key;

			if(isset($this->langarray[strtolower($key)]) && $this->langarray[strtolower($key)])
			{
				$ret = $this->langarray[strtolower($key)];
			}
			else
			{
				$ret = $key."*";
			}
			$ndx = 1;
			while( list($key,$val) = each( $vars ) )
			{
				$ret = preg_replace( "/%$ndx/", $val, $ret );
				++$ndx;
			}
			return $ret;
		}

		function autoload_changed_langfiles()
		{
			return;
		}
		function drop_langs($appname,$DEBUG=False)
		{
			if($DEBUG)
			{
				echo '<br>drop_langs(): Working on: ' . $appname;
				echo '<br>drop_langs(): Not needed with file-based lang class.';
			}
		}

		function add_langs($appname,$DEBUG=False,$force_langs=False)
		{
			if($DEBUG)
			{
				echo '<br>add_langs(): Working on: ' . $appname;
				echo '<br>add_langs(): Not needed with file-based lang class.';
			}
		}

		/*!
		@function add_app
		@abstract loads all app phrases into langarray
		@param $lang	user lang variable (defaults to en)
		*/
		function add_app($app,$lang='')
		{
			define('SEP',filesystem_separator());

			//echo '<br>add_app(): called with app="' . $app . '", lang="' . $userlang . '"';
			if(!isset($lang) || !$lang)
			{
				if(isset(GlobalService::get('phpgw_info')['user']['preferences']['common']['lang']) &&
					GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'])
				{
					$userlang = GlobalService::get('phpgw_info')['user']['preferences']['common']['lang'];
				}
				else
				{
					$userlang = 'en';
				}
			}
			else
			{
				$userlang = $lang;
			}

			$fn = PHPGW_SERVER_ROOT . SEP . $app . SEP . 'setup' . SEP . 'phpgw_' . $userlang . '.lang';
			if(!file_exists($fn))
			{
				$fn = PHPGW_SERVER_ROOT . SEP . $app . SEP . 'setup' . SEP . 'phpgw_en.lang';
			}

			if(file_exists($fn))
			{
				$fp = fopen($fn,'r');
				while($data = fgets($fp,8000))
				{
					list($message_id,$app_name,$null,$content) = explode("\t",$data);
					//echo '<br>add_app(): adding phrase: $this->langarray["'.$message_id.'"]=' . trim($content);
					$this->langarray[$message_id] = trim($content);
				}
				fclose($fp);
			}
			// stuff class array listing apps that are included already
			$this->loaded_apps[] = $app;
		}
	}
?>
