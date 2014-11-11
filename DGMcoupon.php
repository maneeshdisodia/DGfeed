<?php

/*
Plugin Name: DGM Coupon Feeder
Plugin URI: http://www.google.com
DealDescription: Post rss to your blog
Author: maneesh disodia
Version: 1.0
Author URI: http://www.dgm-india.com/
*/

// Include code to expire rss
require_once('include/expire_coupon.php');

$the_feed = new Coupon_Feed;


if (isset($the_feed)) {

	register_activation_hook( __FILE__, array($the_feed, 'install'));
	register_deactivation_hook(__FILE__, array($the_feed, 'deactivate'));
}

$debug = 1;
// $debug = 0;

class Coupon_Feed {
	function Coupon_Feed() {
		$this->__construct();
	}

	function __construct() {

		add_action('feed_coupons', array(&$this, 'get_xml'));
		add_action('cfeed_check_cronmailer', array(&$this,'cron_check'));
		add_filter('cron_schedules', array(&$this, 'more_rec'));


		new Coupon_Feed_Options($this);
		new Expire_Coupon($this);

	}

	function deactivate() {
		wp_clear_scheduled_hook('feed_coupons');
		wp_clear_scheduled_hook('expire_coupons');
	}

	function cron_check() {
		$error = 'In cron_check()'; $this->do_debug($error);
	}

	function do_debug($data) {
		global $debug;

		if ($debug) {
			$data = date('l dS \of F Y h:i:s A'). ' '. $data;

			$myFile = 'debug.log';
			$fh = fopen($myFile, 'a');

			fwrite($fh, $data."\n");
			fclose($fh);
		}
	}
	static function install() {
		global $wpdb;

		$table_name = $wpdb->prefix . "dgcouponfeeder";

		$sql =  "CREATE TABLE " . $table_name . " (
			id mediumint(9) NOT NULL AUTO_INCREMENT,
			post_id text NOT NULL,
			DealId text NOT NULL,
			ExpiryDate text NOT NULL,
			DealImage text NOT NULL,
			DealLink text NOT NULL,
			DealDealDescription text NOT NULL,
			DealType text NOT NULL,
			DealTitle text NOT NULL,
			ProductCategory text NOT NULL,
			MinimumPrice text NOT NULL,
			value text,
			DealCode text NOT NULL,
			Dealcomment text NOT NULL,
			Currency text NOT NULL,
			CountryCode text NOT NULL,
			MaximumOffer text NOT NULL,
			PublishedDate text NOT NULL,
			Conditions text NOT NULL,
			UNIQUE KEY id (id));";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);
	}
	
	function dtc($data) {
    if(is_array($data) || is_object($data))
	{
		echo("<script>console.log('PHP: ".json_encode($data)."');</script>");
	} else {
		echo("<script>console.log('PHP: ".$data."');</script>");
	}
	}

	function more_rec() {

		return array('weekly' => array('interval' => 604800, 'display' => 'Once Weekly'));
	}

	function get_xml() {
		global $wpdb;
		//echo get_site_url();
		$options['coupon_maxpost'] = get_option('coupon_maxpost', '1');
		$url = 'http://127.0.0.1/feed/SampleCouponFeed.xml?';
		//$url = 'http://127.0.0.1/feed/SampleCouponFeed.xml?site='.urlencode(get_site_url());
		//$xml = simplexml_load_file($url);
		//$xml = simplexml_load_file(file_get_contents($url), 0, false);
		$xml = simplexml_load_file($url);
		//$xml = simplexml_load_file(gen.xml);
		//print_r($xml);
		//echo ("<script>console.log('PHP;".json_encode($xml)."'); </script>");
		//$size = sizeof($xml);
		$size = sizeof($xml->channel->item);
		//echo $size;
		//echo ("<script>console.log('PHP;".($size)."'); </script>");
		$coupon_count = 0;

		for ($loop = 0; $loop < $size; $loop++) {
			//echo "hello";
			$DealTitle = $xml->channel->item[$loop]->DealTitle;
			$DealId = $xml->channel->item[$loop]->DealId;
			$DealType = $xml->channel->item[$loop]->DealType;
			$DealCode = $xml->channel->item[$loop]->DealCode;
			//echo $DealId;
			$Dealcomment = $xml->channel->item[$loop]->Dealcomment;
			$Currency = $xml->channel->item[$loop]->Currency;
			$CountryCode = $xml->channel->item[$loop]->CountryCode;
			$MinimumPrice = $xml->channel->item[$loop]->MinimumPrice;
			$MaximumOffer = $xml->channel->item[$loop]->MaximumOffer;		
			$DealDescription = htmlspecialchars($xml->channel->item[$loop]->DealDescription, ENT_QUOTES);
			//echo $DealDescription;
			$DealImage = $xml->channel->item[$loop]->DealImage;
			$PublishedDate = $xml->channel->item[$loop]->PublishedDate;
			//$shutoff = $xml->item[$loop]->shutoff;
			$ExpiryDate = $xml->channel->item[$loop]->ExpiryDate;
			$DealLink = $xml->channel->item[$loop]->DealLink;						
			$ProductCategory = htmlspecialchars($xml->channel->item[$loop]->ProductCategory, ENT_QUOTES);
			$ProductCategoryId = $xml->channel->item[$loop]->ProductCategoryId;
			$Conditions = $xml->channel->item[$loop]->Conditions;
			$Advertiserlogo = $xml->channel->item[$loop]->Advertiserlogo;					
			$value = 1;
			//$value = $xml->item[$loop]->value;
			$geotarget = 0;
			//$geotarget = $xml->item[$loop]->geotarget;
			$sql = 'SELECT COUNT(*) FROM '.$wpdb->prefix.'dgcouponfeeder WHERE DealId = '.$DealId;
			//echo $sql;
			$coupon_posted = $wpdb->get_var($wpdb->prepare($sql,null));
			//echo "hello232";
			$temp=$ExpiryDate;
			$ExpiryDate=date("m/d/Y H:i:s A", strtotime($temp));
			//echo $ExpiryDate;
			$exp_notime = reset(explode(' ', $ExpiryDate));
			//echo "notime=".$exp_notime;
			$exp_year = end(explode('/', $exp_notime));
			//echo "year=".$exp_year;
			$exp_month = reset(explode('/', $exp_notime));
			//echo "month=".$exp_month;
			$exp_day = array_search(1,array_flip(explode('/', $exp_notime)));
			//echo "day=".$exp_day;
			$exp_date = $exp_year.'-'.$exp_month.'-'.$exp_day;
			//echo "exp date=".$exp_date;
			$exp_timestamp = strtotime($exp_date);
			//echo "timestamp=".$exp_timestamp;
			$todays_date = date("Y-m-d");
			$today = strtotime($todays_date);
			
			// Dont post if already posted or coupon expired
			if (!$coupon_posted && $exp_timestamp > $today && !empty($DealId)) {

				$upload_dir = wp_upload_dir();
				$newfile = $upload_dir['path'].'/'.end(explode('/', $DealImage));

				if (!file_exists($newfile)) {
					copy($DealImage, $newfile);
				}
				$img_DealLink = $upload_dir['url']. '/' . end(explode('/', $DealImage));
				$data_array = array(	'DealId' => $DealId,
							'ExpiryDate' => $exp_timestamp,
							'DealImage' => htmlspecialchars($img_DealLink),
							'DealLink' => htmlspecialchars($DealLink),
							'DealDealDescription' => $DealDescription,
							'DealType' => $DealType,
							'DealTitle' => $DealTitle,
							'ProductCategory' => $ProductCategory,
							'MinimumPrice' => $MinimumPrice,
							'value' => $value,
							'DealCode' => $DealCode,
							'Dealcomment' => $Dealcomment,
							'Currency' => $Currency,
							'CountryCode' => $CountryCode,
							'MaximumOffer' => $MaximumOffer,
							'PublishedDate' => $PublishedDate,
							'Conditions' => $Conditions);

				$wpdb->insert( $wpdb->prefix . 'dgcouponfeeder', $data_array );
				$this->post($DealDescription, $DealId, $DealType, $DealTitle, $ProductCategory, $newfile);
				$coupon_count ++;
				if($options["coupon_maxpost"] != 0 && $coupon_count >= $options["coupon_maxpost"]) {
					break; 
				}
			}
		}
	}

	function post($DealDescription, $DealId, $DealType, $DealTitle, $ProductCategory, $filename) {
		global $wpdb;

		$options['coupon_author'] = get_option('coupon_author', '0');
		$options['coupon_status'] = get_option('coupon_status', '0');
		$options['coupon_tag_list'] = get_option('coupon_tag_list', 'rss, %DealType%, %DealTitle%, %ProductCategory%');
		$options['coupon_cat'] = get_option('coupon_cat', '0');
		$options['cgrab_thumbnail'] = get_option('cgrab_thumbnail', 'checked');

		$min_id = get_cat_ID($DealTitle);
		if(!$min_id) {
			require_once (ABSPATH.'/wp-admin/includes/taxonomy.php');
			$maj_id = get_cat_ID($DealType);
			if(!$maj_id) {
				if($options["coupon_cat"]) {
					$maj_id = wp_create_category($DealType, $options["coupon_cat"]);
				}
				else {
					$maj_id = wp_create_category($DealType);
				}
			}

			$min_id = wp_create_category($DealTitle, $maj_id);
		}

		$coupon_token = array ("%DealType%", "%DealTitle%", "%ProductCategory%");
		$coupon_replace = array($DealType, $DealTitle, $ProductCategory);
		$coupon_tag_parse = str_replace($coupon_token, $coupon_replace, $options["coupon_tag_list"]);
		$coupon_tags = explode(",", $coupon_tag_parse);

		for($loop = 0; $loop < sizeof($coupon_tags); $loop++) {
			$coupon_tags[$loop] = trim($coupon_tags[$loop]);
		}

		$status_type = array('draft', 'publish', 'pending', 'private');
		$wm_mypost = new wm_mypost();

		$wm_mypost->post_title = 'Coupon: '.$DealDescription;
		$wm_mypost->post_content = $this->parse($DealId);
		$wm_mypost->post_status = $status_type[$options['coupon_status']];
		$wm_mypost->post_author = $options['coupon_author'];
		$wm_mypost->post_category = array($min_id, $maj_id, $options["coupon_cat"]);
		$wm_mypost->tags_input = $coupon_tags;
		$wp_rewrite->feeds = 'yes';

		$coupon_post_id = wp_insert_post($wm_mypost);

		if ($coupon_post_id) {
			$sql = 'UPDATE '.$wpdb->prefix.'dgcouponfeeder SET post_id='.$coupon_post_id.' WHERE DealId='.$DealId;
			$wpdb->query($sql);

			$wp_filetype = wp_check_filetype(basename($filename), null );

			$attachment = array(
				'post_mime_type' => $wp_filetype['type'],
				'post_title' => preg_replace('/\.[^.]+$/', '', basename($filename)),
				'post_content' => '',
				'post_status' => 'inherit');

			$attach_id = wp_insert_attachment( $attachment, $filename, $coupon_post_id );

			require_once(ABSPATH . 'wp-admin/includes/image.php');

			$attach_data = wp_generate_attachment_metadata( $attach_id, $filename );
			wp_update_attachment_metadata( $attach_id, $attach_data );

			if ($options['cgrab_thumbnail'] == 'checked') {
				add_post_meta($coupon_post_id, '_thumbnail_id', $attach_id, true);
			}
		}
	}

	function parse($id) {

		global $wpdb;


		$options['coupon_format'] = get_option('coupon_format', '<h4><b><font color="blue">%DealDescription%</font></b></h4>'
									.'<a href="%DealLink%"><img src="%DealImage%" border="0" align="left" style="padding:10px;"></a>'
									.'<a href="%DealLink%">%ProductCategory% DGcoupon</a><br><b>%DealType% %DealTitle% <h4><b><U><font color="green"> Coupon Code: %DealCode%</font></U></b></h4></b>'
									.'<br /><br /><br /><b>Exp: <font color="green">%ExpiryDate%</font></b><br /><br />');

		if(empty($id)) {
			$output = '';
		}
		else {
			$DealId = $id;
			$sql = "SELECT ExpiryDate FROM ". $wpdb->prefix. "dgcouponfeeder WHERE DealId = ". $DealId;
			$ExpiryDate_date = $wpdb->get_var($wpdb->prepare($sql,null));

			$todays_date = date("Y-m-d");
			$today = strtotime($todays_date);


			$sql = "SELECT * FROM ". $wpdb->prefix. "dgcouponfeeder WHERE DealId = ". $DealId;
			$mycoupon = $wpdb->get_row($sql);

			if($ExpiryDate_date > $today) {
			}


			$coupon_token = array ('%DealImage%', '%DealLink%', '%DealDescription%', '%DealType%', '%DealTitle%', '%exp%', '%ProductCategory%','%DealCode%');
			$coupon_replace = array(htmlspecialchars_decode(stripslashes($mycoupon->DealImage)), htmlspecialchars_decode(stripslashes($mycoupon->DealLink)), $mycoupon->DealDealDescription, $mycoupon->DealType, $mycoupon->DealTitle, date("Y-m-d",$mycoupon->ExpiryDate), $mycoupon->ProductCategory,$mycoupon->DealCode);

			$coupon_parse = str_replace($coupon_token, $coupon_replace, $options["coupon_format"]);
			$output = htmlspecialchars_decode(stripslashes($coupon_parse));

		}
		return $output;
	}
}


class Coupon_Feed_Options {
	function Coupon_Feed_Options($feed) {
		$this->feed = $feed;
		$this->__construct();
	}

	function __construct() {
		add_action('admin_menu', array(&$this, 'admin_menu'));
	}

	function admin_menu() {
		// Main Admin Menu
		add_menu_page('Coupon', 'Coupon', 'manage_options', 'cfeed-general','',plugins_url('DgLabsCouponFeeder/images/admin/red_feed.png'));

		// Admin Submenus
		add_submenu_page('cfeed-general', 'General', 'General', 'manage_options', 'cfeed-general', array( $this, 'general' ));
		add_submenu_page('cfeed-general', 'HTML', 'HTML', 'manage_options', 'cfeed-html', array( $this, 'html' ));

	}

	function general() {
		global $the_feed;

		$message = '';
		if ($_POST['action'] == 'update') {
			$message .= '<div id="message" class="updated fade"><p><strong>Options saved</div>';
			if($_POST['coupon_interval'] != get_option('coupon_interval')) {

				if (wp_next_scheduled('feed_coupons')) {
					wp_clear_scheduled_hook('feed_coupons');
				}

				switch ($_POST['coupon_interval']) {
					case 1:
						wp_schedule_event( time() + 3600, 'hourly', 'feed_coupons' );
						break;
					case 2: 
						wp_schedule_event( time() + 86400, 'daily', 'feed_coupons' );
						break;
					case 3:
						wp_schedule_event( time() + 172800, 'twicedaily', 'feed_coupons' );
						break;
					case 4:
						wp_schedule_event( time() + 604800, 'weekly', 'feed_coupons' );
						break;
				}
			}

			$_POST['cgrab_thumbnail'] == 'on' ? update_option('cgrab_thumbnail', 'checked') : update_option('cgrab_thumbnail', '');

			update_option('coupon_interval', $_POST['coupon_interval']);
			update_option('coupon_maxpost', $_POST['coupon_maxpost']);
			update_option('coupon_status', $_POST['coupon_status']);
			update_option('coupon_author', $_POST['coupon_author']);
			update_option('coupon_cat', $_POST['coupon_cat']);
			update_option('coupon_tag_list', $_POST['coupon_tag_list']);
		}

		if($_POST['coupon_grab_now'] == 'on') {
			$the_feed->get_xml();
		}

		$options['coupon_interval'] = get_option('coupon_interval', '0'); // Defaults to NONE
		$options['coupon_maxpost'] = get_option('coupon_maxpost','1');
		$options['coupon_status'] = get_option('coupon_status', '0'); // Defaults to draft
		$options['coupon_author'] = get_option('coupon_author', '0'); // Defaults to Admin
		$options['coupon_cat'] = get_option('coupon_cat', '0'); // Defaults to NONE
		$options['coupon_tag_list'] = get_option('coupon_tag_list', 'rss, %DealType%, %DealTitle%, %ProductCategory%');
		$options['cgrab_thumbnail'] = get_option('cgrab_thumbnail', 'checked');

		$authors = get_users('blog_id=1&orderby=display_name&role=subscriber');

		if (wp_next_scheduled('feed_coupons')) {
			$timestamp = wp_next_scheduled( 'feed_coupons' );
			$time_now = time();
			$next_coupon_grab = wp_next_scheduled('feed_coupons');
			$time_period = ($timestamp - $time_now);
			$days = (int) ($time_period / 86400); $time_period = ($time_period % 86400);
			$hours = (int) ($time_period / 3600); $time_period = ($time_period % 3600);
			$minutes = (int) ($time_period / 60); $time_period = ($time_period % 60);
			$seconds = $time_period;

			$message .= '<div id="message" class="updated fade"><p><strong>Next Coupon Grab: '.$days.' days '.$hours. ' hours '. $minutes. ' minutes '. $seconds. ' seconds</strong></p></div>';
		}

		// General Options Header
		$output = '<div class="wrap">'. $message
		. '<div id="icon-options-general" class="icon32"><br /></div>'
		. '<h2>Coupon Grab General Options</h2>'

		. '<form method="post" action=""><input type="hidden" name="action" value="update" />&nbsp;'
		. '<table class="widefat"><thead><tr><th>Timing</th>'
		. '<th><div width="100%" align="right">Run NOW <input name="coupon_grab_now" type="checkbox" id="coupon_grab_now"/></div></th>'
		. '</tr></thead>'
		. '<tbody><tr><td>Time Interval</td><td>Maximum Posts Per Interval</td></tr><tr>'

		. '<tbody><tr>';


		// Interval for pulling rss
		$output .= '<td><select name="coupon_interval">';

		$interval_type = array('none', 'hourly', 'daily', 'twicedaily', 'weekly');
		for ($loop = 0; $loop < 5; $loop++) {
			if($options["coupon_interval"] == $loop) {
				$output .= '<option value="'.$loop.'" SELECTED>'.$interval_type[$loop].'</option>';
			}
			else {
				$output .= '<option value="'.$loop.'">'.$interval_type[$loop].'</option>';
			}
		}
		$output .= '</select></td>';

		// Maximum number of rss to post per interval
		$output .= '<td><input name="coupon_maxpost" type="text" id="coupon_maxpost" value="'.$options["coupon_maxpost"].'" size="2"/ maxlength="2"></td></tr>'

		// Second Header
		. '<thead><tr><th colspan="2">Coupon Meta</th>'
		. '</tr></thead>'
		. '<tbody><tr><td>Status for new rss</td><td>Author for coupon posts</td></tr><tr>';

		// Coupon status defaults to draft
		$output .= '</td><td><select name="coupon_status">';

		$status_type = array('draft', 'publish', 'pending', 'private');
		for ($loop = 0; $loop < 4; $loop++) {
			if($options["coupon_status"] == $loop) {
				$output .= '<option value="'.$loop.'" SELECTED>'.$status_type[$loop].'</option>';
			}
			else {
				$output .= '<option value="'.$loop.'">'.$status_type[$loop].'</option>';
			}
		}
		$output .= '</select></td>';

		// Author of coupon posts
		$blogusers = get_users('blog_id=1&orderby=display_name');

		$output .= '<td><select name="coupon_author">';
		foreach ($blogusers as $user) {
			if(user_can($user->ID, "edit_posts")) {
				if($options["coupon_author"] == $user->ID) {
					$output .= '<option value="'.$user->ID.'" SELECTED>'.$user->display_name .'</option>';
				}
				else {
					$output .= '<option value="'.$user->ID.'">'.$user->display_name .'</option>';
				}
			}
		}
		$output .= '</select></td></tr><tr>'

		// Third Header
		. '<thead><tr><th colspan="2">Category and Tags</th>'
		. '</tr></thead>'
		. '<tbody><tr><td>Parent category</td><td>Tags to be added to rss</td></tr><tr>';

		// Parent Category
		$output .= '<td><select name="coupon_cat">';
		if($options["coupon_cat"] == 0) {
			$output .= '<option value="0" SELECTED>NONE</option>';
		}
		else {
			$output .= '<option value="0">NONE</option>';
		}

		$category_ids = get_all_category_ids();
		foreach ($category_ids as $cat_id) {
			if($options["coupon_cat"] == $cat_id) {
				$output .= '<option value="'.$cat_id.'" SELECTED>'.get_cat_name($cat_id).'</option>';
			}
			else {
				$output .= '<option value="'.$cat_id.'">'.get_cat_name($cat_id).'</option>';
			}
		}
		$output .= '</select></td>';

		// Coupon Tags
		$output .= '<td><input name="coupon_tag_list" type="text" id="coupon_maxpost" value="'.$options["coupon_tag_list"].'"></td>';


		$output .= '</tr><tr>'
		. '<td colspan="2"><input name="cgrab_thumbnail" type="checkbox" id="cgrab_thumbnail" '.$options["cgrab_thumbnail"].' /> Enable featured DealImage tagging</td>';
		
		$output .= '</tr></tbody></table><br />';

		// Directions
		$output .= '<b>For Tags</b>: <i>Enter comma delimeted list of tags (i.e. tag1,tag2,tag3,etc) variables: %ProductCategory, %DealType%, and %DealTitle%</i><br /><br />'
		. '<input type="submit" class="button-primary" value="Save Changes" /></form>';

		// Output
		echo $output;
	}

	function html() {
		$message = '';

		if ($_POST['action'] == 'update') {
			update_option('coupon_format', htmlspecialchars($_POST['coupon_format']), ENT_QUOTES);

			$message .= '<div id="message" class="updated fade"><p><strong>Options saved</div>';
		}

		$options['coupon_format'] = get_option('coupon_format', '<h4><b><font color="blue">%DealDescription%</font></b></h4>'
									.'<a href="%DealLink%"><img src="%DealImage%" border="0" align="left" style="padding:10px;"></a>'
									.'<a href="%DealLink%">%ProductCategory% coupon</a><br><b>%DealType% %DealTitle%</b>'
									.'<br /><br /><br /><b>Exp: <font color="green">%exp%</font></b><br /><br />');

		// Static HTML Header
		$output = '<div class="wrap">'. $message
		. '<div id="icon-options-general" class="icon32"><br /></div>'
		. '<h2>Coupon Grab HTML Options</h2>'

		. '<form method="post" action=""><input type="hidden" name="action" value="update" />&nbsp;'
		. '<table class="widefat"><thead><tr><th colspan="2">HTML to use when displaying rss</th></tr>'
		. '<tbody><tr>'

		// Coupon format
		. '<td><textarea name="coupon_format" cols="40" rows="10">'.htmlspecialchars_decode(stripslashes($options["coupon_format"]), ENT_QUOTES).'</textarea></td>'
		. '<td>You can insert variables into your post template by surrounding them with percent signs (%).<br /><br />'
		. '<b>%DealImage%</b> - The URL to the coupon DealImage, this needs to be embedded in a img tag.<br />'
		. '<b>%DealLink%</b> - The URL to print the coupon, this needs to be embedded in an href tag.<br />'
		. '<b>%DealDescription%</b> - DealDescription<br />'
		. '<b>%DealType%</b> - The major category, i.e. Food<br />'
		. '<b>%DealTitle%</b> - The minor category, i.e. Butter/Margarine<br />'
		. '<b>%exp%</b> - The ExpiryDate date in YYYY-MM-DD format.<br />'
		. '<b>%ProductCategory%</b> - The ProductCategory, i.e. I Can.t Believe It.s Not Butter!<br />'
		. '</tr></tbody></table>'
		. '<br /><input type="submit" class="button-primary" value="Save Changes" /></form>';
		


		// Output
		echo $output;
	}
}

class wm_mypost {
	var $post_title;
	var $post_content;
	var $post_status;
	var $post_author;
	var $post_name;
	var $post_type;
	var $comment_status;
}
?>
