<?php
/**
 * This program is free software; you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation; either version 2 of the License, or
 * (at your option) any later version.
 *
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License along
 * with this program; if not, write to the Free Software Foundation, Inc.,
 * 51 Franklin Street, Fifth Floor, Boston, MA 02110-1301, USA.
 * http://www.gnu.org/copyleft/gpl.html
 *
 * @file
 * @ingroup Change tagging
 */

use MediaWiki\Page\PageIdentity;
use MediaWiki\Permissions\Authority;

/**
 * Generic list for change tagging.
 *
 * @property ChangeTagsLogItem $current
 * @method ChangeTagsLogItem next()
 * @method ChangeTagsLogItem reset()
 * @method ChangeTagsLogItem current()
 */
abstract class ChangeTagsList extends RevisionListBase {
	public function __construct( IContextSource $context, PageIdentity $page, array $ids ) {
		parent::__construct( $context, $page );
		$this->ids = $ids;
	}

	/**
	 * Creates a ChangeTags*List of the requested type.
	 *
	 * @param string $typeName 'revision' or 'logentry'
	 * @param IContextSource $context
	 * @param PageIdentity $page
	 * @param array $ids
	 * @return ChangeTagsList An instance of the requested subclass
	 * @throws Exception If you give an unknown $typeName
	 */
	public static function factory( $typeName, IContextSource $context,
		PageIdentity $page, array $ids
	) {
		switch ( $typeName ) {
			case 'revision':
				$className = ChangeTagsRevisionList::class;
				break;
			case 'logentry':
				$className = ChangeTagsLogList::class;
				break;
			default:
				throw new Exception( "Class $typeName requested, but does not exist" );
		}

		return new $className( $context, $page, $ids );
	}

	/**
	 * Reload the list data from the primary DB.
	 */
	public function reloadFromPrimary() {
		$dbw = wfGetDB( DB_PRIMARY );
		$this->res = $this->doQuery( $dbw );
	}

	/**
	 * Add/remove change tags from all the items in the list.
	 *
	 * @param string[] $tagsToAdd
	 * @param string[] $tagsToRemove
	 * @param string|null $params
	 * @param string $reason
	 * @param Authority $performer
	 * @return Status
	 */
	abstract public function updateChangeTagsOnAll( $tagsToAdd, $tagsToRemove, $params,
													$reason, Authority $performer );
}
