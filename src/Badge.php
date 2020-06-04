<?php
/**
 * Badge class.
 */

namespace OpenLab\Badges;

/**
 * Badge class.
 */
class Badge implements Grantable {
	/**
	 * Badge data.
	 *
	 * @var array
	 */
	private $data = array(
		'id'          => null,
		'name'        => null,
		'short_name'  => null,
		'slug'        => null,
		'link'        => null,
		'position'    => null,
		'group_types' => [],
	);

	/**
	 * Constructor.
	 *
	 * @param int $badge_id Optional. ID of the badge (term_id).
	 */
	public function __construct( $badge_id = null ) {
		if ( $badge_id ) {
			$this->populate( $badge_id );
		}
	}

	/**
	 * Populates a badge from the database.
	 *
	 * @param int $badge_id ID of the badge (term_id).
	 */
	private function populate( $badge_id ) {
		$term = get_term( $badge_id, 'openlab_badge' );
		if ( ! $term ) {
			return;
		}

		$this->set_id( $term->term_id );
		$this->set_name( $term->name );
		$this->set_slug( $term->slug );

		$short_name = get_term_meta( $term->term_id, 'short_name', true );
		if ( ! $short_name ) {
			$short_name = $term->name;
		}
		$this->set_short_name( $short_name );

		$link = get_term_meta( $term->term_id, 'link', true );
		if ( $link ) {
			$this->set_link( $link );
		}

		$position = get_term_meta( $term->term_id, 'position', true );
		if ( $position ) {
			$this->set_position( $position );
		}

		$group_types = get_term_meta( $term->term_id, 'group_type', false );
		if ( $group_types ) {
			$this->set_group_types( $group_types );
		}
	}

	/**
	 * Saves a badge to the database.
	 *
	 * @return bool
	 */
	public function save() {
		$term_id = $this->get_id();

		if ( ! $term_id ) {
			$term = wp_insert_term(
				$this->get_name(),
				'openlab_badge',
				array(
					'slug' => $this->get_slug(),
				)
			);
			if ( is_wp_error( $term ) ) {
				return $term;
			}

			$term_id = (int) $term['term_id'];
			$this->set_id( $term_id );
		} else {
			wp_update_term(
				$term_id,
				'openlab_badge',
				array(
					'name' => $this->get_name(),
					'slug' => $this->get_slug(),
				)
			);
		}

		update_term_meta( $term_id, 'short_name', $this->get_short_name() );
		update_term_meta( $term_id, 'link', $this->get_link() );
		update_term_meta( $term_id, 'position', $this->get_position() );

		// Delete existing first.
		delete_term_meta( $term_id, 'group_type' );
		foreach ( $this->get_group_types() as $group_type ) {
			add_term_meta( $term_id, 'group_type', $group_type );
		}

		return true;
	}

	/**
	 * Deletes a badge object from the database.
	 *
	 * @return bool|int|\WP_Error True on success, false if term does not exist. Zero on attempted
	 *                           deletion of default Category. WP_Error if the taxonomy does not exist.
	 */
	public function delete() {
		return wp_delete_term( $this->get_id(), 'openlab_badge' );
	}

	/**
	 * Sets the ID of a badge.
	 *
	 * @param int $id ID.
	 */
	public function set_id( $id ) {
		$this->data['id'] = (int) $id;
	}

	/**
	 * Sets the name of a badge.
	 *
	 * @param string $name Name.
	 */
	public function set_name( $name ) {
		$this->data['name'] = $name;
	}

	/**
	 * Sets the short name of a badge.
	 *
	 * @param string $short_name Short name.
	 */
	public function set_short_name( $short_name ) {
		$this->data['short_name'] = $short_name;
	}

	/**
	 * Sets the slug of a badge.
	 *
	 * @param string $slug Slug.
	 */
	public function set_slug( $slug ) {
		$this->data['slug'] = $slug;
	}

	/**
	 * Sets the link of a badge.
	 *
	 * @param string $link URL.
	 */
	public function set_link( $link ) {
		$this->data['link'] = $link;
	}

	/**
	 * Sets the position of a badge.
	 *
	 * @param int|string $position Position.
	 */
	public function set_position( $position ) {
		$this->data['position'] = $position;
	}

	/**
	 * Sets the group types for a badge.
	 *
	 * @param array $group_types Group type strings.
	 */
	public function set_group_types( $group_types ) {
		$this->data['group_types'] = $group_types;
	}

	/**
	 * Gets the ID of a badge.
	 *
	 * @return int
	 */
	public function get_id() {
		return (int) $this->data['id'];
	}

	/**
	 * Gets the name for a badge.
	 *
	 * @return string
	 */
	public function get_name() {
		return $this->data['name'];
	}

	/**
	 * Gets the "short name" for a badge.
	 *
	 * Short names are used in breadcrumbs and other places where an abbreviation is needed.
	 *
	 * @return string
	 */
	public function get_short_name() {
		return $this->data['short_name'];
	}

	/**
	 * Gets the slug for a badge.
	 *
	 * @return string
	 */
	public function get_slug() {
		return $this->data['slug'];
	}

	/**
	 * Gets the link for a badge.
	 *
	 * @return string
	 */
	public function get_link() {
		return $this->data['link'];
	}

	/**
	 * Gets the position for a badge.
	 *
	 * @return int
	 */
	public function get_position() {
		return (int) $this->data['position'];
	}

	/**
	 * Gets the group types for a badge.
	 *
	 * @return int
	 */
	public function get_group_types() {
		return $this->data['group_types'];
	}

	/**
	 * Builds the HTML for a badge flag.
	 *
	 * @param int    $group_id Group ID.
	 * @param string $context  'single' or 'directory'.
	 */
	public function get_avatar_flag_html( $group_id, $context = 'single' ) {
		$group = groups_get_group( $group_id );

		$tooltip_id = 'badge-tooltip-' . $group->slug . '-' . $this->get_slug();

		$badge_link_start = '';
		$badge_link_end   = '';

		if ( 'single' === $context ) {
			$badge_link_start = sprintf(
				'<a class="group-badge-shortname" href="%s">',
				esc_attr( $this->get_link() )
			);

			$badge_link_end = '</a>';
		} else {
			$badge_link_start = '<span class="group-badge-shortname">';
			$badge_link_end   = '</span>';
		}

		$html  = '<div class="group-badge">';
		$html .= $badge_link_start;
		$html .= esc_html( $this->get_short_name() );
		$html .= $badge_link_end;

		$html .= '<div id="' . esc_attr( $tooltip_id ) . '" class="badge-tooltip" role="tooltip">';
		$html .= esc_html( $this->get_name() );
		$html .= '</div>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Outputs the HTML for editing a badge.
	 */
	public function edit_html() {
		$slug        = $this->get_slug();
		$name        = $this->get_name();
		$short_name  = $this->get_short_name();
		$group_types = $this->get_group_types();

		$all_group_types = \OpenLab\Badges\App::get_group_types();

		$id = $this->get_id();
		if ( ! $id ) {
			$id   = '_new';
			$slug = '_new';

			// All are checked by default for new badge.
			$group_types = wp_list_pluck( $all_group_types, 'slug' );
		}

		?>
		<label>
			<span class="badge-field-label"><?php esc_html_e( 'Name', 'openlab-badges' ); ?></span>
			<input type="text" name="badges[<?php echo esc_attr( $id ); ?>][name]" id="badge-<?php echo esc_attr( $slug ); ?>-name" value="<?php echo esc_attr( $name ); ?>" />
		</label>

		<label>
			<span class="badge-field-label"><?php esc_html_e( 'Short Name', 'openlab-badges' ); ?></span>
			<input type="text" name="badges[<?php echo esc_attr( $id ); ?>][short_name]" id="badge-<?php echo esc_attr( $slug ); ?>-short-name" value="<?php echo esc_attr( $short_name ); ?>" />
		</label>

		<label>
			<span class="badge-field-label"><?php esc_html_e( 'Link', 'openlab-badges' ); ?></span>
			<input type="text" name="badges[<?php echo esc_attr( $id ); ?>][link]" id="badge-<?php echo esc_attr( $slug ); ?>-name" value="<?php echo esc_attr( $this->get_link() ); ?>" />
		</label>

		<label>
			<span class="badge-field-label"><?php esc_html_e( 'Position', 'openlab-badges' ); ?></span>
			<input type="text" name="badges[<?php echo esc_attr( $id ); ?>][position]" id="badge-<?php echo esc_attr( $slug ); ?>-name" value="<?php echo esc_attr( $this->get_position() ); ?>" />
		</label>

		<fieldset>
			<legend class="badge-field-label">Group Types</legend>

			<?php foreach ( $all_group_types as $group_type ) : ?>
				<label>
					<input type="checkbox" name="badges[<?php echo esc_attr( $id ); ?>][group-types][]" value="<?php echo esc_attr( $group_type['slug'] ); ?>" <?php checked( in_array( $group_type['slug'], $group_types ) ); ?> /> <?php echo esc_html( $group_type['name'] ); ?>
				</label>
			<?php endforeach; ?>

			<p class="description"><?php esc_html_e( 'Select the group types associated with this badge. This will determine which badges can be selected when editing a group, and which badge filters appear in group directories.', 'openlab-badges' ); ?></p>

		</fieldset>
		<?php
	}

	/**
	 * Gets a list of badges.
	 *
	 * @param array $args {
	 *     Function arguments.
	 *
	 *     @type bool   $hide_empty Whether to hide empty badges (badges not associated with a group).
	 *     @type string $orderby    Badge orderby.
	 *     @type string $order      Badge order.
	 *   }
	 */
	public static function get( $args = array() ) {
		$r = array_merge(
			array(
				'hide_empty'  => false,
				'orderby'     => 'position',
				'order'       => 'ASC',
				'group_types' => null,
			),
			$args
		);

		if ( 'position' === $r['orderby'] ) {
			$orderby = 'position';
			$order   = null;
		} else {
			$orderby = $r['orderby'];
			$order   = $r['order'];
		}

		$get_terms_args = [
			'taxonomy'   => 'openlab_badge',
			'hide_empty' => $r['hide_empty'],
		];

		if ( null !== $r['group_types'] ) {
			$get_terms_args['meta_query'] = [
				'group_type' => [
					'key'   => 'group_type',
					'value' => $r['group_types'],
				],
			];
		}

		if ( $order ) {
			$get_terms_args['order'] = $order;
		}

		$terms = get_terms( $get_terms_args );

		// Override crummy filters.
		/*
		usort(
			$terms,
			function( $a, $b ) {
				if ( $a->name === $b->name ) {
					return $a->term_id > $b->term_id;
				}

				return strnatcasecmp( $a->name, $b->name );
			}
		);
		*/

		$badges = array_map(
			function( $term ) {
				return new self( $term->term_id );
			},
			$terms
		);

		usort(
			$badges,
			function( $a, $b ) {
				return $a->get_position() > $b->get_position();
			}
		);

		return $badges;
	}
}
