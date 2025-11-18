<?php
//
// When viewing the list of mentor or mentee submissions, this adds a column
// showing the client code associated with the entry
//
//
//
// Exit if accessed directly.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Add a custom column for the 'assigned_client' meta.
function my_mentor_submission_columns( $columns ) {
	// Insert your new column after the title.
	$new_columns = array();
	foreach ( $columns as $key => $value ) {
		$new_columns[ $key ] = $value;
		if ( 'title' === $key ) {
			$new_columns['assigned_client'] = __( 'Client', 'text-domain' );
		}
	}
	return $new_columns;
}
add_filter( 'manage_mentor_submission_posts_columns', 'my_mentor_submission_columns' );

// Output content for our custom column.
function my_mentor_submission_custom_column( $column, $post_id ) {
	if ( 'assigned_client' === $column ) {
		$client = get_post_meta( $post_id, 'assigned_client', true );
		echo $client ? esc_html( $client ) : '—';
	}
}
add_action( 'manage_mentor_submission_posts_custom_column', 'my_mentor_submission_custom_column', 10, 2 );