<?php
//
// Generates a table with all entries for one client's mentors and mentees
// also enables upload and download of data
//
	// Reusable fields
function mpro_get_all_fields($client_id = 'leap4ed') {
		$matching = get_matching_class_for_client($client_id);
		error_log("Generating report for client $client_id with fields: " . implode(', ', array_keys($matching->get_report_fields())));
		return $matching->get_report_fields();
	}

// Fetch posts
function mpro_get_mentor_posts($user_id = null, $client_id = 'leap4ed') {
	global $wpdb;

	// Always sanitize client_id just in case
	$client_id = sanitize_text_field($client_id);

	$query = $wpdb->prepare(
		"
		SELECT p.ID, p.post_title, p.post_date
		FROM {$wpdb->posts} p
		INNER JOIN {$wpdb->postmeta} pm ON p.ID = pm.post_id
		WHERE p.post_type = 'mentor_submission'
		AND p.post_status = 'publish'
		AND pm.meta_key = 'assigned_client'
		AND pm.meta_value = %s 	ORDER BY p.post_date DESC
		",
		$client_id
	);

	if ($user_id) {
		$query .= $wpdb->prepare(" AND p.ID = %d", $user_id);
	}

	return $wpdb->get_results($query);
}

// Convert meta values
function mpro_get_formatted_meta($post_id, $fields) {

$post_date = get_post_field( 'post_date', $post_id );

$row = [];

	foreach ($fields as $key => $label) {
		$value = get_post_meta($post_id, $key, true);

		if ($key === 'mpro_role') {
			$value = ($value == 1) ? 'Mentee' : (($value == 2) ? 'Mentor' : 'Unknown');
		}

		if ($key === 'post_date') {
			$value = mysql2date( 'm/d/Y', $post_date );
		}
		
		if (is_array($value)) {
			$value = implode(', ', $value);
		}

		$row[$key] = $value ?: '';
	}
	return $row;
}

// CSV output
function mpro_output_csv_report($user_id = null, $client_id = 'leap4ed') {
	$fields = mpro_get_all_fields($client_id);
	$posts = mpro_get_mentor_posts($user_id, $client_id); // 🔥 Pass client_id

	while (ob_get_level()) ob_end_clean();
	header('Content-Type: text/csv');
	header('Content-Disposition: attachment;filename=mentor_report.csv');
	echo "\xEF\xBB\xBF"; // BOM for Excel (important for special characters)
	$output = fopen('php://output', 'w');

	// First row: column headers
	fputcsv($output, array_merge(['ID', 'Name'], array_values($fields)));

	// Data rows
	foreach ($posts as $post) {
		$meta = mpro_get_formatted_meta($post->ID, $fields);
		// deprecated fputcsv($output, array_merge([$post->ID, $post->post_title], array_values($meta)));
		fputcsv($output, array_merge([$post->ID, $post->post_title], array_values($meta)), ',', '"', "\\");

	}

	fclose($output);
	exit;
}

// Shortcode
add_shortcode('data_report', 'mpro_generate_report');

function mpro_generate_report($atts) {
	$atts = shortcode_atts(array(
		'clientid' => '',
	), $atts);

	$client_id = sanitize_text_field($atts['clientid']);
	$user_id = isset($_GET['user_id']) ? intval($_GET['user_id']) : null;

	// If CSV is requested
	if (isset($_GET['csv']) && $_GET['csv'] === '1') {
		mpro_output_csv_report($user_id, $client_id); // <-- pass client ID
		return; // stop after sending CSV
	}

	$fields = mpro_get_all_fields($client_id);
	$posts = mpro_get_mentor_posts($user_id, $client_id); // <-- pass client ID

	ob_start();

	if ($user_id && $posts) {  // not in use - shows a one page report of one mentor/ee
		$post = $posts[0];

		echo "<h2>" . esc_html($post->post_title) . "</h2>";
		echo "<table border='1' cellspacing='0' cellpadding='8' style='border-collapse: collapse; width: 60%;'>";
		echo "<thead><tr><th width='25%'>Field</th><th>Response</th></tr></thead><tbody>";

		$meta = mpro_get_formatted_meta($post->ID, $fields);

		foreach ($fields as $key => $label) {
			echo "<tr><td>" . esc_html($label) . "</td><td>" . esc_html($meta[$key]) . "</td></tr>";
		}

		echo "</tbody></table>";

	} else {
		
		// Generate download CSV button
		$csv_url = add_query_arg('csv', '1', $_SERVER['REQUEST_URI']);
		echo '<p><a href="' . esc_url($csv_url) . '" class="button" style="padding: 8px 16px; background-color: #0073aa; color: #fff; text-decoration: none; border-radius: 4px;">Download CSV</a></p>';

		echo "<h2>Mentor/Mentee Report</h2>";
		mpro_render_program_counts( $client_id ); // display number of mentors and mentees
		
		echo '<div style="overflow-x: auto;"><table border="1" cellspacing="0" cellpadding="8" style="border-collapse: collapse; width: 100%; min-width: 600px;">';
		echo "<thead><tr><th>Admin</th><th>ID</th><th>Name</th>";

		foreach ($fields as $label) {
			echo "<th>" . esc_html($label) . "</th>";
		}

		echo "</tr></thead><tbody>";

		foreach ($posts as $post) {
			$meta = mpro_get_formatted_meta($post->ID, $fields);
			
			
			if ( current_user_can('delete_post', $post->ID) ) {
				$redirect_to = remove_query_arg( 'mpro_deleted', wp_unslash( $_SERVER['REQUEST_URI'] ) );
				$delete_url = wp_nonce_url(
					add_query_arg(
						[
							'action'      => 'mpro_delete_submission',
							'post_id'     => $post->ID,
							'redirect_to' => $redirect_to, // <— pass it along
						],
						admin_url('admin-post.php')
					),
					"mpro_delete_submission_{$post->ID}"
				);
				$delete_cell = '<a href="' . esc_url($delete_url) . '" class="button button-link-delete" style="color:#ffffff;">'
								 . esc_html__('Remove', 'mpro') . '</a>';
				$delete_cell = wp_kses($delete_cell, [
						'a' => [
							'href'  => true,
							'class' => true,
							'style' => true,
							'title' => true,
						],
				]);
			} else {
				$delete_cell = '&mdash;';
			}
			
			


			echo "<tr><td>" . $delete_cell . "</td><td>" . intval($post->ID) . "</td><td>" . esc_html($post->post_title) . "</td>";

			foreach ($fields as $key => $label) {
				echo "<td>" . esc_html($meta[$key]) . "</td>";
			}

			echo "</tr>";
		}

		echo "</tbody></table></div>";
	}

	return ob_get_clean();
}

function mpro_handle_delete_submission() {
	error_log('[mpro] delete handler hit with post_id=' . ($_GET['post_id'] ?? '(none)'));

	if ( ! isset($_GET['post_id']) ) {
		wp_die(__('Missing post_id', 'mpro'));
	}

	$post_id = absint($_GET['post_id']);
	if ( ! $post_id || get_post_type($post_id) !== 'mentor_submission' ) {
		wp_die(__('Invalid submission.', 'mpro'));
	}

	check_admin_referer("mpro_delete_submission_{$post_id}");

	if ( ! current_user_can('delete_post', $post_id) ) {
		wp_die(__('You do not have permission to delete this submission.', 'mpro'));
	}

	$result = wp_trash_post($post_id);

	// Prefer explicit redirect_to, else use referer, else fall back to CPT list
	$redirect = isset($_GET['redirect_to']) ? wp_unslash($_GET['redirect_to']) : wp_get_referer();
	$redirect = wp_validate_redirect(
		$redirect,
		add_query_arg(['post_type' => 'mentor_submission'], admin_url('edit.php'))
	);

	// Append a flag so the view can show a message
	$redirect = add_query_arg(['mpro_deleted' => $result ? '1' : '0'], $redirect);

	wp_safe_redirect($redirect);
	exit;
}
// Make sure the delete handler is registered:
add_action('admin_post_mpro_delete_submission', 'mpro_handle_delete_submission');      // logged-in users
add_action('admin_post_nopriv_mpro_delete_submission', 'mpro_handle_delete_submission'); // if you ever allow not-logged-in (probably not needed)


if ( isset($_GET['mpro_deleted']) ) {
	if ( $_GET['mpro_deleted'] === '1' ) {
		echo '<div class="notice notice-success" style="margin:1em 0;padding:.5em 1em;background:#e7f7e7;border-left:4px solid #46b450;">'
		   . esc_html__('Submission moved to Trash.', 'mpro') . '</div>';
	} else {
		echo '<div class="notice notice-error" style="margin:1em 0;padding:.5em 1em;background:#fdecea;border-left:4px solid #dc3232;">'
		   . esc_html__('Could not remove submission.', 'mpro') . '</div>';
	}
}
