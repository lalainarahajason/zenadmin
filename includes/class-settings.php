<?php
/**
 * Settings Page for ZenAdmin.
 *
 * @package ZenAdmin
 */

namespace ZenAdmin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Settings
 */
class Settings {

	/**
	 * Constructor.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_menu_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
		add_action( 'admin_post_zenadmin_apply_template', array( $this, 'handle_template_application' ) );
		add_action( 'admin_post_zenadmin_reset_all', array( $this, 'handle_reset_all' ) );
	}

	/**
	 * Add menu page.
	 */
	public function add_menu_page() {
		add_options_page(
			__( 'ZenAdmin Settings', 'zenadmin' ),
			__( 'ZenAdmin', 'zenadmin' ),
			'manage_options',
			'zenadmin',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Register settings.
	 */
	public function register_settings() {
		register_setting( 'zenadmin_options', 'zenadmin_blacklist' );
	}

	/**
	 * Handle template application via admin-post.
	 */
	public function handle_template_application() {
		check_admin_referer( 'zenadmin_apply_template' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'zenadmin' ) );
		}

		$template_id = isset( $_POST['template_id'] ) ? sanitize_text_field( wp_unslash( $_POST['template_id'] ) ) : '';

		if ( class_exists( 'ZenAdmin\\Templates' ) ) {
			\ZenAdmin\Templates::apply_template( $template_id );
			add_settings_error( 'zenadmin_messages', 'zenadmin_template_applied', __( 'Template applied successfully.', 'zenadmin' ), 'success' );
		}

		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'options-general.php?page=zenadmin&tab=templates' ) ) );
		exit;
	}

	/**
	 * Handle reset all data.
	 */
	public function handle_reset_all() {
		check_admin_referer( 'zenadmin_reset_all' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( __( 'Unauthorized', 'zenadmin' ) );
		}

		delete_option( 'zenadmin_blacklist' );
		add_settings_error( 'zenadmin_messages', 'zenadmin_reset', __( 'All settings and blocks have been reset.', 'zenadmin' ), 'success' );

		wp_safe_redirect( add_query_arg( 'settings-updated', 'true', admin_url( 'options-general.php?page=zenadmin&tab=help' ) ) );
		exit;
	}

	/**
	 * Render the settings page.
	 */
	public function render_page() {
		$active_tab = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'blocks';
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ZenAdmin Settings', 'zenadmin' ); ?></h1>
			
			<nav class="nav-tab-wrapper">
				<a href="?page=zenadmin&tab=blocks" class="nav-tab <?php echo 'blocks' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Blocked Elements', 'zenadmin' ); ?></a>
				<a href="?page=zenadmin&tab=templates" class="nav-tab <?php echo 'templates' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Templates', 'zenadmin' ); ?></a>
				<a href="?page=zenadmin&tab=tools" class="nav-tab <?php echo 'tools' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Tools', 'zenadmin' ); ?></a>
				<a href="?page=zenadmin&tab=help" class="nav-tab <?php echo 'help' === $active_tab ? 'nav-tab-active' : ''; ?>"><?php esc_html_e( 'Help & Safe Mode', 'zenadmin' ); ?></a>
			</nav>

			<div class="zenadmin-content">
				<?php
				settings_errors( 'zenadmin_messages' );
				
				if ( 'blocks' === $active_tab ) {
					$this->render_blocks_tab();
				} elseif ( 'templates' === $active_tab ) {
					$this->render_templates_tab();
				} elseif ( 'tools' === $active_tab ) {
					$this->render_tools_tab();
				} else {
					$this->render_help_tab();
				}
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Blocks Tab.
	 */
	private function render_blocks_tab() {
		$blacklist = get_option( 'zenadmin_blacklist', array() );
		global $wp_roles;
		$all_roles = wp_list_pluck( $wp_roles->roles, 'name' );
		?>
		<div class="zenadmin-card">
			<h2><?php esc_html_e( 'Currently Blocked Elements', 'zenadmin' ); ?></h2>
			<?php if ( empty( $blacklist ) ) : ?>
				<p><?php esc_html_e( 'No elements blocked yet. Use the "Zen Mode" toggle in the admin bar to start cleaning up!', 'zenadmin' ); ?></p>
			<?php else : ?>
				<table class="wp-list-table widefat fixed striped zenadmin-blocks-table">
					<thead>
						<tr>
							<th style="width:20%"><?php esc_html_e( 'Label', 'zenadmin' ); ?></th>
							<th style="width:35%"><?php esc_html_e( 'Selector', 'zenadmin' ); ?></th>
							<th style="width:15%"><?php esc_html_e( 'Hidden For', 'zenadmin' ); ?></th>
							<th style="width:10%"><?php esc_html_e( 'Actions', 'zenadmin' ); ?></th>
							<th style="width:10%"><?php esc_html_e( 'Date', 'zenadmin' ); ?></th>
						</tr>
					</thead>
					<tbody>
						<?php foreach ( $blacklist as $hash => $item ) : 
							$hidden_for = isset( $item['hidden_for'] ) ? (array) $item['hidden_for'] : array_keys( $all_roles );
							$role_count = count( $hidden_for );
							$total_roles = count( $all_roles );
						?>
							<tr data-id="<?php echo esc_attr( $hash ); ?>" data-roles="<?php echo esc_attr( wp_json_encode( $hidden_for ) ); ?>">
								<td><?php echo esc_html( $item['label'] ); ?></td>
								<td><code style="font-size:11px;word-break:break-all;"><?php echo esc_html( $item['selector'] ); ?></code></td>
								<td>
									<a href="#" class="zenadmin-edit-roles-link" data-id="<?php echo esc_attr( $hash ); ?>">
										<?php 
										if ( $role_count === $total_roles ) {
											esc_html_e( 'All roles', 'zenadmin' );
										} else {
											/* translators: %d: number of roles */
											printf( esc_html__( '%d roles', 'zenadmin' ), $role_count );
										}
										?>
										<span class="dashicons dashicons-edit" style="font-size:14px;vertical-align:middle;"></span>
									</a>
								</td>
								<td>
									<button class="button button-small button-link-delete zenadmin-delete-btn" data-id="<?php echo esc_attr( $hash ); ?>"><?php esc_html_e( 'Delete', 'zenadmin' ); ?></button>
								</td>
								<td><?php echo esc_html( date_i18n( 'Y-m-d H:i:s', strtotime( $item['created_at'] ) ) ); ?></td>
							</tr>
						<?php endforeach; ?>
					</tbody>
				</table>

				<!-- Roles Popup -->
				<div id="zenadmin-roles-popup" class="zenadmin-roles-popup" style="display:none;">
					<div class="zenadmin-roles-popup-content">
						<h3><?php esc_html_e( 'Edit Roles', 'zenadmin' ); ?></h3>
						<p class="description"><?php esc_html_e( 'Select which roles should NOT see this element:', 'zenadmin' ); ?></p>
						<div class="zenadmin-roles-popup-list">
							<?php foreach ( $all_roles as $slug => $name ) : ?>
								<label class="zenadmin-role-popup-item">
									<input type="checkbox" name="popup_hidden_for[]" value="<?php echo esc_attr( $slug ); ?>">
									<?php echo esc_html( $name ); ?>
								</label>
							<?php endforeach; ?>
						</div>
						<div class="zenadmin-roles-popup-actions">
							<button class="button button-primary" id="zenadmin-popup-save"><?php esc_html_e( 'Save', 'zenadmin' ); ?></button>
							<button class="button" id="zenadmin-popup-cancel"><?php esc_html_e( 'Cancel', 'zenadmin' ); ?></button>
						</div>
					</div>
				</div>

				<script>
				jQuery(document).ready(function($) {
					var currentBlockId = null;

					// Open popup
					$('.zenadmin-edit-roles-link').on('click', function(e) {
						e.preventDefault();
						var row = $(this).closest('tr');
						currentBlockId = row.data('id');
						var roles = row.data('roles') || [];

						// Reset all checkboxes
						$('#zenadmin-roles-popup input[type="checkbox"]').prop('checked', false);

						// Check the ones in hidden_for
						roles.forEach(function(role) {
							$('#zenadmin-roles-popup input[value="' + role + '"]').prop('checked', true);
						});

						$('#zenadmin-roles-popup').fadeIn(200);
					});

					// Cancel popup
					$('#zenadmin-popup-cancel').on('click', function(e) {
						e.preventDefault();
						$('#zenadmin-roles-popup').fadeOut(200);
						currentBlockId = null;
					});

					// Save popup
					$('#zenadmin-popup-save').on('click', function(e) {
						e.preventDefault();
						if (!currentBlockId) return;

						var hiddenFor = [];
						$('#zenadmin-roles-popup input[name="popup_hidden_for[]"]:checked').each(function() {
							hiddenFor.push($(this).val());
						});

						var btn = $(this);
						btn.prop('disabled', true).text('<?php esc_html_e( 'Saving...', 'zenadmin' ); ?>');

						$.post(ajaxurl, {
							action: 'zenadmin_update_block_roles',
							security: '<?php echo wp_create_nonce( 'zenadmin_nonce' ); ?>',
							id: currentBlockId,
							hidden_for: JSON.stringify(hiddenFor)
						}, function(response) {
							btn.prop('disabled', false).text('<?php esc_html_e( 'Save', 'zenadmin' ); ?>');
							if (response.success) {
								// Update row data
								$('tr[data-id="' + currentBlockId + '"]').data('roles', hiddenFor);
								// Update link text
								var total = $('#zenadmin-roles-popup input[type="checkbox"]').length;
								var linkText = hiddenFor.length === total ? '<?php esc_html_e( 'All roles', 'zenadmin' ); ?>' : hiddenFor.length + ' roles';
								$('tr[data-id="' + currentBlockId + '"] .zenadmin-edit-roles-link').html(linkText + ' <span class="dashicons dashicons-edit" style="font-size:14px;vertical-align:middle;"></span>');
								$('#zenadmin-roles-popup').fadeOut(200);
								currentBlockId = null;
								ZenAdminToast.success('<?php esc_html_e( 'Roles updated successfully!', 'zenadmin' ); ?>');
							} else {
								ZenAdminToast.error(response.data.message);
							}
						});
					});

					// Delete handler
					$('.zenadmin-delete-btn').on('click', function(e) {
						e.preventDefault();
						if (!confirm('<?php esc_html_e( 'Are you sure?', 'zenadmin' ); ?>')) return;
						
						var btn = $(this);
						var id = btn.data('id');
						
						$.post(ajaxurl, {
							action: 'zenadmin_delete_block',
							security: '<?php echo wp_create_nonce( 'zenadmin_nonce' ); ?>',
							id: id
						}, function(response) {
							if (response.success) {
								btn.closest('tr').fadeOut();
								ZenAdminToast.success('<?php esc_html_e( 'Block deleted successfully!', 'zenadmin' ); ?>');
							} else {
								ZenAdminToast.error(response.data.message);
							}
						});
					});

					// Close popup on escape
					$(document).on('keydown', function(e) {
						if (e.key === 'Escape' && $('#zenadmin-roles-popup').is(':visible')) {
							$('#zenadmin-roles-popup').fadeOut(200);
							currentBlockId = null;
						}
					});
				});
				</script>
			<?php endif; ?>
		</div>
		<?php
	}

	/**
	 * Render Templates Tab.
	 */
	private function render_templates_tab() {
		if ( ! class_exists( 'ZenAdmin\\Templates' ) ) {
			echo '<p>' . esc_html__( 'Templates module not found.', 'zenadmin' ) . '</p>';
			return;
		}
		
		$templates = \ZenAdmin\Templates::get_templates();
		?>
		<div class="zenadmin-card">
			<h2><?php esc_html_e( 'Blocking Templates', 'zenadmin' ); ?></h2>
			<p><?php esc_html_e( 'Apply pre-configured sets of rules to hide common annoyances.', 'zenadmin' ); ?></p>
			
			<div class="zenadmin-templates-grid" style="display: grid; grid-template-columns: repeat(auto-fill, minmax(300px, 1fr)); gap: 20px; margin-top: 20px;">
				<?php foreach ( $templates as $id => $template ) : ?>
					<div class="zenadmin-template-card" style="border: 1px solid #ccd0d4; padding: 20px; background: #fff;">
						<h3><?php echo esc_html( $template['name'] ); ?></h3>
						<p><?php echo esc_html( $template['description'] ); ?></p>
						<p><strong><?php esc_html_e( 'Selectors:', 'zenadmin' ); ?></strong> <?php echo count( $template['selectors'] ); ?></p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'zenadmin_apply_template' ); ?>
							<input type="hidden" name="action" value="zenadmin_apply_template">
							<input type="hidden" name="template_id" value="<?php echo esc_attr( $id ); ?>">
							<button type="submit" class="button button-primary"><?php esc_html_e( 'Apply Template', 'zenadmin' ); ?></button>
						</form>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Render Help Tab.
	 */
	private function render_help_tab() {
		?>
		<div class="zenadmin-card">
			<h2><?php esc_html_e( 'Help & Safe Mode', 'zenadmin' ); ?></h2>
			<p><?php esc_html_e( 'If you accidentally blocked a critical element (like the admin menu) and cannot navigate:', 'zenadmin' ); ?></p>
			<ol>
				<li><?php esc_html_e( 'Add ?zenadmin_safe_mode=1 to your URL.', 'zenadmin' ); ?></li>
				<li><?php esc_html_e( 'Or click the button below.', 'zenadmin' ); ?></li>
			</ol>
			<p>
				<a href="<?php echo esc_url( add_query_arg( 'zenadmin_safe_mode', '1', admin_url() ) ); ?>" class="button button-secondary">
					<?php esc_html_e( 'Activate Safe Mode', 'zenadmin' ); ?>
				</a>
			</p>

			<hr>

			<h3><?php esc_html_e( 'Troubleshooting', 'zenadmin' ); ?></h3>
			<p><?php esc_html_e( 'If elements remain hidden even after deleting them from the list, try clearing your browser session blocks:', 'zenadmin' ); ?></p>
			<p>
				<button type="button" class="button button-secondary" onclick="sessionStorage.removeItem('zenadmin_session_blocks'); ZenAdminToast.success('<?php esc_attr_e( 'Session blocks cleared. Reloading...', 'zenadmin' ); ?>'); setTimeout(function() { window.location.reload(); }, 1500);">
					<?php esc_html_e( 'Clear Browser Session Blocks', 'zenadmin' ); ?>
				</button>
			</p>

			<hr>

			<h3><?php esc_html_e( 'Danger Zone', 'zenadmin' ); ?></h3>
			<p><?php esc_html_e( 'Completely reset ZenAdmin. This will delete ALL blocked elements and clear session data.', 'zenadmin' ); ?></p>
			<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" onsubmit="if(!confirm('<?php esc_attr_e( 'Are you sure? This cannot be undone.', 'zenadmin' ); ?>')) return false; sessionStorage.removeItem('zenadmin_session_blocks');">
				<?php wp_nonce_field( 'zenadmin_reset_all' ); ?>
				<input type="hidden" name="action" value="zenadmin_reset_all">
				<button type="submit" class="button button-link-delete"><?php esc_html_e( 'Reset All Settings', 'zenadmin' ); ?></button>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Tools Tab.
	 */
	private function render_tools_tab() {
		?>
		<div class="zenadmin-card">
			<h2><?php esc_html_e( 'Import / Export', 'zenadmin' ); ?></h2>
			<p><?php esc_html_e( 'Transfer your ZenAdmin configuration between sites.', 'zenadmin' ); ?></p>
			
			<div class="zenadmin-tools-grid" style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px; margin-top: 20px;">
				
				<!-- Export Section -->
				<div class="zenadmin-tool-box" style="border: 1px solid #ccd0d4; padding: 20px; background: #fff;">
					<h3>
						<span class="dashicons dashicons-download"></span> 
						<?php esc_html_e( 'Export Configuration', 'zenadmin' ); ?>
					</h3>
					<p><?php esc_html_e( 'Download a JSON file containing all your blocked elements, labels, and visibility settings.', 'zenadmin' ); ?></p>
					<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'zenadmin_export_nonce', 'zenadmin_nonce' ); ?>
						<input type="hidden" name="action" value="zenadmin_export">
						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Download Export File', 'zenadmin' ); ?>
						</button>
					</form>
				</div>

				<!-- Import Section -->
				<div class="zenadmin-tool-box" style="border: 1px solid #ccd0d4; padding: 20px; background: #fff;">
					<h3>
						<span class="dashicons dashicons-upload"></span> 
						<?php esc_html_e( 'Import Configuration', 'zenadmin' ); ?>
					</h3>
					<p><?php esc_html_e( 'Upload a previously exported ZenAdmin JSON file.', 'zenadmin' ); ?></p>
					
					<form method="post" enctype="multipart/form-data" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
						<?php wp_nonce_field( 'zenadmin_import_nonce', 'zenadmin_nonce' ); ?>
						<input type="hidden" name="action" value="zenadmin_import">
						
						<p>
							<input type="file" name="zenadmin_import_file" accept=".json" required>
						</p>
						
						<p>
							<label>
								<input type="checkbox" name="zenadmin_overwrite" value="1"> 
								<?php esc_html_e( 'Overwrite existing blocks (Dangerous)', 'zenadmin' ); ?>
							</label>
						</p>

						<button type="submit" class="button button-primary">
							<?php esc_html_e( 'Start Import', 'zenadmin' ); ?>
						</button>
					</form>
				</div>

			</div>
		</div>
		<?php
	}
}
