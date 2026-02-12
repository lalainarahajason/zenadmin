<?php
/**
 * Templates Logic for ZenAdmin.
 *
 * @package ZenAdmin
 */

namespace ZenAdmin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Class Templates
 */
class Templates {

	/**
	 * Get all available templates.
	 *
	 * @return array
	 */
	public static function get_templates() {
		return array(
			'yoast_seo' => array(
				'name'        => __( 'Yoast SEO Upsells', 'zenadmin' ),
				'description' => __( 'Hides premium upsells and dashboard notifications from Yoast SEO.', 'zenadmin' ),
				'selectors'   => array(
					'.yoast-notification',
					'.yoast_premium_upsell',
					'[class*="yoast-upgrade"]',
					'#wpseo-dashboard-overview .yoast-container',
					'#webinar-promo-notification',
				),
			),
			'elementor' => array(
				'name'        => __( 'Elementor Promotions', 'zenadmin' ),
				'description' => __( 'Hides "Go Pro" banners and template library promotions.', 'zenadmin' ),
				'selectors'   => array(
					'.elementor-templates-modal__promotion',
					'[class*="e-pro-banner"]',
					'.elementor-control-type-upgrade-promotion',
					'#elementor-go-pro',
				),
			),
			'generic'   => array(
				'name'        => __( 'Generic Plugin Nags', 'zenadmin' ),
				'description' => __( 'Attempts to hide common "Rate this plugin" or "Upgrade" notices.', 'zenadmin' ),
				'selectors'   => array(
					'.notice.is-dismissible[class*="review"]',
					'.notice.is-dismissible[class*="upgrade"]',
					'.notice.is-dismissible[class*="premium"]',
				),
			),
		);
	}

	/**
	 * Apply a template.
	 *
	 * @param string $template_id Template ID.
	 * @return bool|WP_Error
	 */
	public static function apply_template( $template_id ) {
		$templates = self::get_templates();

		if ( ! isset( $templates[ $template_id ] ) ) {
			return new \WP_Error( 'invalid_template', __( 'Template not found.', 'zenadmin' ) );
		}

		$selectors_to_add = $templates[ $template_id ]['selectors'];
		$current_blacklist = get_option( 'zenadmin_blacklist', array() );
		$user_id           = get_current_user_id();
		$time              = current_time( 'mysql' );

		foreach ( $selectors_to_add as $selector ) {
			$hash = hash( 'sha256', $selector . 'zenadmin' ); // Consistent hashing
			
			if ( ! isset( $current_blacklist[ $hash ] ) ) {
				$current_blacklist[ $hash ] = array(
					'selector'   => $selector,
					'label'      => sprintf( __( 'Template: %s', 'zenadmin' ), $templates[ $template_id ]['name'] ),
					'created_at' => $time,
					'user_id'    => $user_id,
					'template'   => $template_id,
				);
			}
		}

		update_option( 'zenadmin_blacklist', $current_blacklist );
		return true;
	}
}
