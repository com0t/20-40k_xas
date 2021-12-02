<?php
/**
 * Custom import export.
 *
 * @link http://shapedplugin.com
 * @since 2.0.0
 *
 * @package Easy_Accordion_free
 * @subpackage Easy_Accordion_free/includes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly.
}

/**
 * Custom import export.
 */
class Easy_Accordion_Import_Export {

	/**
	 * Export
	 *
	 * @param  mixed $accordion_ids Export accordion ids.
	 * @return array
	 */
	public function export( $accordion_ids ) {
		$export = array();
		if ( ! empty( $accordion_ids ) ) {
			$post_in = 'all_shortcodes' === $accordion_ids ? '' : $accordion_ids;

			$args       = array(
				'post_type'        => 'sp_easy_accordion',
				'post_status'      => array( 'inherit', 'publish' ),
				'orderby'          => 'modified',
				'suppress_filters' => 1, // wpml, ignore language filter.
				'posts_per_page'   => -1,
				'post__in'         => $post_in,
			);
			$accordions = get_posts( $args );
			if ( ! empty( $accordions ) ) {
				foreach ( $accordions as $accordion ) {
					$accordion_export = array(
						'title'       => $accordion->post_title,
						'original_id' => $accordion->ID,
						'meta'        => array(),
					);
					foreach ( get_post_meta( $accordion->ID ) as $metakey => $value ) {
						$accordion_export['meta'][ $metakey ] = $value[0];
					}
					$export['accordion'][] = $accordion_export;

					unset( $accordion_export );
				}
				$export['metadata'] = array(
					'version' => SP_EA_VERSION,
					'date'    => gmdate( 'Y/m/d' ),
				);
			}
			return $export;
		}
	}

	/**
	 * Export Accordion by ajax.
	 *
	 * @return void
	 */
	public function export_accordions() {
		$nonce = ( ! empty( $_POST['nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'eapro_options_nonce' ) ) {
			die();
		}

		$accordion_ids = isset( $_POST['eap_ids'] ) ? $_POST['eap_ids'] : ''; // phpcs:ignore

		$export = $this->export( $accordion_ids );

		if ( is_wp_error( $export ) ) {
			wp_send_json_error(
				array(
					'message' => $export->get_error_message(),
				),
				400
			);
		}

		if ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) {
            // @codingStandardsIgnoreLine
            echo wp_json_encode($export, JSON_PRETTY_PRINT);
			die;
		}

		wp_send_json( $export, 200 );
	}

	/**
	 * Import
	 *
	 * @param  array $accordions Import accordion array.
	 * @throws \Exception Error message.
	 * @return string
	 */
	public function import( $accordions ) {
		$errors = array();
		foreach ( $accordions as $index => $accordion ) {
			$errors[ $index ] = array();
			$new_accordion_id = 0;
			try {
				$new_accordion_id = wp_insert_post(
					array(
						'post_title'  => isset( $accordion['title'] ) ? $accordion['title'] : '',
						'post_status' => 'publish',
						'post_type'   => 'sp_easy_accordion',
					),
					true
				);

				if ( is_wp_error( $new_accordion_id ) ) {
					throw new Exception( $new_accordion_id->get_error_message() );
				}

				if ( isset( $accordion['meta'] ) && is_array( $accordion['meta'] ) ) {
					foreach ( $accordion['meta'] as $key => $value ) {
						update_post_meta(
							$new_accordion_id,
							$key,
							maybe_unserialize( str_replace( '{#ID#}', $new_accordion_id, $value ) )
						);
					}
				}
			} catch ( Exception $e ) {
				array_push( $errors[ $index ], $e->getMessage() );

				// If there was a failure somewhere, clean up.
				wp_trash_post( $new_accordion_id );
			}

			// If no errors, remove the index.
			if ( ! count( $errors[ $index ] ) ) {
				unset( $errors[ $index ] );
			}

			// External modules manipulate data here.
			do_action( 'sp_easy_accordion_accordion_imported', $new_accordion_id );
		}

		$errors = reset( $errors );
		return isset( $errors[0] ) ? new WP_Error( 'import_accordion_error', $errors[0] ) : $accordions;
	}

	/**
	 * Import Accordions by ajax.
	 *
	 * @return void
	 */
	public function import_accordions() {
		$nonce = ( ! empty( $_POST['nonce'] ) ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'eapro_options_nonce' ) ) {
			die();
		}
		$data       = isset( $_POST['accordion'] ) ? wp_kses_data( wp_unslash( $_POST['accordion'] ) ) : '';
		$data       = json_decode( $data );
		$data       = json_decode( $data, true );
		$accordions = $data['accordion'];
		if ( ! $data ) {
			wp_send_json_error(
				array(
					'message' => __( 'Nothing to import.', 'easy-accordion-free' ),
				),
				400
			);
		}

		$status = $this->import( $accordions );

		if ( is_wp_error( $status ) ) {
			wp_send_json_error(
				array(
					'message' => $status->get_error_message(),
				),
				400
			);
		}

		wp_send_json_success( $status, 200 );
	}
}
