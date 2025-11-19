<?php
class Matching_Base {
	protected $client_id;
	protected $total_max_score = null; // Cache for expensive calculation

	public function __construct( string $client_id = '' ) {
		$this->client_id = $client_id;
	}

	public function get_all_trait_settings(): array {
		return []; // default empty — must override in child
	}

	public function get_trait_settings( string $trait_label ): array {
		$all = $this->get_all_trait_settings();
		return $all[$trait_label] ?? [
			'cap' => 5,
			'base_per_match' => 1.0,
			'bonus_per_match' => 1.0,
			'description' => $trait_label,
			'bonus_eligible' => true,
		];
	}

	public function get_total_max_score(): float {
		// Return cached value if available
		if ( $this->total_max_score !== null ) {
			return $this->total_max_score;
		}

		$settings = $this->get_all_trait_settings();
		$total = 0;

		foreach ( $settings as $trait ) {
			$total += $trait['cap'];
		}

		// Cache the result
		$this->total_max_score = $total;

		return $total;
	}

	public function get_report_fields(): array {
		return [ // default is using Leap4Ed now
		'post_date' => 'Date Created',
		'mpro_role' => 'Role',
		'mpro_email' => 'Email',
		'mpro_phone' => 'Phone',
		'mpro_gender' => 'Gender',
		'mpro_age' => 'Age',
		'mpro_race' => 'Race',
		'mpro_college' => 'College',
		'mpro_ed' => 'Degree',
		'mrpo_still_working' => 'Still working?',
		'mpro_where_working' => 'Where working?',
		'mpro_address' => 'Address',
		'mpro_leap_experience' => 'LEAP exp.',
		'mpro_languages' => 'Languages',
		'mpro_interests' => 'Hobbies/Interests',
		'mpro_mentor_career_have' => 'Mentor Career Experience',
		'mpro_mentee_career_want' => 'Mentee Career Interests',
		//'mpro_mentor_skill_have' => 'Mentor Skills Experience',
		//'mpro_mentee_skill_want' => 'Mentee Skills Interests',
		'mpro_match_pref' => 'Matching Emphasis',
		'mpro_first_gen' => 'First Gen?',
		'mpro_caring_experience' => 'Caring exp',
		'mpro_mentor_experience' => 'Mentor exp',
		'mpro_mentor_late_response' => 'Response to Mentee',		
				
	];
	}


}
