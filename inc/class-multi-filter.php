<?php
/**
 * Allows filtering an iterator by file type.
 */

namespace WP_L10N_Gen;

use Iterator;
use FilterIterator;

/**
 * Class MultiFilter.
 *
 * Matches item against a single or multiple regular expressions.
 *
 * @package WP_L10N_Gen
 */
class MultiFilter extends FilterIterator {

	protected $filter;

	/**
	 * MultiFilter constructor.
	 *
	 * @param \Iterator    $iterator
	 * @param array|string $filter
	 */
	public function __construct( Iterator $iterator, $filter ) {
		parent::__construct( $iterator );
		$this->filter = $filter;
	}

	/**
	 * Whether to accept the item.
	 *
	 * @return bool
	 */
	public function accept() {
		if ( is_array( $this->filter ) ) {
			$matched = 0;
			foreach ( $this->filter as $filter ) {
				if ( preg_match( $filter, $this->getInnerIterator()->current() ) ) {
					$matched++;
				}
			}

			return $matched === count( $this->filter );
		}

		return preg_match( $this->filter, $this->getInnerIterator()->current() );
	}

}
