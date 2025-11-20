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
		'client_id' => '',
	), $atts);

	$client_id = sanitize_text_field($atts['client_id']);
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
		echo "<thead><tr><th>Admin</th><th>Status</th><th>ID</th><th>Name</th>";

		foreach ($fields as $label) {
			echo "<th>" . esc_html($label) . "</th>";
		}

		echo "</tr></thead><tbody>";

		foreach ($posts as $post) {
			$meta = mpro_get_formatted_meta($post->ID, $fields);

			// Get status
			$status = get_post_meta($post->ID, 'mpro_status', true);
			$status = $status ?: 'active';
			$status_display = $status === 'inactive' ? '<span style="color:#dc3232;">Inactive</span>' : '<span style="color:#46b450;">Active</span>';

			if ( current_user_can('edit_post', $post->ID) ) {
				$edit_cell = '<a href="#" class="button mpro-edit-submission" data-post-id="' . intval($post->ID) . '" data-client-id="' . esc_attr($client_id) . '">'
								 . esc_html__('Edit', 'mpro') . '</a>';
			} else {
				$edit_cell = '&mdash;';
			}


			echo "<tr><td>" . $edit_cell . "</td><td>" . $status_display . "</td><td>" . intval($post->ID) . "</td><td>" . esc_html($post->post_title) . "</td>";

			foreach ($fields as $key => $label) {
				echo "<td>" . esc_html($meta[$key]) . "</td>";
			}

			echo "</tr>";
		}

		echo "</tbody></table></div>";

		// Add edit modal
		?>
		<div id="mproEditModal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.7); z-index:9999; overflow-y:auto;">
			<div style="position:relative; background:#fff; margin:2% auto; max-width:800px; padding:30px; box-shadow:0 0 20px rgba(0,0,0,0.3); border-radius:8px;">
				<button id="closeMproModal" style="position:absolute; top:15px; right:15px; font-size:24px; background:none; border:none; cursor:pointer;">&times;</button>
				<h2>Edit Submission</h2>
				<div id="mproEditFormContainer">
					<p style="text-align:center; padding:40px;">Loading...</p>
				</div>
			</div>
		</div>

		<script>
		jQuery(document).ready(function($) {
			// Open edit modal
			$(document).on('click', '.mpro-edit-submission', function(e) {
				e.preventDefault();
				var postId = $(this).data('post-id');
				var clientId = $(this).data('client-id');

				$('#mproEditModal').show();
				$('#mproEditFormContainer').html('<p style="text-align:center; padding:40px;">Loading...</p>');

				// Load submission data
				$.ajax({
					url: ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'mpro_load_submission',
						post_id: postId,
						client_id: clientId,
						nonce: '<?php echo wp_create_nonce('mpro_edit_submission'); ?>'
					},
					success: function(response) {
						if (response.success) {
							$('#mproEditFormContainer').html(response.data.html);
						} else {
							$('#mproEditFormContainer').html('<p style="color:red;">Error loading submission data.</p>');
						}
					},
					error: function() {
						$('#mproEditFormContainer').html('<p style="color:red;">Error loading submission data.</p>');
					}
				});
			});

			// Close modal
			$('#closeMproModal').on('click', function() {
				$('#mproEditModal').hide();
			});

			// Close on background click
			$('#mproEditModal').on('click', function(e) {
				if (e.target.id === 'mproEditModal') {
					$(this).hide();
				}
			});

			// Handle save button
			$(document).on('click', '#mproSaveSubmission', function(e) {
				e.preventDefault();
				var $form = $('#mproEditForm');
				var formData = $form.serialize();

				$(this).prop('disabled', true).text('Saving...');

				$.ajax({
					url: ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: formData + '&action=mpro_save_submission&nonce=<?php echo wp_create_nonce('mpro_save_submission'); ?>',
					success: function(response) {
						if (response.success) {
							location.reload();
						} else {
							alert('Error: ' + (response.data.message || 'Failed to save'));
							$('#mproSaveSubmission').prop('disabled', false).text('Save');
						}
					},
					error: function() {
						alert('Error saving submission');
						$('#mproSaveSubmission').prop('disabled', false).text('Save');
					}
				});
			});

			// Handle delete button
			$(document).on('click', '#mproDeleteSubmission', function(e) {
				e.preventDefault();
				if (!confirm('Are you sure you want to delete this submission? This cannot be undone.')) {
					return;
				}

				var postId = $(this).data('post-id');

				$(this).prop('disabled', true).text('Deleting...');

				$.ajax({
					url: ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'mpro_delete_submission_ajax',
						post_id: postId,
						nonce: '<?php echo wp_create_nonce('mpro_delete_submission'); ?>'
					},
					success: function(response) {
						if (response.success) {
							location.reload();
						} else {
							alert('Error: ' + (response.data.message || 'Failed to delete'));
							$('#mproDeleteSubmission').prop('disabled', false).text('Delete');
						}
					},
					error: function() {
						alert('Error deleting submission');
						$('#mproDeleteSubmission').prop('disabled', false).text('Delete');
					}
				});
			});

			// Handle toggle status button
			$(document).on('click', '#mproToggleStatus', function(e) {
				e.preventDefault();
				var postId = $(this).data('post-id');
				var currentStatus = $(this).data('current-status');

				$(this).prop('disabled', true).text('Updating...');

				$.ajax({
					url: ajaxurl || '<?php echo admin_url('admin-ajax.php'); ?>',
					type: 'POST',
					data: {
						action: 'mpro_toggle_status',
						post_id: postId,
						current_status: currentStatus,
						nonce: '<?php echo wp_create_nonce('mpro_toggle_status'); ?>'
					},
					success: function(response) {
						if (response.success) {
							location.reload();
						} else {
							alert('Error: ' + (response.data.message || 'Failed to update status'));
							$('#mproToggleStatus').prop('disabled', false).text(currentStatus === 'active' ? 'Mark Inactive' : 'Mark Active');
						}
					},
					error: function() {
						alert('Error updating status');
						$('#mproToggleStatus').prop('disabled', false).text(currentStatus === 'active' ? 'Mark Inactive' : 'Mark Active');
					}
				});
			});
		});
		</script>
		<?php
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

// AJAX handler to load submission data
function mpro_ajax_load_submission() {
	check_ajax_referer('mpro_edit_submission', 'nonce');

	$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
	$client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';

	if (!$post_id || get_post_type($post_id) !== 'mentor_submission') {
		wp_send_json_error(['message' => 'Invalid submission']);
	}

	if (!current_user_can('edit_post', $post_id)) {
		wp_send_json_error(['message' => 'Permission denied']);
	}

	// Get all fields for this client
	$fields = mpro_get_all_fields($client_id);
	$meta = mpro_get_formatted_meta($post_id, $fields);
	$post = get_post($post_id);

	// Get status
	$status = get_post_meta($post_id, 'mpro_status', true);
	$status = $status ?: 'active';

	// Whitelist of editable text fields (excludes dropdown/multi-select fields that must match Gravity Forms)
	$editable_fields = [
		'mpro_role',
		'mpro_email',
		'mpro_position_title',
		'mpro_company_name',
		'mpro_brief_bio',
	];

	// Build form HTML
	ob_start();
	?>
	<form id="mproEditForm" style="max-height:500px; overflow-y:auto; margin-bottom:20px;">
		<input type="hidden" name="post_id" value="<?php echo esc_attr($post_id); ?>">
		<input type="hidden" name="client_id" value="<?php echo esc_attr($client_id); ?>">

		<table class="form-table" style="width:100%;">
			<tr>
				<th style="width:30%;"><label>Name</label></th>
				<td>
					<input type="text" name="post_title" value="<?php echo esc_attr($post->post_title); ?>" class="regular-text" style="width:100%;">
				</td>
			</tr>
			<?php foreach ($fields as $key => $label):
				$value = isset($meta[$key]) ? $meta[$key] : '';
				// Skip post_date as it's not editable
				if ($key === 'post_date') continue;
				// Only show whitelisted editable fields
				if (!in_array($key, $editable_fields)) continue;
			?>
			<tr>
				<th><label><?php echo esc_html($label); ?></label></th>
				<td>
					<?php if ($key === 'mpro_role'): ?>
						<select name="<?php echo esc_attr($key); ?>" style="width:100%;">
							<option value="1" <?php selected($value, 'Mentee'); ?>>Mentee</option>
							<option value="2" <?php selected($value, 'Mentor'); ?>>Mentor</option>
						</select>
					<?php else: ?>
						<textarea name="<?php echo esc_attr($key); ?>" rows="2" style="width:100%;"><?php echo esc_textarea($value); ?></textarea>
					<?php endif; ?>
				</td>
			</tr>
			<?php endforeach; ?>
		</table>
	</form>

	<div style="display:flex; gap:10px; justify-content:space-between; padding-top:20px; border-top:1px solid #ddd;">
		<button type="button" id="mproSaveSubmission" class="button button-primary">Save</button>
		<div>
			<button type="button" id="mproToggleStatus" class="button" data-post-id="<?php echo esc_attr($post_id); ?>" data-current-status="<?php echo esc_attr($status); ?>">
				<?php echo $status === 'active' ? 'Mark Inactive' : 'Mark Active'; ?>
			</button>
			<button type="button" id="mproDeleteSubmission" class="button button-link-delete" data-post-id="<?php echo esc_attr($post_id); ?>" style="color:#dc3232;">Delete</button>
		</div>
	</div>
	<?php
	$html = ob_get_clean();

	wp_send_json_success(['html' => $html]);
}
add_action('wp_ajax_mpro_load_submission', 'mpro_ajax_load_submission');

// AJAX handler to save submission
function mpro_ajax_save_submission() {
	check_ajax_referer('mpro_save_submission', 'nonce');

	$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
	$client_id = isset($_POST['client_id']) ? sanitize_text_field($_POST['client_id']) : '';

	if (!$post_id || get_post_type($post_id) !== 'mentor_submission') {
		wp_send_json_error(['message' => 'Invalid submission']);
	}

	if (!current_user_can('edit_post', $post_id)) {
		wp_send_json_error(['message' => 'Permission denied']);
	}

	// Update post title
	if (isset($_POST['post_title'])) {
		wp_update_post([
			'ID' => $post_id,
			'post_title' => sanitize_text_field($_POST['post_title'])
		]);
	}

	// Get all fields for this client
	$fields = mpro_get_all_fields($client_id);

	// Update all meta fields
	foreach ($fields as $key => $label) {
		if (isset($_POST[$key]) && $key !== 'post_date') {
			$value = $_POST[$key];

			// Special handling for role
			if ($key === 'mpro_role') {
				$value = absint($value);
			} else {
				$value = sanitize_textarea_field($value);
			}

			update_post_meta($post_id, $key, $value);
		}
	}

	wp_send_json_success(['message' => 'Submission updated']);
}
add_action('wp_ajax_mpro_save_submission', 'mpro_ajax_save_submission');

// AJAX handler to delete submission
function mpro_ajax_delete_submission() {
	check_ajax_referer('mpro_delete_submission', 'nonce');

	$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;

	if (!$post_id || get_post_type($post_id) !== 'mentor_submission') {
		wp_send_json_error(['message' => 'Invalid submission']);
	}

	if (!current_user_can('delete_post', $post_id)) {
		wp_send_json_error(['message' => 'Permission denied']);
	}

	$result = wp_trash_post($post_id);

	if ($result) {
		wp_send_json_success(['message' => 'Submission deleted']);
	} else {
		wp_send_json_error(['message' => 'Failed to delete submission']);
	}
}
add_action('wp_ajax_mpro_delete_submission_ajax', 'mpro_ajax_delete_submission');

// AJAX handler to toggle status
function mpro_ajax_toggle_status() {
	check_ajax_referer('mpro_toggle_status', 'nonce');

	$post_id = isset($_POST['post_id']) ? absint($_POST['post_id']) : 0;
	$current_status = isset($_POST['current_status']) ? sanitize_text_field($_POST['current_status']) : 'active';

	if (!$post_id || get_post_type($post_id) !== 'mentor_submission') {
		wp_send_json_error(['message' => 'Invalid submission']);
	}

	if (!current_user_can('edit_post', $post_id)) {
		wp_send_json_error(['message' => 'Permission denied']);
	}

	// Toggle status
	$new_status = $current_status === 'active' ? 'inactive' : 'active';
	update_post_meta($post_id, 'mpro_status', $new_status);

	wp_send_json_success(['message' => 'Status updated', 'new_status' => $new_status]);
}
add_action('wp_ajax_mpro_toggle_status', 'mpro_ajax_toggle_status');
