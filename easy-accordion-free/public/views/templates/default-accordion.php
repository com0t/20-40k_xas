<?php
/**
 * The post accordion template.
 *
 * @package easy_accordion_free
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

if ( empty( $content_sources ) ) {
	return;
}

if ( $acc_section_title ) {
	echo '<h2 class="eap_section_title eap_section_title_' . esc_attr( $post_id ) . '"> ' . wp_kses_post( $main_section_title ) . ' </h2>';
}
echo '<div id="sp-ea-' . esc_attr( $post_id ) . '" class="' . esc_attr( $accordion_wraper_class ) . '" data-ex-icon="' . esc_attr( $eap_expand_icon ) . '" data-col-icon="' . esc_attr( $eap_collapse_icon ) . '"  data-ea-active="' . esc_attr( $eap_active_event ) . '"  data-ea-mode="' . esc_attr( $accordion_layout ) . '" data-preloader="' . esc_attr( $eap_preloader ) . '">';
if ( $eap_preloader ) {
	echo '<div id="eap-preloader-' . esc_attr( $post_id ) . '" class="accordion-preloader">';
	echo '<img src="' . esc_url( SP_EA_URL . 'public/assets/ea_loader.gif' ) . '"/>';
	echo '</div>';
}
$ea_key = 1;
foreach ( $content_sources as $key => $content_source ) {
	$content_title = $content_source['accordion_content_title'];
	$content_embed = $content_source['accordion_content_description'];
	global $wp_embed;
	$content_embed = $wp_embed->autoembed( $content_embed );
	if ( $eap_autop ) {
		$content_embed = wpautop( $content_embed );
	}

	$content_description = do_shortcode( $content_embed );
	if ( 'ea-first-open' === $eap_accordion_mode ) {
		$a_open_first      = ( 1 === $ea_key ) ? 'collapsed show' : '';
		$expand_icon_first = ( 1 === $ea_key ) ? $eap_expand_icon : $eap_collapse_icon;
		$expand_class      = ( 1 === $ea_key ) ? 'ea-expand' : '';
		$aria_expanded     = ( 1 === $ea_key ) ? 'true' : 'false';
	} elseif ( 'ea-multi-open' === $eap_accordion_mode ) {
		$a_open_first      = 'collapsed show';
		$expand_icon_first = $eap_expand_icon;
		$expand_class      = 'ea-expand';
		$aria_expanded     = 'true';
	} elseif ( 'ea-all-close' === $eap_accordion_mode ) {
		$a_open_first      = 'spcollapse';
		$expand_icon_first = $eap_collapse_icon;
		$expand_class      = '';
		$aria_expanded     = 'false';
	}
	$data_parent_id      = ( ! $eap_mutliple_collapse ) ? 'data-parent=#sp-ea-' . $post_id . '' : '';
	$eap_exp_icon_markup = ( $eap_icon ) ? '<i class="ea-expand-icon fa ' . $expand_icon_first . '"></i>' : '';
	$data_sptarget       = 'data-sptarget=#collapse' . $post_id . $key . '';
	$eap_icon_markup     = $eap_exp_icon_markup;

	$allowed_tags           = wp_kses_allowed_html( 'post' );
	$allowed_tags['iframe'] = array(
		'src'             => array(),
		'height'          => array(),
		'width'           => array(),
		'frameborder'     => array(),
		'allowfullscreen' => array(),
		'title'           => array(),
		'alt'             => array(),
	);
	$allowed_tags['video']  = array(
		'width'    => true,
		'height'   => true,
		'controls' => true,
		'style'    => true,
		'poster'   => true,
	);
	$allowed_tags['source'] = array(
		'src'  => true,
		'type' => true,
	);

	$allowed_tags['style'] = array();

	if ( ! empty( $content_description ) ) {
		$content_description_markup = sprintf(
			'<div class="ea-body">%1$s</div>',
			$content_description
		);
	} elseif ( empty( $content_description ) ) {
		$content_description_markup = sprintf(
			'<div class="ea-body">No Content</div>'
		);
	}
	echo '<div class="ea-card ' . esc_attr( $expand_class . ' ' . $accordion_item_class ) . '">';
		echo sprintf(
			'<h3 class="ea-header"><a class="collapsed" data-sptoggle="spcollapse" %1$s href="javascript:void(0)" aria-expanded="%4$s">%2$s %3$s</a></h3>',
			esc_attr( $data_sptarget ),
			wp_kses_post( $eap_icon_markup ),
			wp_kses_post( $content_title ),
			esc_attr( $aria_expanded )
		);
	echo '<div class="sp-collapse spcollapse ' . esc_attr( $a_open_first ) . '" id="collapse' . esc_attr( $post_id . $key ) . '" ' . esc_attr( $data_parent_id ) . '>';
	echo wp_kses( $content_description_markup, apply_filters( 'sp_ea_description_allow_tags', $allowed_tags ) );
	echo '</div>';
	echo '</div>';
	$ea_key++;
}
if ( $eap_schema_markup ) {
	echo '<script type="application/ld+json">
	{
	  "@context": "https://schema.org",
	  "@type": "FAQPage",
	  "mainEntity": [';
	foreach ( $content_sources as $keys => $content_source ) {
		$content_title       = $content_source['accordion_content_title'];
		$content_description = $content_source['accordion_content_description'];
		echo '{
			"@type": "Question",
			"name": "' . esc_attr( wp_strip_all_tags( $content_title ) ) . '",
			"acceptedAnswer": {
			  "@type": "Answer",
			  "text": "' . esc_html( wp_strip_all_tags( $content_description ) ) . '"
			}
		  }';
		if ( $keys !== $key ) {
			echo ',';
		}
	}
	echo ']
	}
	</script>';

}
echo '</div>';
