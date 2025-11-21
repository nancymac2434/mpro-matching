<?php
/**
 * Plugin Name: MPro Matching 
 * Description: Matches mentors and mentees based on Gravity Forms survey responses.
 * Version: 1.0
 * Author: Nancy McNamara
 * License: GPL2
 */

if (!defined('ABSPATH')) exit; // Exit if accessed directly

// Define constants
define('MPRO_MATCHING_PATH', plugin_dir_path(__FILE__));
define('MPRO_MATCHING_URL', plugin_dir_url(__FILE__));

// Role constants
define('MPRO_ROLE_MENTEE', '1');
define('MPRO_ROLE_MENTOR', '2');

// Include necessary files
require_once MPRO_MATCHING_PATH . 'includes/class-matching-functions.php';
require_once MPRO_MATCHING_PATH . 'includes/class-gravity-forms.php';
require_once MPRO_MATCHING_PATH . 'includes/class-gravity-forms-SCHEMA.php';
require_once MPRO_MATCHING_PATH . 'includes/class-mpro-display.php';
require_once MPRO_MATCHING_PATH . 'includes/class-cpt.php';
require_once MPRO_MATCHING_PATH . 'includes/class-cleanup.php';
require_once MPRO_MATCHING_PATH . 'includes/admin-reports.php';
require_once MPRO_MATCHING_PATH . 'includes/admin-columns.php';
require_once MPRO_MATCHING_PATH . 'includes/upload-data.php';
require_once MPRO_MATCHING_PATH . 'includes/matching-schemas.php';
require_once MPRO_MATCHING_PATH . 'includes/class-matching-base.php';
require_once MPRO_MATCHING_PATH . 'includes/class-leap4ed-matching.php';
require_once MPRO_MATCHING_PATH . 'includes/class-salem-matching.php';
require_once MPRO_MATCHING_PATH . 'includes/class-coffee-matching.php';

// Consolidated form submission handler
function mpro_handle_form_submission($entry, $form) {
	$client_id = get_client_id_for_form($form['id']);

	if (!$client_id) {
		error_log("MPro Matching: Unknown form ID: {$form['id']}");
		return;
	}

	$handler = new Leap4Ed_GravityForms();
	$handler->save_survey_data($entry, $form);
}

add_action('gform_after_submission_12', 'mpro_handle_form_submission', 10, 2);
add_action('gform_after_submission_14', 'mpro_handle_form_submission', 10, 2);
add_action('gform_after_submission_15', 'mpro_handle_form_submission', 10, 2);
add_action('gform_after_submission_18', 'mpro_handle_form_submission', 10, 2);

// Initialize plugin
function mpro_matching_init() {
	new Leap4Ed_GravityForms();        // This handles form 12, keep name for legacy compatibility
	new MPro_GravityForms_Handler();   // This handles forms 14 & 15
	new MPro_Display();                // Rename this class from Leap4Ed_Display to MPro_Display
}
add_action('plugins_loaded', 'mpro_matching_init');

register_activation_hook(__FILE__, 'mpro_grant_pm_caps');


function render_custom_gravity_form( $atts ) {
	$atts = shortcode_atts( array(
		'clientid' => '',
		'form_id'   => '12', // Default to 12 if not provided
	), $atts );

	$clientid = sanitize_text_field( $atts['clientid'] );
	$form_id   = intval( $atts['form_id'] );

	// Gravity Forms shortcode with dynamic population
	return do_shortcode( '[gravityform id="' . $form_id . '" title="false" description="false" field_values="clientid=' . $clientid . '"]' );
}
add_shortcode( 'magic_matching', 'render_custom_gravity_form' );

add_filter('gform_field_value_client_id', function($value) {
	if ( isset($_GET['client_id']) ) {
		return sanitize_text_field($_GET['client_id']);
	}
	return $value;
});




add_action('admin_menu', function() {
	// Parent Menu - "Magic Matching"
	add_menu_page(
		'MPRO Matching & Reporting',   // Page Title
		'MPRO Matching & Reporting',   // Menu Title
		'manage_options',   // Capability
		'magic-matching',   // Menu Slug
		'__return_false',   // No function, just a parent menu
		'dashicons-admin-generic', // Icon
		25                 // Position
	);

	// Submenu - "Mentor Matching Form" (Admin Page with Correct Report)
	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Salem Application', // Page Title
		'Salem Application', // Menu Title
		'manage_options',    // Capability
		'salem-matching-form', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/salem-matching-form/');
			exit;
		}
	);
	
	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Salem Matching Report', // Page Title
		'Salem Matching Report', // Menu Title
		'manage_options',    // Capability
		'salem-matching-report', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/salem-matching-report/');
			exit;
		}
	);
		
	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Salem Data Report/Export', // Page Title
		'Salem Data Report/Export', // Menu Title
		'manage_options',    // Capability
		'salem-data-report', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/salem-data-report/');
			exit;
		}
	);

	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Coffee Application', // Page Title
		'Coffee Application', // Menu Title
		'manage_options',    // Capability
		'coffee-matching-form', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/coffee-matching-form/');
			exit;
		}
	);

	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Coffee Matching Report', // Page Title
		'Coffee Matching Report', // Menu Title
		'manage_options',    // Capability
		'coffee-matching-report', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/coffee-matching-report/');
			exit;
		}
	);

	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Coffee Data Report/Export', // Page Title
		'Coffee Data Report/Export', // Menu Title
		'manage_options',    // Capability
		'coffee-data-report', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/coffee-data-report/');
			exit;
		}
	);

	add_submenu_page(
		'magic-matching',  // Parent Slug
		'Lynn CHP Application', // Page Title mentor_matches
		'Lynn CHP Application', // Menu Title
		'manage_options',  // Capability
		'lynn-community-health-center-mentor-application', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/lynn-community-health-center-mentor-application/');
			exit;
		}
	);

	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Lynn CHP Matching Report', // Page Title
		'Lynn CHP Matching Report', // Menu Title
		'manage_options',    // Capability
		'leap-for-education-community-health', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/leap-for-education-community-health/');
			exit;
		}
	);

	add_submenu_page(
		'magic-matching',    // Parent Slug
		'Lynn CHP Data Report/Export', // Page Title
		'Lynn CHP Data Report/Export', // Menu Title
		'manage_options',    // Capability
		'leap4ed-chp-data-report', // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/leap4ed-chp-data-report/');
			exit;
		}
	);
	
	// Add Mentee/Mentor Import Tool submenu
	add_submenu_page(
		'magic-matching',                     // Parent Slug
		'Mentee/Mentor Import Tool',         // Page Title
		'Mentee/Mentor Import Tool',         // Menu Title
		'manage_options',                    // Capability
		'leap4ed-import-tool',       // Menu Slug
		function() { // Redirect to frontend page
			wp_redirect('/mentor-mentee-import-tool/');
			exit;
		}		
	);


});

/**
 * AJAX handler for approving a match
 */
add_action('wp_ajax_mpro_approve_match', 'mpro_ajax_approve_match');
add_action('wp_ajax_nopriv_mpro_approve_match', 'mpro_ajax_approve_match');

function mpro_ajax_approve_match() {
	// Verify nonce
	check_ajax_referer('mpro_match_action', 'nonce');

	$mentee_id = isset($_POST['mentee_id']) ? absint($_POST['mentee_id']) : 0;
	$mentor_id = isset($_POST['mentor_id']) ? absint($_POST['mentor_id']) : 0;

	if (!$mentee_id || !$mentor_id) {
		wp_send_json_error(['message' => 'Invalid mentee or mentor ID.']);
		return;
	}

	// Check for existing approval
	if (mpro_is_match_approved($mentee_id, $mentor_id)) {
		wp_send_json_error(['message' => 'This match is already approved.']);
		return;
	}

	// Check if mentee is already matched with someone else
	$existing_mentor = get_post_meta($mentee_id, 'mpro_approved_mentor_id', true);
	if ($existing_mentor && $existing_mentor != $mentor_id) {
		$existing_mentor_post = get_post($existing_mentor);
		$existing_mentor_name = $existing_mentor_post ? $existing_mentor_post->post_title : 'another mentor';
		wp_send_json_error([
			'message' => 'This mentee is already approved with ' . $existing_mentor_name . '. Please unapprove that match first.'
		]);
		return;
	}

	$success = mpro_approve_match($mentee_id, $mentor_id);

	if ($success) {
		wp_send_json_success(['message' => 'Match approved successfully.']);
	} else {
		wp_send_json_error(['message' => 'Failed to approve match.']);
	}
}

/**
 * AJAX handler for unapproving a match
 */
add_action('wp_ajax_mpro_unapprove_match', 'mpro_ajax_unapprove_match');
add_action('wp_ajax_nopriv_mpro_unapprove_match', 'mpro_ajax_unapprove_match');

function mpro_ajax_unapprove_match() {
	// Verify nonce
	check_ajax_referer('mpro_match_action', 'nonce');

	$mentee_id = isset($_POST['mentee_id']) ? absint($_POST['mentee_id']) : 0;
	$mentor_id = isset($_POST['mentor_id']) ? absint($_POST['mentor_id']) : 0;

	if (!$mentee_id || !$mentor_id) {
		wp_send_json_error(['message' => 'Invalid mentee or mentor ID.']);
		return;
	}

	$success = mpro_unapprove_match($mentee_id, $mentor_id);

	if ($success) {
		wp_send_json_success(['message' => 'Match unapproved successfully.']);
	} else {
		wp_send_json_error(['message' => 'Failed to unapprove match.']);
	}
}
