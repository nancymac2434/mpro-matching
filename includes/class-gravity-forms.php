<?php
/**
 * gather all data submitted, create the custom post, and set all the gfrom data as metadata
 *
 * Extract selected values from a multi-select Gravity Forms field.
 *
 * @param array  $entry  The Gravity Forms entry data.
 * @param string $field_id The base field ID of the multi-select field.
 * @return array The selected values.
 */
 
 // TIPI
 $likert_traits = [
	 '57' => ['name' => 'Extraverted, Enthusiastic', 'mapping' => [
		 'glikertcol57222f5d02' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol578baab7a5' => ['label' => 'Disagree', 'score' => 2],
		 'glikertcol57c8ad5557' => ['label' => 'Neutral', 'score' => 3],
		 'glikertcol57379523c2' => ['label' => 'Agree', 'score' => 4],
		 'glikertcol57e7d908dd' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '58' => ['name' => 'Critical, Quarrelsome', 'mapping' => [
		 'glikertcol5862d5e232' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol587c85f975' => ['label' => 'Disagree', 'score' => 2],
		 'glikertcol586d9e294e' => ['label' => 'Neutral', 'score' => 3],
		 'glikertcol5880bc80a2' => ['label' => 'Agree', 'score' => 4],
		 'glikertcol580e3d46ce' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '59' => ['name' => 'Dependable, Self-disciplined', 'mapping' => [
		 'glikertcol59f445fde0' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol59def385b8' => ['label' => 'Disagree', 'score' => 2],
		 'glikertcol595a836b24' => ['label' => 'Neutral', 'score' => 3],
		 'glikertcol5949f75fac' => ['label' => 'Agree', 'score' => 4],
		 'glikertcol5923c3ad00' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '62' => ['name' => 'Anxious, Easily upset', 'mapping' => [
		 'glikertcol62cfcec1b7' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol62ab420986' => ['label' => ' Disagree', 'score' => 2],
		 'glikertcol62b5533ffd' => ['label' => ' Neutral', 'score' => 3],  
		 'glikertcol62b78cddf0' => ['label' => ' Agree', 'score' => 4],
		 'glikertcol62c749062b' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '63' => ['name' => 'Open to New Experiences, Complex', 'mapping' => [
		 'glikertcol6369381df7' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol63206fc995' => ['label' => ' Disagree', 'score' => 2],
		 'glikertcol63bcb027be' => ['label' => ' Neutral', 'score' => 3],  
		 'glikertcol6348e17ced' => ['label' => ' Agree', 'score' => 4],
		 'glikertcol63335dca0f' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '64' => ['name' => 'Reserved, Quiet', 'mapping' => [
		 'glikertcol64b09b2de4' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol64d5a7a35e' => ['label' => ' Disagree', 'score' => 2],
		 'glikertcol640aa6192e' => ['label' => ' Neutral', 'score' => 3],  
		 'glikertcol64af72c597' => ['label' => ' Agree', 'score' => 4],
		 'glikertcol6478b4f323' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '79' => ['name' => 'Sympathetic, Warm', 'mapping' => [
		 'glikertcol79df4220bd' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol7982bded4f' => ['label' => ' Disagree', 'score' => 2],
		 'glikertcol790b5a5835' => ['label' => ' Neutral', 'score' => 3],  
		 'glikertcol79167f0b31' => ['label' => ' Agree', 'score' => 4],
		 'glikertcol79d5911194' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '80' => ['name' => 'Disorganized, Careless', 'mapping' => [
		 'glikertcol8024cb6c1b' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol80006aedcf' => ['label' => ' Disagree', 'score' => 2],
		 'glikertcol80985327e0' => ['label' => ' Neutral', 'score' => 3],  
		 'glikertcol80312d021e' => ['label' => ' Agree', 'score' => 4],
		 'glikertcol80cabff39a' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '81' => ['name' => 'Calm, Emotionally stable', 'mapping' => [
		 'glikertcol816dd9d433' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol8135eb187f' => ['label' => ' Disagree', 'score' => 2],
		 'glikertcol812f062c24' => ['label' => ' Neutral', 'score' => 3],  
		 'glikertcol8175e0d96e' => ['label' => ' Agree', 'score' => 4],
		 'glikertcol811b085e72' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
	 '82' => ['name' => 'Conventional, Uncreative', 'mapping' => [
		 'glikertcol8278c0c5b6' => ['label' => 'Strongly Disagree', 'score' => 1],
		 'glikertcol82ad6af9ea' => ['label' => ' Disagree', 'score' => 2],
		 'glikertcol82e397d939' => ['label' => ' Neutral', 'score' => 3],  
		 'glikertcol82c94e1a5b' => ['label' => ' Agree', 'score' => 4],
		 'glikertcol8223ba9b54' => ['label' => 'Strongly Agree', 'score' => 5],
	 ]],
 // Q4
  '86' => ['name' => 'Feeling nervous, anxious or on edge', 'mapping' => [
	  'glikertcol86cc278802' => ['label' => 'Not at all', 'score' => 0],
	  'glikertcol86c5311d9f' => ['label' => 'Several Days', 'score' => 1],
	  'glikertcol86b5bb5d71' => ['label' => 'More than half the days', 'score' => 2],
	  'glikertcol86c7ee60c1' => ['label' => 'Nearly Every Day', 'score' => 3],
  ]],
  '90' => ['name' => 'Not being able to stop or control worrying', 'mapping' => [
		'glikertcol86cc278802' => ['label' => 'Not at all', 'score' => 0],
		'glikertcol86c5311d9f' => ['label' => 'Several Days', 'score' => 1],
		'glikertcol86b5bb5d71' => ['label' => 'More than half the days', 'score' => 2],
		'glikertcol86c7ee60c1' => ['label' => 'Nearly Every Day', 'score' => 3],
	]],
  '89' => ['name' => 'Feeling down, depressed, or hopeless', 'mapping' => [
		  'glikertcol86cc278802' => ['label' => 'Not at all', 'score' => 0],
		  'glikertcol86c5311d9f' => ['label' => 'Several Days', 'score' => 1],
		  'glikertcol86b5bb5d71' => ['label' => 'More than half the days', 'score' => 2],
		  'glikertcol86c7ee60c1' => ['label' => 'Nearly Every Day', 'score' => 3],
	  ]],
  '88' => ['name' => 'Little interest or pleasure in doing things', 'mapping' => [
			'glikertcol86cc278802' => ['label' => 'Not at all', 'score' => 0],
			'glikertcol86c5311d9f' => ['label' => 'Several Days', 'score' => 1],
			'glikertcol86b5bb5d71' => ['label' => 'More than half the days', 'score' => 2],
			'glikertcol86c7ee60c1' => ['label' => 'Nearly Every Day', 'score' => 3],
		]],
 
 ];
 
function mpro_extract_multiselect_values($entry, $field_id) {
	global $likert_traits;
	$selected_values = [];

	foreach ($entry as $key => $value) {
		if (strpos($key, $field_id . '.') === 0 && !empty($value)) {
			$selected_values[] = $value;
		}
	}

	return $selected_values;
}

function mpro_map_likert_response($value, $field_id) {
	global $likert_traits; 

	if (isset($likert_traits[$field_id]['mapping'][$value])) {
		return $likert_traits[$field_id]['mapping'][$value]; 
	}

	return ['label' => 'Unknown', 'score' => null]; // Handle missing values
}

function get_client_id_for_form($form_id) {
	$form_map = [
		12 => 'leap4ed-chp',
		15 => 'salem',
		18 => 'coffee',
		14 => 'vt',
		11 => 'mentorpro',
	];

	return $form_map[$form_id] ?? null;
}

class Leap4Ed_GravityForms {

	public function __construct() {
	}
	
	public  function save_survey_data($entry, $form) {
		
		global $likert_traits;	
		
		$client_id = get_client_id_for_form($form['id']);
		if ( empty($client_id) ) {
			$client_id = 'mentorpro'; // fallback value if needed
		}
		$page_slug = get_post_field( 'post_name', get_the_ID() );
				
		//error_log("🔍 Gravity Forms Entry Data: " . print_r($entry, true));
		$fname = rgar($entry, '1.3'); 
		$lname = rgar($entry, '1.6'); 
		$address_line1 = rgar( $entry, '99.1' );
		$address_line2 = rgar( $entry, '99.2' );
		$city          = rgar( $entry, '99.3' );
		$state         = rgar( $entry, '99.4' );
		$zip           = rgar( $entry, '99.5' );
		$country       = rgar( $entry, '99.6' );
		
		$address_parts = array_filter([
			$address_line1,
			$address_line2,
			trim("$city, $state $zip"),
			$country,
		]);
		
		$address = implode("\n", $address_parts);

		// Store as a new post
		$post_id = wp_insert_post([
			'post_type'   => 'mentor_submission',
			'post_status' => 'publish',
			'post_title'  => $fname . ' ' . $lname, // Use full name as title
		]);

		// Check for errors
		if (is_wp_error($post_id)) {
			error_log("MPro Matching: Failed to create mentor_submission: " . $post_id->get_error_message());
			error_log("MPro Matching: Entry ID: " . $entry['id']);
			return;
		}

		if (!$post_id || $post_id === 0) {
			error_log("MPro Matching: wp_insert_post returned 0 for entry {$entry['id']}");
			return;
		}

		// Post created successfully, continue with meta updates
		if ($post_id) {
			
		// Get the schema to read field IDs dynamically
		$schema = get_matching_schema($client_id);

		// Extract survey data
		$email = rgar($entry, '3');

		// Get role field ID from schema (different forms use different field IDs)
		$role_field_id = '97'; // default for leap4ed-chp
		if ($schema && isset($schema['field_map'])) {
			foreach ($schema['field_map'] as $field_id => $config) {
				if (isset($config['meta_key']) && $config['meta_key'] === 'mpro_role') {
					$role_field_id = $field_id;
					break;
				}
			}
		}
		$role  = rgar($entry, $role_field_id);

		// Normalize role to ensure consistent format
		// Handle both numeric values (1, 2) and text labels (Mentee, Mentor, mentee, mentor)
		$role_lower = strtolower(trim($role));
		if ($role_lower === 'mentee' || $role === '1' || $role === 1) {
			$role = 'mentee';
			$role_numeric = '1';
		} elseif ($role_lower === 'mentor' || $role === '2' || $role === 2) {
			$role = 'mentor';
			$role_numeric = '2';
		} else {
			// Log unexpected role value and use default
			error_log("MPro Matching: Unexpected role value '{$role}' for entry {$entry['id']}");
			$role = 'mentee'; // default to mentee
			$role_numeric = '1';
		}

		// Save core meta fields (always needed for all clients)
		update_post_meta($post_id, 'assigned_client', $client_id);
		update_post_meta($post_id, 'mpro_fname', $fname);
		update_post_meta($post_id, 'mpro_lname', $lname);
		update_post_meta($post_id, 'mpro_email', $email);
		update_post_meta($post_id, 'mpro_role', $role_numeric);

		// Process address field (field 99) if present
		update_post_meta($post_id, 'mpro_address', $address);

		// SCHEMA-DRIVEN: Extract and save all fields defined in client's field_map
		if ($schema && isset($schema['field_map'])) {
			foreach ($schema['field_map'] as $field_id => $config) {
				// Handle trait fields (TIPI)
				if (isset($config['trait']) && $config['trait']) {
					if (isset($likert_traits[$field_id])) {
						$raw_value = rgar($entry, $field_id);
						$mapped_response = mpro_map_likert_response($raw_value, $field_id);

						update_post_meta($post_id, "mpro_trait_{$field_id}", $mapped_response['label']);
						update_post_meta($post_id, "mpro_trait_{$field_id}_score", $mapped_response['score']);
					}
					continue;
				}

				// Handle PHQ-4 fields
				if (isset($config['phq4']) && $config['phq4']) {
					// PHQ-4 processing (if needed in future)
					continue;
				}

				// Handle regular meta fields
				if (isset($config['meta_key'])) {
					$meta_key = $config['meta_key'];

					// Skip already-handled core fields
					if (in_array($meta_key, ['mpro_fname', 'mpro_lname', 'mpro_email', 'mpro_role'])) {
						continue;
					}

					// Extract value from entry
					$value = rgar($entry, $field_id);

					// Save to post meta
					if ($value !== '' && $value !== null) {
						update_post_meta($post_id, $meta_key, $value);
					}
				}
			}
		}

		// Process TIPI scores (calculate aggregate scores) - only for clients that use TIPI
		$has_trait_fields = false;
		if ($schema && isset($schema['field_map'])) {
			foreach ($schema['field_map'] as $field_id => $config) {
				if (isset($config['trait']) && $config['trait']) {
					$has_trait_fields = true;
					break;
				}
			}
		}

		if ($has_trait_fields && isset($likert_traits) && !empty($likert_traits)) {
			$this->process_tipi_scores($entry, $post_id);
		}

		// SCHEMA-DRIVEN: Handle multi-select fields from schema
		if ($schema && isset($schema['multi_selects'])) {
			foreach ($schema['multi_selects'] as $meta_key => $field_id) {
				$values = mpro_extract_multiselect_values($entry, $field_id);
				if (!empty($values)) {
					update_post_meta($post_id, $meta_key, $values);
				}
			}
		}

		}
	}


	/**
	 * Retrieve and store selected options for multi-select fields
	 */
	private function get_selected_options($entry, $field_id, $choices) {
		$selected_values = [];
		foreach ($choices as $choice) {
			$choice_key = $field_id . '.' . $choice; // Format used by Gravity Forms
			if (!empty($entry[$choice_key])) {
				$selected_values[] = $choice;
			}
		}
		return $selected_values;
	}

		/**
	 * Maps TIPI Likert responses to numeric scores.
	 */
	function mpro_map_likert_response($value, $field_id) {
		global $likert_traits;
	
		if (isset($likert_traits[$field_id]['mapping'][$value])) {
			return $likert_traits[$field_id]['mapping'][$value]; 
		}
	
		return ['label' => 'Unknown', 'score' => null]; // Handle missing values
	}
	
	/**
	 * Process and store TIPI scores.
	 */
	private function process_tipi_scores($entry, $post_id) {
		global $likert_traits;
	
		$trait_scores = []; // Store mapped responses and scores
	
		foreach ($likert_traits as $field_id => $trait) {
			$raw_value = rgar($entry, $field_id);
			$mapped_response = mpro_map_likert_response($raw_value, $field_id);
	
			// Store response & score
			$trait_scores[$trait['name']] = [
				'response' => $mapped_response['label'],
				'score' => $mapped_response['score']
			];
			
			// Save to WordPress meta
			update_post_meta($post_id, "mpro_trait_{$field_id}", $mapped_response['label']);
			update_post_meta($post_id, "mpro_trait_{$field_id}_score", $mapped_response['score']);

		}
		
		// ✅ TIPI Matching Score (Absolute Differences)
		$extraversion = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_57_score', true) , get_post_meta($post_id, 'mpro_trait_64_score', true));
		$agreeableness = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_79_score', true) , get_post_meta($post_id, 'mpro_trait_58_score', true));
		$conscientiousness = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_59_score', true) , get_post_meta($post_id, 'mpro_trait_80_score', true));
		$stability = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_81_score', true) , get_post_meta($post_id, 'mpro_trait_62_score', true));
		$openness = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_63_score', true) , get_post_meta($post_id, 'mpro_trait_82_score', true));
						
		update_post_meta($post_id, "mpro_trait_extraversion", $extraversion);
		update_post_meta($post_id, "mpro_trait_agreeableness", $agreeableness);
		update_post_meta($post_id, "mpro_trait_conscientiousness", $conscientiousness);
		update_post_meta($post_id, "mpro_trait_stability", $stability);
		update_post_meta($post_id, "mpro_trait_openness", $openness);
		
	}
	

}

function calculate_tipi_trait($positive_response, $reverse_response) {
	// Ensure valid TIPI range; set default value if missing
	$positive_response = (!empty($positive_response) && $positive_response >= 1 && $positive_response <= 5) ? $positive_response : 3;
	$reverse_response = (!empty($reverse_response) && $reverse_response >= 1 && $reverse_response <= 5) ? $reverse_response : 3;
	
	// Compute the TIPI trait score for a 5-point scale
	return ($positive_response + (6 - $reverse_response)) / 2;
}

function calculate_phq4_score($q1, $q2, $q3, $q4) {
	// Ensure values are within range (0-3)
	if (!in_array($q1, [0,1,2,3]) || !in_array($q2, [0,1,2,3]) || 
		!in_array($q3, [0,1,2,3]) || !in_array($q4, [0,1,2,3])) {
		return null; // Invalid input
	}

	// Calculate scores
	$phq2_score = floatval($q1) + floatval($q2);
	$gad2_score = floatval($q3) + floatval($q4);
	$phq4_total = $phq2_score + $gad2_score;

	return interpret_phq4_score($phq4_total);

	//return [
	//	'phq4_total' => $phq4_total,
	//	'phq2_score' => $phq2_score,
	//	'gad2_score' => $gad2_score,
	//	'distress_level' => interpret_phq4_score($phq4_total)
	//];
}

function interpret_phq4_score($score) {
	if ($score >= 9) return 'Severe Distress';
	if ($score >= 6) return 'Moderate Distress';
	if ($score >= 3) return 'Mild Distress';
	return 'Normal';
}


