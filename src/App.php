<?php
/**
 * Main App class.
 */

namespace OpenLab\Badges;

/**
 * App class.
 */
class App {
	/**
	 * Initialize the app.
	 */
	public static function init() {
		add_action( 'init', array( __CLASS__, 'register_taxonomy' ) );
		add_action( 'map_meta_cap', array( __CLASS__, 'map_meta_cap' ), 10, 4 );

		Template::init();
		Admin::init();
	}

	/**
	 * Register Badge taxonomy.
	 */
	public static function register_taxonomy() {
		$labels = array(
			'name'          => __( 'Badges', 'openlab-badges' ),
			'all_items'     => __( 'All Badges', 'openlab-badges' ),
			'singular_name' => __( 'Badge', 'openlab-badges' ),
			'add_new_item'  => __( 'Add New Badge', 'openlab-badges' ),
			'edit_item'     => __( 'Edit Badge', 'openlab-badges' ),
			'new_item'      => __( 'New Badge', 'openlab-badges' ),
			'view_item'     => __( 'View Badge', 'openlab-badges' ),
			'search_items'  => __( 'Search Badges', 'openlab-badges' ),
		);

		register_taxonomy(
			'openlab_badge',
			'group',
			array(
				'label'     => __( 'Badges', 'openlab-badges' ),
				'public'    => false,
				'show_ui'   => true,
				'labels'    => $labels,
				'menu_icon' => 'dashicons-shield',
			)
		);
	}

	/**
	 * Maps custom capabilities for 'badges'.
	 *
	 * Note that the function is intended for Multisite use only.
	 *
	 * @param array  $caps    Primitive caps.
	 * @param string $cap     Meta cap being mapped.
	 * @param int    $user_id User ID.
	 * @param array  $args    Cap arguments.
	 *
	 * @return array $caps
	 */
	public static function map_meta_cap( $caps, $cap, $user_id, $args ) {
		switch ( $cap ) {
			case 'manage_badges':
			case 'grant_badges':
				return array( 'manage_network_options' );
		}

		return $caps;
	}

	/**
	 * Fetches all group types on an installation.
	 *
	 * A wrapper for bp_groups_get_group_types() that allows the list to be filtered
	 * in cases where the installation is not using BP group types.
	 *
	 * @return array
	 */
	public static function get_group_types() {
		$group_types = array_map(
			function( $group_type ) {
				return [
					'slug' => $group_type->name,
					'name' => $group_type->labels['name'],
				];
			},
			bp_groups_get_group_types( [], 'objects' )
		);

		/**
		 * Filters the group types to be used by OpenLab Badges.
		 *
		 * @since 1.0
		 *
		 * @param array {
		 *   Info about badges.
		 *
		 *   @type string $slug Slug.
		 *   @type string $name Name.
		 * }
		 */
		return apply_filters( 'openlab_badges_get_group_types', $group_types );
	}
}
