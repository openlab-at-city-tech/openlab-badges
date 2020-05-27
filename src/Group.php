<?php
/**
 * Group class.
 */

namespace OpenLab\Badges;

/**
 * Group class.
 *
 * Overlays BP group objects to provide access to group-badge associations.
 */
class Group {
	/**
	 * The ID of the group.
	 *
	 * @var int $group_id
	 */
	private $group_id;

	/**
	 * Constructor.
	 *
	 * @param int $group_id Optional. Group ID. Will populate if provided.
	 */
	public function __construct( $group_id = null ) {
		if ( $group_id ) {
			$this->set_group_id( $group_id );
		}
	}

	/**
	 * Sets the group ID.
	 *
	 * @param int $group_id The group ID.
	 */
	public function set_group_id( $group_id ) {
		$this->group_id = (int) $group_id;
	}

	/**
	 * Gets the group ID.
	 *
	 * @return int
	 */
	public function get_group_id() {
		return (int) $this->group_id;
	}

	/**
	 * Gets badges belonging to the group.
	 *
	 * @return array Array of Badge objects.
	 */
	public function get_badges() {
		$terms  = wp_get_object_terms( $this->get_group_id(), 'openlab_badge' );
		$badges = array_map(
			function( $term ) {
				return new Badge( $term->term_id );
			},
			$terms
		);
		return $badges;
	}

	/**
	 * Grants a badge to a group.
	 *
	 * @param Grantable $badge Badge object.
	 * @return array|\WP_Error
	 */
	public function grant( Grantable $badge ) {
		$set = wp_set_object_terms( $this->get_group_id(), $badge->get_id(), 'openlab_badge', true );
		return $set;
	}

	/**
	 * Grants a badge to a group.
	 *
	 * @param Grantable $badge Badge object.
	 * @return array|\WP_Error
	 */
	public function revoke( Grantable $badge ) {
		$removed = wp_remove_object_terms( $this->get_group_id(), $badge->get_id(), 'openlab_badge' );
		return $removed;
	}
}
