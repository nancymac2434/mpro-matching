<?php

// Performs the matching for the 'salem' client

require_once plugin_dir_path(__FILE__) . 'class-matching-base.php';

class Salem_Matching extends Matching_Base {

	//private $client_id;

	public function __construct( string $client_id = '' ) {
		$this->client_id = $client_id;
	}

	public function get_report_fields(): array {
		return [
			'mpro_ssu_id' => 'SSU ID',
			'post_date' => 'Date Created',
			'mpro_role' => 'Role',
			'mpro_email' => 'Email',
			'mpro_gender' => 'Gender',
			'mpro_age' => 'Age',
			'mpro_family_origin' => 'Family Origin',
			'mpro_education' => 'Education',
			'mpro_languages' => 'Language(s)',
			'mpro_career_match' => 'Career Interest',
			'mpro_languages' => 'Languages',
			'mpro_interests' => 'Hobbies/Interests',
			//'mpro_mentor_skills_have' => 'Mentor Skills Experience',
			//'mpro_mentee_skills_want' => 'Mentee Skills Interests',
			//'mpro_match_pref' => 'Matching Emphasis',
		];
	}



	public function get_all_trait_settings(): array {
		return [
			'Similar career interests' => [
				'cap' => 6.5,
				'base_per_match' => 4.0,
				'bonus_per_match' => 1.5,
				'description' => 'Career Interest',
				'bonus_eligible' => true,
			],
			'Similar mentoring goals' => [
				'cap' => 12,
				'base_per_match' => 4.0,
				'bonus_per_match' => 1.5,
				'description' => 'Skill Building',
				'bonus_eligible' => true,
			],
			'Similar hobbies and interests' => [
				'cap' => 12,
				'base_per_match' => 4.0,
				'bonus_per_match' => 1.5,
				'description' => 'Hobbies & Interests',
				'bonus_eligible' => true,
			],
			'Language Match' => [
				'cap' => 4,
				'description' => 'Language',
				'bonus_eligible' => false,
			],
			'Same Gender' => [
				'cap' => 5,
				'base_per_match' => 5.0,
				'bonus_per_match' => 1.0,
				'description' => 'Gender',
				'bonus_eligible' => false,
			],
			'Same Age' => [
				'cap' => 5,
				'base_per_match' => 5.0,
				'bonus_per_match' => 1.0,
				'description' => 'Age Range',
				'bonus_eligible' => false,
			],
			'Similar family and cultural' => [
				'cap' => 6,
				'base_per_match' => 4,
				'bonus_per_match' => 1.5,
				'description' => 'Family and cultural',
				'bonus_eligible' => true,
			],
		];
	}

	public function generate_matching_report(): array {
		global $wpdb;

		// 1️⃣ Get all mentees for a specific assigned client (excluding inactive)
		$mentees = $wpdb->get_results(
			$wpdb->prepare("
				SELECT p.ID, p.post_title
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} r ON p.ID = r.post_id AND r.meta_key = 'mpro_role' AND r.meta_value = %s
				INNER JOIN {$wpdb->postmeta} c ON p.ID = c.post_id AND c.meta_key = 'assigned_client' AND c.meta_value = %s
				WHERE p.post_type = 'mentor_submission' AND p.post_status = 'publish'
				AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} s
					WHERE s.post_id = p.ID AND s.meta_key = 'mpro_status' AND s.meta_value = 'inactive'
				)
			", MPRO_ROLE_MENTEE, $this->client_id)
		);

		// 2️⃣ Get all mentors for a specific assigned client (excluding inactive)
		$mentors = $wpdb->get_results(
			$wpdb->prepare("
				SELECT p.ID, p.post_title
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} r ON p.ID = r.post_id AND r.meta_key = 'mpro_role' AND r.meta_value = %s
				INNER JOIN {$wpdb->postmeta} c ON p.ID = c.post_id AND c.meta_key = 'assigned_client' AND c.meta_value = %s
				WHERE p.post_type = 'mentor_submission' AND p.post_status = 'publish'
				AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} s
					WHERE s.post_id = p.ID AND s.meta_key = 'mpro_status' AND s.meta_value = 'inactive'
				)
			", MPRO_ROLE_MENTOR, $this->client_id)
		);
		
		$mentor_match_count = []; 
		$schema = get_matching_schema($this->client_id);
//error_log("Schema for client $this->client_id: =" . print_r($schema, true));
		$max_mentees_per_mentor = $schema['max_mentees_per_mentor'] ?? 1; // Limit per mentor
		$matches = []; // Store best matches
		$mentees_no_language_match = [];
		
		$all_mentee_names = array_map(fn($m) => $m->post_title, $mentees);
		$all_mentor_names = array_map(fn($m) => $m->post_title, $mentors);

		$single_meta_fields = $schema['single_meta_fields'] ?? [];
		$multi_meta_fields  = $schema['multi_meta_fields'] ?? [];
		
		// 3️⃣ Loop through each mentee
		foreach ($mentees as $mentee) {
			$mentee_id = $mentee->ID;
			$mentee_name = $mentee->post_title;			
			$best_match = null;
			$best_match_score = 0;
			$best_match_fields = [];
			$best_tipi_match_score = 0;
			
			// Retrieve mentee attributes
			$mentee_meta = mpro_get_single_meta_values($mentee_id, $single_meta_fields);
			$mentee_multi_meta = mpro_get_multi_meta_values($mentee_id, $multi_meta_fields);
			
			
			// Extract values
			extract($mentee_meta);
			$mentee_gender = $mentee_meta['mpro_gender'] ?? '';
			$mentee_age = $mentee_meta['mpro_age'] ?? '';
			$mentee_race = $mentee_meta['mpro_family_origin'] ?? '';
			$mentee_ed = $mentee_meta['mpro_education'] ?? '';
			$mentee_skills_want = $mentee_meta['mpro_mentee_skill_want'] ?? '';
//error_log('mentee_skills_want : ' . $mentee_skills_want);
			$mentee_career = $mentee_meta['mpro_career_match'] ?? '';
			
			$mentee_match_pref = $mentee_meta['mpro_match_pref'] ?? '';

			$mentee_interests = $mentee_multi_meta['mpro_interests'];
			$mentee_languages = $mentee_multi_meta['mpro_languages'];
						
			// Track mentor assignments
			foreach ($mentors as $mentor) {
				$mentor_id = $mentor->ID;
				$mentor_name = $mentor->post_title;
				
				// ✅ Skip mentors who already have full mentees assigned
				if (isset($mentor_match_count[$mentor_name]) && $mentor_match_count[$mentor_name] >= $max_mentees_per_mentor) {
					continue; // Move to the next mentor
				}

				// Retrieve mentor attributes
				$mentor_meta = mpro_get_single_meta_values($mentor_id, $single_meta_fields);
				$mentor_multi_meta = mpro_get_multi_meta_values($mentor_id, $multi_meta_fields);

				// Extract values
				extract($mentor_meta);
				$mentor_gender = $mentor_meta['mpro_gender'] ?? '';
				$mentor_age = $mentor_meta['mpro_age'] ?? '';
				$mentor_race = $mentor_meta['mpro_family_origin'] ?? '';
				$mentor_ed = $mentor_meta['mpro_education'] ?? '';
				$mentor_interests = $mentor_multi_meta['mpro_interests'] ?? '';
				$mentor_languages = $mentor_multi_meta['mpro_languages'] ?? '';

				$mentor_skills_have = $mentor_meta['mpro_mentor_skill_have'] ?? '';
				$mentor_career = $mentor_meta['mpro_career_match'] ?? '';
				 
				$mentor_match_pref = $mentor_meta['mpro_match_pref'] ?? '';

				$match_score = 0;
				$max_score = 0;
				$match_fields = [];
				$tipi_match_score = 0;
																
				// ✅ Calculate points for Career Interests
				//These 3 cats are ranked: career interests, Similar hobbies and interests, Similar family and cultural
				$trait = 'Similar career interests';
				$career_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_career,
					$mentee_career
				);
				
				$match_score += $career_result['points'];
				$max_score   += $career_result['max_points'];
				$match_fields[] = $career_result['message'];

				// ✅ Calculate points for Skills
				$trait = 'Similar mentoring goals';
				$skills_result = $this->score_top3_trait_match(
					$trait,
					'',  //$mentor_match_pref,
					'',  //$mentee_match_pref,
					$mentor_skills_have,
					$mentee_skills_want
				);

				$match_score += $skills_result['points'];
				$max_score   += $skills_result['max_points'];
				$match_fields[] = $skills_result['message'];

				// ✅ Calculate points for Hobbies match based on mentee & mentor match preferences
				//These 3 cats are ranked: career interests, Similar hobbies and interests, Similar family and cultural
				$trait = 'Similar hobbies and interests';
				$hobby_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_interests,
					$mentee_interests
				);
				
				$match_score += $hobby_result['points'];
				$max_score   += $hobby_result['max_points'];
				$match_fields[] = $hobby_result['message'];
				
				// ✅ Calculate points for Gender match based on mentee & mentor match preferences
				$trait = 'Same Gender';
				$gender_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_gender,
					$mentee_gender
				);
				
				$match_score += $gender_result['points'];
				$max_score   += $gender_result['max_points'];
				$match_fields[] = $gender_result['message'];
				
				// ✅ Calculate points for Gender match 
				$trait = 'Same Age';
				$age_result = $this->score_top3_trait_match(
					$trait,
					'',
					'',
					$mentor_age,
					$mentee_age
				);
				
				$match_score += $age_result['points'];
				$max_score   += $age_result['max_points'];
				$match_fields[] = $age_result['message'];
				
				
				// ✅ Calculate points for Age match based 
				//Similar career interests,Similar hobbies and interests,Similar family and cultural
				$trait = 'Similar family and cultural';
				$race_result = $this->score_top3_trait_match(
					$trait,
					'',
					'',
					$mentor_race,
					$mentee_race
				);
				
				$match_score += $race_result['points'];
				$max_score   += $race_result['max_points'];
				$match_fields[] = $race_result['message'];
									
				// ✅ Ensure mentor and mentee share at least one language
				$common_languages = array_filter(array_intersect($mentee_languages, $mentor_languages));				
				$trait = 'Language Match'; // matches your trait caps and messages
				
				// 🚫 Hard skip if no language overlap
				if (empty($common_languages)) {
					// Don't add to match score or max — just exit early
					$match_fields[] = "$trait: No common language (ineligible)";
					$mentees_no_language_match[$mentee_id] = true; 
					continue; // skip this mentor entirely
				}
				
				// ✅ Language matches found — now apply points if 2+ matches
				$settings = $this->get_trait_settings($trait);
				$cap = $settings['cap']; // From centralized config
				
				$language_points = 0;
				$match_message = '';
				$match_count = count($common_languages);
				
				switch ($match_count) {
					case 1:
						$language_points = 0;
						//$match_message = "One shared language (" . implode(', ', $common_languages) . ")";
						// if match for non-English, give extra points
						if (implode(', ', $common_languages) != 'English') {
							$match_message = "One shared non-English language (" . implode(', ', $common_languages) . ")";
							$language_points = 4;
						}
						break;
					case 2:
						$language_points = 4;
						$match_message = "{$settings['description']}: Two shared languages (" . implode(', ', $common_languages) . ") (4 points)";
						break;
					default:
						$language_points = 8;
						$match_message = "{$settings['description']}: Multiple shared languages (" . implode(', ', $common_languages) . ") (8 points)";
						break;
				}
				
				// Cap the score using the settings
				$language_points = min($language_points, $cap);
				
				// Add to score totals
				$match_score += $language_points;
				$max_score   += $cap;
				$match_fields[] = $match_message;
								
				
				$total_score = $match_score;

				// ✅ Store this match as a candidate (but don’t assign yet)
				$max_score = $this->get_total_max_score(); // ✅


				$mentor_match_candidates[$mentor_id][] = [
					'mentee_id' => $mentee_id,
					'mentor_id' => $mentor_id,
					'mentee' => $mentee_name, 
					'mentor' => $mentor_name,
					'score'  => $total_score,
					'percentage' => round(($total_score / $max_score) * 100, 2) . '%',
					//'percentage' => $total_score,
					'match_fields' => $match_fields,
				];
		
			} // foreach mentor
			
		} // foreach mentee
				
		// ✅ Sort and Assign the Best Two Matches for Each Mentor
		// ✅ Track assigned mentees and mentor assignments
		$assigned_mentees = [];
		$mentor_match_count = [];
		$matches = [];
		
		// ✅ Step 1: Collect all matches into a single list
		$all_potential_matches = [];
		
		foreach ($mentor_match_candidates as $mentor_id => $mentee_list) {
			foreach ($mentee_list as $match) {
				$all_potential_matches[] = [
					'mentor_id' => $match['mentor_id'],
					'mentee_id' => $match['mentee_id'],
					'mentor' => $match['mentor'], 
					'mentee' => $match['mentee'], 
					'score' => $match['score'],
					'percentage' => $match['percentage'],
					'field'  => !empty($match['match_fields']) ? implode(', ', $match['match_fields']) : 
								(empty($common_languages) ? 'No Common Language' : 'N/A'),
				];
			}
		}
		
		// ✅ Step 2: Sort all matches by highest score first (best matches first)
		usort($all_potential_matches, function($a, $b) {
			return $b['score'] <=> $a['score']; // Descending order
		});
		
		// ✅ Step 3: Assign mentees to their best possible mentor first
		foreach ($all_potential_matches as $match) {
			$mentor_name = $match['mentor'];
			$mentee_name = $match['mentee'];
		
			// ✅ Ensure the mentee gets their best possible match first
			if (isset($assigned_mentees[$mentee_name])) {
				continue; // Skip if mentee is already assigned
			}
		
			// ✅ Ensure the mentor does not exceed their match limit
			if (isset($mentor_match_count[$mentor_name]) && $mentor_match_count[$mentor_name] >= $max_mentees_per_mentor) {
				continue; // Skip if mentor has reached the limit
			}
		
			// ✅ Assign the mentee to the mentor
			$matches[] = [
				'mentor_id' => $match['mentor_id'], 
				'mentee_id' => $match['mentee_id'], 
				'mentor' => $mentor_name, 
				'mentee' => $mentee_name, 
				'score'  => $match['score'],
				'percentage' => $match['percentage'],
				'field'  => $match['field'],
			];
		
			// ✅ Mark the mentee as assigned
			$assigned_mentees[$mentee_name] = true;
		
			// ✅ Track mentor assignments
			if (!isset($mentor_match_count[$mentor_name])) {
				$mentor_match_count[$mentor_name] = 0;
			}
			$mentor_match_count[$mentor_name]++;
		}
		
		// ✅ Step 4: Sort the final matches by highest percentage first
		usort($matches, function($a, $b) {
			return $b['score'] <=> $a['score']; // Ensure descending order
		});
		
		// Call rebalance and pass all mentors by name
		$all_mentor_names = array_map(fn($m) => $m->post_title, $mentors);
		rebalance_matches($matches, $all_mentor_names);
		
		// 🔁 REBUILD tracking from updated matches
		$assigned_mentees = array_column($matches, 'mentee', 'mentee');
		$mentor_match_count = array_count_values(array_column($matches, 'mentor'));
		
		// ✅ Get fresh unmatched lists
		$unmatched_mentees = array_diff($all_mentee_names, array_keys($assigned_mentees));
		$unmatched_mentors = array_diff($all_mentor_names, array_keys($mentor_match_count));		

		return [
			'matches' => $matches,
			'unmatched_mentees' => array_values($unmatched_mentees),
			'unmatched_mentors' => array_values($unmatched_mentors),
			'mentees_no_language_match' => $mentees_no_language_match,
		];

	}

	public function calculate_ranking_match_score($mentee_ranking, $mentor_ranking) {
		$score = 0;
	
		// Flip rankings for easy lookup (e.g., "1" -> priority level)
		$mentee_priority = array_flip($mentee_ranking);
		$mentor_priority = array_flip($mentor_ranking);
	
		foreach ($mentee_ranking as $skill) {
			if (isset($mentor_priority[$skill])) {
				// Calculate how close the ranking is
				$mentee_position = $mentee_priority[$skill];
				$mentor_position = $mentor_priority[$skill];
	
				// High priority matches get more weight
				if ($mentee_position == $mentor_position) {
					$score += 5 - $mentee_position; // Top ranks get highest score
				} elseif (abs($mentee_position - $mentor_position) == 1) {
					$score += 3; // Close ranks
				} else {
					$score += 1; // Lower relevance match
				}
			}
		}
	
		return $score;
	}
	
	public function compare_top_3( $mentor_input, $mentee_input, string $trait = '' ): array {
		// Validate inputs
		if (empty($mentor_input) || empty($mentee_input)) {
			return ['score' => 0, 'matches' => []];
		}

		$valid_options = [];
		if (is_array($mentor_input)) {
			$mentor = $mentor_input;
		} else {
			$mentor = array_map('trim', explode(',', $mentor_input));
		}
	
		if (is_array($mentee_input)) {
			$mentee = $mentee_input;
		} else {
			$mentee = array_map('trim', explode(',', $mentee_input));
		}
		
		$mentor_top_raw = array_slice($mentor, 0, 3);
		$mentee_top_raw = array_slice($mentee, 0, 3);
		
		$mentor_top = array_map('normalize_string', $mentor_top_raw);
		$mentee_top = array_map('normalize_string', $mentee_top_raw);
		
		$normalized_matches = array_intersect($mentor_top, $mentee_top);
		
		// Map matches back to the original strings (optional, for reporting)
		$real_matches = [];
		
		foreach ($mentor_top_raw as $i => $original_mentor) {
			$normalized = normalize_string($original_mentor);
			if (in_array($normalized, $normalized_matches)) {
				$real_matches[] = $original_mentor;
			}
		}
	
		$matches = array_intersect($mentor_top, $mentee_top);
		$match_score = count($matches);
	
		return [
			'score'   => $match_score,
			'matches' => array_values($matches)
		];
	}

public function score_top3_trait_match( string $trait_label, string $mentor_pref_raw, string $mentee_pref_raw, $mentor_input, $mentee_input ): array {

		// Step 1: Compare top 3 responses
		$comparison = $this->compare_top_3( $mentor_input, $mentee_input, $trait_label );
		$is_match = count($comparison['matches']) > 0;
		$match_count = count($comparison['matches']);

			if ($trait_label === "Same Age")	
		{	//error_log("entering function - mentor input " . $mentor_input . ' mentee input ' . $mentee_input);
			//error_log("is match " . $is_match . " match count " . $match_count);
		}
	
		// Step 2: Try to find ranks
		$mentor_rankings = array_map( 'trim', explode(',', $mentor_pref_raw ) );
		$mentee_rankings = array_map( 'trim', explode(',', $mentee_pref_raw ) );
	
		$mentor_rank = array_search( $trait_label, $mentor_rankings );
		$mentee_rank = array_search( $trait_label, $mentee_rankings );
	
		$mentor_rank = $mentor_rank !== false ? $mentor_rank + 1 : null;
		$mentee_rank = $mentee_rank !== false ? $mentee_rank + 1 : null;
	
		// Step 3: Get weighted base score
		$score = score_trait_match(
			$trait_label,
			$mentor_pref_raw,
			$mentee_pref_raw,
			$match_count, //$is_match,
			$this
		);

		$base_points = $score['points'];
	
		// Step 4: Only apply bonus if ranked by at least one party
		$settings = $this->get_trait_settings($trait_label);
		$cap = $settings['cap'];
		$bonus_eligible = $settings['bonus_eligible'] ?? true;
	
		$bonus_points = 0;
		if ( $bonus_eligible && ( $mentor_rank !== null || $mentee_rank !== null ) ) {
			$bonus_points = apply_trait_bonus(
				$comparison['matches'],
				$settings['bonus_per_match'],
				$mentor_rank,
				$mentee_rank
			);
		}
	
		$total_points = min( $base_points + $bonus_points, $cap );
	
		// Step 5: Format message
		if ( $is_match ) {

			$match_count = count( $comparison['matches'] );
			$label = $settings['description'];
			$matches_str = implode( ', ', $comparison['matches'] );

			if ($trait_label === "Same Age")	
					{	//error_log("in Step 5 - mentor input " . $mentor_input . ' mentee input ' . $mentee_input);
						//error_log("is match " . $is_match . " match count " . $match_count);
						//error_log("match count " . $match_count . " matches str " . $matches_str);
					}
	
			if ( $bonus_eligible && ( $mentor_rank !== null || $mentee_rank !== null ) ) {
				$mentor_str = $mentor_rank !== null ? "#$mentor_rank" : "not ranked";
				$mentee_str = $mentee_rank !== null ? "#$mentee_rank" : "not ranked";
				$message = "$label: $match_count shared ($matches_str) — trait ranked $mentor_str by mentor, $mentee_str by mentee 	($total_points pts)";
			} else {	
				//error_log('label is ' . $label);
				if (('Age Range' === $label) || ('Gender' === $label)) {
					$message = "Same $label: ($matches_str) — ($total_points pts)";
				} else {
					$message = "$label: $match_count shared ($matches_str) — ($total_points pts)";
				}
			}
		} else {
			$message = $settings['description'] . ": No match (0 points)";
		}
	
		return [
			'points'      => $total_points,
			'message'     => $message,
			'matches'     => $comparison['matches'],
			'bonus'       => $bonus_points,
			'max_points'  => $cap,
		];
	}
}