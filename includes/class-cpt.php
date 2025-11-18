<?php
/** Register CPT with explicit caps (single registration) */
add_action('init', function () {
	register_post_type('mentor_submission', [
		'labels' => [
			'name'          => __('Mentor Submissions'),
			'singular_name' => __('Mentor Submission'),
		],
		'public'         => false,
		'show_ui'        => true,
		'show_in_menu' => 'magic-matching', // parent menu slug from add_menu_page()
		//'show_in_menu'   => true,
		'supports'       => ['title', 'author'],
		'map_meta_cap'   => true, // map meta-caps (edit_post, delete_post, etc.)
		'capabilities'   => [
			// single item
			'edit_post'              => 'edit_mentor_submission',
			'read_post'              => 'read_mentor_submission',
			'delete_post'            => 'delete_mentor_submission',
			'create_posts'           => 'do_not_allow',

			// plural (these gate the list table & menu)
			'edit_posts'             => 'edit_mentor_submissions',          // REQUIRED for menu/list
			'edit_others_posts'      => 'edit_others_mentor_submissions',
			'publish_posts'          => 'publish_mentor_submissions',
			'read_private_posts'     => 'read_private_mentor_submissions',
			'delete_posts'           => 'delete_mentor_submissions',
			'delete_private_posts'   => 'delete_private_mentor_submissions',
			'delete_published_posts' => 'delete_published_mentor_submissions',
			'delete_others_posts'    => 'delete_others_mentor_submissions',

			'edit_private_posts'     => 'edit_private_mentor_submissions',
			'edit_published_posts'   => 'edit_published_mentor_submissions',

		],
	]);
}, 9);

/** Grant the MINIMUM caps so Admins + Contract can SEE the menu and DELETE */
function mpro_grant_pm_caps() {
	$targets = array_filter([
		get_role('administrator'),
		get_role('contract'),
	]);

	if (empty($targets)) return;

	$caps = [
		// REQUIRED so the CPT menu and list screen appear:
		'edit_mentor_submissions',

		// Basic read/open
		'read_mentor_submission',
		'read_private_mentor_submissions',

		// Delete capabilities (covers own/others/published/private)
		'delete_mentor_submission',
		'delete_mentor_submissions',
		'delete_others_mentor_submissions',
		'delete_private_mentor_submissions',
		'delete_published_mentor_submissions',

		// Optional but handy if they’ll view/edit rows
		'edit_mentor_submission',
		'edit_others_mentor_submissions',
		'edit_published_mentor_submissions',
		
		'publish_mentor_submissions',

	];

	foreach ($targets as $role) {
		foreach ($caps as $cap) {
			if (!$role->has_cap($cap)) {
				$role->add_cap($cap);
			}
		}
	}
}
add_action('init', 'mpro_grant_pm_caps', 20);

add_action('load-post-new.php', function () {
	$post_type = isset($_GET['post_type']) ? sanitize_key($_GET['post_type']) : get_current_screen()->post_type;
	if ($post_type === 'mentor_submission') {
		wp_die(__('Creating Mentor Submissions from the dashboard is disabled. Please use the application form.'), 403);
	}
});

// Remove "Add New" submenu under the CPT list.
add_action('admin_menu', function () {
	remove_submenu_page('edit.php?post_type=mentor_submission', 'post-new.php?post_type=mentor_submission');
}, 9999);

// Remove the admin-bar "+ New → Mentor Submission"
add_action('admin_bar_menu', function ($bar) {
	$bar->remove_node('new-mentor_submission');
}, 999);

// When untrashing mentor_submission, make it 'publish' (if user can publish)
add_filter('wp_untrash_post_status', function ($new_status, $post_id) {
	if (get_post_type($post_id) === 'mentor_submission' && current_user_can('publish_mentor_submissions', $post_id)) {
		return 'publish';
	}
	return $new_status; // default 'draft' for everything else
}, 10, 2);

/**
 * Admin-editable fields for mentor_submission CPT
 */
add_action('init', function () {
	$keys = [
		'max_matches_per_mentor' => ['type' => 'integer', 'sanitize' => function($v){ $v = trim((string)$v); if ($v==='') return ''; $n=(int)$v; return $n>=0 ? $n : 0; }],
		'mpro_fname'             => ['type' => 'string',  'sanitize' => function($v){ return sanitize_text_field($v); }],
		'mpro_lname'             => ['type' => 'string',  'sanitize' => function($v){ return sanitize_text_field($v); }],
		'mpro_email'             => ['type' => 'string',  'sanitize' => function($v){ return sanitize_email($v); }],
		'mpro_phone'             => ['type' => 'string',  'sanitize' => function($v){ return preg_replace('/[^0-9+\-\(\)x\. ]+/', '', $v); }],
	];
	foreach ($keys as $meta_key => $opts) {
		register_post_meta('mentor_submission', $meta_key, [
			'type'              => $opts['type'],
			'single'            => true,
			'sanitize_callback' => $opts['sanitize'],
			'show_in_rest'      => true,
			'auth_callback'     => function($allowed, $meta_key, $post_id) {
				// Only admins/edit_others_posts can change these
				return current_user_can('manage_options') || current_user_can('edit_others_posts');
			},
		]);
	}
});

add_action('add_meta_boxes', function () {
	add_meta_box(
		'mentor_admin_editables',
		'Admin Editable Fields',
		function($post){
			if (get_post_type($post) !== 'mentor_submission') { return; }
			wp_nonce_field('save_mentor_admin_editables','mentor_admin_editables_nonce');
			$vals = [
				'max_matches_per_mentor' => get_post_meta($post->ID, 'max_matches_per_mentor', true),
				'mpro_fname'             => get_post_meta($post->ID, 'mpro_fname', true),
				'mpro_lname'             => get_post_meta($post->ID, 'mpro_lname', true),
				'mpro_email'             => get_post_meta($post->ID, 'mpro_email', true),
				'mpro_phone'             => get_post_meta($post->ID, 'mpro_phone', true),
			];

			$max = esc_attr($vals['max_matches_per_mentor']);
			$fn  = esc_attr($vals['mpro_fname']);
			$ln  = esc_attr($vals['mpro_lname']);
			$em  = esc_attr($vals['mpro_email']);
			$ph  = esc_attr($vals['mpro_phone']);

			echo <<<HTML
<style>
#mentor_admin_editables .field { margin:8px 0; }
#mentor_admin_editables .field label { display:block; font-weight:600; margin-bottom:4px; }
#mentor_admin_editables input[type="text"],
#mentor_admin_editables input[type="number"],
#mentor_admin_editables input[type="email"] { width:100%; max-width:420px; }
</style>
<div id="mentor_admin_editables">
  <div class="field">
	<label for="max_matches_per_mentor">Max mentees this mentor can take</label>
	<input type="number" min="0" step="1" id="max_matches_per_mentor" name="max_matches_per_mentor"
		   placeholder="(leave blank to use client default)"
		   value="{$max}">
  </div>
  <hr>
  <div class="field">
	<label for="mpro_fname">First name</label>
	<input type="text" id="mpro_fname" name="mpro_fname" value="{$fn}">
  </div>
  <div class="field">
	<label for="mpro_lname">Last name</label>
	<input type="text" id="mpro_lname" name="mpro_lname" value="{$ln}">
  </div>
  <div class="field">
	<label for="mpro_email">Email</label>
	<input type="email" id="mpro_email" name="mpro_email" value="{$em}">
  </div>
  <div class="field">
	<label for="mpro_phone">Phone</label>
	<input type="text" id="mpro_phone" name="mpro_phone" value="{$ph}">
  </div>
  <p class="description">Only admins (or editors) can change these. All other meta remains locked.</p>
</div>
HTML;
		},
		'mentor_submission',
		'normal',
		'high'
	);
});


add_action('save_post_mentor_submission', function($post_id){
	if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
	if (!isset($_POST['mentor_admin_editables_nonce']) || !wp_verify_nonce($_POST['mentor_admin_editables_nonce'],'save_mentor_admin_editables')) return;
	if (!current_user_can('edit_post', $post_id)) return;

	$map = ['max_matches_per_mentor','mpro_fname','mpro_lname','mpro_email','mpro_phone'];

	foreach ($map as $key) {
		if (!array_key_exists($key, $_POST)) continue; // not present on this save
		$raw = $_POST[$key];
		// Apply the same sanitizer we registered:
		$registered = get_registered_meta_keys('post', 'mentor_submission');
		$sanitizer  = $registered[$key]['sanitize_callback'] ?? null;
		$val = is_callable($sanitizer) ? call_user_func($sanitizer, $raw) : $raw;

		// Empty behavior for max_matches_per_mentor: delete = use default
		if ($key === 'max_matches_per_mentor' && $val === '') {
			delete_post_meta($post_id, $key);
		} else {
			update_post_meta($post_id, $key, $val);
		}
	}
});

add_filter('manage_mentor_submission_posts_columns', function($cols){
	$new = [];
	foreach ($cols as $k=>$v) {
		$new[$k] = $v;
		if ($k === 'title') {
			$new['max_matches_per_mentor'] = 'Max Matches';
		}
	}
	return $new;
});

add_action('manage_mentor_submission_posts_custom_column', function($col, $post_id){
	if ($col === 'max_matches_per_mentor') {
		$val = get_post_meta($post_id, 'max_matches_per_mentor', true);
		echo $val !== '' ? (int)$val : '<em>default</em>';
	}
}, 10, 2);

add_filter('manage_edit-mentor_submission_sortable_columns', function($cols){
	$cols['max_matches_per_mentor'] = 'max_matches_per_mentor';
	return $cols;
});

add_action('pre_get_posts', function($q){
	if (!is_admin() || !$q->is_main_query()) return;
	if ($q->get('orderby') === 'max_matches_per_mentor') {
		$q->set('meta_key', 'max_matches_per_mentor');
		$q->set('orderby', 'meta_value_num');
	}
});
