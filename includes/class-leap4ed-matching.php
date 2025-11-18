<?php

// Performs the matching for the 'Leap4Ed-chp' client

require_once plugin_dir_path(__FILE__) . 'class-matching-base.php';

class Leap4Ed_Matching extends Matching_Base {

	public function __construct($client_id = '') {
		$this->client_id = $client_id;
		//error_log('(in class-Leap4Ed) client ID is ' . $client_id);
	}
	
	public function get_report_fields() {
		return [
			'mpro_fname' => 'First Name',
			'mpro_lname' => 'Last Name',
			'post_date' => 'Date Created',
			'mpro_role' => 'Role',
			'mpro_email' => 'Email',
			'mpro_phone' => 'Phone',
			'mpro_gender' => 'Gender',
			'mpro_race' => 'Race',
			'mpro_ed' => 'Education',
			'mpro_languages' => 'Language(s)',
			'mpro_interests' => 'Hobbies/Interests',
			'mpro_mentor_career_have' => 'Mentor Career Experience',
			'mpro_mentee_career_want' => 'Mentee Career Interests',
			'mpro_match_pref' => 'Matching Emphasis',
			'mpro_preference_to_meet' => 'Meet Preference',
			'mpro_trait_openness' => 'Openness',
			'mpro_trait_stability' => 'Conscientiousness',
			'mpro_trait_conscientiousness' => 'Extraversion',
			'mpro_trait_agreeableness' => 'Agreeableness',
			'mpro_trait_extraversion' => 'Stability',
		];
	}
	
	public function get_all_trait_settings() {
		return [
			'Same Career Interests' => [
				'cap' => 10,
				'base_per_match' => 5.0,
				'bonus_per_match' => 1.5,
				'description' => 'Career Interests',
				'bonus_eligible' => true,
			],
			'Same Mentoring Skills' => [
				'cap' => 10,
				'base_per_match' => 2.0,
				'bonus_per_match' => 1.5,
				'description' => 'Skill Building',
				'bonus_eligible' => true,
			],
			'Same Hobbies' => [
				'cap' => 5,
				'base_per_match' => 1.25,
				'bonus_per_match' => 1.0,
				'description' => 'Hobbies',
				'bonus_eligible' => true,
			],
			'Language Match' => [
				'cap' => 8,
				'description' => 'Language',
				'bonus_eligible' => false,
			],
			'Same Gender' => [
				'cap' => 5,
				'base_per_match' => 1,
				'bonus_per_match' => 1.5,
				'description' => 'Gender',
				'bonus_eligible' => true,
			],
			'Same Race' => [
				'cap' => 5,
				'base_per_match' => 1,
				'bonus_per_match' => 1.5,
				'description' => 'Race',
				'bonus_eligible' => true,
			],
			'Personality Traits Match' => [
				'cap' => 5,
				'description' => 'Personality Traits',
				'bonus_eligible' => false,
			],
		];
	}

	public function generate_matching_report() {
		global $wpdb;

		// 1️⃣ Get all mentees for a specific assigned client
		$mentees = $wpdb->get_results(
			$wpdb->prepare("
				SELECT p.ID, p.post_title
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} r ON p.ID = r.post_id AND r.meta_key = 'mpro_role' AND r.meta_value = '1'
				INNER JOIN {$wpdb->postmeta} c ON p.ID = c.post_id AND c.meta_key = 'assigned_client' AND c.meta_value = %s
				WHERE p.post_type = 'mentor_submission' AND p.post_status = 'publish'
			", $this->client_id)
		);

		// 2️⃣ Get all mentors for a specific assigned client
		$mentors = $wpdb->get_results(
			$wpdb->prepare("
				SELECT p.ID, p.post_title
				FROM {$wpdb->posts} p
				INNER JOIN {$wpdb->postmeta} r ON p.ID = r.post_id AND r.meta_key = 'mpro_role' AND r.meta_value = '2'
				INNER JOIN {$wpdb->postmeta} c ON p.ID = c.post_id AND c.meta_key = 'assigned_client' AND c.meta_value = %s
				WHERE p.post_type = 'mentor_submission' AND p.post_status = 'publish'
			", $this->client_id)
		);
		
		$single_meta_fields = $single_meta_fields ?? [];
		$multi_meta_fields  = $multi_meta_fields  ?? [];
		
		// Collect ID sets used later for unmatched math
		$all_mentee_ids = array_map(fn($p) => (int)$p->ID, $mentees);
		$all_mentor_ids = array_map(fn($p) => (int)$p->ID, $mentors);
		
		$mentee_names = [];
		foreach ($mentees as $t) { $mentee_names[(int)$t->ID] = $t->post_title; }
		
		$mentor_names = [];
		foreach ($mentors as $m) { $mentor_names[(int)$m->ID] = $m->post_title; }
		
		// Prepare candidate bucket (used later to build $all_potential_matches)
		$mentor_match_candidates = [];
		
		$mentor_match_count = []; 
		$schema = get_matching_schema($this->client_id);
		$max_mentees_per_mentor = $schema['max_mentees_per_mentor'] ?? 1;
		$matches = []; // Store best matches
		$mentees_no_language_match = [];
		$unmatched_reasons = [];
		$mentees_skipped_ids = $mentees_skipped_ids ?? [];

		
		$all_mentee_names = array_map(fn($m) => $m->post_title, $mentees);
		$all_mentor_names = array_map(fn($m) => $m->post_title, $mentors);

		// Define standard fields to retrieve
		if ($this->client_id === 'leap4ed-chp') {
			$single_meta_fields = ['mpro_gender', 'mpro_race', 'mpro_ed', 'mpro_match_pref' , 'mpro_first_gen' , 'mpro_mentor_career_have' , 'mpro_mentee_career_want' , 'mpro_mentor_skills_have' , 'mpro_mentee_skills_want','mpro_preference_to_meet'];
			$multi_meta_fields  = ['mpro_interests', 'mpro_languages'];
		}
				
		// 3️⃣ Loop through each mentee
		foreach ($mentees as $mentee) {
			$mentee_id = $mentee->ID;
			$mentee_name = $mentee->post_title;			
			$best_match = null;
			$best_match_score = 0;
			$best_match_fields = [];
			$best_tipi_match_score = 0;
			
			// Determine client-wide default (whatever you already use; fallback to 1)
			$max_mentees_per_mentor = $schema['max_mentees_per_mentor'] ?? 1;
			
			// Reuse the same meta for mentees: 0 means “don’t match me”
			$mentee_cap = mpro_get_mentor_cap($mentee_id, $max_mentees_per_mentor);
			if ($mentee_cap === 0) {
				// Optional: track for reporting
				$mentees_skipped = $mentees_skipped ?? [];
				$mentees_skipped[] = $mentee_name;
				$mentees_skipped_ids[] = (int) $mentee_id; // track ID
				continue; // skip this mentee entirely
			}
			
			// Retrieve mentee attributes
			$mentee_meta = mpro_get_single_meta_values($mentee_id, $single_meta_fields);
			$mentee_multi_meta = mpro_get_multi_meta_values($mentee_id, $multi_meta_fields);
			
			$mentee_extraversion = get_post_meta($mentee_id, 'mpro_trait_extraversion', true);
			$mentee_agreeableness = get_post_meta($mentee_id, 'mpro_trait_agreeableness', true);
			$mentee_conscientiousness = get_post_meta($mentee_id, 'mpro_trait_conscientiousness', true);
			$mentee_stability = get_post_meta($mentee_id, 'mpro_trait_stability', true);
			$mentee_openness = get_post_meta($mentee_id, 'mpro_trait_openness', true);
			
			// Extract values
			extract($mentee_meta);
			$mentee_gender = $mentee_meta['mpro_gender'] ?? '';
			//$mentee_age = $mentee_meta['mpro_age'] ?? '';
			$mentee_race = $mentee_meta['mpro_race'] ?? '';
			$mentee_first_gen = $mentee_meta['mpro_first_gen'] ?? '';
			$mentee_ed = $mentee_meta['mpro_ed'] ?? '';
			if ($this->client_id === 'leap4ed-chp') {
				$mentee_career_want = $mentee_meta['mpro_mentee_career_want'] ?? '';
				$mentee_skills_want = $mentee_meta['mpro_mentee_skills_want'] ?? '';
			} else {
				$mentee_skills_want = $mentee_meta['mpro_mentee_have'] ?? '';
			}
			$mentee_match_pref = $mentee_meta['mpro_match_pref'] ?? '';
			$mentee_meet_pref = $mentee_meta['mpro_preference_to_meet'] ?? '';

			$mentee_interests = $mentee_multi_meta['mpro_interests'];
			//$mentee_languages = $mentee_multi_meta['mpro_languages'];
			$mentee_languages = isset($mentee_multi_meta['mpro_languages']) ? (array) $mentee_multi_meta['mpro_languages'] : [];

			
			$mentee_has_any_language_match = false;
						
			// Track mentor assignments
			foreach ($mentors as $mentor) {
				$mentor_id = $mentor->ID;
				$mentor_name = $mentor->post_title;
				
				// Prepare arrays once before the loop if not already:
				if (!isset($mentor_caps))        $mentor_caps = [];        // mentor_id => cap
				if (!isset($mentor_match_count)) $mentor_match_count = []; // mentor_id => current count
				
				// Resolve cap for this mentor (cache it)
				if (!isset($mentor_caps[$mentor_id])) {
					$mentor_caps[$mentor_id] = mpro_get_mentor_cap($mentor_id, $max_mentees_per_mentor);
					//error_log('mentor cap set to ' . $mentor_caps[$mentor_id] . ' for ' . $mentor_name);
				}
				
				$current = isset($mentor_match_count[$mentor_id]) ? (int)$mentor_match_count[$mentor_id] : 0;
				
				// ✅ Skip only if THIS mentor is full relative to their own cap
				if ($current >= $mentor_caps[$mentor_id]) {
					continue;
				}
				
				// Retrieve mentor attributes
				$mentor_meta = mpro_get_single_meta_values($mentor_id, $single_meta_fields);
				$mentor_multi_meta = mpro_get_multi_meta_values($mentor_id, $multi_meta_fields);

				// Extract values
				extract($mentor_meta);
				$mentor_gender = $mentor_meta['mpro_gender'] ?? '';
				$mentor_race = $mentor_meta['mpro_race'] ?? '';
				$mentor_ed = $mentor_meta['mpro_ed'] ?? '';
				$mentor_first_gen = $mentor_meta['mpro_first_gen'] ?? '';;
				$mentor_interests = $mentor_multi_meta['mpro_interests'] ?? '';
				//$mentor_languages = $mentor_multi_meta['mpro_languages'] ?? '';
				$mentor_languages = isset($mentor_multi_meta['mpro_languages']) ? (array) $mentor_multi_meta['mpro_languages'] : [];
				$mentor_experience_mentoring = $mentor_meta['mpro_mentor_experience'] ?? '';
				$mentor_experience_caring    = $mentor_meta['mpro_caring_experience'] ?? '';

				if ($this->client_id === 'leap4ed-chp') {
					$mentor_skills_have = $mentor_meta['mpro_mentor_skills_have'] ?? '';
					$mentor_career_have = $mentor_meta['mpro_mentor_career_have'] ?? '';
				} else {
					$mentor_skills_have = $mentor_meta['mpro_mentor_have'] ?? '';
				}

			
				 
				$mentor_match_pref = $mentor_meta['mpro_match_pref'] ?? '';
				$mentor_meet_pref = $mentor_meta['mpro_preference_to_meet'] ?? '';

				$match_score = 0;
				$max_score = 0;
				$match_fields = [];
				$tipi_match_score = 0;
																
				// ✅ Calculate points for Career
				$trait = 'Same Career Interests';
				$career_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_career_have,
					$mentee_career_want
				);
				
				$match_score += $career_result['points'];
				$max_score   += $career_result['max_points'];
				$match_fields[] = $career_result['message'];
				
				// ✅ Calculate points for Skills
				$trait = 'Same Mentoring Skills';
				$skills_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_skills_have,
					$mentee_skills_want
				);
				
				$match_score += $skills_result['points'];
				$max_score   += $skills_result['max_points'];
				$match_fields[] = $skills_result['message'];
				
				// ✅ Calculate points for Hobbies match based on mentee & mentor match preferences
				$trait = 'Same Hobbies';
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
				
				
				// ✅ Calculate points for Race match based on mentee & mentor match preferences
				$trait = 'Same Race';
				$race_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_race,
					$mentee_race
				);
				
				$match_score += $race_result['points'];
				$max_score   += $race_result['max_points'];
				$match_fields[] = $race_result['message'];
					
				// ✅ Calculate points for First Gen
				if (!empty($mentee_first_gen) && $mentee_first_gen == $mentor_first_gen) {
					$match_fields[] = "First Gen (3 points)";
					$max_score   += 3;
					$match_score += 3;
				}

				if ($this->client_id === "leap4ed-chp") {
				// ✅ Calculate points for Meet Pref
					if (!empty($mentee_meet_pref) && $mentee_meet_pref == $mentor_meet_pref) {
						$match_fields[] = "Preferred way to meet shared (" . $mentee_meet_pref . ") (3 points)";
						$max_score   += 3;
						$match_score += 3;
					}
				}
				
				// ✅ Ensure mentor and mentee share at least one language
				$common_languages = array_filter(array_intersect($mentee_languages, $mentor_languages));				
				$trait = 'Language Match'; // matches your trait caps and messages
				
				// 🚫 Hard skip if no language overlap
				if (empty($common_languages)) {
					// Don't add to match score or max — just exit early
					$match_fields[] = "$trait: No common language (ineligible)";
					//$mentees_no_language_match[$mentee_id] = true; 
					continue; // skip this mentor entirely
				}
				
				// ✅ Language matches found — now apply points if 2+ matches
				$mentee_has_any_language_match = true;
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
				// Cap the score using the settings
				$language_points = min($language_points, $cap);
				
				// Compute achievable max for THIS pair
				// map: 0 shared -> 0; 1 English-only -> 0; 1 non-English -> 4; 2 -> 4; 3+ -> 8
				$achievable_lang_max = 0;
				if ($match_count >= 3)       { $achievable_lang_max = 8; }
				elseif ($match_count == 2)   { $achievable_lang_max = 4; }
				elseif ($match_count == 1)   { $achievable_lang_max = (implode(', ', $common_languages) !== 'English') ? 4 : 0; }
				
				// Respect the configured cap (in case you keep it at 4)
				$achievable_lang_max = min($achievable_lang_max, $cap);
				
				// Totals
				$match_score += $language_points;
				$max_score   += $achievable_lang_max;   // ✅ denominator now realistic

				$match_fields[] = $match_message;
								
				// ✅ TIPI Matching Score (Absolute Differences) 
				if ($this->client_id === 'leap4ed-chp') {   // do not use personality matching for leap4ed
					$trait = 'Personality Traits Match';
					$settings = $this->get_trait_settings($trait);
					$cap = $settings['cap']; // typically 5
					// Get mentor trait scores
					$mentor_extraversion      = get_post_meta($mentor_id, 'mpro_trait_extraversion', true);
					$mentor_agreeableness     = get_post_meta($mentor_id, 'mpro_trait_agreeableness', true);
					$mentor_conscientiousness = get_post_meta($mentor_id, 'mpro_trait_conscientiousness', true);
					$mentor_stability         = get_post_meta($mentor_id, 'mpro_trait_stability', true);
					$mentor_openness          = get_post_meta($mentor_id, 'mpro_trait_openness', true);
					// Trait score arrays
					$traits = [
						'Openness'         => [$mentee_openness, $mentor_openness],
						'Conscientiousness'=> [$mentee_conscientiousness, $mentor_conscientiousness],
						'Extraversion'     => [$mentee_extraversion, $mentor_extraversion],
						'Agreeableness'    => [$mentee_agreeableness, $mentor_agreeableness],
						'Stability'        => [$mentee_stability, $mentor_stability],
					];
					$comparable = 0;
					$match_count = 0;
					$matched_traits = [];
					
					foreach ($traits as $trait_name => [$mentee_score, $mentor_score]) {
						if (is_numeric($mentee_score) && is_numeric($mentor_score)) {
							$comparable++;
							if ($mentor_score >= $mentee_score) {
								$match_count++;
								$matched_traits[] = $trait_name;
							}
						}
					}
				
					// Scoring
					$raw_score = $match_count;
					$tipi_score = min($raw_score, $cap);
					
					// Totals
					$match_score += $tipi_score;
					//$max_score   += $cap;
					$max_score   += min($comparable, $cap);

					
					// Friendly message
					if ($match_count > 0) {
						$match_fields[] = $settings['description'] . " Match (" . implode(', ', $matched_traits) . ") ($tipi_score points)";
					} else {
						$match_fields[] = $settings['description'] . ": Mentor scored lower on all traits (0 points)";
					}
									
				} // end TIPI
				
				$total_score = $match_score;

				// ✅ Store this match as a candidate (but don’t assign yet)
				//$max_score = $this->get_total_max_score(); // ✅
				$max_score = max(1, $max_score); // guard

				$mentor_match_candidates[$mentor_id][] = [
					'mentee_id' => $mentee_id,
					'mentor_id' => $mentor_id,
					'mentee' => $mentee_name, 
					'mentor' => $mentor_name,
					'score'  => $total_score,
					//'percentage' => round(($total_score / $max_score) * 100, 2) . '%',
					//'percentage' filled later
					'match_fields' => $match_fields,
				];
		
			} // foreach mentor
			
			if (!$mentee_has_any_language_match) {
				$mentees_no_language_match[$mentee_id] = true;
			}
			
		} // foreach mentee
				
// ✅ Sort and Assign the Best Matches for Each Mentor
		$assigned_mentees = [];
		$mentor_match_count = [];
		$matches = [];
		
		// ===== Step 0: Precompute per-mentor caps & name map (once) =====
		$mentor_caps   = [];              // mentor_id => cap
		$mentor_names  = [];              // mentor_id => post_title
		$all_mentor_ids = [];
		
		foreach ($mentors as $m) {
			$mid = (int) $m->ID;
			$all_mentor_ids[]    = $mid;
			$mentor_names[$mid]  = $m->post_title;
			if (!isset($mentor_caps[$mid])) {
				$mentor_caps[$mid] = mpro_get_mentor_cap($mid, $max_mentees_per_mentor);
			}
		}
		
		// ===== Step 1: Collect all matches into a single list =====
		$all_potential_matches = [];
		foreach ($mentor_match_candidates as $mentor_id => $mentee_list) {
			foreach ($mentee_list as $match) {
				$all_potential_matches[] = [
					'mentor_id'  => (int) $match['mentor_id'],
					'mentee_id'  => (int) $match['mentee_id'],
					'mentor'     => $match['mentor'],
					'mentee'     => $match['mentee'],
					'score'      => (float) $match['score'],
					// 'percentage' will be filled AFTER we know cohort max
					'field'      => !empty($match['match_fields']) ? implode(', ', $match['match_fields']) : 'N/A',
				];
			}
		}
		
		// ===== Step 2: Sort all matches by highest score first =====
		usort($all_potential_matches, function($a, $b) {
			return $b['score'] <=> $a['score']; // Desc
		});
		
		// Compute the best achievable score in this cohort
		$cohort_max_score = max(0.0001, (float) ($all_potential_matches[0]['score'] ?? 0.0001));
		
		// Fill in cohort-scaled percentage on candidates
		foreach ($all_potential_matches as &$pm) {
			$pm['percentage'] = round(($pm['score'] / $cohort_max_score) * 100, 2) . '%';
		}
		unset($pm);
		
		// ===== Step 3: Greedy assign best-first, honoring per-mentor caps =====
		$assigned_mentees   = [];  // mentee_id => true
		$mentor_match_count = [];  // mentor_id => count
		$matches            = [];  // final rows
		
		foreach ($all_potential_matches as $match) {
			$mid = (int) $match['mentor_id'];
			$tid = (int) $match['mentee_id'];
		
			if (isset($assigned_mentees[$tid])) continue; // mentee already assigned
		
			// capacity for this mentor
			$cap = (int) ($mentor_caps[$mid] ?? 0);
			$cur = (int) ($mentor_match_count[$mid] ?? 0);
			if ($cur >= $cap) continue; // mentor is at their own cap
		
			// Assign (no recalculation needed; use cohort % we set on candidates)
			$matches[] = [
				'mentor_id'  => $mid,
				'mentee_id'  => $tid,
				'mentor'     => $mentor_names[$mid] ?? $match['mentor'],  // keep a name for display
				'mentee'     => $match['mentee'],
				'score'      => $match['score'],
				'percentage' => $match['percentage'],
				'field'      => $match['field'],
			];
		
			$assigned_mentees[$tid]  = true;
			$mentor_match_count[$mid] = $cur + 1;
		}
		
		// ===== Step 4: Sort final matches by score (optional/UI) =====
		usort($matches, function($a, $b) {
			return $b['score'] <=> $a['score'];
		});
		
		// ===== Rebalance by per-mentor caps (IDs + caps) =====
		$matches = rebalance_matches_by_caps(
			$matches,
			$mentor_caps,
			$mentor_names
		);
		
		// 🔁 Recompute cohort percentage on FINAL matches (in case rebalance changed the set)
		foreach ($matches as &$m) {
			$m['percentage'] = round(($m['score'] / $cohort_max_score) * 100, 2) . '%';
		}
		unset($m);
		
		// ===== Rebuild tracking from updated matches =====
		$assigned_mentees   = [];
		$mentor_match_count = [];
		
		foreach ($matches as $row) {
			$assigned_mentees[(int)$row['mentee_id']] = true;
			$mid = (int)$row['mentor_id'];
			$mentor_match_count[$mid] = ($mentor_match_count[$mid] ?? 0) + 1;
		}
		
		// ===== Compute unmatched lists (IDs -> names) =====
		$assigned_mentee_ids   = array_keys($assigned_mentees);
		//$unmatched_mentee_ids  = array_diff($all_mentee_ids, $assigned_mentee_ids);
		$unmatched_mentee_ids  = array_diff($all_mentee_ids, $assigned_mentee_ids, $mentees_skipped_ids);
		$unmatched_mentor_ids  = array_diff($all_mentor_ids, array_keys($mentor_match_count));

		// Map to names for return payload
		$unmatched_mentees = array_values(array_map(
			function($id) use ($mentee_names) { return $mentee_names[$id] ?? (string)$id; },
			$unmatched_mentee_ids
		));
		$unmatched_mentors = array_values(array_map(
			function($id) use ($mentor_names){ return $mentor_names[$id] ?? (string)$id; },
			$unmatched_mentor_ids
		));
		
		// ===== Return =====
		return [
			'matches'                  => $matches,
			'unmatched_mentees'        => $unmatched_mentees,
			'unmatched_mentors'        => $unmatched_mentors,
			'mentees_no_language_match'=> $mentees_no_language_match,
			'mentees_skipped' => $mentees_skipped ?? [],
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
	
	public function compare_top_3($mentor_input, $mentee_input, $trait = '') {
		// For ranking-type fields that may have embedded commas in values
		$requires_csv_parsing = in_array($trait, [
			'Same Career Interests',
			'Same Mentoring Skills'
		]);
		//echo "<pre>parsing?	: $requires_csv_parsing</pre>";	
		//print_r($this->client_id);
		
		if ($this->client_id === 'leap4ed') {
			$valid_career_options = [
				'Business, Entrepreneurship, Finance',
				'Computer Science and Coding',
				'Creative Careers (art, journalism, graphic design, photography, etc)',
				'Culinary and Hospitality',
				'Education, Human Services, Social Work',
				'Health Careers (psychology, dentistry, physical therapy, veterinary, etc)',
				'Law, Criminal Justice, Government',
				'Science, Technology, Engineering',
				'Trades (auto mechanics, construction, electrical, plumbing, etc.)',
			];
			
			$valid_mentoring_skills = [
				'Career advice',
				'College Pathways',
				'Internships & first jobs',
				'Mock interviewing',
				'Networking',
				'Non-college pathways (trade schools, certifications, etc.)',
				'Presentation skills & public speaking',
				'Professional communication (email, phone calls)',
			];
		} else {
			$valid_career_options_new = [
			'Administration (Administrators, Health Information, Education, billing, coding, information technology, finance, compliance, marketing)',
			'Allied Health (Paramedics, Dietitians, Radiology Techs (sonography), EMTs, Occupational Health, Physical Therapy, Speech-Language Pathologists, Medical Technologists)',
			'Behavioral Health (Therapist, mental health counselor, psychopharmacologist, social worker, psychologist, psychiatrist)',
			'Clinical (Doctor, nurse, physician assistant, pharmacist, dentist, dental technician, optometrist)',
			'Patient Support (Medical Assistants, Care coordinator, community health worker, patient care navigators, enrollment, patient access coordinator)',
			'Public Health and Education (Public Health Education, Public Health Nurses, Epidemiologists)',

		];
			$valid_career_options = [
				'Public Health and Education (Public Health Education, Public Health Nurses, Epidemiologists)',
				'Patient Care (Medical Assistants, CNAs, Nurses, Patient Advocates or Healthcare Navigator)',
				'Support Roles (Medical billing and coding, Chaplains/Rabbis/Monks/Imam, Information Technology, Finance)',
				'Administration and Management (Administrators, Health Information Managers, Education)',
				'Clinical (Doctor, Nurse, Physician Assistant, Pharmacist, Dentist)',
				'Allied Health (Paramedics, Dietitians, Radiology Techs (sonography), EMTs, Occupational Health, Physical Therapy, Speech-Language,Pathologists, Medical Technologists)',
				'Medical Research (clinical research coordinators, PhDs)',
				'Biomedical Engineering',
			];
			
			$valid_mentoring_skills = [
				'Leadership and Teambuilding',
				'Healthcare insights',
				'Empathy and Communication',
				'Soft Skills',
				'Critical Thinking',
				'Health System Management',
				'Healthcare Technology',
				'Healthy Behaviors & Habits',
				'Building Connections',
				'Career Goals',
				'Academic Success',
			];
		}
		
		$valid_options = [];
		
		if ($trait === 'Same Career Interests') {
			$valid_options = $valid_career_options;
		} elseif ($trait === 'Same Mentoring Skills') {
			$valid_options = $valid_mentoring_skills;
		}
		
		if (is_array($mentor_input)) {
			$mentor = $mentor_input;
		} else {
			$mentor = $requires_csv_parsing
				? smart_parse_ranked_choices($mentor_input, $valid_options)
				: array_map('trim', explode(',', $mentor_input));
		}
	
		if (is_array($mentee_input)) {
			$mentee = $mentee_input;
		} else {
			$mentee = $requires_csv_parsing
				? smart_parse_ranked_choices($mentee_input, $valid_options)
				: array_map('trim', explode(',', $mentee_input));
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

public function score_top3_trait_match( $trait_label, $mentor_pref_raw, $mentee_pref_raw, $mentor_input, $mentee_input ) {
	
		// Step 1: Compare top 3 responses
		$comparison = $this->compare_top_3( $mentor_input, $mentee_input, $trait_label );
		$is_match = count($comparison['matches']) > 0;
	
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
			$is_match,
			$this
		);
		$base_points = $score['points'];
	
		// Step 4: Only apply bonus if ranked by at least one party
		//$settings = get_trait_settings( $trait_label );
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
	
			if ( $bonus_eligible && ( $mentor_rank !== null || $mentee_rank !== null ) ) {
				$mentor_str = $mentor_rank !== null ? "#$mentor_rank" : "not ranked";
				$mentee_str = $mentee_rank !== null ? "#$mentee_rank" : "not ranked";
				$message = "$label: $match_count shared ($matches_str) — trait ranked $mentor_str by mentor, $mentee_str by mentee ($total_points pts)";
			} else {
				$message = "$label: $match_count shared ($matches_str) — ($total_points pts)";
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