<?php
//
// allows client to upload xls and generate custom post and assign the metadata
//
function mpro_mentor_matching_import_tool_shortcode() {
	if (!current_user_can('manage_options')) {
		return '<p>You do not have sufficient permissions to access this page.</p>';
	}

	ob_start(); // Start output buffering
	?>
	<div class="wrap">
		<h1>Mentee/Mentor Import Tool</h1>

		<?php
		if (!empty($_POST['import_csv']) && isset($_FILES['csv_file']) && $_FILES['csv_file']['error'] === UPLOAD_ERR_OK) {
			$csv_file = $_FILES['csv_file']['tmp_name'];

			if (($handle = fopen($csv_file, 'r')) !== false) {
				$header = fgetcsv($handle, 1000, ',');
				$count = 0;

				while (($row = fgetcsv($handle, 1000, ',')) !== false) {
					$data = array_map('trim', array_combine($header, $row));

					if (!$data || empty($data['mpro_lname'])) {
						continue;
					}

					$post_id = wp_insert_post([
						'post_title'  => sanitize_text_field($data['mpro_fname']) . ' ' . sanitize_text_field($data['mpro_lname']),
						'post_type'   => 'mentor_submission',
						'post_status' => 'publish'
					]);
echo "<table>";
					if (!is_wp_error($post_id)) {
						foreach ($data as $key => $value) {
							$multi_fields = ['mpro_interests', 'mpro_languages'];

							$meta_key = sanitize_key($key);

							if (in_array($meta_key, $multi_fields)) {
								$array_value = array_map('trim', explode(',', $value));
								update_post_meta($post_id, $meta_key, $array_value);
echo "<tr><td>" . $meta_key . "</td><td>" . $array_value . "</tr>";
							} else {
								update_post_meta($post_id, $meta_key, sanitize_text_field($value));
echo "<tr><td>" . $meta_key . "</td><td>" . $value . "</tr>";
							}
						}
						$count++;
					}
				}
echo "</table>";

				fclose($handle);
				echo '<div class="notice notice-success"><p>Imported ' . esc_html($count) . ' records.</p></div>';
			} else {
				echo '<div class="notice notice-error"><p>Could not open CSV file.</p></div>';
			}
		} elseif (!empty($_POST['import_csv'])) {
			echo '<div class="notice notice-error"><p>File upload failed. Please check the file and try again.</p></div>';
		}
		?>

		<form method="post" enctype="multipart/form-data">
			<p><label for="csv_file">Choose CSV file:</label> <input type="file" name="csv_file" required></p>
			<p><input type="submit" name="import_csv" class="button button-primary" value="Import"></p>
		</form>
	</div>
	<?php
	return ob_get_clean(); // Return buffered content
}
add_shortcode('mentor_import_tool', 'mpro_mentor_matching_import_tool_shortcode');
