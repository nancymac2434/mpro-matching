<?php

function get_matching_schema($client_id) {
	$schemas = [
		'leap4ed' => [
			'max_mentees_per_mentor' => 2,
			'single_meta_fields' => [
				'mpro_gender', 'mpro_race', 'mpro_ed', 'mpro_match_pref',
				'mpro_first_gen', 'mpro_mentor_career_have', 'mpro_mentee_career_want',
				'mpro_mentor_skills_have', 'mpro_mentee_skills_want', 'mpro_preference_to_meet'
			],
			'multi_meta_fields' => [
				'mpro_interests', 'mpro_languages'
			],
			'field_map' => [
				'1.3' => ['meta_key' => 'mpro_fname'],
				'1.6' => ['meta_key' => 'mpro_lname'],
				'3'   => ['meta_key' => 'mpro_email'],
				'97'  => ['meta_key' => 'mpro_role'],
				'18'  => ['meta_key' => 'mpro_gender'],
				// TIPI
				'57'  => ['trait' => true],
				'58'  => ['trait' => true],
				'59'  => ['trait' => true],
				'62'  => ['trait' => true],
				'63'  => ['trait' => true],
				'64'  => ['trait' => true],
				'79'  => ['trait' => true],
				'80'  => ['trait' => true],
				'81'  => ['trait' => true],
				'82'  => ['trait' => true],
				// PHQ-4
				'86'  => ['phq4' => true],
				'88'  => ['phq4' => true],
				'89'  => ['phq4' => true],
				'90'  => ['phq4' => true],
			],
			'multi_selects' => [
				'mpro_languages'     => '35',
				'mpro_interests'     => '36',
				'mpro_family_origin' => '45',
			],
			'career_match' => [
				'mentor_have' => '83',
				'mentee_want' => '109',
			],
			'role_overrides' => [
				'mentee' => [
					'first_gen' => '118',
					'ed'        => '111',
				],
				'mentor' => [
					'first_gen' => '119',
					'ed'        => '37',
			]
		]
	],
	
	 	'salem' => [
				'max_mentees_per_mentor' => 13,
				'single_meta_fields' => [
					'mpro_ssu_id' , 'mpro_gender', 'mpro_family_origin', 'mpro_age', 'mpro_ed', 'mpro_match_pref', 'mpro_career_match',
				],
				'multi_meta_fields' => [
					'mpro_interests', 'mpro_languages',
					'mpro_mentor_skills_have', 'mpro_mentee_skills_want'
				],
				'field_map' => [
					'1.3' => ['meta_key' => 'mpro_fname'],
					'1.6' => ['meta_key' => 'mpro_lname'],
					'3'   => ['meta_key' => 'mpro_email'],
					'92'  => ['meta_key' => 'mpro_role'],
					'18'  => ['meta_key' => 'mpro_gender'],
					'44'  => ['meta_key' => 'mpro_age'],
					'47'  => ['meta_key' => 'mpro_family_origin'],
					'73'  => ['meta_key' => 'mpro_career_match'],
					'37'  => ['meta_key' => 'mpro_education'],
					'94'  => ['meta_key' => 'mpro_match_pref'],
					'95'  => ['meta_key' => 'mpro_ssu_id'],
					'85'  => ['meta_key' => 'mpro_mentor_skills_have'],
					'83'  => ['meta_key' => 'mpro_mentee_skills_want'],
				],
	
				'multi_selects' => [
					'mpro_languages' => '35',
					'mpro_interests' => '36',
					'mpro_mentor_skills_have' => '85',
					'mpro_mentee_skills_want' => '83',
				],
	
				'skills_match' => [
					'mpro_mentor_skills_have' => '85',
					'mpro_mentee_skills_want' => '83',
				],
	
				'match_bonus_fields' => [
					'mpro_match_pref' => ['field_id' => '94', 'type' => 'ranked'],
				]
	],

	 	'coffee' => [
				'max_mentees_per_mentor' => 1,
				'single_meta_fields' => [
					'mpro_years_worked', 'mpro_seniority_level',
					'mpro_leadership_compass',
					'mpro_field_of_work', 'mpro_position_title', 'mpro_company_name',
					'mpro_field_importance', 'mpro_alignment_preference', 'mpro_brief_bio',
				],
				'multi_meta_fields' => [
					'mpro_strengths', 'mpro_mentor_goals_have', 'mpro_mentee_goals_want',
					'mpro_mentor_soft_skills_have', 'mpro_mentee_soft_skills_want',
				],
				'field_map' => [
					'1.3' => ['meta_key' => 'mpro_fname'],
					'1.6' => ['meta_key' => 'mpro_lname'],
					'3'   => ['meta_key' => 'mpro_email'],
					'92'  => ['meta_key' => 'mpro_role'],
					'73'  => ['meta_key' => 'mpro_seniority_level'],
					'107' => ['meta_key' => 'mpro_years_worked'],
					'36'  => ['meta_key' => 'mpro_strengths'],
					'96'  => ['meta_key' => 'mpro_mentee_goals_want'],
					'103' => ['meta_key' => 'mpro_mentor_goals_have'],
					'97'  => ['meta_key' => 'mpro_mentee_soft_skills_want'],
					'104' => ['meta_key' => 'mpro_mentor_soft_skills_have'],
					'98'  => ['meta_key' => 'mpro_leadership_compass'],
					'99'  => ['meta_key' => 'mpro_field_of_work'],
					'101' => ['meta_key' => 'mpro_position_title'],
					'102' => ['meta_key' => 'mpro_company_name'],
					'105' => ['meta_key' => 'mpro_field_importance'],
					'106' => ['meta_key' => 'mpro_alignment_preference'],
					'108' => ['meta_key' => 'mpro_brief_bio'],
				],

				'multi_selects' => [
					'mpro_strengths' => '36',
					'mpro_mentee_goals_want' => '96',
					'mpro_mentor_goals_have' => '103',
					'mpro_mentee_soft_skills_want' => '97',
					'mpro_mentor_soft_skills_have' => '104',
				],

				'goals_match' => [
					'mpro_mentor_goals_have' => '103',
					'mpro_mentee_goals_want' => '96',
				],

				'soft_skills_match' => [
					'mpro_mentor_soft_skills_have' => '104',
					'mpro_mentee_soft_skills_want' => '97',
				],
	],

		'leap4ed-chp' => [
			'max_mentees_per_mentor' => 2,
			'single_meta_fields' => [
				'mpro_gender', 'mpro_race', 'mpro_ed', 'mpro_match_pref',
				'mpro_first_gen', 'mpro_mentor_career_have', 'mpro_mentee_career_want',
				'mpro_mentor_skills_have', 'mpro_mentee_skills_want', 'mpro_preference_to_meet'
			],
			'multi_meta_fields' => [
				'mpro_interests', 'mpro_languages'
			],
			'field_map' => [
				'1.3' => ['meta_key' => 'mpro_fname'],
				'1.6' => ['meta_key' => 'mpro_lname'],
				'3'   => ['meta_key' => 'mpro_email'],
				'97'  => ['meta_key' => 'mpro_role'],
				'18'  => ['meta_key' => 'mpro_gender'],
				// TIPI
				'57'  => ['trait' => true],
				'58'  => ['trait' => true],
				'59'  => ['trait' => true],
				'62'  => ['trait' => true],
				'63'  => ['trait' => true],
				'64'  => ['trait' => true],
				'79'  => ['trait' => true],
				'80'  => ['trait' => true],
				'81'  => ['trait' => true],
				'82'  => ['trait' => true],
				// PHQ-4
				'86'  => ['phq4' => true],
				'88'  => ['phq4' => true],
				'89'  => ['phq4' => true],
				'90'  => ['phq4' => true],
			],
			'multi_selects' => [
				'mpro_languages'     => '35',
				'mpro_interests'     => '36',
				'mpro_family_origin' => '45',
			],
			'career_match' => [
				'mentor_have' => '83',
				'mentee_want' => '109',
			],
			'role_overrides' => [
				'mentee' => [
					'first_gen' => '118',
					'ed'        => '111',
				],
				'mentor' => [
					'first_gen' => '119',
					'ed'        => '37',
				]
			]
		],
		
		'mentorpro' => [
					'max_mentees_per_mentor' => 1,
					'field_map' => [
						'1.3' => ['meta_key' => 'mpro_fname'],
						'1.6' => ['meta_key' => 'mpro_lname'],
						'3'   => ['meta_key' => 'mpro_email'],
						'92'  => ['meta_key' => 'mpro_role'],
						'18'  => ['meta_key' => 'mpro_gender'],
						'33'  => ['meta_key' => 'mpro_zip'],
						'44'  => ['meta_key' => 'mpro_age'],
						'47'  => ['meta_key' => 'mpro_race'],
						'48'  => ['meta_key' => 'mpro_firstgen'],
						'51'  => ['meta_key' => 'mpro_immigrant'],
						'73'  => ['meta_key' => 'mpro_career_match'],
						'37'  => ['meta_key' => 'mpro_education'],
						'94'  => ['meta_key' => 'mpro_match_pref'],
						'91'  => ['meta_key' => 'mpro_mentor_experience'],
						'93'  => ['meta_key' => 'mpro_mentor_helping'],
					],
		
					'multi_selects' => [
						'mpro_languages' => '35',
						'mpro_family_origin' => '45',
						'mpro_interests' => '36',
					],
		
					'skills_match' => [
						'mpro_mentor_have' => '85',
						'mpro_mentee_want' => '83',
					],
		
					'match_bonus_fields' => [
						'mpro_match_pref' => ['field_id' => '94', 'type' => 'ranked'],
					]
		]
	];

	return $schemas[$client_id] ?? null;

}