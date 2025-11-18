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

add_action('gform_after_submission_12', function($entry, $form) {
	$handler = new Leap4Ed_GravityForms(); // ✅ create an instance
	$handler->save_survey_data($entry, $form); // ✅ call instance method
}, 10, 2);

add_action('gform_after_submission_14', function($entry, $form) {
	$handler = new MPro_GravityForms_Handler(); // ✅ create an instance
	$handler->mpro_save_survey_data($entry, $form); // ✅ call instance method
}, 10, 2);
add_action('gform_after_submission_15', function($entry, $form) {
	$handler = new MPro_GravityForms_Handler(); // ✅ create an instance
	$handler->mpro_save_survey_data($entry, $form); // ✅ call instance method
}, 10, 2);



// Initialize plugin
//function leap4ed_matching_init() {
//	new Leap4Ed_GravityForms();
//	new MPro_GravityForms_Handler();
//	new Leap4Ed_Display(); 
//}
//add_action('plugins_loaded', 'leap4ed_matching_init');
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
