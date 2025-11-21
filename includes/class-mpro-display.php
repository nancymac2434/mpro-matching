<?php
//
// calls the matching algorithm and generates the table
//
class MPro_Display {

	public function __construct() {
		add_shortcode('mentor_matches', [$this, 'display_matches']);
	}

	public function display_matches( $atts ) {
		// Set a default value and merge with any passed attributes.
		$atts = shortcode_atts( array(
			'client_id' => 'mentorpro', // Default client ID if not provided.
		), $atts, 'mentor_matches' );

		// Sanitize and validate client ID
		$client_id = sanitize_key( $atts['client_id'] );
		$valid_clients = array( 'leap4ed-chp', 'salem', 'coffee', 'mentorpro' );

		if ( ! in_array( $client_id, $valid_clients, true ) ) {
			error_log( "MPro Matching: Invalid client ID attempted: " . esc_html( $atts['client_id'] ) );
			return "<p>Invalid client ID. Please contact the administrator.</p>";
		}

		// Determine and load the appropriate matching class
		$matching = null;

		switch ( $client_id ) {
			case 'leap4ed-chp':
				require_once plugin_dir_path( __FILE__ ) . 'class-leap4ed-matching.php';
				$matching = new Leap4Ed_Matching( $client_id );
				break;

			case 'salem':
				require_once plugin_dir_path( __FILE__ ) . 'class-salem-matching.php';
				$matching = new Salem_Matching( $client_id );
				break;

			case 'coffee':
				require_once plugin_dir_path( __FILE__ ) . 'class-coffee-matching.php';
				$matching = new Coffee_Matching( $client_id );
				break;

			case 'mentorpro':
			default:
				require_once plugin_dir_path( __FILE__ ) . 'class-matching-base.php';
				// Fall back to base class for mentorpro
				$matching = new Matching_Base( $client_id );
				break;
		}

// Call the matching algorithm
$result = $matching->generate_matching_report();
$all_matches = $result['matches'] ?? [];
$approved_matches = $result['approved_matches'] ?? [];
$suggested_matches = $result['suggested_matches'] ?? [];
$unmatched_mentees = $result['unmatched_mentees'] ?? [];
$unmatched_mentors = $result['unmatched_mentors'] ?? [];
$mentees_no_language_match = $result['mentees_no_language_match'] ?? [];

if (empty($all_matches)) {
	return "<p>No matches found for client <strong>$client_id</strong>.</p>";
}
		ob_start();
		
/*		if (!empty($unmatched_mentees)) {
			echo '<h3>🚫 Unmatched Mentees</h3><ul>';
			echo '<ul>';
			foreach ($unmatched_mentees as $mentee_name) {
				// Find mentee ID based on name
				$mentee_id = array_search($mentee_name, array_map('get_the_title', array_keys($mentees_no_language_match)));
error_log("Array_keys : =" . print_r(array_keys, true));
				
				//$mentee_id = false;
				if ($mentee_id !== false) {
					// This mentee was in the "no language match" list
					echo '<li>' . esc_html($mentee_name) . ' — No common language match</li>';
				} else {
					// Regular unmatched mentee
					echo '<li>';
					echo esc_html($mentee_name);
					echo '</li>';
				}
			}
			echo '</ul>';
		}

*/
if (!empty($unmatched_mentees)) {
	echo '<h3>🚫 Unmatched Mentees</h3><ul>';

	foreach ($unmatched_mentees as $mentee_name) {
		// Try to find the mentee ID by searching the matches/results if you also returned a mapping,
		// but simplest is to look up the post by title (only if titles are unique).
		// Better: change generate_matching_report() to also return a map of mentee_name => id. 
		// For now, try to find ID by title (fallback).
		$mentee_id = 0;
		$found = get_page_by_title($mentee_name, OBJECT, 'mentor_submission');
		if ($found) {
			$mentee_id = (int)$found->ID;
		}

		if ($mentee_id && isset($mentees_no_language_match[$mentee_id])) {
			echo '<li>' . esc_html($mentee_name) . ' — No common language match</li>';
		} else {
			// Regular unmatched mentee (likely capacity / no available mentor)
			echo '<li>' . esc_html($mentee_name) . '</li>';
		}
	}
	echo '</ul>';
}

$mentees_skipped = $result['mentees_skipped'] ?? [];
if ($mentees_skipped) {
	echo '<h3>⏸️ Mentees not participating (Max Matches = 0)</h3><ul>';
	foreach ($mentees_skipped as $n) echo '<li>' . esc_html($n) . '</li>';
	echo '</ul>';
}
		
		if (!empty($unmatched_mentors)) {
			echo '<h3>🤝 Mentors Without Mentees</h3><ul>';
			foreach ($unmatched_mentors as $mentor) {
				echo "<li>".$mentor;
				echo "</li>";
			}
			echo '</ul>';
		}
		// Build mentor match counts (from all matches)
		$mentor_match_counts = [];
		foreach ( $all_matches as $match ) {
			$mentor_id = $match['mentor_id'];
			if ( ! isset( $mentor_match_counts[ $mentor_id ] ) ) {
				$mentor_match_counts[ $mentor_id ] = 0;
			}
			$mentor_match_counts[ $mentor_id ]++;
		}

		mpro_render_program_counts( $client_id );
		?>
		<form method="post" action="<?php echo esc_url(admin_url('admin-post.php')); ?>">
		  <input type="hidden" name="action" value="mpro_download_matches">
		  <input type="hidden" name="client_id" value="<?php echo esc_attr($client_id); ?>">
		  <?php wp_nonce_field('leap4ed_download_csv', 'leap4ed_csv_nonce'); ?>
		  <input type="submit" value="📥 Download Matches CSV" class="button button-primary">
		</form>

		<style>
			.mpro-action-btn {
				padding: 5px 12px;
				border: none;
				border-radius: 4px;
				cursor: pointer;
				font-size: 12px;
				font-weight: 500;
				transition: all 0.2s;
			}
			.mpro-approve-btn {
				background: #10b981;
				color: white;
			}
			.mpro-approve-btn:hover {
				background: #059669;
			}
			.mpro-unapprove-btn {
				background: #ef4444;
				color: white;
			}
			.mpro-unapprove-btn:hover {
				background: #dc2626;
			}
			.mpro-action-btn:disabled {
				opacity: 0.5;
				cursor: not-allowed;
			}
			.mpro-approved-badge {
				display: inline-block;
				background: #10b981;
				color: white;
				padding: 2px 8px;
				border-radius: 4px;
				font-size: 11px;
				font-weight: 600;
				margin-left: 8px;
			}
			.mpro-message {
				padding: 10px 15px;
				margin: 10px 0;
				border-radius: 4px;
				display: none;
			}
			.mpro-message.success {
				background: #d1fae5;
				border: 1px solid #10b981;
				color: #065f46;
			}
			.mpro-message.error {
				background: #fee2e2;
				border: 1px solid #ef4444;
				color: #991b1b;
			}
		</style>

		<div id="mpro-message" class="mpro-message"></div>

		<?php if (!empty($approved_matches)): ?>
		<h2 style="margin-top: 30px;">✅ Approved Matches</h2>
		<p style="color: #666; margin-bottom: 15px;">These matches are locked and won't change when the page reloads.</p>
		<table class="widefat mpro-matches-table" style="width:100%; border-collapse: collapse; margin-top:20px;">
			<thead>
				<tr>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 15%;">Mentee</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 15%;">Mentor</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 8%;">Status</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Notes</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 10%;">Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($approved_matches as $match): ?>
				<tr data-mentee-id="<?php echo esc_attr($match['mentee_id']); ?>" data-mentor-id="<?php echo esc_attr($match['mentor_id']); ?>">
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php echo esc_html($match['mentee']); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php
						$count = isset( $mentor_match_counts[ $match['mentor_id'] ] )
							? " ({$mentor_match_counts[$match['mentor_id']]})"
							: '';
						echo esc_html($match['mentor'] . $count); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<span class="mpro-approved-badge">APPROVED</span>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php echo esc_html($match['field']); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<button class="mpro-action-btn mpro-unapprove-btn"
								data-action="unapprove"
								data-mentee-id="<?php echo esc_attr($match['mentee_id']); ?>"
								data-mentor-id="<?php echo esc_attr($match['mentor_id']); ?>">
							Unapprove
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>

		<?php if (!empty($suggested_matches)): ?>
		<h2 style="margin-top: 30px;">💡 Suggested Matches</h2>
		<p style="color: #666; margin-bottom: 15px;">These are algorithm-generated suggestions. Click "Approve" to lock a match.</p>
		<table class="widefat mpro-matches-table" style="width:100%; border-collapse: collapse; margin-top:20px;">
			<thead>
				<tr>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 15%;">Mentee</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 15%;">Mentor</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 8%;">Score</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Matched Fields</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 10%;">Actions</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($suggested_matches as $match): ?>
				<tr data-mentee-id="<?php echo esc_attr($match['mentee_id']); ?>" data-mentor-id="<?php echo esc_attr($match['mentor_id']); ?>">
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php echo esc_html($match['mentee']); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php
						$count = isset( $mentor_match_counts[ $match['mentor_id'] ] )
							? " ({$mentor_match_counts[$match['mentor_id']]})"
							: '';
						echo esc_html($match['mentor'] . $count); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php echo esc_html($match['percentage']); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php echo esc_html($match['field']); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<button class="mpro-action-btn mpro-approve-btn"
								data-action="approve"
								data-mentee-id="<?php echo esc_attr($match['mentee_id']); ?>"
								data-mentor-id="<?php echo esc_attr($match['mentor_id']); ?>">
							Approve
						</button>
					</td>
				</tr>
			<?php endforeach; ?>
			</tbody>
		</table>
		<?php endif; ?>
		<?php
		?>
		<!-- Modal HTML -->
		<div id="reportModal" style="display: none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.6); z-index: 9999;">
		  <div style="position: relative; background: #fff; margin: 5% auto; max-width: 900px; padding: 20px; box-shadow: 0 0 10px #000;">
			<button id="closeReportModal" style="position: absolute; top: 10px; right: 10px;">✖</button>
			<iframe id="reportFrame" src="" width="100%" height="600" style="border: none;"></iframe>
		  </div>
		</div>
		
		<script>
		document.addEventListener('DOMContentLoaded', function () {
		  const modal = document.getElementById('reportModal');
		  const iframe = document.getElementById('reportFrame');
		  const closeBtn = document.getElementById('closeReportModal');

		  document.addEventListener('click', function (e) {
			  const link = e.target.closest('.open-report-modal');
			  if (!link) return;

			  e.preventDefault();

			  const userId = link.getAttribute('data-user');
			  const clientId = link.getAttribute('data-client') || 'leap4ed';
			  const reportUrl = "https://template.mentorpro.com/" + clientId + "-data-report/?user_id=" + userId;

			  const modal = document.getElementById('reportModal');
			  const iframe = document.getElementById('reportFrame');

			  iframe.src = reportUrl;
			  modal.style.display = 'block';
		  });

		  closeBtn.addEventListener('click', function () {
			modal.style.display = 'none';
			iframe.src = '';
		  });

		  // Handle approve/unapprove actions
		  document.addEventListener('click', function(e) {
			  const btn = e.target.closest('.mpro-action-btn');
			  if (!btn) return;

			  e.preventDefault();

			  const action = btn.dataset.action;
			  const menteeId = btn.dataset.menteeId;
			  const mentorId = btn.dataset.mentorId;
			  const messageEl = document.getElementById('mpro-message');

			  // Disable button during request
			  btn.disabled = true;
			  const originalText = btn.textContent;
			  btn.textContent = 'Processing...';

			  // Make AJAX request
			  const formData = new FormData();
			  formData.append('action', 'mpro_' + action + '_match');
			  formData.append('nonce', '<?php echo wp_create_nonce('mpro_match_action'); ?>');
			  formData.append('mentee_id', menteeId);
			  formData.append('mentor_id', mentorId);

			  fetch('<?php echo admin_url('admin-ajax.php'); ?>', {
				  method: 'POST',
				  body: formData
			  })
			  .then(response => response.json())
			  .then(data => {
				  if (data.success) {
					  // Show success message
					  messageEl.textContent = data.data.message;
					  messageEl.className = 'mpro-message success';
					  messageEl.style.display = 'block';

					  // Reload page after short delay to show updated matches
					  setTimeout(() => {
						  window.location.reload();
					  }, 1000);
				  } else {
					  // Show error message
					  messageEl.textContent = data.data.message;
					  messageEl.className = 'mpro-message error';
					  messageEl.style.display = 'block';

					  // Re-enable button
					  btn.disabled = false;
					  btn.textContent = originalText;

					  // Hide message after 5 seconds
					  setTimeout(() => {
						  messageEl.style.display = 'none';
					  }, 5000);
				  }
			  })
			  .catch(error => {
				  console.error('Error:', error);
				  messageEl.textContent = 'An error occurred. Please try again.';
				  messageEl.className = 'mpro-message error';
				  messageEl.style.display = 'block';

				  // Re-enable button
				  btn.disabled = false;
				  btn.textContent = originalText;

				  // Hide message after 5 seconds
				  setTimeout(() => {
					  messageEl.style.display = 'none';
				  }, 5000);
			  });
		  });
		});
		</script>
		<?php
		return ob_get_clean();
	}
}

