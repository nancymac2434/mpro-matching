<?php

// Performs the matching for the 'coffee' client

require_once plugin_dir_path(__FILE__) . 'class-matching-base.php';

class Coffee_Matching extends Matching_Base {

	//private $client_id;

	public function __construct( string $client_id = '' ) {
		$this->client_id = $client_id;
	}

	public function get_report_fields(): array {
		return [
			'post_date' => 'Date Created',
			'mpro_role' => 'Role',
			'mpro_email' => 'Email',
			'mpro_position_title' => 'Position Title',
			'mpro_company_name' => 'Company Name',
			'mpro_years_worked' => 'Years Worked',
			'mpro_seniority_level' => 'Seniority Level',
			'mpro_field_of_work' => 'Field of Work',
			'mpro_leadership_compass' => 'Leadership Compass',
			'mpro_strengths' => 'Strengths',
			'mpro_mentor_goals_have' => 'Mentor Goals Experience',
			'mpro_mentee_goals_want' => 'Mentee Goals Interests',
			'mpro_mentor_soft_skills_have' => 'Mentor Soft Skills Experience',
			'mpro_mentee_soft_skills_want' => 'Mentee Soft Skills Interests',
			//'mpro_mentor_skills_have' => 'Mentor Skills Experience',
			//'mpro_mentee_skills_want' => 'Mentee Skills Interests',
			//'mpro_match_pref' => 'Matching Emphasis',
			'mpro_field_importance' => 'Field Importance',
			'mpro_alignment_preference' => 'Goals/Skills Preference',
			'mpro_brief_bio' => 'Brief Bio',
		];
	}



	public function get_all_trait_settings(): array {
		return [
			'Similar strengths' => [
				'cap' => 12,
				'base_per_match' => 4.0,
				'bonus_per_match' => 1.5,
				'description' => 'Strengths',
				'bonus_eligible' => true,
			],
			'Similar goals' => [
				'cap' => 12,
				'base_per_match' => 4.0,
				'bonus_per_match' => 1.5,
				'description' => 'Goals',
				'bonus_eligible' => true,
			],
			'Similar soft skills' => [
				'cap' => 12,
				'base_per_match' => 4.0,
				'bonus_per_match' => 1.5,
				'description' => 'Soft Skills',
				'bonus_eligible' => true,
			],
			'Leadership Compass Match' => [
				'cap' => 5,
				'base_per_match' => 5.0,
				'bonus_per_match' => 0,
				'description' => 'Leadership Compass',
				'bonus_eligible' => false,
			],
			'Field of Work Match' => [
				'cap' => 5,
				'base_per_match' => 5.0,
				'bonus_per_match' => 0,
				'description' => 'Field of Work',
				'bonus_eligible' => false,
			],
			'Years Worked Match' => [
				'cap' => 10,
				'description' => 'Years Worked',
				'bonus_eligible' => false,
			],
			'Seniority Level Match' => [
				'cap' => 10,
				'description' => 'Seniority Level',
				'bonus_eligible' => false,
			],
		];
	}

	/**
	 * Convert years worked string to numeric level for comparison
	 */
	private function get_years_worked_level($years_worked_string) {
		$years_worked_map = [
			'0 to 3 years' => 1,
			'3 to 5 years' => 2,
			'5 to 7 years' => 3,
			'7 to 10 years' => 4,
			'10 to 15 years' => 5,
			'15+ years' => 6,
		];

		return $years_worked_map[$years_worked_string] ?? 0;
	}

	/**
	 * Calculate years worked match score - mentors get more points for having worked more years
	 */
	private function score_years_worked_match($mentor_years_worked, $mentee_years_worked) {
		$mentor_level = $this->get_years_worked_level($mentor_years_worked);
		$mentee_level = $this->get_years_worked_level($mentee_years_worked);

		$settings = $this->get_trait_settings('Years Worked Match');
		$cap = $settings['cap'];

		// If mentor has less or equal years worked, no points
		if ($mentor_level <= $mentee_level) {
			return [
				'points' => 0,
				'message' => "Years Worked: Mentor not more experienced (0 pts)",
			];
		}

		// Award points based on years worked gap
		// Gap of 1 level = 2 points, gap of 2 = 4 points, etc.
		$gap = $mentor_level - $mentee_level;
		$points = min($gap * 2, $cap);

		return [
			'points' => $points,
			'message' => "Years Worked: Mentor ($mentor_years_worked) more experienced than Mentee ($mentee_years_worked) ($points pts)",
		];
	}

	/**
	 * Convert seniority level string to numeric level for comparison
	 */
	private function get_seniority_level_rank($seniority_level_string) {
		$seniority_level_map = [
			'Executive' => 13,
			'VP' => 12,
			'Senior Director' => 11,
			'Director' => 10,
			'Senior Manager' => 9,
			'Trader/Buyer/Manager' => 8,
			'Junior Trader/Assistant Buyer/Assistant Manager' => 7,
			'Associate' => 6,
			'Supervisor' => 5,
			'Coordinator' => 4,
			'Specialist' => 3,
			'Analyst' => 2,
			'Intern' => 1,
		];

		return $seniority_level_map[$seniority_level_string] ?? 0;
	}

	/**
	 * Calculate seniority level match score - mentors get more points for having higher seniority level
	 */
	private function score_seniority_level_match($mentor_seniority_level, $mentee_seniority_level) {
		$mentor_rank = $this->get_seniority_level_rank($mentor_seniority_level);
		$mentee_rank = $this->get_seniority_level_rank($mentee_seniority_level);

		$settings = $this->get_trait_settings('Seniority Level Match');
		$cap = $settings['cap'];

		// If mentor has less or equal seniority level, no points
		if ($mentor_rank <= $mentee_rank) {
			return [
				'points' => 0,
				'message' => "Seniority Level: Mentor not higher level (0 pts)",
			];
		}

		// Award points based on seniority level gap
		// Gap of 1 level = 2 points, gap of 2 = 4 points, etc.
		$gap = $mentor_rank - $mentee_rank;
		$points = min($gap * 2, $cap);

		return [
			'points' => $points,
			'message' => "Seniority Level: Mentor ($mentor_seniority_level) higher than Mentee ($mentee_seniority_level) ($points pts)",
		];
	}

	public function generate_matching_report(): array {
		global $wpdb;

		// 0️⃣ Load approved matches first
		$approved_matches = mpro_get_approved_matches($this->client_id);
		$approved_mentee_ids = array_map(function($m) { return (int)$m['mentee_id']; }, $approved_matches);
		$approved_mentor_counts = []; // Track how many approved matches each mentor has

		foreach ($approved_matches as $match) {
			$mentor_id = (int)$match['mentor_id'];
			if (!isset($approved_mentor_counts[$mentor_id])) {
				$approved_mentor_counts[$mentor_id] = 0;
			}
			$approved_mentor_counts[$mentor_id]++;
		}

		// 1️⃣ Get all mentees for a specific assigned client (excluding inactive and approved)
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
				AND NOT EXISTS (
					SELECT 1 FROM {$wpdb->postmeta} am
					WHERE am.post_id = p.ID AND am.meta_key = 'mpro_approved_mentor_id'
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
			$mentee_years_worked = $mentee_meta['mpro_years_worked'] ?? '';
			$mentee_skills_want = $mentee_multi_meta['mpro_mentee_skills_want'] ?? [];
//error_log('mentee_skills_want : ' . print_r($mentee_skills_want, true));
			$mentee_seniority_level = $mentee_meta['mpro_seniority_level'] ?? '';
			$mentee_leadership_compass = $mentee_meta['mpro_leadership_compass'] ?? '';
			$mentee_field_of_work = $mentee_meta['mpro_field_of_work'] ?? '';
			$mentee_field_importance = $mentee_meta['mpro_field_importance'] ?? '';
			$mentee_alignment_preference = $mentee_meta['mpro_alignment_preference'] ?? '';

			$mentee_match_pref = $mentee_meta['mpro_match_pref'] ?? '';

			$mentee_strengths = $mentee_multi_meta['mpro_strengths'];
			$mentee_goals = $mentee_multi_meta['mpro_mentee_goals_want'];
			$mentee_soft_skills = $mentee_multi_meta['mpro_mentee_soft_skills_want'];

			// Track mentor assignments
			foreach ($mentors as $mentor) {
				$mentor_id = $mentor->ID;
				$mentor_name = $mentor->post_title;

				// Account for approved matches when calculating current count
				$approved_count = isset($approved_mentor_counts[$mentor_id]) ? (int)$approved_mentor_counts[$mentor_id] : 0;
				$current = isset($mentor_match_count[$mentor_name]) ? (int)$mentor_match_count[$mentor_name] : 0;
				$total_current = $current + $approved_count;

				// ✅ Skip mentors who already have full mentees assigned (including approved matches)
				if ($total_current >= $max_mentees_per_mentor) {
					continue; // Move to the next mentor
				}

				// Retrieve mentor attributes
				$mentor_meta = mpro_get_single_meta_values($mentor_id, $single_meta_fields);
				$mentor_multi_meta = mpro_get_multi_meta_values($mentor_id, $multi_meta_fields);

				// Extract values
				extract($mentor_meta);
				$mentor_years_worked = $mentor_meta['mpro_years_worked'] ?? '';
				$mentor_strengths = $mentor_multi_meta['mpro_strengths'] ?? '';
				$mentor_goals = $mentor_multi_meta['mpro_mentor_goals_have'] ?? '';
				$mentor_soft_skills = $mentor_multi_meta['mpro_mentor_soft_skills_have'] ?? '';

				$mentor_skills_have = $mentor_multi_meta['mpro_mentor_skills_have'] ?? [];
				$mentor_seniority_level = $mentor_meta['mpro_seniority_level'] ?? '';
				$mentor_leadership_compass = $mentor_meta['mpro_leadership_compass'] ?? '';
				$mentor_field_of_work = $mentor_meta['mpro_field_of_work'] ?? '';

				$mentor_match_pref = $mentor_meta['mpro_match_pref'] ?? '';

				$match_score = 0;
				$max_score = 0;
				$match_fields = [];
				$tipi_match_score = 0;

				// ✅ Calculate points for Strengths match
				$trait = 'Similar strengths';
				$strengths_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_strengths,
					$mentee_strengths
				);

				$match_score += $strengths_result['points'];
				$max_score   += $strengths_result['max_points'];
				$match_fields[] = $strengths_result['message'];

				// ✅ Calculate points for Goals match
				$trait = 'Similar goals';
				$goals_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_goals,
					$mentee_goals
				);

				$match_score += $goals_result['points'];
				$max_score   += $goals_result['max_points'];
				$match_fields[] = $goals_result['message'];

				// ✅ Add bonus points if mentee prefers goals alignment
				if ($mentee_alignment_preference === 'align on goals' && $goals_result['points'] > 0) {
					$match_score += 5;
					$match_fields[] = "Goals Alignment Bonus: +5 pts (mentee prioritizes goals)";
				}

				// ✅ Calculate points for Soft Skills match
				$trait = 'Similar soft skills';
				$soft_skills_result = $this->score_top3_trait_match(
					$trait,
					$mentor_match_pref,
					$mentee_match_pref,
					$mentor_soft_skills,
					$mentee_soft_skills
				);

				$match_score += $soft_skills_result['points'];
				$max_score   += $soft_skills_result['max_points'];
				$match_fields[] = $soft_skills_result['message'];

				// ✅ Add bonus points if mentee prefers skills alignment
				if ($mentee_alignment_preference === 'align on skills' && $soft_skills_result['points'] > 0) {
					$match_score += 5;
					$match_fields[] = "Skills Alignment Bonus: +5 pts (mentee prioritizes skills)";
				}

				// ✅ Calculate points for Leadership Compass match
				$trait = 'Leadership Compass Match';
				$leadership_result = $this->score_top3_trait_match(
					$trait,
					'',
					'',
					$mentor_leadership_compass,
					$mentee_leadership_compass
				);

				$match_score += $leadership_result['points'];
				$max_score   += $leadership_result['max_points'];
				$match_fields[] = $leadership_result['message'];

				// ✅ Calculate points for Field of Work match
				$trait = 'Field of Work Match';
				$field_of_work_result = $this->score_top3_trait_match(
					$trait,
					'',
					'',
					$mentor_field_of_work,
					$mentee_field_of_work
				);

				$match_score += $field_of_work_result['points'];
				$max_score   += $field_of_work_result['max_points'];
				$match_fields[] = $field_of_work_result['message'];

				// ✅ Add bonus points based on mentee's field importance preference
				if ($field_of_work_result['points'] > 0) {
					$field_bonus = 0;
					if ($mentee_field_importance === 'Very Important') {
						$field_bonus = 3;
					} elseif ($mentee_field_importance === 'Somewhat important') {
						$field_bonus = 2;
					}
					if ($field_bonus > 0) {
						$match_score += $field_bonus;
						$match_fields[] = "Field Importance Bonus: +{$field_bonus} pts ({$mentee_field_importance})";
					}
				}

				// ✅ Calculate points for Years Worked match - mentor should have more experience
				$years_worked_result = $this->score_years_worked_match(
					$mentor_years_worked,
					$mentee_years_worked
				);

				$match_score += $years_worked_result['points'];
				$settings = $this->get_trait_settings('Years Worked Match');
				$max_score   += $settings['cap'];
				$match_fields[] = $years_worked_result['message'];

				// ✅ Calculate points for Seniority Level match - mentor should have higher job level
				$seniority_level_result = $this->score_seniority_level_match(
					$mentor_seniority_level,
					$mentee_seniority_level
				);

				$match_score += $seniority_level_result['points'];
				$settings = $this->get_trait_settings('Seniority Level Match');
				$max_score   += $settings['cap'];
				$match_fields[] = $seniority_level_result['message'];

				$total_score = $match_score;

				// ✅ Store this match as a candidate (but don't assign yet)
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
					'field'  => !empty($match['match_fields']) ? implode(', ', $match['match_fields']) : 'N/A',
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

		// ===== Combine approved matches with new matches =====
		$all_matches = array_merge($approved_matches, $matches);

		return [
			'matches' => $all_matches,
			'approved_matches' => $approved_matches,
			'suggested_matches' => $matches,
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
