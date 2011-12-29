<?php
/*
Plugin Name: Twitter Feed
Plugin URI: http://carling.otherlocker.info/
Description: Used by millions, Twitter Search is quite possibly the best way in the world to <strong>search on Twitter</strong>.
Version: 0.0.0.0.0.0.3
Author: Mario Alvarez
Author URI: http://dsafasd.com
License: WTFPL :p
*/

define ('TWS_TABLENAME', 'tws_cache');


/**
 * Initialize the plugin when the admin init runs 
 */
add_action('admin_init', 'tws_admin_init');
add_action('admin_menu', 'tws_add_option');


/**
 * Install
 */
register_activation_hook(__FILE__, 'tws_install');
function tws_install ()
{

	global $wpdb;
	global $tws_db_version;

	$tws_db_version = '1';

	$table_name = $wpdb->prefix . TWS_TABLENAME;
	  
	$sql = "CREATE TABLE " . $table_name . " (
		`id_tws_cache` int(11) unsigned NOT NULL AUTO_INCREMENT PRIMARY KEY,
		`data` text NOT NULL,
		`text` varchar(140) NOT NULL,
		`served` int(9) unsigned NOT NULL,
		`date_add` timestamp NOT NULL
	) comment = 'Twitter Feed cache data';";


	if ( ! (bool) $wpdb->get_var("show tables like $table_name"))
	{
		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}

	add_option("tws_db_version", $tws_db_version);

}


/**
 * Admin init
 * add a post meta-box to the post admin page and create a hook so that when
 * we save the post we save our data as post-meta
 */ 	
function tws_admin_init ()
{
	// box id, title, function to run, the page to display box on, where to display the box, and what priority to assign to the box display
	add_meta_box('tws_meta_box', 'Twitter Feed', 'tws_meta_box', 'post', 'side', 'default');
	register_setting('tws_optiongrousp', 'tws_defaultbannedwords', 'wp_filter_nohtml_kses');
	register_setting('tws_optiongrousp', 'tws_defaulttwuser', 'wp_filter_nohtml_kses');
	register_setting('tws_optiongrousp', 'tws_defaultlang', 'wp_filter_nohtml_kses');
	register_setting('tws_optiongrousp', 'tws_useragent', 'wp_filter_nohtml_kses');

	/**
	 * Hook into save_post action - save our data at the same time the post is saved
	 */
	add_action('save_post','tws_save_post');
}


/**
 * 
 */
function tws_add_option ()
{
	add_options_page('Opciones generales', 'Twitter Feed', 'manage_options', 'twitter_feed_options_menu', 'tws_optionsdo');
}


/**
 * 
 */
function tws_optionsdo ()
{
	?>
	<div class="wrap">
		<h2>Twitter Feed global options</h2>
	
		<form action="options.php" method="POST">
			
			<?php settings_fields('tws_optiongrousp'); ?>
			<fieldset>
				<p class="meta_options">
					<label for="tws_defaulttwuser">Select the default Twitter username.<br />
						<input type="text" name="tws_defaulttwuser" value="<?=get_option('tws_defaulttwuser')?>">
					</label>
				</p>
				
				<p class="meta_options">
					<label for="tws_defaulbannedwords">Default banned words, also you can append more words in each post. Insert words comma separated.<br />
						<textarea name="tws_defaultbannedwords" cols="70"><?=get_option('tws_defaultbannedwords')?></textarea>
					</label>
				</p>

				<p class="meta_options">
					<label for="tws_defaultlang">Language (ISO 639-1 code)<br/>
						<input type="text" name="tws_defaultlang" value="<?=get_option('tws_defaultlang')?>" />
					</label>
				</p>

				<p class="meta_options">
					<label>Twitter requires an email to they be able to report abuses.<br />
						<input type="text" name="tws_useragent" value="<?=get_option('tws_useragent')?>">
					</label>
				</p>
				
				<p class="submit">
					<input type="submit" class="button-primary" value="<?php _e('Save Changes') ?>" />
				</p>
			</fieldset>
		</form>	
	</div>
	<?php
}


/**
 * Display a select element in our meta-box
 * @param object $post - the current post object
 * @param object $box - the current meta-box details
 */
function tws_meta_box ($post, $box)
{
	// pull post_meta data, the true statement -> just return the value -> default key & value
	$twfEnabled 	= get_post_meta($post->ID, '_tws_enabled', TRUE);
	$searchMeta 	= get_post_meta($post->ID, '_tws_search', TRUE);
	$censorMeta 	= get_post_meta($post->ID, '_tws_censor', TRUE);
	$timelineType 	= get_post_meta($post->ID, '_tws_tltype', TRUE);
	$twUser 		= get_post_meta($post->ID, '_tws_twuser', TRUE);
	$twUList		= get_post_meta($post->ID, '_tws_twulist', TRUE);

	// meta-bax form element
	echo "
<script src='" . get_site_url() . "/wp-content/plugins/twitter-search/jquery.tooltip.min.js' type='text/javascript'></script>
<link rel='stylesheet' href='" . get_site_url() . "/wp-content/plugins/twitter-search/jquery.tooltip.css' type='text/css' media='all' />
<style>
	.hidden {
		display: none;
	}
</style>
<script>
jQuery(document).ready(function() {

	jQuery('#tws_tltype').change(function() {       

		var value = jQuery('#tws_tltype option:selected').val();
		var theDiv = jQuery('#tws_' + value);
	
		theDiv.slideDown();
		theDiv.siblings('[id^=tws_]').slideUp();
	});

	jQuery('#tws_censor *').tooltip();
	jQuery('#useDefaulUser').change(function () {

		if( ! jQuery(this).hasClass('checked'))
		{
			//do stuff if the checkbox isn't checked
			jQuery(this).addClass('checked');
			jQuery('#tws_twuser').val('" . get_option('tws_defaulttwuser') . "');
			return;
		}

		jQuery('#tws_twuser').val('');
		//do stuff if the checkbox isn't checked
		jQuery(this).removeClass('checked');
	});
	
});
</script>
	";
	echo '
		<p class="meta_options">
			<label for="tws_enabled">Activar / Desactivar</label><br />
			<select name="tws_enabled" id="tws_enabled">
				<option value="0" ' . (is_null($twfEnabled) || $twfEnabled == '0' ? 'selected="selected" ' : '') . '>Desactivado</option>
				<option value="1" ' . ($twfEnabled == '1' ? 'selected="selected" ' : '') . '>Activado</option>
			</select>
		</p>

		<p class="meta_options">
			<select name="tws_tltype" id="tws_tltype">
				<option disabled="disabled">-- Elige el tipo de Timeline --</option>
				<option value="search" ' . ($timelineType == 'search' ? 'selected="selected" ' : '') . '>Consulta</option>
				<option value="user-timeline" ' . ($timelineType == 'user-timeline' ? 'selected="selected" ' : '') . '>Usuario Twitter</option>
			</select>
		</p>

		<div id="tws_search" ' . ($timelineType == 'search' ? '' : 'class="hidden"') . '>

			<p class="meta_options">
				<label for="tws_search">Búsqueda:<br /></label>
				<input type="text" id="tws_search" name="tws_search" value="' . $searchMeta . '">
			</p>

		</div>
		
		<div id="tws_user-timeline" ' . ($timelineType == 'user-timeline' ? '' : 'class="hidden"') . '>
			<p class="meta_options">
				<label>Usuario de Twitter:</label><br />
				<input type="text" name="tws_twuser" id="tws_twuser" value="' . $twUser . '">
				
				<br />
				<label>Utilizar el usuario default <span>' . get_option('tws_defaulttwuser') . '</span></label>
				<input type="checkbox" name="useDefaulUser" id="useDefaulUser" value="1" /><br />
			</p>

			<p class="meta_options">
				<label>Búsqueda (opcional):</label><br />
				<input type="text" name="tws_twulist" id="tws_twulist" value="' . $twUList . '">
			</p>

		</div>

		<p class="meta_options">
			<label>Ingresa las palabras que deseas censurar<br /></label>
			<textarea id="tws_censor" name="tws_censor" cols="28" title="' . get_option('tws_defaultbannedwords') . '">' . $censorMeta . '</textarea>
		</p>
		
	';
}


/**
 * Save post handler - saves the appropriate data
 * @param int $post_id - the id of the current post
 */
function tws_save_post ($post_id)
{

	// proceed if content in $_POST
	if (isset($_POST['tws_enabled']))
		update_post_meta($post_id, '_tws_enabled', $_POST['tws_enabled']);
	
	if (isset($_POST['tws_search']))
		update_post_meta($post_id, '_tws_search', $_POST['tws_search']);
	
	if (isset($_POST['tws_censor']))
		update_post_meta($post_id, '_tws_censor', $_POST['tws_censor']);

	if (isset($_POST['tws_tltype']))
		update_post_meta($post_id, '_tws_tltype', $_POST['tws_tltype']);

	if (isset($_POST['tws_twuser']))
		update_post_meta($post_id, '_tws_twuser', $_POST['tws_twuser']);

	if (isset($_POST['tws_twulist']))
		update_post_meta($post_id, '_tws_twulist', $_POST['tws_twulist']);

}



/**
 * Let the new magic begin!
 */
if ( ! function_exists('get_twitter_search'))
{
	function get_twitter_search ($limit=NULL)
	{


		/**
		 * Check for cURL extension
		 */
		if ( ! in_array('curl', get_loaded_extensions()))
			throw new Exception('cURL not installed', 1);
			

		global $wpdb;


		/**
		 * This plugin its supposed to work only on single posts
		 */
		if ( ! is_single())
			return;


		/**
		 * Get post ID
		 */
		$post_id = get_the_ID();


		/**
		 * Check if enabled
		 */
		if ( ! $enabled = (bool) get_post_meta($post_id, '_tws_enabled', TRUE))
			return;
		

		/**
		 * Get global configs
		 */
		$divide_pattern = '/[\s]*[,][\s]*/';
		$configs = array(
			'enabled' => $enabled,
			'search' => preg_split($divide_pattern, get_post_meta($post_id, '_tws_search', TRUE)),
			'banned' => array_merge(
				preg_split($divide_pattern, get_post_meta($post_id, '_tws_censor', TRUE)),
				preg_split($divide_pattern, get_option('tws_defaultbannedwords', TRUE))
			),
			'type' => get_post_meta($post_id, '_tws_tltype', TRUE),
			'user' => get_post_meta($post_id, '_tws_twuser', TRUE),
			'list' => get_post_meta($post_id, '_tws_twulist', TRUE),
			'lang' => get_option('tws_defaultlang'),
			'user-agent' => get_option('tws_useragent')
		);


		/**
		 * Clean empty banned words
		 */
		if (is_array($configs['banned']))
			foreach ($configs['banned'] as $k => $word)
				if ( ! $word)
					unset($configs['banned'][$k]);


		/**
		 * Instantiate
		 */
		require_once ('TwitterSearchClass.php');
		$TW = new TwitterSearch();
		$TW->user_agent = $configs['user-agent'];


		/**
		 * Language
		 */
		if (isset($configs['lang']))
			$TW->lang($configs['lang']);


		/**
		 * Determine type of twitter query to prepare the class
		 */
		switch ($configs['type'])
		{
			
			/**
			 * Search
			 */
			case 'search':


				if ( ! $configs['search'])
					return;


				/**
				 * Prepare the twitter query!
				 */
				if (is_array($configs['search']))
					foreach ($configs['search'] as $key => $term)
						$TW->contains($term);

				else if (is_string($configs['search']))
					$TW->contains($configs['search']);

				break;


			/**
			 * User timelina
			 */
			case 'user-timeline':

				
				if ( ! $configs['user'])
					return;


				/**
				 * Prepare the twitter query!
				 */
				$TW->from($configs['user']);


				/**
				 * Check for words to perform search over user timeline
				 */
				if ((bool) strlen($configs['list']))
				{
					
					/**
					 * Check for array
					 */
					if (strpos($configs['list'], ','))
						foreach (preg_split('/[\s]*[,][\s]*/', $configs['list']) as $word)
							if ($word)
								$TW->contains($word);

					else
						$TW->contains($configs['list']);
				}

				break;

		}


		/**
		 * Lets go!
		 */
		$tuits = $TW->results();


		/**
		 * If we have tuits, they shall go to DB
		 */
		if (intval($TW->responseInfo['http_code']) == 200 && ! empty($tuits))
		{
			

			switch ($configs['type'])
			{

				case 'search':

					foreach ($tuits as $tuit)
						$_row[] = "('$tuit->id_str', '" . base64_encode(json_encode($tuit)) . "', '" . mysql_real_escape_string($tuit->text) . "', '" . mysql_real_escape_string(implode(',', $configs['search'])) . "')";

					$_query = "INSERT IGNORE INTO " . $wpdb->prefix . TWS_TABLENAME . "
						(`id_tws_cache`, `data`, `text`, `search_query`) VALUES 
						" . implode(", \n", $_row) . "
						ON DUPLICATE KEY UPDATE
							`data` =         VALUES(`data`),
							`text` =         VALUES(`text`),
							`search_query` = VALUES(`search_query`),
							`from_user` =    NULL";

					break;


				case 'user-timeline':

					foreach ($tuits as $tuit)
						$_row[] = "('$tuit->id_str', '" . base64_encode(json_encode($tuit)) . "', '" . mysql_real_escape_string($tuit->text) . "', '" . $configs['user'] . "')";

					$_query = "INSERT IGNORE INTO " . $wpdb->prefix . TWS_TABLENAME . "
						(`id_tws_cache`, `data`, `text`, `from_user`) VALUES 
						" . implode(", \n", $_row) . "
						ON DUPLICATE KEY UPDATE
							`data` =         VALUES(`data`),
							`text` =         VALUES(`text`),
							`search_query` = NULL,
							`from_user` =    VALUES(`from_user`)";
					
					break;

			}


			// die ($_query);

			/**
			 * Silent run
			 */
			$wpdb->query($_query);

		}


		/**
		 * Check for request status or empty response to know if cache will be needed
		 */
		// if (true)
		if (intval($TW->responseInfo['http_code']) == 420 || empty($tuits))
		{

			/**
			 * Cache will be needed
			 */


			$query = "
			
			select
				`data`

			from
				`" . $wpdb->prefix . "tws_cache`

			where
				";
			

			switch ($configs['type'])
			{

				case 'search':
					

					/**
					 * Just in case...
					 */
					if (strpos($configs['search'], ','))
						$configs['search'] = explode(',', $configs['search']);

					/**
					 * Prepare the DB query!
					 */
					if (is_array($configs['search']))
					{
						
						foreach ($configs['search'] as $word)
							$_like[] = "`search_query` like '%'" . mysql_real_escape_string($word) . '%';

						$query .= implode(' or ', $_like);
					}


					else if (is_string($configs['search']))
						$query .= "`search_query` like '%$configs[search]%";

					break;


				case 'user-timeline':

					$query .= "`from_user` = '$configs[user]'";


					/**
					 * Check for words to perform search over user timeline
					 */
					if ((bool) strlen($configs['list']))
					{
						
						/**
						 * Check for array
						 */
						if (strpos($configs['list'], ','))
						{
							
							foreach (preg_split('/[\s]*[,][\s]*/', $configs['list']) as $word)
								if ($word)
									$_like[] = PHP_EOL . "`text` like '%$word%'";

							$query .= " and (" . implode(' or ', $_like) . ")";
						}
									

						else
							$query .= PHP_EOL . "and `text` like '%$configs[list]%'";
					}
					
					break;
			}


			$query .= "

			group by `id_tws_cache`

			order by `date_add` desc

			limit 100";

			/*
			" . ((isset($limit) && is_numeric($limit) && $limit > 0)
				? "limit $limit" 
				: NULL) . "

			";
			*/

			unset ($tuits);	


			if (count($results = $wpdb->get_col($query)) > 0)
			{
				
				foreach ($results as $row)
					$tuits[] = json_decode(base64_decode($row));
			}
		}




		/**
		 * Now, tuits shall be parsed, censored and so on
		 */
		if (count($tuits) > 0)
		{


			foreach ($tuits as $key => &$tuit)
			{

				/**
				 * Banned words
				 */
				foreach ($configs['banned'] as $badword)
					if (preg_match('/' . strtolower($badword) . '/', strtolower($tuit->text)))
					{
						$_junk[] = $tuit;
						unset($tuits[$key]);
					}


				/**
				 * Links
				 */
				$tuit->text = preg_replace('@(https?://([-\w\.]+)+(:\d+)?(/([\w/_\.]*(\?\S+)?)?)?)@', '<a href="$1" rel="nofollow" target="_blank">$1</a>', $tuit->text);


				/**
				 * Mentions
				 */
				$tuit->text = preg_replace('/@([A-Za-z0-9_]+)/', '<a href="http://twitter.com/$1" rel="nofollow" target="_blank">@$1</a>', $tuit->text);


				/**
				 * Hashtags
				 */
				$tuit->text = preg_replace('/[#]+([A-Za-z0-9-_]+)/', '<a href="http://twitter.com/search?q=%23$1" target="_blank">$0</a>', $tuit->text);

				// $tuit->text = ereg_replace("[[:alpha:]]+://[^<>[:space:]]+[[:alnum:]/]", "<a href=\"\\0\" rel=\"nofollow\">\\0</a>", $tuit->text);
			}
		}



		// if (isset($_junk) && count($_junk) > 0)
		// {
		// 	echo 'junk';
		// 	return $_junk;
		// }


		/**
		 * To limit!
		 */
		if (isset($limit) && is_numeric($limit) && $limit > 0)
			return array_slice($tuits, 0, $limit);

		/**
		 * To inifinite and beyond
		 */
		return $tuits;

	}
}