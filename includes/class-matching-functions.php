<?php
function get_matching_class_for_client( $client_id ) {
	switch ( $client_id ) {
		case 'leap4ed-chp':
			return new Leap4Ed_Matching($client_id);
		case 'salem':
			return new Salem_Matching($client_id);
		default:
			return new Matching_Base($client_id); // fallback or wp_die
	}
}

/**
 * Per-mentor cap with fallback to client default.
 * Blank meta = use default. 0 = take none.
 */
if ( ! function_exists('mpro_get_mentor_cap') ) {
	function mpro_get_mentor_cap( $mentor_id, $client_default_cap ) {
		$cap = get_post_meta( $mentor_id, 'max_matches_per_mentor', true );
		if ($cap === '' || $cap === null) return (int)$client_default_cap;
		return max(0, (int)$cap);
	}
}

// Rebalance: enforce per-mentor caps by trimming lowest scores first.
// NOTE: This version only trims; it does not re-place overflowed mentees.
if (!function_exists('rebalance_matches_by_caps')) {
	function rebalance_matches_by_caps(array $matches, array $mentor_caps, array $mentor_names = []) {
		// 1) Group match row indexes by mentor
		$by_mentor = []; // mentor_id => [rowIndex, ...]
		foreach ($matches as $i => $row) {
			$mid = isset($row['mentor_id']) ? (int)$row['mentor_id'] : 0;
			if ($mid <= 0) { continue; }
			$by_mentor[$mid][] = $i;
		}

		// 2) For mentors over cap, mark the lowest-score rows for removal
		$pool_indexes = [];
		foreach ($by_mentor as $mid => $idxs) {
			$cap = isset($mentor_caps[$mid]) ? (int)$mentor_caps[$mid] : 0;
			if ($cap < 0) { $cap = 0; }

			if (count($idxs) > $cap) {
				// Sort these row indexes by score ASC (lowest first)
				usort($idxs, function($a, $b) use ($matches) {
					$sa = isset($matches[$a]['score']) ? (float)$matches[$a]['score'] : 0.0;
					$sb = isset($matches[$b]['score']) ? (float)$matches[$b]['score'] : 0.0;
					return $sa <=> $sb; // ASC
				});

				$to_trim = count($idxs) - $cap;
				$pool_indexes = array_merge($pool_indexes, array_slice($idxs, 0, $to_trim));
			}
		}

		// 3) If nothing to trim, return as-is
		if (empty($pool_indexes)) {
			return $matches;
		}

		// 4) Remove the pooled rows (preserve order of remaining)
		$drop = array_flip($pool_indexes);
		$pruned = [];
		foreach ($matches as $i => $row) {
			if (isset($drop[$i])) { continue; }
			$pruned[] = $row;
		}

		// (Optional) At this point we could try to re-place pooled mentees,
		// but we need a score matrix or all_potential_matches to do that well.
		// We can add that next.

		return $pruned;
	}
}
	
if ( ! function_exists('mpro_get_single_meta_values') ) {
	function mpro_get_single_meta_values( $user_id, $fields ) {
		$data = [];
		foreach ( $fields as $field ) {
			$value = get_post_meta( $user_id, $field, true );
			if ( $value ) {
				$data[$field] = $value;
			}
		}
		return $data;
	}
}

if ( ! function_exists('mpro_get_multi_meta_values') ) {
	function mpro_get_multi_meta_values( $user_id, $fields ) {
		$data = [];
		foreach ( $fields as $field ) {
			$raw = get_post_meta( $user_id, $field, true );
			if ( is_string($raw) ) {
				$data[$field] = array_map('trim', explode(',', $raw));
			} elseif ( is_array($raw) ) {
				$data[$field] = $raw;
			} else {
				$data[$field] = [];
			}
		}
		return $data;
	}
}

function normalize_string($str) {
	return strtolower(trim(preg_replace('/\s+/', ' ', $str)));
}

function apply_trait_bonus($matches, $bonus_per_item, $mentor_rank, $mentee_rank) {
	$shared_count = count($matches);
	if ($shared_count === 0) return 0;

	$base_bonus = $shared_count * $bonus_per_item;

	// Trait preference weight
	$mentor_weight = (6 - $mentor_rank);
	$mentee_weight = (6 - $mentee_rank);
	$avg_weight = ($mentor_weight + $mentee_weight) / 2;

	// Scale bonus based on how much they care about the trait
	$scaled_bonus = $base_bonus * ($avg_weight / 5.0);

	return round($scaled_bonus, 2);
}

function score_trait_match($trait_label, $mentor_pref_raw, $mentee_pref_raw, $match_count, $matching_obj) {
	$settings = $matching_obj->get_trait_settings($trait_label);

	// Convert comma-separated values to arrays
	$mentor_rankings = array_map('trim', explode(',', $mentor_pref_raw));
	$mentee_rankings = array_map('trim', explode(',', $mentee_pref_raw));

	// Find position (0-based index)
	$mentor_index = array_search($trait_label, $mentor_rankings);
	$mentee_index = array_search($trait_label, $mentee_rankings);

	// Convert to 1-based ranks
	$mentor_rank = $mentor_index !== false ? $mentor_index + 1 : null;
	$mentee_rank = $mentee_index !== false ? $mentee_index + 1 : null;

	// Ranking function: 1st = 5, 2nd = 4 ... 5th = 1, others = 0
	$get_weight = function($rank) {
		return (is_numeric($rank) && $rank >= 1 && $rank <= 5) ? (6 - $rank) : 0;
	};

	$mentor_weight = $get_weight($mentor_rank);
	$mentee_weight = $get_weight($mentee_rank);

	// Average weight reflects how strongly this trait is prioritized
	$relevance_score = ($mentor_weight + $mentee_weight) / 2;

	// Base points for match count
	$base_points = $match_count * ($settings['base_per_match'] ?? 0);

	$total_points = $base_points + $relevance_score;

	// Optional: cap the score
	$cap = $settings['cap'] ?? null;
	if ($cap !== null) {
		$total_points = min($total_points, $cap);
	}

	return [
		'points' => $total_points,
		'message' => "{$trait_label}: {$match_count} matches — base {$base_points}, relevance {$relevance_score}, total {$total_points}"
	];
}


function smart_parse_ranked_choices($input, $valid_options) {
	if (empty($input) || !is_string($input)) {
		return []; // Return empty array safely
	}
	
	$raw_parts = array_map('trim', explode(',', $input));
	$parsed = [];

	$current = '';
	foreach ($raw_parts as $part) {
		$try = $current ? "$current, $part" : $part;

		if (in_array($try, $valid_options)) {
			$parsed[] = $try;
			$current = '';
		} else {
			$current = $try;
		}
	}

	// Edge case: one last match at end
	if ($current && in_array($current, $valid_options)) {
		$parsed[] = $current;
	}

	return $parsed;
}



/**
 * Ensures each mentor is assigned at least one mentee.
 * Reassigns from mentors with 2+ mentees to any mentor with 0.
 */
function rebalance_matches(&$matches, $all_mentor_names) {
	 $mentor_match_count = [];
	 $mentee_assignments = [];
 
	 foreach ($matches as $match) {
		 $mentor = $match['mentor'];
		 $mentee = $match['mentee'];
 
		 if (!isset($mentor_match_count[$mentor])) {
			 $mentor_match_count[$mentor] = 0;
		 }
		 $mentor_match_count[$mentor]++;
		 $mentee_assignments[$mentee] = $mentor;
	 }
 
	 $mentors_with_two = array_keys(array_filter($mentor_match_count, fn($count) => $count > 1));
	 $all_mentors = $all_mentor_names;
	 $unmatched_mentors = array_diff($all_mentors, array_keys($mentor_match_count));
 
	 foreach ($unmatched_mentors as $unmatched_mentor) {
		 $reassigned = false;
 
		 foreach ($matches as $index => $match) {
			 $current_mentor = $match['mentor'];
			 $mentee = $match['mentee'];
 
			 // Check if this mentor has 2 and we can spare one
			 if (in_array($current_mentor, $mentors_with_two)) {
 
				 // Reassign
				 $matches[$index]['mentor'] = $unmatched_mentor;
				 $matches[$index]['mentor_id'] = null; // Optional: null or update to real ID
				 $mentor_match_count[$current_mentor]--;
				 $mentor_match_count[$unmatched_mentor] = 1;
				 $mentee_assignments[$mentee] = $unmatched_mentor;
				  
				 // Stop once we’ve fixed this unmatched mentor
				 $reassigned = true;
 
				 // If mentor now has 1 mentee, remove from overmatched
				 if ($mentor_match_count[$current_mentor] === 1) {
					 $mentors_with_two = array_diff($mentors_with_two, [$current_mentor]);
				 }
 
				 break;
			 }
		 }
 
		 if (!$reassigned) {
			 error_log("❌ Could not reassign any mentee to {$unmatched_mentor}");
		 }
	 }
 }

/**
  * Get the total number of mentors and mentees for a given client.
  *
  * @param string $client_id The assigned client ID.
  * @return array {
  *   @type int $mentors Number of mentors
  *   @type int $mentees Number of mentees
  * }
  */
 function mpro_get_program_counts( $client_id ) {
	 global $wpdb;
 
	 // Count mentors
	 $mentors = $wpdb->get_var( $wpdb->prepare("
		 SELECT COUNT(*)
		 FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} r ON p.ID = r.post_id
			 AND r.meta_key = 'mpro_role' AND r.meta_value = '2'
		 INNER JOIN {$wpdb->postmeta} c ON p.ID = c.post_id
			 AND c.meta_key = 'assigned_client' AND c.meta_value = %s
		 WHERE p.post_type = 'mentor_submission'
		   AND p.post_status = 'publish'
	 ", $client_id ) );
 
	 // Count mentees
	 $mentees = $wpdb->get_var( $wpdb->prepare("
		 SELECT COUNT(*)
		 FROM {$wpdb->posts} p
		 INNER JOIN {$wpdb->postmeta} r ON p.ID = r.post_id
			 AND r.meta_key = 'mpro_role' AND r.meta_value = '1'
		 INNER JOIN {$wpdb->postmeta} c ON p.ID = c.post_id
			 AND c.meta_key = 'assigned_client' AND c.meta_value = %s
		 WHERE p.post_type = 'mentor_submission'
		   AND p.post_status = 'publish'
	 ", $client_id ) );
 
	 return [
		 'mentors' => intval( $mentors ),
		 'mentees' => intval( $mentees ),
	 ];
 }
 
 /**
  * Render the counts at the top of a report.
  *
  * @param string $client_id
  */
 function mpro_render_program_counts( $client_id ) {
	 $counts = mpro_get_program_counts( $client_id );
	 echo '<div class="program-counts">';
	 echo '<strong>Total Mentors:</strong> ' . esc_html( $counts['mentors'] ) . '<br>';
	 echo '<strong>Total Mentees:</strong> ' . esc_html( $counts['mentees'] );
	 echo '</div>';
 }

// Routes for CSV download (logged-in + nopriv if you want to allow it)
add_action('admin_post_mpro_download_matches',     'mpro_download_matches_csv'); // logged-in
add_action('admin_post_nopriv_mpro_download_matches', 'mpro_download_matches_csv'); // public

if (!function_exists('mpro_download_matches_csv')) {
	function mpro_download_matches_csv() {

		// ✅ Nonce-based security (works for logged-in AND public users)
		if (
			!isset($_POST['leap4ed_csv_nonce']) ||
			!wp_verify_nonce($_POST['leap4ed_csv_nonce'], 'leap4ed_download_csv')
		) {
			wp_die('Security check failed.');
		}

		$client_id = sanitize_text_field($_POST['client_id'] ?? 'mentorpro');

		// Build report via the right matching class
		$engine = get_matching_class_for_client($client_id);
		if (!$engine || !method_exists($engine, 'generate_matching_report')) {
			wp_die('Matching engine unavailable.');
		}

		$result  = $engine->generate_matching_report();
		$matches = $result['matches'] ?? [];

		$filename = "mpro_matches_{$client_id}.csv";

		// Send headers for a clean file download (no caching)
		nocache_headers();
		header('Content-Type: text/csv; charset=utf-8');
		header('Content-Disposition: attachment; filename="'.$filename.'"');

		$out = fopen('php://output', 'w');
		if ($out === false) wp_die('Unable to open output stream.');

		// CSV header row
		fputcsv($out, ['Mentee', 'Mentor', 'Score (%)', 'Matched Fields']);

		// CSV data rows
		foreach ($matches as $m) {
			fputcsv($out, [
				$m['mentee']     ?? '',
				$m['mentor']     ?? '',
				$m['percentage'] ?? '',
				$m['field']      ?? '',
			]);
		}

		fclose($out);
		exit;
	}
}
