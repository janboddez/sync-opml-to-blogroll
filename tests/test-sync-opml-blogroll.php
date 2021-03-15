<?php

class Test_Sync_OPML_Blogroll extends \WP_Mock\Tools\TestCase {
	public function setUp() : void {
		\WP_Mock::setUp();
	}

	public function tearDown() : void {
		\WP_Mock::tearDown();
	}

	public function test_sync_opml_blogroll_register() {
		$options = array(
			'url'                => '',
			'username'           => '',
			'password'           => '',
			'denylist'           => '',
			'categories_enabled' => false,
			'default_category'   => null,
		);

		\WP_Mock::userFunction( 'get_option', array(
			'times' => 1,
			'args'  => array(
				'sync_opml_blogroll_settings',
				$options,
			),
			'return' => $options,
		) );

		\WP_Mock::userFunction( 'register_deactivation_hook', array(
			'times' => 1,
			'args'  => array(
				dirname( dirname( __FILE__ ) ) . '/sync-opml-blogroll.php',
				array(
					\Sync_OPML_Blogroll\Sync_OPML_Blogroll::get_instance(),
					'deactivate',
				),
			),
		) );

		$plugin = \Sync_OPML_Blogroll\Sync_OPML_Blogroll::get_instance();

		\WP_Mock::expectFilterAdded( 'pre_option_link_manager_enabled', '__return_true' );

		\WP_Mock::expectActionAdded( 'init', array( $plugin, 'schedule_event' ) );
		\WP_Mock::expectActionAdded( 'sync_opml_blogroll', array( $plugin, 'sync' ) );
		\WP_Mock::expectActionAdded( 'add_option_sync_opml_blogroll_settings', array( $plugin, 'trigger_sync' ) );
		\WP_Mock::expectActionAdded( 'update_option_sync_opml_blogroll_settings', array( $plugin, 'trigger_sync' ) );
		\WP_Mock::expectActionAdded( 'admin_head', array( $plugin, 'link_manager_css' ) );

		$plugin->register();

		$this->assertHooksAdded();
	}
}
