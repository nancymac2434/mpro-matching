<?php
//
// ensures that metadata is removed when post is deleted
//
class MPro_Cleanup {
	public static function delete_submission_meta($post_id) {
		if (get_post_type($post_id) === 'mentor_submission') {
			global $wpdb;
			$wpdb->delete($wpdb->postmeta, array('post_id' => $post_id)); // Deletes all metadata
		}
	}
}

// Hook to delete metadata when a submission is deleted
add_action('before_delete_post', ['MPro_Cleanup', 'delete_submission_meta']);