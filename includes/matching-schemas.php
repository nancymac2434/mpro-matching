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
				'2'   => ['meta_key' => 'mpro_phone'],
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
					'mpro_mentor_skill_have', 'mpro_mentee_skill_want',
				],
				'multi_meta_fields' => [
					'mpro_interests', 'mpro_languages'
				],
				'field_map' => [
					'1.3' => ['meta_key' => 'mpro_fname'],
					'1.6' => ['meta_key' => 'mpro_lname'],
					'3'   => ['meta_key' => 'mpro_email'],
					'2'   => ['meta_key' => 'mpro_phone'],
					'92'  => ['meta_key' => 'mpro_role'],
					'18'  => ['meta_key' => 'mpro_gender'],
					'44'  => ['meta_key' => 'mpro_age'],
					'47'  => ['meta_key' => 'mpro_family_origin'],
					'73'  => ['meta_key' => 'mpro_career_match'],
					'37'  => ['meta_key' => 'mpro_education'],
					'94'  => ['meta_key' => 'mpro_match_pref'],
					'95'  => ['meta_key' => 'mpro_ssu_id'],
					'85'  => ['meta_key' => 'mpro_mentor_skill_have'],
					'83'  => ['meta_key' => 'mpro_mentee_skill_want'],
				],
	
				'multi_selects' => [
					'mpro_languages' => '35',
					'mpro_interests' => '36',
				],
	
				'skills_match' => [
					'mpro_mentor_skill_have' => '85',
					'mpro_mentee_skill_want' => '83',
				],
	
				'match_bonus_fields' => [
					'mpro_match_pref' => ['field_id' => '94', 'type' => 'ranked'],
				]
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
				'2'   => ['meta_key' => 'mpro_phone'],
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
						'2'   => ['meta_key' => 'mpro_phone'],
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