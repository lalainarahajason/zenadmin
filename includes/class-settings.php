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

		add_action( 'admin_post_zenadmin_reset_all', array( $this, 'handle_reset_all' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
	}

	/**
	 * Enqueue scripts for settings page.
	 */
	public function enqueue_scripts( $hook ) {
		if ( 'settings_page_zenadmin' !== $hook ) {
			return;
		}

		wp_enqueue_media();
		wp_enqueue_style( 'wp-color-picker' );
		wp_enqueue_script( 'wp-color-picker' );

		// Inline JS for Media Uploader & Color Picker
		$script = "
		jQuery(document).ready(function($){
			// Media Uploader
			$('.zenadmin-upload-btn').click(function(e) {
				e.preventDefault();
				var button = $(this);
				var inputId = button.data('input');
				
				var custom_uploader = wp.media({
					title: '" . esc_js( __( 'Select Logo', 'zenadmin' ) ) . "',
					button: {
						text: '" . esc_js( __( 'Use this logo', 'zenadmin' ) ) . "'
					},
					multiple: false
				}).on('select', function() {
					var attachment = custom_uploader.state().get('selection').first().toJSON();
					$('#' + inputId).val(attachment.url);
				}).open();
			});

			// Color Picker
			$('.zenadmin-color-field').wpColorPicker();

			// Reset Colors
			$('#zenadmin-reset-colors').click(function(e) {
				e.preventDefault();
				if (confirm('" . esc_js( __( 'Are you sure you want to reset all admin colors?', 'zenadmin' ) ) . "')) {
					$('.zenadmin-color-field').each(function() {
						var defaultColor = $(this).data('default-color');
						$(this).wpColorPicker('color', defaultColor);
					});
					// Auto-save to persist changes
					setTimeout(function() {
						$('#submit').click();
					}, 100);
				}
			});
		});
		";
		wp_add_inline_script( 'common', $script );
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
		register_setting( 'zenadmin_options', 'zenadmin_white_label' );
	}

	/**
	 * Handle template application via admin-post.
	 */


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
		// Tab navigation with whitelist validation
		$allowed_tabs = array( 'blocks', 'tools', 'white-label', 'help', 'documentation' );
		$active_tab   = isset( $_GET['tab'] ) ? sanitize_text_field( wp_unslash( $_GET['tab'] ) ) : 'blocks';

		// Validate against whitelist
		if ( ! in_array( $active_tab, $allowed_tabs, true ) ) {
			$active_tab = 'blocks';
		}
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'ZenAdmin Settings', 'zenadmin' ); ?></h1>
			
			<style>
				.nav-tab {
					display: inline-flex !important;
					align-items: center;
					gap: 6px;
				}
				.nav-tab .dashicons {
					font-size: 18px;
					width: 18px;
					height: 18px;
					color: #555;
					margin-top: -1px; /* Slight optical adjustment */
				}
				.nav-tab-active .dashicons {
					color: #000;
				}
			</style>
			<nav class="nav-tab-wrapper">
				<a href="?page=zenadmin&tab=blocks" class="nav-tab <?php echo 'blocks' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-shield"></span>
					<?php esc_html_e( 'Blocked Elements', 'zenadmin' ); ?>
				</a>
				<a href="?page=zenadmin&tab=white-label" class="nav-tab <?php echo 'white-label' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-appearance"></span>
					<?php esc_html_e( 'White Label', 'zenadmin' ); ?>
				</a>
				<a href="?page=zenadmin&tab=help" class="nav-tab <?php echo 'help' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-sos"></span>
					<?php esc_html_e( 'Help & Safe Mode', 'zenadmin' ); ?>
				</a>
				<a href="?page=zenadmin&tab=tools" class="nav-tab <?php echo 'tools' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-admin-tools"></span>
					<?php esc_html_e( 'Tools', 'zenadmin' ); ?>
				</a>
				<a href="?page=zenadmin&tab=documentation" class="nav-tab <?php echo 'documentation' === $active_tab ? 'nav-tab-active' : ''; ?>">
					<span class="dashicons dashicons-book"></span>
					<?php esc_html_e( 'Documentation', 'zenadmin' ); ?>
				</a>
			</nav>

			<div class="zenadmin-content">
				<?php
				settings_errors( 'zenadmin_messages' );

				if ( 'blocks' === $active_tab ) {
					$this->render_blocks_tab();
				} elseif ( 'tools' === $active_tab ) {
					$this->render_tools_tab();
				} elseif ( 'white-label' === $active_tab ) {
					$this->render_white_label_tab();
				} elseif ( 'documentation' === $active_tab ) {
					$this->render_documentation_tab();
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

		// Ensure $blacklist is always an array
		if ( ! is_array( $blacklist ) ) {
			$blacklist = array();
		}

		global $wp_roles;
		$all_roles = wp_list_pluck( $wp_roles->roles, 'name' );
		?>
		<div class="zenadmin-card">
			<h2><?php esc_html_e( 'Currently Blocked Elements', 'zenadmin' ); ?></h2>
			<?php
			// Display lock notice if settings are locked
			if ( defined( 'ZENADMIN_LOCK_SETTINGS' ) && ZENADMIN_LOCK_SETTINGS ) :
				?>
				<div class="notice notice-warning inline">
					<p>
						<span class="dashicons dashicons-lock" style="vertical-align:middle;"></span>
						<strong><?php esc_html_e( 'Settings Locked:', 'zenadmin' ); ?></strong>
						<?php esc_html_e( 'Modifications are disabled. Contact your administrator to make changes.', 'zenadmin' ); ?>
					</p>
				</div>
			<?php endif; ?>
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
						<?php
						foreach ( $blacklist as $hash => $item ) :
							// Skip invalid items
							if ( ! is_array( $item ) || ! isset( $item['selector'] ) || ! isset( $item['label'] ) ) {
								continue;
							}

							$hidden_for  = isset( $item['hidden_for'] ) ? (array) $item['hidden_for'] : array_keys( $all_roles );
							$role_count  = count( $hidden_for );
							$total_roles = count( $all_roles );
							?>
							<tr data-id="<?php echo esc_attr( $hash ); ?>" data-roles="<?php echo esc_attr( wp_json_encode( $hidden_for ) ); ?>">
								<td>
									<?php echo esc_html( $item['label'] ); ?>
									<?php if ( ! empty( $item['hard_block'] ) ) : ?>
										<br>
										<span class="dashicons dashicons-shield" style="color:#d63638; font-size:16px; width:16px; height:16px; margin-right:2px; vertical-align:text-bottom;" title="<?php esc_attr_e( 'Hard Block Enabled', 'zenadmin' ); ?>"></span>
										<small style="color:#d63638;"><?php esc_html_e( 'Access Restricted', 'zenadmin' ); ?></small>
									<?php endif; ?>
								</td>
								<td><code style="font-size:11px;word-break:break-all;"><?php echo esc_html( $item['selector'] ); ?></code></td>
								<td>
									<?php if ( ! ( defined( 'ZENADMIN_LOCK_SETTINGS' ) && ZENADMIN_LOCK_SETTINGS ) ) : ?>
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
									<?php else : ?>
										<?php
										if ( $role_count === $total_roles ) {
											esc_html_e( 'All roles', 'zenadmin' );
										} else {
											/* translators: %d: number of roles */
											printf( esc_html__( '%d roles', 'zenadmin' ), $role_count );
										}
										?>
									<?php endif; ?>
								</td>
								<td>
									<?php if ( ! ( defined( 'ZENADMIN_LOCK_SETTINGS' ) && ZENADMIN_LOCK_SETTINGS ) ) : ?>
										<button class="button button-small button-link-delete zenadmin-delete-btn" data-id="<?php echo esc_attr( $hash ); ?>"><?php esc_html_e( 'Delete', 'zenadmin' ); ?></button>
									<?php else : ?>
										<span class="dashicons dashicons-lock" style="color:#999;" title="<?php esc_attr_e( 'Settings are locked', 'zenadmin' ); ?>"></span>
									<?php endif; ?>
								</td>
								<td><?php echo esc_html( isset( $item['created_at'] ) ? wp_date( 'Y-m-d H:i:s', strtotime( $item['created_at'] ) ) : '-' ); ?></td>
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
						
						var btn = $(this);
						var id = btn.data('id');

						ZenAdminModal.open({
							type: 'confirm',
							title: '<?php esc_html_e( 'Delete Block', 'zenadmin' ); ?>',
							message: '<?php esc_html_e( 'Are you sure you want to delete this block?', 'zenadmin' ); ?>',
							i18n: {
								cancel: '<?php esc_html_e( 'Cancel', 'zenadmin' ); ?>',
								confirm: '<?php esc_html_e( 'Delete', 'zenadmin' ); ?>'
							},
							onConfirm: function() {
								$.post(ajaxurl, {
									action: 'zenadmin_delete_block',
									security: '<?php echo wp_create_nonce( 'zenadmin_nonce' ); ?>',
									id: id
								}, function(response) {
									if (response.success) {
										btn.closest('tr').fadeOut();
										ZenAdminToast.success('<?php esc_html_e( 'Block deleted successfully! Reloading...', 'zenadmin' ); ?>');
										setTimeout(function() {
											window.location.reload();
										}, 1000);
									} else {
										ZenAdminToast.error(response.data.message);
									}
								});
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

	/**
	 * Render White Label Tab.
	 */
	/**
	 * Render White Label Tab.
	 */
	private function render_white_label_tab() {
		$options = get_option( 'zenadmin_white_label', array() );
		?>
		<div class="zenadmin-card">
			<h2><?php esc_html_e( 'White Label Settings', 'zenadmin' ); ?></h2>
			<p><?php esc_html_e( 'Completely rebrand WordPress for your clients.', 'zenadmin' ); ?></p>
			
			<form method="post" action="options.php">
				<?php settings_fields( 'zenadmin_options' ); ?>

				<!-- Global Enable -->
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Enable White Label', 'zenadmin' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="zenadmin_white_label[enabled]" value="1" <?php checked( isset( $options['enabled'] ) && $options['enabled'] ); ?>>
								<?php esc_html_e( 'Activate White Label features', 'zenadmin' ); ?>
							</label>
						</td>
					</tr>
				</table>

				<hr>

				<h3><?php esc_html_e( '1. Global Identity', 'zenadmin' ); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Plugin Name', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_plugin_name]" value="<?php echo isset( $options['wl_plugin_name'] ) ? esc_attr( $options['wl_plugin_name'] ) : ''; ?>" class="regular-text" placeholder="e.g. Agency Tools">
							<p class="description"><?php esc_html_e( 'Renames "ZenAdmin" in menus and plugin list.', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Plugin Description', 'zenadmin' ); ?></th>
						<td>
							<textarea name="zenadmin_white_label[wl_plugin_desc]" class="large-text" rows="2" placeholder="e.g. Essential system utilities."><?php echo isset( $options['wl_plugin_desc'] ) ? esc_textarea( $options['wl_plugin_desc'] ) : ''; ?></textarea>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Agency Name', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[agency_name]" value="<?php echo isset( $options['agency_name'] ) ? esc_attr( $options['agency_name'] ) : ''; ?>" class="regular-text">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Agency URL', 'zenadmin' ); ?></th>
						<td>
							<input type="url" name="zenadmin_white_label[agency_url]" value="<?php echo isset( $options['agency_url'] ) ? esc_attr( $options['agency_url'] ) : ''; ?>" class="regular-text">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Menu Icon', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_menu_icon]" value="<?php echo isset( $options['wl_menu_icon'] ) ? esc_attr( $options['wl_menu_icon'] ) : ''; ?>" class="regular-text" placeholder="dashicons-shield or SVG base64">
							<p class="description"><?php esc_html_e( 'Dashicon class (e.g. dashicons-shield) or SVG string.', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Stealth Mode', 'zenadmin' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="zenadmin_white_label[stealth_mode]" value="1" <?php checked( isset( $options['stealth_mode'] ) && $options['stealth_mode'] ); ?>>
								<?php esc_html_e( 'Hide ZenAdmin from the plugins list (plugins.php).', 'zenadmin' ); ?>
							</label>
						</td>
					</tr>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'zenadmin' ); ?>"></p>

				<hr>

				<h3><?php esc_html_e( '2. Login Branding', 'zenadmin' ); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Custom Logo', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[login_logo]" id="zenadmin_login_logo" value="<?php echo isset( $options['login_logo'] ) ? esc_attr( $options['login_logo'] ) : ''; ?>" class="regular-text">
							<button type="button" class="button zenadmin-upload-btn" data-input="zenadmin_login_logo"><?php esc_html_e( 'Upload', 'zenadmin' ); ?></button>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Logo Link URL', 'zenadmin' ); ?></th>
						<td>
							<input type="url" name="zenadmin_white_label[wl_login_logo_url]" value="<?php echo isset( $options['wl_login_logo_url'] ) ? esc_attr( $options['wl_login_logo_url'] ) : ''; ?>" class="regular-text">
							<p class="description"><?php esc_html_e( 'Where the logo links to (defaults to Agency URL if empty).', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Background Color', 'zenadmin' ); ?></th>
						<td>
							<input type="color" name="zenadmin_white_label[wl_login_bg_color]" value="<?php echo isset( $options['wl_login_bg_color'] ) ? esc_attr( $options['wl_login_bg_color'] ) : '#f1f1f1'; ?>">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Button Color', 'zenadmin' ); ?></th>
						<td>
							<input type="color" name="zenadmin_white_label[wl_login_btn_color]" value="<?php echo isset( $options['wl_login_btn_color'] ) ? esc_attr( $options['wl_login_btn_color'] ) : '#2271b1'; ?>">
						</td>
					</tr>
					</tr>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'zenadmin' ); ?>"></p>

				<hr>

				<h3><?php esc_html_e( '3. Admin Color Scheme (Pro)', 'zenadmin' ); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Primary Color', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_admin_primary]" value="<?php echo isset( $options['wl_admin_primary'] ) ? esc_attr( $options['wl_admin_primary'] ) : '#2271b1'; ?>" class="zenadmin-color-field" data-default-color="#2271b1">
							<p class="description"><?php esc_html_e( 'Main buttons, links, and highlights.', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Sidebar Background', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_admin_sidebar_bg]" value="<?php echo isset( $options['wl_admin_sidebar_bg'] ) ? esc_attr( $options['wl_admin_sidebar_bg'] ) : '#1d2327'; ?>" class="zenadmin-color-field" data-default-color="#1d2327">
							<p class="description"><?php esc_html_e( 'Background color of the admin menu.', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Accent Color', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_admin_accent]" value="<?php echo isset( $options['wl_admin_accent'] ) ? esc_attr( $options['wl_admin_accent'] ) : '#2271b1'; ?>" class="zenadmin-color-field" data-default-color="#2271b1">
							<p class="description"><?php esc_html_e( 'Active menu items and notification bubbles.', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Admin Bar Background', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_admin_bar_bg]" value="<?php echo isset( $options['wl_admin_bar_bg'] ) ? esc_attr( $options['wl_admin_bar_bg'] ) : '#1d2327'; ?>" class="zenadmin-color-field" data-default-color="#1d2327">
							<p class="description"><?php esc_html_e( 'Background color of the top admin bar.', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"></th>
						<td>
							<button type="button" id="zenadmin-reset-colors" class="button button-secondary">
								<span class="dashicons dashicons-undo" style="vertical-align:middle;margin-right:4px;"></span>
								<?php esc_html_e( 'Reset Colors', 'zenadmin' ); ?>
							</button>
							<p class="description"><?php esc_html_e( 'Revert to default WordPress colors.', 'zenadmin' ); ?></p>
					</tr>
				</table>
				<p class="submit"><input type="submit" name="submit" id="submit" class="button button-primary" value="<?php esc_attr_e( 'Save Changes', 'zenadmin' ); ?>"></p>

				<hr>

				<h3><?php esc_html_e( '4. Interface & Dashboard', 'zenadmin' ); ?></h3>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Admin Footer Text', 'zenadmin' ); ?></th>
						<td>
							<textarea name="zenadmin_white_label[footer_text]" class="large-text" rows="2"><?php echo isset( $options['footer_text'] ) ? esc_textarea( $options['footer_text'] ) : ''; ?></textarea>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Hide Elements', 'zenadmin' ); ?></th>
						<td>
							<label style="display:block;margin-bottom:5px;">
								<input type="checkbox" name="zenadmin_white_label[hide_version]" value="1" <?php checked( isset( $options['hide_version'] ) && $options['hide_version'] ); ?>>
								<?php esc_html_e( 'Hide WordPress Version', 'zenadmin' ); ?>
							</label>
							<label style="display:block;margin-bottom:5px;">
								<input type="checkbox" name="zenadmin_white_label[wl_hide_wp_logo]" value="1" <?php checked( isset( $options['wl_hide_wp_logo'] ) && $options['wl_hide_wp_logo'] ); ?>>
								<?php esc_html_e( 'Hide WordPress Logo (Admin Bar)', 'zenadmin' ); ?>
							</label>
							<label style="display:block;">
								<input type="checkbox" name="zenadmin_white_label[wl_hide_updates]" value="1" <?php checked( isset( $options['wl_hide_updates'] ) && $options['wl_hide_updates'] ); ?>>
								<?php esc_html_e( 'Hide Core & Plugin Update Notifications', 'zenadmin' ); ?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Dashboard Widgets', 'zenadmin' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="zenadmin_white_label[wl_dashboard_reset]" value="1" <?php checked( isset( $options['wl_dashboard_reset'] ) && $options['wl_dashboard_reset'] ); ?>>
								<?php esc_html_e( 'Remove ALL default dashboard widgets', 'zenadmin' ); ?>
							</label>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Welcome Widget Title', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_welcome_title]" value="<?php echo isset( $options['wl_welcome_title'] ) ? esc_attr( $options['wl_welcome_title'] ) : ''; ?>" class="regular-text" placeholder="Welcome to your website">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Welcome Widget Content', 'zenadmin' ); ?></th>
						<td>
							<textarea name="zenadmin_white_label[wl_welcome_content]" class="large-text" rows="5" placeholder="HTML content here..."><?php echo isset( $options['wl_welcome_content'] ) ? esc_textarea( $options['wl_welcome_content'] ) : ''; ?></textarea>
						</td>
					</tr>
				</table>

				<hr>

				<h3><?php esc_html_e( '5. Global Access Control (Hard Blocking)', 'zenadmin' ); ?></h3>
				<p class="description"><?php esc_html_e( 'Restrict access to specific admin pages for certain roles. Admins are immune.', 'zenadmin' ); ?></p>
				<table class="form-table">
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Blocked Pages (Slugs)', 'zenadmin' ); ?></th>
						<td>
							<textarea name="zenadmin_white_label[wl_hard_block_pages]" class="large-text" rows="3" placeholder="tools.php, options-general.php"><?php echo isset( $options['wl_hard_block_pages'] ) ? esc_textarea( $options['wl_hard_block_pages'] ) : ''; ?></textarea>
							<p class="description"><?php esc_html_e( 'Comma-separated list of filenames (e.g. tools.php).', 'zenadmin' ); ?></p>
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Redirect To', 'zenadmin' ); ?></th>
						<td>
							<input type="text" name="zenadmin_white_label[wl_redirect_dest]" value="<?php echo isset( $options['wl_redirect_dest'] ) ? esc_attr( $options['wl_redirect_dest'] ) : ''; ?>" class="regular-text" placeholder="<?php echo esc_url( admin_url() ); ?>">
						</td>
					</tr>
					<tr valign="top">
						<th scope="row"><?php esc_html_e( 'Apply to Roles', 'zenadmin' ); ?></th>
						<td>
							<?php
							global $wp_roles;
							$roles         = $wp_roles->roles;
							$applied_roles = isset( $options['wl_applied_roles'] ) ? (array) $options['wl_applied_roles'] : array();
							foreach ( $roles as $slug => $data ) {
								if ( 'administrator' === $slug ) {
									continue; // Skip admin
								}
								?>
								<label style="display:inline-block; margin-right:15px;">
									<input type="checkbox" name="zenadmin_white_label[wl_applied_roles][]" value="<?php echo esc_attr( $slug ); ?>" <?php checked( in_array( $slug, $applied_roles, true ) ); ?>>
									<?php echo esc_html( $data['name'] ); ?>
								</label>
								<?php
							}
							?>
						</td>
					</tr>
				</table>

				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}

	/**
	 * Render Documentation Tab.
	 */
	private function render_documentation_tab() {
		$white_label_enabled = false;
		$white_label_options = get_option( 'zenadmin_white_label', array() );
		if ( isset( $white_label_options['enabled'] ) && $white_label_options['enabled'] ) {
			$white_label_enabled = true;
		}

		// Inline Styles for Documentation Layout
		?>
		<style>
			.zenadmin-doc-container {
				display: grid;
				grid-template-columns: 250px 1fr;
				gap: 30px;
				margin-top: 20px;
				background: #fff;
				border: 1px solid #ccd0d4;
				box-shadow: 0 1px 1px rgba(0,0,0,0.04);
			}
			.zenadmin-doc-sidebar {
				background: #f8f9fa;
				border-right: 1px solid #ccd0d4;
				padding: 0;
			}
			.zenadmin-doc-sticky-wrapper {
				position: sticky;
				top: 40px;
			}
			.zenadmin-doc-nav {
				list-style: none;
				margin: 0;
				padding: 0;
			}
			.zenadmin-doc-nav li {
				margin: 0;
				border-bottom: 1px solid #eee;
			}
			.zenadmin-doc-nav a {
				display: block;
				padding: 15px 20px;
				text-decoration: none;
				color: #444;
				font-weight: 500;
				border-left: 3px solid transparent;
				transition: all 0.2s;
			}
			.zenadmin-doc-nav a:hover, .zenadmin-doc-nav a.active {
				background: #fff;
				color: #2271b1;
				border-left-color: #2271b1;
			}
			.zenadmin-doc-nav .dashicons {
				margin-right: 8px;
				color: #888;
			}
			.zenadmin-doc-content {
				padding: 30px 40px;
				max-width: 800px;
			}
			.zenadmin-doc-section {
				margin-bottom: 50px;
				padding-top: 20px;
			}
			.zenadmin-doc-section h2 {
				font-size: 24px;
				border-bottom: 2px solid #f0f0f1;
				padding-bottom: 15px;
				margin-bottom: 20px;
			}
			.zenadmin-doc-section h3 {
				font-size: 18px;
				margin-top: 30px;
				margin-bottom: 15px;
			}
			.zenadmin-doc-section p {
				font-size: 14px;
				line-height: 1.6;
				color: #3c434a;
			}
			.zenadmin-notice-pro {
				background: #f0f6fc;
				border-left: 4px solid #72aee6;
				padding: 15px;
				margin: 20px 0;
			}
			.zenadmin-code-block {
				background: #f6f7f7;
				border: 1px solid #dcdcde;
				padding: 10px 15px;
				border-radius: 4px;
				font-family: monospace;
				margin: 10px 0;
				display: block;
			}
		</style>

		<div class="zenadmin-doc-container">
			<!-- Sidebar -->
			<div class="zenadmin-doc-sidebar">
				<div class="zenadmin-doc-sticky-wrapper">
					<ul class="zenadmin-doc-nav">
						<li>
							<a href="#getting-started">
								<span class="dashicons dashicons-welcome-learn-more"></span>
								<?php esc_html_e( 'Getting Started', 'zenadmin' ); ?>
							</a>
						</li>
						<li>
							<a href="#advanced-selection">
								<span class="dashicons dashicons-search"></span>
								<?php esc_html_e( 'Advanced Selection', 'zenadmin' ); ?>
							</a>
						</li>
						<li>
							<a href="#role-management">
								<span class="dashicons dashicons-admin-users"></span>
								<?php esc_html_e( 'Role Management', 'zenadmin' ); ?>
							</a>
						</li>
						<?php if ( $white_label_enabled ) : ?>
						<li>
							<a href="#white-label-guide">
								<span class="dashicons dashicons-admin-appearance"></span>
								<?php esc_html_e( 'White Label Guide', 'zenadmin' ); ?>
							</a>
						</li>
						<?php endif; ?>
						<li>
							<a href="#troubleshooting">
								<span class="dashicons dashicons-sos"></span>
								<?php esc_html_e( 'Troubleshooting', 'zenadmin' ); ?>
							</a>
						</li>
					</ul>
					
					<?php if ( $white_label_enabled ) : ?>
						<div style="padding: 20px; text-align: center; border-top: 1px solid #ccd0d4; margin-top: 20px;">
							<p style="margin-bottom:10px;"><strong><?php esc_html_e( 'Need Help?', 'zenadmin' ); ?></strong></p>
							<a href="mailto:<?php echo esc_attr( get_bloginfo( 'admin_email' ) ); ?>" class="button button-secondary">
								<?php esc_html_e( 'Contact Support', 'zenadmin' ); ?>
							</a>
						</div>
					<?php endif; ?>
				</div>
			</div>

			<!-- Content -->
			<div class="zenadmin-doc-content">
				
				<!-- Getting Started -->
				<section id="getting-started" class="zenadmin-doc-section">
					<h2><?php esc_html_e( 'Getting Started', 'zenadmin' ); ?></h2>
					<p><?php esc_html_e( 'ZenAdmin allows you to clean up your WordPress interface by simply pointing and clicking on elements you want to hide.', 'zenadmin' ); ?></p>
					
					<h3><?php esc_html_e( '1. Activation', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( 'Look for the "Zen Mode" toggle in your top admin bar. Click it to activate the selection mode.', 'zenadmin' ); ?></p>
					
					<h3><?php esc_html_e( '2. Interaction', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( 'Hover over any element on the page (menu items, dashboard widgets, notices). You will see a purple border highlighting the element. Click to open the blocking modal.', 'zenadmin' ); ?></p>
					
					<h3><?php esc_html_e( '3. Preview', 'zenadmin' ); ?></h3>
					<div class="zenadmin-notice-pro">
						<p><strong><?php esc_html_e( 'Pro Tip:', 'zenadmin' ); ?></strong> <?php esc_html_e( 'Use the "Preview" button (Eye icon) in the modal to see exactly what will be hidden before you confirm.', 'zenadmin' ); ?></p>
					</div>
				</section>

				<!-- Advanced Selection -->
				<section id="advanced-selection" class="zenadmin-doc-section">
					<h2><?php esc_html_e( 'Advanced Selection Strategies', 'zenadmin' ); ?></h2>
					
					<h3><?php esc_html_e( 'Intelligent Targeting (HREF)', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( 'ZenAdmin is smarter than standard CSS hiders. For menu items, we target the link destination (HREF) rather than just CSS IDs. This means even if a plugin updates and changes its ID, your block will likely remain active.', 'zenadmin' ); ?></p>
					<code class="zenadmin-code-block">a[href="admin.php?page=my-plugin"]</code>

					<h3><?php esc_html_e( 'Hard Blocking vs. Hiding', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( ' Standard blocking only hides the element visually (CSS).', 'zenadmin' ); ?></p>
					<p><?php esc_html_e( ' "Hard Blocking" (available in the modal for menu items) actually restricts access to the PHP page itself, redirecting unauthorized users to the dashboard.', 'zenadmin' ); ?></p>
				</section>

				<!-- Role Management -->
				<section id="role-management" class="zenadmin-doc-section">
					<h2><?php esc_html_e( 'Role Management', 'zenadmin' ); ?></h2>
					<p><?php esc_html_e( 'You can control exactly who sees what.', 'zenadmin' ); ?></p>
					
					<ul>
						<li><strong><?php esc_html_e( 'Global:', 'zenadmin' ); ?></strong> <?php esc_html_e( 'By default, blocks apply to all roles except you (the creator).', 'zenadmin' ); ?></li>
						<li><strong><?php esc_html_e( 'Per-Block:', 'zenadmin' ); ?></strong> <?php esc_html_e( 'In the "Blocked Elements" tab, click the "Edit Roles" icon to select specific roles (e.g., hide "Tools" only for Editors).', 'zenadmin' ); ?></li>
					</ul>
				</section>

				<?php if ( $white_label_enabled ) : ?>
				<!-- White Label -->
				<section id="white-label-guide" class="zenadmin-doc-section">
					<h2><?php esc_html_e( 'White Label Guide', 'zenadmin' ); ?></h2>
					<p><?php esc_html_e( 'Customize the WordPress experience for your clients.', 'zenadmin' ); ?></p>
					
					<h3><?php esc_html_e( 'Stealth Mode', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( 'Enable "Stealth Mode" in the White Label tab to hide ZenAdmin itself from the plugins list. This prevents clients from accidentally deactivting the tool that keeps their interface clean.', 'zenadmin' ); ?></p>

					<h3><?php esc_html_e( 'Login Customization', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( 'Upload your agency logo to replace the WordPress logo on the wp-login.php page.', 'zenadmin' ); ?></p>
				</section>
				<?php endif; ?>

				<!-- Troubleshooting -->
				<section id="troubleshooting" class="zenadmin-doc-section">
					<h2><?php esc_html_e( 'Troubleshooting', 'zenadmin' ); ?></h2>
					
					<h3><?php esc_html_e( 'Safe Mode', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( 'If you accidentally blocked the settings menu and cannot access this page:', 'zenadmin' ); ?></p>
					<code class="zenadmin-code-block"><?php echo esc_url( admin_url( '?zenadmin_safe_mode=1' ) ); ?></code>
					<p><?php esc_html_e( 'Add ?zenadmin_safe_mode=1 to your URL to temporarily disable all blocks.', 'zenadmin' ); ?></p>

					<h3><?php esc_html_e( 'Kill Switch', 'zenadmin' ); ?></h3>
					<p><?php esc_html_e( 'For developers: You can disable ZenAdmin completely via wp-config.php:', 'zenadmin' ); ?></p>
					<code class="zenadmin-code-block">define( 'ZENADMIN_DISABLE', true );</code>
				</section>

			</div>
		</div>

		<script>
		// Simple smooth scrolling for internal links
		jQuery(document).ready(function($) {
			$('.zenadmin-doc-nav a').on('click', function(e) {
				// Update active state
				$('.zenadmin-doc-nav a').removeClass('active');
				$(this).addClass('active');
			});
		});
		</script>
		<?php
	}
}
