<?php
/*
Plugin Name: SMS Sprüche widget
Plugin URI: http://sms-sprüche.org/
Description: A widget that will show SMS Sprüche in your sidebar. You can select how many SMS Sprüche should be shown. You can select SMS Sprüche from different categroies.
Author: Michael Jentsch
Version: 0.1
Author URI: http://m-software.de/
License: GPL2

    Copyright 2009  Michael Jentsch (email : m.jentsch@web.de)

    This program is free software; you can redistribute it and/or modify
    it under the terms of the GNU General Public License version 2, 
    as published by the Free Software Foundation. 
    
    You may NOT assume that you can use any other version of the GPL.

    This program is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.
    
    The license for this software can likely be found here: 
    http://www.gnu.org/licenses/gpl-2.0.html
    
*/

class SMS_Sprueche_Widget extends WP_Widget {
	
	function curl_file_get_contents ($url)
	{
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_HEADER, 0);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER,1);
		$result = curl_exec($ch);
		curl_close($ch);
		return $result;
	}

	function SMS_Sprueche_Widget() {
		$widget_ops = array('classname' => 'widget_smsspueche', 'description' => __('SMS Sprüche Widget'));
		$control_ops = array('width' => 300, 'height' => 550);
		$this->WP_Widget('smsspueche', __('SMS Sprüche'), $widget_ops, $control_ops);
	}

	function page_URL() {
 		$pageURL = 'http';
		if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
		$pageURL .= "://";
		if ($_SERVER["SERVER_PORT"] != "80") {
			$pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
		} else {
			$pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
		}
		return $pageURL;
	}

	function get_url_params ($instance)
	{
		$ret = "?";
		$url = $this->page_URL(); // Needed fpr func = 3
		foreach ($instance as $key => $value)
		{
			$ret .= urlencode ($key) . "=" . urlencode ($value) . "&";
		}
		$ret .= "lang=" . urlencode(WPLANG) . "&";
		$ret .= "url=" . urlencode($url);
		return $ret;
	}
	
	function get_sms_sprueche_data ($instance)
	{
		// TODO: Cache (V1.1)
		$server = "api.xn--sms-sprche-geb.org";
		$url = "http://" . $server . "/rest/index.php" . $this->get_url_params ($instance);
		$data = $this->curl_file_get_contents ($url);
		$result = json_decode ($data, true);
		return $result;
	}
	
	function get_sms_sprueche_content ($data)
	{
		// Content aus den Daten machen
		$content = "";
		foreach ($data['results'] as $sms)
		{
			$content .= "<p>" . $sms . "</p>";
		}
		return $content;
	}

	/* Show Widget */
	function widget( $args, $instance ) {
		$img = plugins_url( 'images/sms-sprueche.png', __FILE__ );	
		extract($args);
		$title = apply_filters( 'widget_title', empty($instance['title']) ? '' : $instance['title'], $instance );
		$anz   = apply_filters( 'widget_anz', $instance['anz'], $instance );
		$data = $this->get_sms_sprueche_data ($instance);
		$text  = $this->get_sms_sprueche_content ($data);

		echo $before_widget;
		if ( !empty( $title ) ) { echo $before_title . $title . $after_title; } 
		ob_start();
		eval('?>'.$text);
		$text = ob_get_contents();
		ob_end_clean();
		?>			
		<div class="smsspuechewidget">
		<?php echo $instance['filter'] ? wpautop($text) : $text; ?>
		</div>
		<?php
		echo "<a id='sms-sprueche-a' href='http://www.sms-sprüche.org/' title='" . $data['info'] . "' target='smssprueche'>";
		echo "<img id='sms-sprueche-img' alt='" . $data['info'] . "' src='$img' border='0'></a>";
		echo $after_widget;
	}

	function update( $new_instance, $old_instance ) {
		$instance = $old_instance;
		$instance['title']  = strip_tags($new_instance['title']);
		$instance['filter'] = isset($new_instance['filter']);
		$instance['anz']    = intval($new_instance['anz']);
		$instance['func']   = intval($new_instance['func']);
		for ($i = 1; $i < 24; $i++)
		{
			$instance['cat' . $i]    = intval ($new_instance['cat' . $i]);
		}

		return $instance;
	}

	function form( $instance ) {
		$myargs = array( 'title' => '', 'filter' => '', 'anz' => '');
		for ($i = 1; $i < 24; $i++)
		{
			$myargs['cat' . $i] = "";
		}
		$instance = wp_parse_args( (array) $instance, $myargs );
		$title  = strip_tags($instance['title']);
		$text   = format_to_edit($instance['text']);
		$anz    = intval ($instance['anz']); 
		$func   = intval ($instance['func']); 
		$filter = intval ($instance['filter']); 
		for ($i = 1; $i < 24; $i++)
		{
			$cat[$i]    = $instance['cat' . $i];
			if ($cat[$i] == 1) {
				$selcat[$i] = "checked";
			} else {
				$selcat[$i] = "";
			}
		}
		 

		$intitleid = $this->get_field_id('title');
		$intitlename = $this->get_field_name('title');
		if (strlen (esc_attr($title)) < 1)
		{
			$intitlevalue = "SMS Sprüche"; // Default Text
		} else {
			$intitlevalue = esc_attr($title); 
		}
		$inanzid = $this->get_field_id('anz');
		$inanzname = $this->get_field_name('anz');
		$sel01 = "";
		$sel02 = "";
		$sel05 = "";
		$sel10 = "";
		$sel20 = "";
		switch ($anz) {
			case  1: $sel01 = "selected"; break;
			case  2: $sel02 = "selected"; break;
			case  5: $sel05 = "selected"; break;
			case 10: $sel10 = "selected"; break;
			case 20: $sel20 = "selected"; break;
			default: $sel05 = "selected"; break;
		}

		$infilterid = $this->get_field_id('filter');
		$infiltername = $this->get_field_name('filter');
		$selfilter = "";
		if ($filter > 0)
		{
			$selfilter = "checked";
		}

		$infuncid = $this->get_field_id('func');
		$infuncname = $this->get_field_name('func');
		$func1 = "";
		$func2 = "";
		$func3 = "";
		switch ($func) {
			case 1:  $func1 = "selected"; break;
			case 2:  $func2 = "selected"; break;
			case 3:  $func3 = "selected"; break;
			default: $func1 = "selected"; break;
		}

		for ($i = 1; $i < 24; $i++)
		{
			$incatid[$i] = $this->get_field_id('cat' . $i);
			$incatname[$i] = $this->get_field_name('cat' . $i);
		}

?>
<SCRIPT LANGUAGE="JavaScript">
// <!--
function check(name)
{
	field = document.getElementsByClassName(name);
	for (i = 0; i < field.length; i++) field[i].checked = true ;
}

function uncheck(name)
{
	field = document.getElementsByClassName(name);
	for (i = 0; i < field.length; i++) field[i].checked = false ;
}
//  -->
</script>
		<p>
<?
	if (!function_exists ("curl_version"))
	{
?>
		<center>
		<h3 style='color:red;'>You need to enable curl first.</h3>
		<a href='http://www.php.net/manual/en/book.curl.php'>PHP Curl Info</a><br>
		</center>
<?
	}
?>
		<label for="<?=$intitleid?>"><?php _e('Title:'); ?></label>
		<input  class="widefat" id="<?=$intitleid?>" 
			name="<?=$intitlename?>" type="text" value="<?=$intitlevalue?>" />
		</p>

		<p>
		<label for="<?=$inanzid?>"><?php _e('How many items would you like to display?'); ?></label><br>
		<select name="<?=$inanzname?>" id="<?=$inanzid?>" size="1">
			<option <?=$sel01?> value="1" > 1 <?=_e('items')?></option>
			<option <?=$sel02?> value="2" > 2 <?=_e('items')?></option>
			<option <?=$sel05?> value="5" > 5 <?=_e('items')?></option>
			<option <?=$sel10?> value="10">10 <?=_e('items')?></option>
			<option <?=$sel20?> value="20">20 <?=_e('items')?></option>
		</select>
		</p>

		<p>
		<label for="<?=$infuncid?>"><?php _e('Feature Filter'); ?></label><br>
		<select name="<?=$infuncname?>" id="<?=$infuncid?>" size="1">
			<option <?=$func1?> value="1">Random</option>
			<option <?=$func2?> value="2">Random (Dayly refreshed)</option>
			<option <?=$func3?> value="3">Per Page (Never chenged)</option>
		</select>
		</p>

		<label><?php _e('Category'); ?></label><br>
		<a onclick="check('smskat')" style='cursor:pointer; text-decoration:underline;'>Select all</a> 
		<a onclick="uncheck('smskat')" style='cursor:pointer; text-decoration:underline;'>Deselect all</a>
		<div style="width:200px; height:100px; overflow:auto; border:1px solid silver;">
		<input class="smskat" type="checkbox" name="<?=$incatname['1']?>" id="<?=$incatid['1']?>" <?=$selcat['1']?> value="1">Alkohol-Drogen<br>
		<!-- 2 fehlt :-) -->
		<input class="smskat" type="checkbox" name="<?=$incatname['3']?>" id="<?=$incatid['3']?>" <?=$selcat['3']?> value="1">Ausreden<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['4']?>" id="<?=$incatid['4']?>" <?=$selcat['4']?> value="1">Englisch<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['5']?>" id="<?=$incatid['5']?>" <?=$selcat['5']?> value="1">Flirt<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['6']?>" id="<?=$incatid['6']?>" <?=$selcat['6']?> value="1">Freundschaft<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['7']?>" id="<?=$incatid['7']?>" <?=$selcat['7']?> value="1">Geburtstag<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['8']?>" id="<?=$incatid['8']?>" <?=$selcat['8']?> value="1">Gemein<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['9']?>" id="<?=$incatid['9']?>" <?=$selcat['9']?> value="1">Gute-Nacht<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['10']?>" id="<?=$incatid['10']?>" <?=$selcat['10']?> value="1">Guten-Morgen<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['11']?>" id="<?=$incatid['11']?>" <?=$selcat['11']?> value="1">Kaltduscher<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['12']?>" id="<?=$incatid['12']?>" <?=$selcat['12']?> value="1">Liebe<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['13']?>" id="<?=$incatid['13']?>" <?=$selcat['13']?> value="1">Macho<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['14']?>" id="<?=$incatid['14']?>" <?=$selcat['14']?> value="1">Schule<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['15']?>" id="<?=$incatid['15']?>" <?=$selcat['15']?> value="1">Sehnsucht<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['16']?>" id="<?=$incatid['16']?>" <?=$selcat['16']?> value="1">Sonstige<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['17']?>" id="<?=$incatid['17']?>" <?=$selcat['17']?> value="1">Sorry<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['18']?>" id="<?=$incatid['18']?>" <?=$selcat['18']?> value="1">Trennung</br>
		<input class="smskat" type="checkbox" name="<?=$incatname['19']?>" id="<?=$incatid['19']?>" <?=$selcat['19']?> value="1">Warmduscher<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['20']?>" id="<?=$incatid['20']?>" <?=$selcat['20']?> value="1">Weihnachten<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['21']?>" id="<?=$incatid['21']?>" <?=$selcat['21']?> value="1">Zitate<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['22']?>" id="<?=$incatid['22']?>" <?=$selcat['22']?> value="1">Zungenbrecher<br>
		<input class="smskat" type="checkbox" name="<?=$incatname['23']?>" id="<?=$incatid['23']?>" <?=$selcat['23']?> value="1">Zweideutige<br>
		</div>
		<br>

<!-- TODO
		<p>
		<input id="<?=$infilterid?>" name="<?=$infiltername?>" type="checkbox" <?=$selfilter?> />
		&nbsp;
		<label for="<?php echo $this->get_field_id('filter'); ?>"><?php _e('Add Ajax Refresh Button'); ?>
		</label>
		</p>
 -->
<?php
	}
}

/* Register Widget */
add_action('widgets_init', create_function('', 'return register_widget("SMS_Sprueche_Widget");'));

?>
