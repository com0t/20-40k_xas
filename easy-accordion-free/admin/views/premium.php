<?php
/**
 * The premium page for the Easy Accordion Free
 *
 * @package Easy Accordion Free
 * @subpackage easy-accordion-free/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}  // if direct access.

/**
 * The premium class for the Easy Accordion Free
 */
class Easy_Accordion_Premium {

	/**
	 * Single instance of the class
	 *
	 * @var null
	 * @since 2.0
	 */
	protected static $_instance = null;

	/**
	 * Main EASY_ACCORDION_PRO_HELP Instance
	 *
	 * @since 2.0
	 * @static
	 * @see sp_eap_premium()
	 * @return self Main instance
	 */
	public static function instance() {
		if ( is_null( self::$_instance ) ) {
			self::$_instance = new self();
		}

		return self::$_instance;
	}

	/**
	 * Add admin menu.
	 *
	 * @return void
	 */
	public function premium_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=sp_easy_accordion',
			__( 'Easy Accordion Premium', 'easy-accordion-free' ),
			__( 'Premium', 'easy-accordion-free' ),
			'manage_options',
			'eap_premium',
			array(
				$this,
				'premium_page_callback',
			)
		);
	}

	/**
	 * Happy users.
	 *
	 * @param boolean $username Happy user.
	 * @param array   $args User args.
	 * @return $data
	 */
	public function happy_users( $username = 'shapedplugin', $args = array() ) {
		if ( $username ) {
			$params = array(
				'timeout'   => 10,
				'sslverify' => false,
			);

			$raw = wp_remote_retrieve_body( wp_remote_get( 'http://wptally.com/api/' . $username, $params ) );
			$raw = json_decode( $raw, true );

			if ( is_array( $raw ) && array_key_exists( 'error', $raw ) ) {
				$data = array(
					'error' => $raw['error'],
				);
			} else {
				$data = $raw;
			}
		} else {
			$data = array(
				'error' => __( 'No data found!', 'easy-accordion-free' ),
			);
		}

		return $data;
	}

	/**
	 * Premium Page Callback
	 */
	public function premium_page_callback() {
		wp_enqueue_style( 'sp-easy-accordion-admin-premium', SP_EA_URL . 'admin/css/premium-page.min.css', array(), SP_EA_VERSION );
		wp_enqueue_style( 'sp-easy-accordion-admin-premium-modal', SP_EA_URL . 'admin/css/modal-video.min.css', array(), SP_EA_VERSION );
		wp_enqueue_script( 'sp-easy-accordion-admin-premium', SP_EA_URL . 'admin/js/jquery-modal-video.min.js', array( 'jquery' ), SP_EA_VERSION, true );
		?>
	<div class="sp-easy-accordion-premium-page">
		<!-- Banner section start -->
		<section class="sp-eap-banner">
			<div class="sp-eap-container">
				<div class="row">
					<div class="sp-eap-col-xl-6">
						<div class="sp-eap-banner-content">
							<h2 class="sp-eap-font-30 main-color sp-eap-font-weight-500"><?php esc_html_e( 'Upgrade To Easy Accordion Pro', 'easy-accordion-free' ); ?></h2>
							<h4 class="sp-eap-mt-10 sp-eap-font-18 sp-eap-font-weight-500"><?php echo wp_kses_post( 'Supercharge <strong>Your Accordion & FAQs Page </strong> with powerful functionality!', 'easy-accordion-free' ); ?></h4>
							<p class="sp-eap-mt-25 text-color-2 line-height-20 sp-eap-font-weight-400"><?php esc_html_e( 'A highly flexible and customizable accordion plugin designed for everyone including designers & developers.', 'easy-accordion-free' ); ?></p>
							<p class="sp-eap-mt-20 text-color-2 sp-eap-line-height-20 sp-eap-font-weight-400"><?php esc_html_e( 'The best responsive and drag & drop Accordion FAQ builder plugin for WordPress with a lot of customization options including 16+ Beautiful Accordion Themes. It helps you to display multiple accordions including Nested or Multi-level into your WordPress site.', 'easy-accordion-free' ); ?></p>
						</div>
						<div class="sp-eap-banner-button sp-eap-mt-40">
							<a class="sp-eap-btn sp-eap-btn-sky" href="https://shapedplugin.com/plugin/easy-accordion-pro/?ref=1" target="_blank">Upgrade To Pro Now</a>
							<a class="sp-eap-btn sp-eap-btn-border ml-16 sp-eap-mt-15" href="https://demo.shapedplugin.com/easy-accordion/" target="_blank">Live Demo</a>
						</div>
					</div>
					<div class="sp-eap-col-xl-6">
						<div class="sp-eap-banner-img">
							<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/eap-vector.svg' ); ?>" alt="">
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- Banner section End -->

		<!-- Count section Start -->
		<section class="sp-eap-count">
			<div class="sp-eap-container">
				<div class="sp-eap-count-area">
					<div class="count-item">
						<h3 class="sp-eap-font-24">
					<?php
					$plugin_data  = $this->happy_users();
					$plugin_names = array();
					if ( is_array( $plugin_data ) && ! array_key_exists( 'error', $plugin_data ) ) {
						$plugin_names         = array_values( $plugin_data['plugins'] );
						$active_installations = array_column( $plugin_names, 'installs', 'url' );
						echo esc_attr( $active_installations['http://wordpress.org/plugins/easy-accordion-free'] ) . '+';
					}
					?>
						</h3>
						<span class="sp-eap-font-weight-400">Active Installations</span>
					</div>
					<div class="count-item">
						<h3 class="sp-eap-font-24">
						<?php

						if ( ! empty( $plugin_names ) ) {
							$active_downloads = array_column( $plugin_names, 'downloads', 'url' );
							echo esc_attr( $active_downloads['http://wordpress.org/plugins/easy-accordion-free'] );
						}
						?>
						</h3>
						<span class="sp-eap-font-weight-400">all time downloads</span>
					</div>
					<div class="count-item">
						<h3 class="sp-eap-font-24">
						<?php
						if ( ! empty( $plugin_names ) ) {
							$active_ratings = array_column( $plugin_names, 'rating', 'url' );
							echo esc_attr( $active_ratings['http://wordpress.org/plugins/easy-accordion-free'] ) . '/5';
						}
						?>
						</h3>
						<span class="sp-eap-font-weight-400">user reviews</span>
					</div>
				</div>
			</div>
		</section>
		<!-- Count section End -->

		<!-- Video Section Start -->
		<section class="sp-eap-video">
			<div class="sp-eap-container">
				<div class="section-title text-center">
					<h2 class="sp-eap-font-28">The Easiest and Drag & Drop Accordion FAQ Builder Plugin</h2>
					<h4 class="sp-eap-font-16 sp-eap-mt-10 sp-eap-font-weight-400">Learn why Easy Accordion Pro is the best Accordion FAQ plugin.</h4>
				</div>
				<div class="video-area text-center">
					<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/eap-vector-2.svg' ); ?>" alt="">
					<div class="video-button">
						<a class="js-video-button" href="#" data-channel="youtube" data-video-url="//www.youtube.com/embed/Q5FMbMGz76Q">
							<span><i class="fa fa-play"></i></span>
						</a>
					</div>
				</div>
			</div>
		</section>
		<!-- Video Section End -->

		<!-- Features Section Start -->
		<section class="sp-eap-feature">
			<div class="sp-eap-container">
				<div class="section-title text-center">
					<h2 class="sp-eap-font-28">Amazing Pro Key Features</h2>
					<h4 class="sp-eap-font-16 sp-eap-mt-10 sp-eap-font-weight-400">Upgrading to Pro will get you the following amazing benefits.</h4>
				</div>
				<div class="feature-wrapper">
					<div class="feature-area">
						<div class="feature-item mr-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/layouts.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">2 Accordion Layouts (Horizontal and Vertical)</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">Easy Accordion Pro comes with 2 unique layouts presets like Horizontal and Vertical to display your accordions. The layout presets are fully customizable with numerous options.</p>
							</div>
						</div>

						<div class="feature-item ml-30">
							<div class="feature-icon">
								<img src="
							<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/Nested-Accordion.svg' ); ?>
								" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Multi-level or Nested Accordion.</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">Creating a multi-level or nested accordion for your website is super easy. With Easy Accordion Pro, you can create unlimited multi-level or nested accordions with different themes.</p>
							</div>
						</div>
					</div>
					<div class="feature-area">
					<div class="feature-item mr-30">
							<div class="feature-icon">
								<img src="
							<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/color-styling.svg' ); ?>
								" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">16+ Beautiful Themes with Preview.</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">The premium plugin comes with 16+ ready themes that are fully customizable directly from the generator settings panel. Choose your desired theme and stylize it to fit your requirements.</p>
							</div>
						</div>
						<div class="feature-item ml-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/Customize-Everything-Easily.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Easily Customize Everything</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">You will be able to make it look exactly how you want with layout and colors & typography settings! You have full control over styling to design your way. No coding skills required!</p>
							</div>
						</div>
					</div>
					<div class="feature-area">
					<div class="feature-item mr-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/flat-icon.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">14+ Expand & Collapse Icon Style Sets</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">In Easy Accordion Pro, there are tons of robust features including 14+ Expand & Collapse icon style sets. You can customize collapsing icon color, hover color, margin, size, positions, etc.</p>
							</div>
						</div>
						<div class="feature-item ml-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/post.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Accordion from Posts, Pages & Category</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">You can create WordPress Accordion FAQ from Posts, Pages, Custom Post Types, and Taxonomy easily with Easy Accordion Pro. Select a post type, filter, order, order by, and limit.</p>
							</div>
						</div>
					</div>
					<div class="feature-area">
					<div class="feature-item mr-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/cart.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">WooCommerce FAQ Tab Accordion</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">To add the product FAQ tab on the product page in your WooCommerce store is super easy with Easy Accordion Pro. WooCommerce FAQ plugin helps to add the expected FAQ tab easily on the product page. </p>
							</div>
						</div>
						<div class="feature-item ml-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/animation.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">20+ Smooth Animations</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">A nice animation CSS library has been added in Easy Accordion Pro. You can select an animation style for the description content and set accordion to expand and collapse transition time.</p>
							</div>
						</div>
					</div>
					<div class="feature-area">
					<div class="feature-item mr-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/Supported-any-Contents.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Supported any Contents</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">You can put the content of any type inside the accordion expandable section including <b>Shortcodes, Images, YouTube, Audio, Map, Iframe, or any custom HTML code</b>. Upgrade to Easy Accordion Pro!</p>
							</div>
						</div>
						<div class="feature-item ml-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/schema.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Schema Ready FAQs</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">Properly marked-up pages are eligible to have a rich result displayed on the search results page. Easy Accordion Pro is fully SEO-friendly semantic markup ready with schema.org. </p>
							</div>
						</div>
					</div>
					<div class="feature-area">
						<div class="feature-item mr-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/typo.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Advanced Typography (fonts, color & styling)</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">
								Set font family, size, weight, text-transform, & colors to match your brand. The Pro version supports 950+ Google fonts and typography options. You can enable or disable fonts loading.</p>
							</div>
						</div>
						<div class="feature-item ml-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/Translation-RTL-Ready.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Multisite, Multilingual, RTL, Accessibility Ready</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">The plugin is Multisite, Multilingual, RTL, and Accessibility ready. For Arabic, Hebrew, Persian, etc. languages, you can select the right-to-left option for slider direction, without writing any CSS.</p>
							</div>
						</div>
					</div>
					<div class="feature-area">
						<div class="feature-item mr-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/page-bilder.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Page Builders & Countless Theme Compatibility</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">The plugin works smoothly with the popular themes and page builders plugins, e,g: Gutenberg, WPBakery, Elementor/Pro, Divi builder, BeaverBuilder, Fusion Builder, SiteOrgin, Themify Builder, etc.</p>
							</div>
						</div>
						<div class="feature-item ml-30">
							<div class="feature-icon">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/premium/frequent.svg' ); ?>" alt="">
							</div>
							<div class="feature-content">
								<h3 class="sp-eap-font-18 sp-eap-font-weight-600">Top-notch Support and Frequently Updates</h3>
								<p class="sp-eap-font-15 sp-eap-mt-15 sp-eap-line-height-24">Our dedicated top-notch support team is always ready to offer you world-class support and help when needed. Our engineering team is continuously working to improve the plugin and release new versions!</p>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- Features Section End -->

		<!-- Buy Section Start -->
		<section class="sp-eap-buy">
			<div class="sp-eap-container">
				<div class="row">
					<div class="sp-eap-col-xl-12">
						<div class="buy-content text-center">
							<h2 class="sp-eap-font-28">
							Join 
						<?php
						$install = 0;
						foreach ( $plugin_names as &$plugin_name ) {
							$install += $plugin_name['installs'];
						}
						echo esc_attr( $install + '15000' ) . '+';
						?>
							Happy Users in 160+ Countries
							</h2>
							<p class="sp-eap-font-16 sp-eap-mt-25 sp-eap-line-height-22">98% of customers are happy with <b>ShapedPlugin's</b> products and support. <br>
								So it’s a great time to join them.</p>
							<a class="sp-eap-btn sp-eap-btn-buy sp-eap-mt-40" href="https://shapedplugin.com/plugin/easy-accordion-pro/?ref=1" target="_blank">Get Started for $29 Today!</a>
							<span>14 Days Money-back Guarantee! No Question Asked.</span>
						</div>
					</div>
				</div>
			</div>
		</section>
		<!-- Buy Section End -->

		<!-- Testimonial section start -->
		<div class="testimonial-wrapper">
		<section class="sp-eap-premium testimonial">
		<div class="row">
				<div class="col-lg-6">
					<div class="testimonial-area">
						<div class="testimonial-content">
							<p>Great plugin and great support. Nice, simple plugin with a few useful extra options in the Pro version. However, it is the service/support that needs a special mention. I got prompt and helpful replies within a few hours (allowing for the time difference between countries) – even sent me a short video of how to make changes on the settings page when I had a problem. Not many developers would do that for you – believe me!</p>
						</div>
						<div class="testimonial-info">
							<div class="img">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/Richard-Joss-min.jpeg' ); ?>" alt="">
							</div>
							<div class="info">
								<h3>Richard Joss</h3>
								<div class="star">
								<i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
				<div class="col-lg-6">
					<div class="testimonial-area">
						<div class="testimonial-content">
							<p>My colleagues are very impressed with the result of the multiple accordion. Just what we needed :) Very useful having the video tutorial, many alternatives don’t. However there is a piece missing from the beginning which would provide the context by showing the page or post where the accordion(s) will be placed. Mine has introductory text prior to the accordions, which proved very problematic with alternative providers.</p>
						</div>
						<div class="testimonial-info">
							<div class="img">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/Joel-Roberts-min.png' ); ?>" alt="">
							</div>
							<div class="info">
								<h3>Joel Roberts</h3>
								<div class="star">
								<i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i><i class="fa fa-star"></i>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</section>
		</div>
		<!-- Testimonial section end -->
	</div>
	<!-- End premium page -->
						<?php
	}
}
