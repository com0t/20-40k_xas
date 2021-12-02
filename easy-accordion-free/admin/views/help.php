<?php
/**
 * The help page for the Easy Accordion Free
 *
 * @package Easy Accordion Free
 * @subpackage easy-accordion-free/admin
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}  // if direct access.

/**
 * The help class for the Easy Accordion Free
 */
class Easy_Accordion_Free_Help {

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
	 * @see sp_eap_help()
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
	public function help_admin_menu() {
		add_submenu_page(
			'edit.php?post_type=sp_easy_accordion',
			__( 'Easy Accordion Help', 'easy-accordion-free' ),
			__( 'Help', 'easy-accordion-free' ),
			'manage_options',
			'eap_help',
			array(
				$this,
				'help_page_callback',
			)
		);
	}

	/**
	 * The Easy Accordion Help Callback.
	 *
	 * @return void
	 */
	public function help_page_callback() {
		wp_enqueue_style( 'sp-easy-accordion-help', SP_EA_URL . 'admin/css/help-page.min.css', array(), SP_EA_VERSION );
		$add_shortcode_link = admin_url( 'post-new.php?post_type=sp_easy_accordion' );
		?>

		<div class="sp-easy-accordion-help">
				<!-- Header section start -->
				<section class="sp-eap-help header">
					<div class="header-area">
						<div class="container">
							<div class="header-logo">
								<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/ea-logo.svg' ); ?>" alt="">
								<span><?php echo esc_html( SP_EA_VERSION ); ?></span>
							</div>
							<div class="header-content">
								<p>Thank you for installing Easy Accordion plugin! This video will help you get started with the plugin.</p>
							</div>
						</div>
					</div>
					<div class="video-area">
						<iframe width="560" height="315" src="https://www.youtube.com/embed/rZrx-4cisAY" frameborder="0" allowfullscreen=""></iframe>
					</div>
					<div class="content-area">
						<div class="container">
							<div class="content-button">
								<a href="<?php echo esc_url( $add_shortcode_link ); ?>">Start Adding Accordion</a>
								<a href="https://docs.shapedplugin.com/docs/easy-accordion/introduction/" target="_blank">Read Documentation</a>
							</div>
						</div>
					</div>
				</section>
				<!-- Header section end -->

				<!-- Upgrade section start -->
				<section class="sp-eap-help upgrade">
					<div class="upgrade-area">
					<div class="upgrade-img"> 
					<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/eap-25.svg' ); ?>" alt="">
					</div>
						<h2>Upgrade To Unleash the Power of Easy Accordion Pro</h2> 
						<p>Get the most out of Easy Accordion by upgrading to unlock all of its powerful features. With Easy Accordion Pro, you can unlock amazing features like:</p>
					</div>
					<div class="upgrade-info">
						<div class="container">
							<div class="row">
								<div class="col-lg-6">
									<ul class="upgrade-list">
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">
										16+ Beautiful Themes with Preview.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">
										2 Layouts. (Horizontal and Vertical).</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Advanced Shortcode Generator.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Multi-level or Nested Accordion.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">14+ Expand & Collapse Icon Style Sets.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Accordion from Posts, Pages & Category.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Accordion from Custom Post Types & Taxonomy.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">WooCommerce FAQ Tab Accordion.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Group Accordion FAQs Showcase.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Limit To Display Number of Accordion.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Margin Between Accordions.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Accordion Border and Radius options.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">AutoPlay Accordion.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">940+ Google Fonts. (Typography Options).</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Supported any Contents. (e.g. HTML, shortcodes, images, YouTube, audio etc.)</li>
									</ul>
								</div>
								<div class="col-lg-6">
									<ul class="upgrade-list">
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Expand/Collapse All Button.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Ajax Search through all FAQ items in the front-end.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Accordion Title Background Color & Custom Padding.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Accordion Description Background Color.</li>						
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Accordion Description Custom Padding.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">FontAwesome Icon Picker before Accordion Title.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">HTML Title Tag (H1-H6 tags) and Strip HTML.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Multilingual & RTL Ready.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Widget Supported.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Multi-site Supported.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">Highly Customizable & developer friendly.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt="">And much more options.</li>
										<li><img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/checkmark.svg' ); ?>" alt=""><span>Not Happy? 100% No Questions Asked <a href="https://shapedplugin.com/refund-policy/" target="_blank">Refund Policy!</a></span></li>
									</ul>
								</div>
							</div>
						</div>
					</div>
					<div class="container">
						<div class="upgrade-pro">
							<div class="pro-content">
								<div class="pro-icon">
									<img src="<?php echo esc_url( SP_EA_URL . 'admin/css/images/eap-256.svg' ); ?>" alt="">
								</div>
								<div class="pro-text">
									<h2>Upgrade To Easy Accordion Pro</h2>
									<p>Start creating beautiful Accordion or FAQ in minutes!</p>
								</div>
							</div>
							<div class="pro-btn">
								<a href="https://shapedplugin.com/plugin/easy-accordion-pro/?ref=1" target="_blank">Upgrade To Pro Now</a>
							</div>
						</div>
					</div>
				</section>
				<!-- Upgrade section end -->

				<!-- Testimonial section start -->
				<section class="sp-eap-help testimonial">
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
				<!-- Testimonial section end -->

		</div>
		<?php
	}

	/**
	 * Add plugin action menu
	 *
	 * @param array  $links The action link.
	 * @param string $file The file.
	 *
	 * @return array
	 */
	public function add_plugin_action_links( $links, $file ) {

		if ( SP_EA_BASENAME === $file ) {
			$new_links =
				sprintf( '<a href="%s">%s</a>', admin_url( 'post-new.php?post_type=sp_easy_accordion' ), __( 'Add Accordion', 'easy-accordion-free' ) );
			array_unshift( $links, $new_links );

			$links['go_pro'] = sprintf( '<a target="_blank" href="%1$s" style="color: #35b747; font-weight: 700;">Go Premium!</a>', 'https://shapedplugin.com/plugin/easy-accordion-pro/?ref=1' );
		}

		return $links;
	}

}
