<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}  // if direct access

/**
 * Scripts and styles
 */
class SP_EA_Front_Scripts {

	/**
	 * This class Instance.
	 *
	 * @var null
	 * @since 1.0
	 */
	protected static $_instance = null;

	/**
	 * This class Instance function.
	 *
	 * @return SP_EA_Front_Scripts
	 * @since 1.0
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Initialize the class
	 */
	public function __construct() {
		add_action( 'wp_enqueue_scripts', array( $this, 'front_scripts' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'admin_scripts' ) );
	}


	/**
	 * Plugin Scripts and Styles
	 */
	public function front_scripts() {
		$settings = get_option( 'sp_eap_settings' );
		$prefix   = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';
		// CSS Files.
		if ( false !== $settings['eap_dequeue_fa_css'] ) {
			wp_enqueue_style( 'sp-ea-font-awesome', esc_url( SP_EA_URL . 'public/assets/css/font-awesome.min.css' ), array(), SP_EA_VERSION );
		}
		wp_enqueue_style( 'sp-ea-style', esc_url( SP_EA_URL . 'public/assets/css/ea-style.css' ), array(), SP_EA_VERSION );

		// JS Files.
		wp_register_script( 'sp-ea-accordion-js', esc_url( SP_EA_URL . 'public/assets/js/collapse' . $prefix . '.js' ), array( 'jquery' ), SP_EA_VERSION, false );
		wp_register_script( 'sp-ea-accordion-config', esc_url( SP_EA_URL . 'public/assets/js/script.js' ), array( 'jquery', 'sp-ea-accordion-js' ), SP_EA_VERSION, true );

		$accordion_posts = new WP_Query(
			array(
				'post_type'      => 'sp_easy_accordion',
				'posts_per_page' => 500,
				'fields'         => 'ids',
			)
		);

		$accordion_ids  = $accordion_posts->posts;
		$ea_dynamic_css = '';

		foreach ( $accordion_ids as $accordion_id ) {
			include SP_EA_PATH . '/public/dynamic-style.php';
		}
		wp_add_inline_style( 'sp-ea-style', $ea_dynamic_css );
	}

	/**
	 * Plugin Scripts and Styles
	 */
	public function admin_scripts() {
		$settings = get_option( 'sp_eap_settings' );
		$prefix   = defined( 'WP_DEBUG' ) && WP_DEBUG ? '' : '.min';
		// CSS Files.
		if ( false !== $settings['eap_dequeue_fa_css'] ) {
			wp_enqueue_style( 'sp-ea-font-awesome', esc_url( SP_EA_URL . 'public/assets/css/font-awesome.min.css' ), array(), SP_EA_VERSION );
		}
		wp_enqueue_style( 'sp-ea-style', esc_url( SP_EA_URL . 'public/assets/css/ea-style.css' ), array(), SP_EA_VERSION );

		// JS Files.
		// wp_register_script( 'sp-ea-accordion-js', esc_url( SP_EA_URL . 'public/assets/js/collapse' . $prefix . '.js' ), array( 'jquery' ), SP_EA_VERSION, false );
		// wp_register_script( 'sp-ea-accordion-config', esc_url( SP_EA_URL . 'public/assets/js/script.js' ), array( 'jquery', 'sp-ea-accordion-js' ), SP_EA_VERSION, true );
	}
}

new SP_EA_Front_Scripts();
