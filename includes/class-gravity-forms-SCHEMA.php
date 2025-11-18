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

class MPro_GravityForms_Handler {

	public function __construct() {
		//add_action('gform_after_submission', [$this, 'mpro_save_survey_data'], 10, 2);
		//add_action('gform_after_submission_14', ['MPro_GravityForms_Handler', 'mpro_save_survey_data'], 10, 2);
		//add_action('gform_after_submission_15', ['MPro_GravityForms_Handler', 'mpro_save_survey_data'], 10, 2);

	}

	public function mpro_save_survey_data($entry, $form) {
		global $likert_traits;

		$client_id = get_client_id_for_form($form['id']);
		if (!$client_id) return; // exit if unknown form
		$schema = get_matching_schema($client_id);
		
		if (!$schema) return;

		// Basic post creation
		$fname = rgar($entry, '1.3');
		$lname = rgar($entry, '1.6');
		$post_id = wp_insert_post([
			'post_type'   => 'mentor_submission',
			'post_status' => 'publish',
			'post_title'  => $fname . ' ' . $lname,
		]);
		if (!$post_id) return;

		$phq4_fields = [];

		// Loop over mapped fields
		foreach ($schema['field_map'] as $gf_id => $meta) {
			$value = rgar($entry, $gf_id);

			if (isset($meta['trait'])) {
				$mapped = mpro_map_likert_response($value, $gf_id);
				update_post_meta($post_id, "mpro_trait_{$gf_id}", $mapped['label']);
				update_post_meta($post_id, "mpro_trait_{$gf_id}_score", $mapped['score']);
				continue;
			}

			if (isset($meta['phq4'])) {
				$phq4_fields[$gf_id] = $value;
				continue;
			}

			if (!empty($meta['meta_key'])) {
				update_post_meta($post_id, $meta['meta_key'], $value);
			}
		}

		// Role overrides
		$role = rgar($entry, '97');
		if (!empty($schema['role_overrides'][$role])) {
			foreach ($schema['role_overrides'][$role] as $meta_key => $gf_id) {
				update_post_meta($post_id, "mpro_{$meta_key}", rgar($entry, $gf_id));
			}
		}

		// TIPI
		if (!empty(array_filter($schema['field_map'], fn($m) => isset($m['trait']))) ) {
			$this->process_tipi_scores($entry, $post_id);
		}
		// PHQ-4
		if (!empty(array_filter($schema['field_map'], fn($m) => isset($m['phq4']))) ) {
			$q1 = mpro_map_likert_response($phq4_fields['86'] ?? '', '86')['score'];
			$q2 = mpro_map_likert_response($phq4_fields['88'] ?? '', '88')['score'];
			$q3 = mpro_map_likert_response($phq4_fields['89'] ?? '', '89')['score'];
			$q4 = mpro_map_likert_response($phq4_fields['90'] ?? '', '90')['score'];
			if (isset($q1, $q2, $q3, $q4)) {
				update_post_meta($post_id, 'mpro_phq4_distress', calculate_phq4_score($q1, $q2, $q3, $q4));
			}
		}

		// Multi-selects
		foreach ($schema['multi_selects'] as $meta_key => $gf_field_id) {
			$values = mpro_extract_multiselect_values($entry, $gf_field_id);
			if (!empty($values)) {
				update_post_meta($post_id, $meta_key, $values);
			}
		}

		// Career match
		if (!empty($schema['career_match'])) {
			update_post_meta($post_id, 'mpro_mentor_career_have', rgar($entry, $schema['career_match']['mentor_have']));
			update_post_meta($post_id, 'mpro_mentee_career_want', rgar($entry, $schema['career_match']['mentee_want']));
		}

		update_post_meta($post_id, 'assigned_client', $client_id);
	}

	private function process_tipi_scores($entry, $post_id) {
		$extraversion     = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_57_score', true), get_post_meta($post_id, 'mpro_trait_64_score', true));
		$agreeableness    = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_79_score', true), get_post_meta($post_id, 'mpro_trait_58_score', true));
		$conscientiousness= calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_59_score', true), get_post_meta($post_id, 'mpro_trait_80_score', true));
		$stability        = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_81_score', true), get_post_meta($post_id, 'mpro_trait_62_score', true));
		$openness         = calculate_tipi_trait(get_post_meta($post_id, 'mpro_trait_63_score', true), get_post_meta($post_id, 'mpro_trait_82_score', true));

		update_post_meta($post_id, 'mpro_trait_extraversion', $extraversion);
		update_post_meta($post_id, 'mpro_trait_agreeableness', $agreeableness);
		update_post_meta($post_id, 'mpro_trait_conscientiousness', $conscientiousness);
		update_post_meta($post_id, 'mpro_trait_stability', $stability);
		update_post_meta($post_id, 'mpro_trait_openness', $openness);
	}
} // class Leap4Ed_GravityForms ends

