<?php
/**
 * Class Portability_Test
 *
 * @package ZenAdmin
 */

/**
 * Portability tests.
 */
class Portability_Test extends WP_UnitTestCase {

	/**
	 * @var ZenAdmin_Portability
	 */
	protected $portability;

	/**
	 * Setup.
	 */
	public function setUp(): void {
		parent::setUp();
		// Ensure class is loaded
		if ( ! class_exists( 'ZenAdmin_Portability' ) ) {
			require_once dirname( dirname( dirname( __DIR__ ) ) ) . '/includes/class-portability.php';
		}
		$this->portability = new ZenAdmin_Portability();
	}

	/**
	 * Test validate_import with valid data.
	 */
	public function test_validate_import_success() {
		$raw_rules = array(
			'somehash123' => array(
				'selector'   => '.my-selector',
				'label'      => 'My Label',
				'hidden_for' => array( 'editor' ),
				'created_at' => '2025-01-01 10:00:00',
			),
		);

		$valid = $this->portability->validate_import( $raw_rules );

		// Expect strict hash recalculation: md5('.my-selector')
		$expected_hash = md5( '.my-selector' );

		$this->assertArrayHasKey( $expected_hash, $valid );
		$this->assertEquals( '.my-selector', $valid[ $expected_hash ]['selector'] );
		$this->assertEquals( 'My Label', $valid[ $expected_hash ]['label'] );
		$this->assertEquals( array( 'editor' ), $valid[ $expected_hash ]['hidden_for'] );
	}

	/**
	 * Test validate_import skips invalid entries.
	 */
	public function test_validate_import_skips_invalid() {
		$raw_rules = array(
			'bad1' => array( 'label' => 'No Selector' ),
			'bad2' => array( 'selector' => '.no-label' ),
			'good' => array(
				'selector' => '#good',
				'label'    => 'Good',
			),
		);

		$valid = $this->portability->validate_import( $raw_rules );

		$this->assertCount( 1, $valid );
		$this->assertArrayHasKey( md5( '#good' ), $valid );
	}

	/**
	 * Test sanitization.
	 */
	public function test_validate_import_sanitization() {
		$raw_rules = array(
			'hack' => array(
				'selector'   => '<script>alert(1)</script>.class',
				'label'      => '<b>Bold</b>',
				'hidden_for' => array( '<i>author</i>' ),
			),
		);

		$valid = $this->portability->validate_import( $raw_rules );
		
		// Selector is sanitized by sanitize_text_field in the class, 
		// but typically we might want stricter CSS sanitization. 
		// For now, testing that tags are stripped.
		$item = reset( $valid );

		$this->assertStringNotContainsString( '<script>', $item['selector'] );
		$this->assertStringNotContainsString( '<b>', $item['label'] );
		$this->assertStringNotContainsString( '<i>', $item['hidden_for'][0] );
	}
}
