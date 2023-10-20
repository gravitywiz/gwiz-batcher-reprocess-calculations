<?php
/*
 * Plugin Name:  Gravity Wiz Batcher: Reprocess Calculations
 * Plugin URI:   http://gravitywiz.com
 * Description:  Batcher to reprocess calculations for entries of a specific form.
 * Author:       Gravity Wiz
 * Version:      0.1
 * Author URI:   http://gravitywiz.com
 */

add_action( 'init', 'gwiz_batcher_reprocess_calculations' );

function gwiz_batcher_reprocess_calculations() {

	if ( ! is_admin() && ! ( defined( 'DOING_AJAX' ) && DOING_AJAX ) ) {
		return;
	}

	require_once( plugin_dir_path( __FILE__ ) . 'class-gwiz-batcher.php' );

	new Gwiz_Batcher( array(
		'title'        => 'GW Batcher: Reprocess Calculations',
		'id'           => 'gw-batcher',
		'show_form_selector' => true,
		'size'         => 25,
		'get_items'    => function ( $size, $offset, $form_id = null ) {

			$paging  = array(
				'offset'    => $offset,
				'page_size' => $size,
			);

			$entries = GFAPI::get_entries( $form_id, array(), null, $paging, $total );

			return array(
				'items' => $entries,
				'total' => $total,
			);
		},
		'process_item' => function ( $entry ) {

			$form = GFAPI::get_form( $entry['form_id'] );

			/**
			 * @var GF_Field $field
			 */
			foreach ( $form['fields'] as $field ) {

				if ( GFFormsModel::is_field_hidden( $form, $field, array(), $entry ) ) {
					continue;
				}

				// Target Calculated Number fields but exclude Calculated Product fields.
				if ( ! $field->has_calculation() || $field->get_input_type() === 'calculation' ) {
					continue;
				}

				$entry[ $field->id ] = GFCommon::calculate( $field, $form, $entry );

			}

			GFAPI::update_entry( $entry );

		},
		'on_finish'          => function( $count, $total ) {
			GFCommon::log_debug( sprintf( 'Finished reprocessing calculations for %d of %d entries.', $count, $total ) );
		},
	) );

}
