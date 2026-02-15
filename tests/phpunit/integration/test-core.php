<?php
/**
 * Class Core_Test
 *
 * @package ZenAdmin
 */

/**
 * Testable subclass to expose protected methods.
 */
class Testable_Core extends ZenAdmin\Core {
	public function expose_validate_roles( $roles ) {
		return $this->validate_roles( $roles );
	}
}

/**
 * Core tests.
 */
class Core_Test extends WP_UnitTestCase {

	/**
	 * Test validate_roles removes invalid roles.
	 */
	public function test_validate_roles_removes_invalid_roles() {
		$core = new Testable_Core();
		
		$input_roles = array( 'administrator', 'editor', 'fake_role_123', 'subscriber' );
		$expected    = array( 'administrator', 'editor', 'subscriber' );
		
		$result = $core->expose_validate_roles( $input_roles );
		
		$this->assertEquals( $expected, $result );
	}

	/**
	 * Test inject_styles only targets specific roles if hidden_for is set.
	 */
	public function test_inject_styles_conditions() {
		$core = new ZenAdmin\Core();
		
		// Setup: Create a block that is hidden only for 'editor'
		$selector = '.test-selector';
		$hash     = md5( $selector );
		$blacklist = array(
			$hash => array(
				'selector'   => $selector,
				'hidden_for' => array( 'editor' ),
			),
		);
		update_option( 'zenadmin_blacklist', $blacklist );

		// Scenario 1: Current user is Administrator (should NOT be hidden)
		$admin_id = self::factory()->user->create( array( 'role' => 'administrator' ) );
		wp_set_current_user( $admin_id );
		
		// Capture output
		ob_start();
		$core->inject_styles();
		$output_admin = ob_get_clean();
		
		$this->assertEmpty( $output_admin, 'Styles should not be injected for administrator.' );

		// Scenario 2: Current user is Editor (should BE hidden)
		$editor_id = self::factory()->user->create( array( 'role' => 'editor' ) );
		wp_set_current_user( $editor_id );
		
		// Capture output
		ob_start();
		$core->inject_styles();
		$output_editor = ob_get_clean();
		
		$this->assertStringContainsString( $selector, $output_editor, 'Styles should be injected for editor.' );
		$this->assertStringContainsString( 'display: none !important', $output_editor );

		// Scenario 3: Legacy block (no hidden_for) -> Should be hidden for everyone
		$selector_legacy = '.legacy-selector';
		$hash_legacy     = md5( $selector_legacy );
		$blacklist[ $hash_legacy ] = array(
			'selector' => $selector_legacy,
			// hidden_for is missing/empty
		);
		update_option( 'zenadmin_blacklist', $blacklist );

		// Test with Admin again (should be hidden now because legacy = global hide)
		wp_set_current_user( $admin_id );
		
		ob_start();
		$core->inject_styles();
		$output_legacy = ob_get_clean();

		$this->assertStringContainsString( $selector_legacy, $output_legacy, 'Legacy blocks should be hidden for everyone.' );
	}
}
