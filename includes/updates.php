<?php

/**
 * Data conversion helpers for when updating the plugin
 */

	/**
	 * Update from v1.x method for storing interest groups to v2.x+ approach
	 */
	function gmt_mailchimp_update_interest_groups_2_0_0() {

		// Get all Mailchimp forms
		$forms = get_posts(
			array(
				'posts_per_page'   => -1,
				'post_type'        => 'gmt-mailchimp',
				'post_status'      => 'any',
			)
		);

		// Update each form's metadata
		foreach( $forms as $form ) {
			// Get form metadata
			$saved = get_post_meta( $form->ID, 'mailchimp_details', true );
			$defaults = mailchimp_metabox_defaults();
			$details = wp_parse_args( $saved, $defaults );

			// Make sure old data exists first
			if ( !array_key_exists('group', $details) || empty( $details['group'] ) ) continue;

			// Update form interests
			$details['interests'] = array();
			$details['interests'][$details['group']] = 'on';
			unset( $details['category'] );
			unset( $details['group'] );

			// Save form data to database
			update_post_meta( $form->ID, 'mailchimp_details', $details );
		}

	}