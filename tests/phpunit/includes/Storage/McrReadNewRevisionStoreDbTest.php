<?php
namespace MediaWiki\Tests\Storage;

use InvalidArgumentException;
use MediaWiki\MediaWikiServices;
use MediaWiki\Storage\RevisionRecord;
use MediaWiki\Storage\SlotRecord;
use TextContent;
use WikitextContent;

/**
 * Tests RevisionStore against the intermediate MCR DB schema for use during schema migration.
 *
 * @covers \MediaWiki\Storage\RevisionStore
 *
 * @group RevisionStore
 * @group Storage
 * @group Database
 * @group medium
 */
class McrReadNewRevisionStoreDbTest extends RevisionStoreDbTestBase {

	use McrReadNewSchemaOverride;

	protected function assertRevisionExistsInDatabase( RevisionRecord $rev ) {
		$this->assertSelect(
			'slots',
			[ 'count(*)' ],
			[ 'slot_revision_id' => $rev->getId() ],
			[ [ '1' ] ]
		);

		$this->assertSelect(
			'content',
			[ 'count(*)' ],
			[ 'content_address' => $rev->getSlot( 'main' )->getAddress() ],
			[ [ '1' ] ]
		);

		parent::assertRevisionExistsInDatabase( $rev );
	}

	/**
	 * @param SlotRecord $a
	 * @param SlotRecord $b
	 */
	protected function assertSameSlotContent( SlotRecord $a, SlotRecord $b ) {
		parent::assertSameSlotContent( $a, $b );

		// Assert that the same content ID has been used
		$this->assertSame( $a->getContentId(), $b->getContentId() );
	}

	public function testGetQueryInfo_NoSlotDataJoin() {
		$store = MediaWikiServices::getInstance()->getRevisionStore();
		$queryInfo = $store->getQueryInfo();

		// with the new schema enabled, query info should not join the main slot info
		$this->assertFalse( array_key_exists( 'a_slot_data', $queryInfo['tables'] ) );
		$this->assertFalse( array_key_exists( 'a_slot_data', $queryInfo['joins'] ) );
	}

	public function provideGetArchiveQueryInfo() {
		yield [
			[
				'tables' => [
					'archive',
				],
				'fields' => array_merge(
					$this->getDefaultArchiveFields( false ),
					[
						'ar_comment_text' => 'ar_comment',
						'ar_comment_data' => 'NULL',
						'ar_comment_cid' => 'NULL',
						'ar_user_text' => 'ar_user_text',
						'ar_user' => 'ar_user',
						'ar_actor' => 'NULL',
					]
				),
				'joins' => [
				],
			]
		];
	}

	public function provideGetQueryInfo() {
		// TODO: more option variations
		yield [
			[ 'page', 'user' ],
			[
				'tables' => [
					'revision',
					'page',
					'user',
				],
				'fields' => array_merge(
					$this->getDefaultQueryFields( false ),
					$this->getCommentQueryFields(),
					$this->getActorQueryFields(),
					[
						'page_namespace',
						'page_title',
						'page_id',
						'page_latest',
						'page_is_redirect',
						'page_len',
						'user_name',
					]
				),
				'joins' => [
					'page' => [ 'INNER JOIN', [ 'page_id = rev_page' ] ],
					'user' => [ 'LEFT JOIN', [ 'rev_user != 0', 'user_id = rev_user' ] ],
				],
			]
		];
	}

	public function provideGetSlotsQueryInfo() {
		yield [
			[],
			[
				'tables' => [
					'slots',
					'slot_roles',
				],
				'fields' => array_merge(
					[
						'slot_revision_id',
						'slot_content_id',
						'slot_origin',
						'role_name',
					]
				),
				'joins' => [
					'slot_roles' => [ 'INNER JOIN', [ 'slot_role_id = role_id' ] ],
				],
			]
		];
		yield [
			[ 'content' ],
			[
				'tables' => [
					'slots',
					'slot_roles',
					'content',
					'content_models',
				],
				'fields' => array_merge(
					[
						'slot_revision_id',
						'slot_content_id',
						'slot_origin',
						'role_name',
						'content_size',
						'content_sha1',
						'content_address',
						'model_name',
					]
				),
				'joins' => [
					'slot_roles' => [ 'INNER JOIN', [ 'slot_role_id = role_id' ] ],
					'content' => [ 'INNER JOIN', [ 'slot_content_id = content_id' ] ],
					'content_models' => [ 'INNER JOIN', [ 'content_model = model_id' ] ],
				],
			]
		];
	}

	public function provideInsertRevisionOn_failures() {
		foreach ( parent::provideInsertRevisionOn_failures() as $case ) {
			yield $case;
		}

		yield 'slot that is not main slot' => [
			[
				'content' => [
					'main' => new WikitextContent( 'Chicken' ),
					'lalala' => new WikitextContent( 'Duck' ),
				],
				'comment' => $this->getRandomCommentStoreComment(),
				'timestamp' => '20171117010101',
				'user' => true,
			],
			new InvalidArgumentException( 'Only the main slot is supported' )
		];
	}

	public function provideNewMutableRevisionFromArray() {
		foreach ( parent::provideNewMutableRevisionFromArray() as $case ) {
			yield $case;
		}

		yield 'Basic array, multiple roles' => [
			[
				'id' => 2,
				'page' => 1,
				'timestamp' => '20171017114835',
				'user_text' => '111.0.1.2',
				'user' => 0,
				'minor_edit' => false,
				'deleted' => 0,
				'len' => 29,
				'parent_id' => 1,
				'sha1' => '89qs83keq9c9ccw9olvvm4oc9oq50ii',
				'comment' => 'Goat Comment!',
				'content' => [
					'main' => new WikitextContent( 'Söme Cöntent' ),
					'aux' => new TextContent( 'Öther Cöntent' ),
				]
			]
		];
	}

}
