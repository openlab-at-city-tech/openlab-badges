<?php
/**
 * Template class.
 */

namespace OpenLab\Badges;

/**
 * Template class.
 *
 * Integrates into front-end.
 */
class Template {
	/**
	 * Initialize the Template class.
	 */
	public static function init() {
		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'register_scripts' ) );

		//add_action( 'bp_group_header_after_avatar', array( __CLASS__, 'avatar_links_single_group_header' ) );
		//add_action( 'bp_group_directory_after_avatar', array( __CLASS__, 'avatar_links_group_directory' ) );

		//add_action( 'openlab_theme_after_group_description_directory', [ __CLASS__, 'badge_links_directory' ] );

		add_filter( 'bp_before_has_groups_parse_args', array( __CLASS__, 'filter_group_args' ) );

		add_action( 'groups_group_after_save', array( __CLASS__, 'save_group_settings' ) );
	}

	/**
	 * Registers front-end assets.
	 */
	public static function register_scripts() {
		wp_register_style( 'openlab-badges', OLBADGES_PLUGIN_URL . '/assets/css/openlab-badges.css', [], OLBADGES_VERSION );
		wp_register_script( 'openlab-badges', OLBADGES_PLUGIN_URL . '/assets/js/openlab-badges.js', [ 'jquery' ], OLBADGES_VERSION, true );
	}

	/**
	 * Gets the avatar links for the header of a single group page.
	 */
	public static function avatar_links_single_group_header() {
		self::avatar_links( 'single' );
	}

	/**
	 * Gets the avatar links for the listing in a group directory.
	 */
	public static function avatar_links_group_directory() {
		self::avatar_links( 'directory' );
	}

	/**
	 * Gets a set of avatar links.
	 *
	 * @param string $context Context. 'single' or 'directory'.
	 */
	public static function avatar_links( $context = 'single' ) {
		wp_enqueue_style( 'openlab-badges' );
		wp_enqueue_script( 'openlab-badges' );

		$group_id = bp_get_group_id();

		$badge_group  = new Group( $group_id );
		$group_badges = $badge_group->get_badges();

		$html = '';
		if ( $group_badges ) {
			$html .= '<ul class="badge-links">';
			foreach ( $group_badges as $group_badge ) {
				$html .= '<li>' . $group_badge->get_avatar_badge_html( $group_id, $context ) . '</li>';
			}
			$html .= '</ul>';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	public static function badge_links( $context ) {
		wp_enqueue_style( 'openlab-badges' );
		wp_enqueue_script( 'openlab-badges' );

		$group_id = bp_get_group_id();

		$badge_group  = new Group( $group_id );
		$group_badges = $badge_group->get_badges();

		$html = '';
		if ( $group_badges ) {
			$html .= '<ul class="badge-links">';
			foreach ( $group_badges as $group_badge ) {
				$html .= '<li>' . $group_badge->get_avatar_flag_html( $group_id, $context ) . '</li>';
			}
			$html .= '</ul>';
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $html;
	}

	/**
	 * Filters bp_get_groups() args to account for badges.
	 *
	 * @param array $args Group query arguments.
	 */
	public static function filter_group_args( $args ) {
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( ! isset( $_GET['badges'] ) || 'all' === $_GET['badges'] || ! is_array( $_GET['badges'] ) ) {
			return $args;
		}

		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		$badge_ids = array_map( 'intval', $_GET['badges'] );

		// Tax query not currently supported for groups. See https://buddypress.trac.wordpress.org/ticket/4017.
		// phpcs:disable
		/*
		$tq = isset( $args['tax_query'] ) ? $args['tax_query'] : array();

		$tq['openlab_badge'] = array(
			'taxonomy' => 'openlab_badge',
			'term'     => $badge_id,
			'field'    => 'term_id',
		);

		$args['tax_query'] = $tq;
		*/
		// phpcs:enable

		$objects_in_term = bp_get_objects_in_term( $badge_ids, 'openlab_badge' );
		if ( ! $objects_in_term ) {
			$objects_in_term = array( 0 );
		}

		if ( empty( $args['include'] ) ) {
			$args['include'] = $objects_in_term;
		} else {
			$args['include'] = array_intersect( (array) $args['include'], $objects_in_term );
		}

		return $args;
	}

	/**
	 * Gets the markup for the group admin area.
	 */
	public static function group_admin_markup() {
		$group_id        = bp_get_current_group_id();
		$badge_group     = new Group( $group_id );
		$group_badges    = $badge_group->get_badges();
		$group_badge_ids = array_map(
			function( $group_badge ) {
				return $group_badge->get_id();
			},
			$group_badges
		);

		$all_badges = Badge::get();

		wp_enqueue_style( 'openlab-badges' );
		wp_enqueue_script( 'openlab-badges' );

		?>
		<?php if ( $all_badges ) : ?>
			<ul class="badge-selector">
			<?php foreach ( $all_badges as $badge ) : ?>
				<li>
					<input type="checkbox" value="<?php echo esc_attr( $badge->get_id() ); ?>" name="badge-selector[]" id="badge-selector-<?php echo esc_attr( $badge->get_slug() ); ?>" <?php checked( in_array( $badge->get_id(), $group_badge_ids, true ) ); ?> />

					<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					<?php echo $badge->get_avatar_badge_html( $group_id ); ?>

					<label for="badge-selector-<?php echo esc_attr( $badge->get_slug() ); ?>">
						<?php echo esc_html( $badge->get_name() ); ?>
					</label>
				</li>
			<?php endforeach; ?>
			</ul>

			<?php wp_nonce_field( 'openlab_badges_group_settings', 'openlab-badges-group-settings-nonce', false ); ?>

		<?php else : ?>
			<p><?php esc_html_e( 'You have not created any badges yet.', 'openlab-badges' ); ?></p>
			<?php
		endif;
	}

	/**
	 * Catch group settings save.
	 *
	 * @param \BP_Groups_Group $group Group object.
	 */
	public static function save_group_settings( \BP_Groups_Group $group ) {
		static $run;

		// Prevent dupes.
		if ( $run ) {
			return;
		}

		if ( ! isset( $_POST['openlab-badges-group-settings-nonce'] ) ) {
			return;
		}

		// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$nonce = wp_unslash( $_POST['openlab-badges-group-settings-nonce'] );

		if ( ! wp_verify_nonce( $nonce, 'openlab_badges_group_settings' ) ) {
			return;
		}

		if ( ! current_user_can( 'grant_badges' ) ) {
			return;
		}

		if ( empty( $_POST['badge-selector'] ) ) {
			$badge_ids = array();
		} else {
			$badge_ids = array_map( 'intval', $_POST['badge-selector'] );
		}

		$badge_group     = new Group( $group->id );
		$group_badges    = $badge_group->get_badges();
		$group_badge_ids = array_map(
			function( $group_badge ) {
				return $group_badge->get_id();
			},
			$group_badges
		);

		$to_grant  = array_diff( $badge_ids, $group_badge_ids );
		$to_revoke = array_diff( $group_badge_ids, $badge_ids );

		foreach ( $to_grant as $to_grant_id ) {
			$badge = new Badge( $to_grant_id );
			$badge_group->grant( $badge );
		}

		foreach ( $to_revoke as $to_revoke_id ) {
			$badge = new Badge( $to_revoke_id );
			$badge_group->revoke( $badge );
		}

		$run = true;
	}
}
