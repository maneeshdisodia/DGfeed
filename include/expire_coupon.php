<?php

// This goes in the parent file require_once('include/Expire_Coupon.php');

class Expire_Coupon {

	function Expire_Coupon($feed) {

		$this->feed = $feed;
		$this->__construct();
	}

	function __construct() {

		// Add hook
		add_action('expire_coupons', array(&$this, 'expire'));

		// Schedule hook (daily)
		if (!wp_next_scheduled('expire_coupons')) {

			wp_schedule_event( time() + 86400, 'daily', 'expire_coupons' );
		}
	}

	function expire() {

		// Get table name
		$table_name = $wpdb->prefix . 'DGcouponfeeder';

		// Get relivant data from DB
		$sql = "SELECT id, DealId, post_id, ExpiryDate FROM ".$table_name;
		$coupons = $wpdb->get_results($sql);

		// Get today's date and convert into a Unix timestamp
		$todays_date = date("Y-m-d");
		$today = strtotime($todays_date);

		// Loop through all coupons in database
		foreach ($coupons as $coupon) {

			// Has coupon expired?
			if ($coupon->ExpiryDate < $today) {

				// Delete post's attachments
				$this->delete_post_children($coupon->post_id);

				// Delete post
				wp_delete_post($coupon->post_id, true);

				// Delete from coupon database
				$sql = "DELETE FROM ".$table_name. " WHERE id = '".$coupon->id."'";
				$wpdb->query($sql);
			}
		}
	}

	function delete_post_children($post_id) {

		global $wpdb;

		// Select all attachments to this post
		$ids = $wpdb->get_col("SELECT ID FROM {$wpdb->posts} WHERE post_parent = $post_id AND post_type = 'attachment'");

		// Delete all attachments to this post
		foreach ( $ids as $id ) {
			wp_delete_attachment($id, true);
		}
	}
}

?>
