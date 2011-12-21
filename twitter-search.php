<?php
/*
Plugin Name: Twitter Search
Plugin URI: http://carling.otherlocker.info/
Description: Used by millions, Twitter Search is quite possibly the best way in the world to <strong>search on Twitter</strong>.
Version: 0.0.0.0.0.0.1a
Author: Mario Alvarez
Author URI: http://dsafasd.com
License: WTFPL :p
*/

/**
 * Initialize the plugin when the admin init runs 
 */
add_action('admin_init', 'tws_admin_init');
add_action('admin_menu', 'tws_add_option');


/**
 * Admin init
 * add a post meta-box to the post admin page and create a hook so that when
 * we save the post we save our data as post-meta
 */ 	
function tws_admin_init ()
{
	// box id, title, function to run, the page to display box on, where to display the box, and what priority to assign to the box display
	add_meta_box('special-post', 'Twitter Search', 'tws_meta_box', 'post', 'side', 'default');
	register_setting('tws_optiongrousp', 'tf_defaultbannedwords', 'wp_filter_nohtml_kses');
	register_setting('tws_optiongrousp', 'tf_defaulttwuser', 'wp_filter_nohtml_kses');

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
	add_options_page('Opciones generales', 'Twitter Search', 'manage_options', 'twitter_filter_options_menu', 'tws_optionsdo');
}


/**
 * 
 */
function tws_optionsdo ()
{
	?>
	<div class="wrap">
		<h2>Twitter Filter</h2>
	
		<form action="options.php" method="POST">
			
			<?php settings_fields('tws_optiongrousp'); ?>
			<?php $op = get_option('tf_defaultbannedwords'); ?>
			<?php $twuname = get_option('tf_defaulttwuser'); ?>
			<fieldset>
				<p class="meta_options">
					<label for="tf_defaulttwuser">Select the default Twitter username.<br />
						<input type="text" name="tf_defaulttwuser" value="<?=$twuname?>">
					</label>
				</p>
				
				<p class="meta_options">
					<label for="tf_defaulbannedwords">Please, add the default banned keywords, you still can append more words in each post. Insert words comma separated.<br />
						<textarea name="tf_defaultbannedwords" cols="70"><?php echo $op; ?></textarea>
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
function tws_meta_box ($post,$box)
{
	// pull post_meta data, the true statement -> just return the value -> default key & value
	$twfEnabled 	= get_post_meta($post->ID,'_tws_enabled',true);
	$searchMeta 	= get_post_meta($post->ID,'_tws_search',true);
	$censorMeta 	= get_post_meta($post->ID,'_tws_censor',true);
	$timelineType 	= get_post_meta($post->ID,'_tws_tltype',true);
	$twUser 		= get_post_meta($post->ID,'_tws_twuser',true);
	$twUList		= get_post_meta($post->ID,'_tws_twulist',true);

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
		var theDiv = jQuery('#DivBy' + value);
	
		theDiv.slideDown();
		theDiv.siblings('[id^=DivBy]').slideUp();
	});

	jQuery('#tws_censor *').tooltip();
	jQuery('#useDefaulUser').change(function () {

		if( ! jQuery(this).hasClass('checked'))
		{
			//do stuff if the checkbox isn't checked
			jQuery(this).addClass('checked');
			jQuery('#tws_twuser').val('" . get_option('tf_defaulttwuser') . "');
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
		<label for="tws_enabled">Activar / Desactivar <br /><select name="tws_enabled" id="tws_enabled">
		<option value="0" ' . (is_null($twfEnabled) || $twfEnabled == '0' ? 'selected="selected" ' : '') . '>Desactivado</option>
		<option value="1" ' . ($twfEnabled == '1' ? 'selected="selected" ' : '') . '>Activado</option>
		</select></label>
		</p>
		<p class="meta_options">
				<label>
					<select name="tws_tltype" id="tws_tltype">
						<option disabled="disabled">-- Elige el tipo de Timeline --</option>
						<option value="1" ' . ($timelineType == '1' ? 'selected="selected" ' : '') . '>Consulta</option>
						<option value="2" ' . ($timelineType == '2' ? 'selected="selected" ' : '') . '>Usuario Twitter</option>
					</select>
				</label>
			</p>
		<div id="DivBy1" ' . ($timelineType == '1' ? '' : 'class="hidden"') . '>
			<p class="meta_options">
				<label for="tws_search">Escribe el patrón de busqueda:<br /><input type="text" id="tws_search" name="tws_search" value="' . $searchMeta . '"></label>
			</p>
			<p class="meta_options">
				<label>Ingresa las palabras que deseas censurar<br /><textarea id="tws_censor" name="tws_censor" cols="28" title="' . get_option('tf_defaultbannedwords') . '">' . $censorMeta . '</textarea>
			</p>
		</div>
		<div id="DivBy2" ' . ($timelineType == '2' ? '' : 'class="hidden"') . '>
			<p class="meta_options">
				<label>Usuario de Twitter:<br />
					<input type="text" name="tws_twuser" id="tws_twuser" value="' . $twUser . '">
				</label>
				<br />
				<label>Utilizar el usuario default <span>' . get_option('tf_defaulttwuser') . '</span><input type="checkbox" name="useDefaulUser" id="useDefaulUser" value="1" /><br />
				</label>
			</p>
			<p class="meta_options">
				<label>Nombre de la lista a mostrar:<br />
					<input type="text" name="tws_twulist" id="tws_twulist" value="' . $twUList . '">
				</label>
			</p>
		</div>
		
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