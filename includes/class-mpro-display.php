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
	
		$client_id = $atts['client_id'];
	
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

	default:
		require_once plugin_dir_path( __FILE__ ) . 'class-matching-base.php';
		// Optionally fall back to a base class or log an error
		return "<p>Unknown client ID: $client_id</p>";
}

// Call the matching algorithm
$result = $matching->generate_matching_report();
$matches = $result['matches'] ?? [];
$unmatched_mentees = $result['unmatched_mentees'] ?? [];
$unmatched_mentors = $result['unmatched_mentors'] ?? [];
$mentees_no_language_match = $result['mentees_no_language_match'] ?? [];

if (empty($matches)) {
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
		// Build mentor match counts
		$mentor_match_counts = [];
		foreach ( $matches as $match ) {
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
		
		<table class="widefat" style="width:100%; border-collapse: collapse; margin-top:20px;">
			<thead>
				<tr>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 15%;">Mentee</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 15%;">Mentor</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left; width: 8%;">Score</th>
					<th style="border: 1px solid #ddd; padding: 10px; text-align: left;">Matched Fields</th>
				</tr>
			</thead>
			<tbody>
			<?php foreach ($matches as $match): ?>
				<tr>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<!--<a href="#" 
						   class="open-report-modal" 
						   data-user="<?php echo esc_attr($match['mentee_id']); ?>"
						   data-client="<?php echo esc_attr($client_id); ?>">-->
							<?php echo esc_html($match['mentee']); ?>
						<!--</a>-->
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<!--<a href="#" 
						   class="open-report-modal" 
						   data-user="<?php echo esc_attr($match['mentor_id']); ?>"
						   data-client="<?php echo esc_attr($client_id); ?>">-->
							<?php 
							// Append count in parentheses
							$count = isset( $mentor_match_counts[ $match['mentor_id'] ] )
								? " ({$mentor_match_counts[$match['mentor_id']]})"
								: '';
							echo esc_html($match['mentor'] . $count); ?>
						<!--</a>-->
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php echo esc_html($match['percentage']); ?>
					</td>
					<td style="border: 1px solid #ddd; padding: 10px;">
						<?php echo esc_html($match['field']); ?>
					</td>
				</tr>
			<?php endforeach; ?>
		</tbody>
		</table>
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
		});
		</script>
		<?php
		return ob_get_clean();
	}
}

