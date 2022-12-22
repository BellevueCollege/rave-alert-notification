<?php

/**
 * Registers the `bc_alert` post type.
 */
function bc_alert_init() {
	register_post_type(
		'bc-alert',
		[
			'labels'                => [
				'name'                  => __( 'BC Alerts', 'rave-alert-notification' ),
				'singular_name'         => __( 'BC Alert', 'rave-alert-notification' ),
				'all_items'             => __( 'All BC Alerts', 'rave-alert-notification' ),
				'archives'              => __( 'BC Alert Archives', 'rave-alert-notification' ),
				'attributes'            => __( 'BC Alert Attributes', 'rave-alert-notification' ),
				'insert_into_item'      => __( 'Insert into BC Alert', 'rave-alert-notification' ),
				'uploaded_to_this_item' => __( 'Uploaded to this BC Alert', 'rave-alert-notification' ),
				'featured_image'        => _x( 'Featured Image', 'bc-alert', 'rave-alert-notification' ),
				'set_featured_image'    => _x( 'Set featured image', 'bc-alert', 'rave-alert-notification' ),
				'remove_featured_image' => _x( 'Remove featured image', 'bc-alert', 'rave-alert-notification' ),
				'use_featured_image'    => _x( 'Use as featured image', 'bc-alert', 'rave-alert-notification' ),
				'filter_items_list'     => __( 'Filter BC Alerts list', 'rave-alert-notification' ),
				'items_list_navigation' => __( 'BC Alerts list navigation', 'rave-alert-notification' ),
				'items_list'            => __( 'BC Alerts list', 'rave-alert-notification' ),
				'new_item'              => __( 'New BC Alert', 'rave-alert-notification' ),
				'add_new'               => __( 'Add New', 'rave-alert-notification' ),
				'add_new_item'          => __( 'Add New BC Alert', 'rave-alert-notification' ),
				'edit_item'             => __( 'Edit BC Alert', 'rave-alert-notification' ),
				'view_item'             => __( 'View BC Alert', 'rave-alert-notification' ),
				'view_items'            => __( 'View BC Alerts', 'rave-alert-notification' ),
				'search_items'          => __( 'Search BC Alerts', 'rave-alert-notification' ),
				'not_found'             => __( 'No BC Alerts found', 'rave-alert-notification' ),
				'not_found_in_trash'    => __( 'No BC Alerts found in trash', 'rave-alert-notification' ),
				'parent_item_colon'     => __( 'Parent BC Alert:', 'rave-alert-notification' ),
				'menu_name'             => __( 'BC Alerts', 'rave-alert-notification' ),
			],
			'public'                => true,
			'hierarchical'          => false,
			'show_ui'               => true,
			'show_in_nav_menus'     => true,
			'supports'              => [ 'title', 'editor' ],
			'has_archive'           => false,
			'rewrite'               => true,
			'query_var'             => true,
			'menu_position'         => null,
			'menu_icon'             => 'dashicons-warning',
			'show_in_rest'          => true,
			'rest_base'             => 'bc-alert',
			'rest_controller_class' => 'WP_REST_Posts_Controller',
		]
	);

}

add_action( 'init', 'bc_alert_init' );

/**
 * Sets the post updated messages for the `bc_alert` post type.
 *
 * @param  array $messages Post updated messages.
 * @return array Messages for the `bc_alert` post type.
 */
function bc_alert_updated_messages( $messages ) {
	global $post;

	$permalink = get_permalink( $post );

	$messages['bc-alert'] = [
		0  => '', // Unused. Messages start at index 1.
		/* translators: %s: post permalink */
		1  => sprintf( __( 'BC Alert updated. <a target="_blank" href="%s">View BC Alert</a>', 'rave-alert-notification' ), esc_url( $permalink ) ),
		2  => __( 'Custom field updated.', 'rave-alert-notification' ),
		3  => __( 'Custom field deleted.', 'rave-alert-notification' ),
		4  => __( 'BC Alert updated.', 'rave-alert-notification' ),
		/* translators: %s: date and time of the revision */
		5  => isset( $_GET['revision'] ) ? sprintf( __( 'BC Alert restored to revision from %s', 'rave-alert-notification' ), wp_post_revision_title( (int) $_GET['revision'], false ) ) : false, // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		/* translators: %s: post permalink */
		6  => sprintf( __( 'BC Alert published. <a href="%s">View BC Alert</a>', 'rave-alert-notification' ), esc_url( $permalink ) ),
		7  => __( 'BC Alert saved.', 'rave-alert-notification' ),
		/* translators: %s: post permalink */
		8  => sprintf( __( 'BC Alert submitted. <a target="_blank" href="%s">Preview BC Alert</a>', 'rave-alert-notification' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
		/* translators: 1: Publish box date format, see https://secure.php.net/date 2: Post permalink */
		9  => sprintf( __( 'BC Alert scheduled for: <strong>%1$s</strong>. <a target="_blank" href="%2$s">Preview BC Alert</a>', 'rave-alert-notification' ), date_i18n( __( 'M j, Y @ G:i', 'rave-alert-notification' ), strtotime( $post->post_date ) ), esc_url( $permalink ) ),
		/* translators: %s: post permalink */
		10 => sprintf( __( 'BC Alert draft updated. <a target="_blank" href="%s">Preview BC Alert</a>', 'rave-alert-notification' ), esc_url( add_query_arg( 'preview', 'true', $permalink ) ) ),
	];

	return $messages;
}

add_filter( 'post_updated_messages', 'bc_alert_updated_messages' );

/**
 * Sets the bulk post updated messages for the `bc_alert` post type.
 *
 * @param  array $bulk_messages Arrays of messages, each keyed by the corresponding post type. Messages are
 *                              keyed with 'updated', 'locked', 'deleted', 'trashed', and 'untrashed'.
 * @param  int[] $bulk_counts   Array of item counts for each message, used to build internationalized strings.
 * @return array Bulk messages for the `bc_alert` post type.
 */
function bc_alert_bulk_updated_messages( $bulk_messages, $bulk_counts ) {
	global $post;

	$bulk_messages['bc-alert'] = [
		/* translators: %s: Number of BC Alerts. */
		'updated'   => _n( '%s BC Alert updated.', '%s BC Alerts updated.', $bulk_counts['updated'], 'rave-alert-notification' ),
		'locked'    => ( 1 === $bulk_counts['locked'] ) ? __( '1 BC Alert not updated, somebody is editing it.', 'rave-alert-notification' ) :
						/* translators: %s: Number of BC Alerts. */
						_n( '%s BC Alert not updated, somebody is editing it.', '%s BC Alerts not updated, somebody is editing them.', $bulk_counts['locked'], 'rave-alert-notification' ),
		/* translators: %s: Number of BC Alerts. */
		'deleted'   => _n( '%s BC Alert permanently deleted.', '%s BC Alerts permanently deleted.', $bulk_counts['deleted'], 'rave-alert-notification' ),
		/* translators: %s: Number of BC Alerts. */
		'trashed'   => _n( '%s BC Alert moved to the Trash.', '%s BC Alerts moved to the Trash.', $bulk_counts['trashed'], 'rave-alert-notification' ),
		/* translators: %s: Number of BC Alerts. */
		'untrashed' => _n( '%s BC Alert restored from the Trash.', '%s BC Alerts restored from the Trash.', $bulk_counts['untrashed'], 'rave-alert-notification' ),
	];

	return $bulk_messages;
}

add_filter( 'bulk_post_updated_messages', 'bc_alert_bulk_updated_messages', 10, 2 );
