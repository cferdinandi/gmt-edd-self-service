<?php

	/**
	 * Self-service form shortcode
	 * @return string Shortcode markup
	 */
	function gmt_edd_self_service_form( $atts ) {

		// Get shortcode atts
		$self_service = shortcode_atts( array(
			'label' => 'Retrieve Purchases',
			'placeholder' => '',
			'honeypot' => '',
		), $atts );

		// Prevent this content from caching
		// define('DONOTCACHEPAGE', TRUE);

		// Status
		$status = gmt_edd_self_service_get_session( 'edd_self_service_status', true );
		$success = gmt_edd_self_service_get_session( 'edd_self_service_success', true );
		$email = gmt_edd_self_service_get_session( 'edd_self_service_email', true );

		// Get options
		$tarpit = empty( $self_service['honeypot'] ) ? '' : '<div class="edd-self-service-row ' . esc_attr( $self_service['honeypot'] ) . '"><div class="edd-self-service-grid-label"><label for="edd_self_service_email_confirm">' . __( 'If you are human, leave this blank', 'edd_self_service' ) . '</label></div><div class="edd-self-service-grid-input"><input type="text" id="edd_self_service_email_confirm" name="edd_self_service_email_confirm" value="" autofill="off"></div></div>';

		if ( $success ) {
			return '<p id="edd-self-service-form"><em>' . __( 'New receipts and download links for your purchases have been sent to your email address. Thanks!', 'edd_self_service' ) . '</em></p>';
		}

		return
			'<form class="edd-self-service-form" id="edd-self-service-form" name="edd_self_service_form" action="" method="post">' .
				'<input type="hidden" id="edd_self_service_tarpit_time" name="edd_self_service_tarpit_time" value="' . esc_attr( current_time( 'timestamp' ) ) . '">' .
				$tarpit .
				'<input type="hidden" id="edd_self_service_submit" name="edd_self_service_submit" value="' . get_site_option( 'gmt_edd_self_service_submit_hash' ) . '">' .
				'<label class="edd-self-service-label" for="edd_self_service_email">' . __( 'Email Address', 'edd_self_service' ) . '</label>' .
				'<div class="edd-self-service-row">' .
					'<div class="edd-self-service-grid-input">' .
						'<input type="email" class="edd-self-service-email" id="edd_self_service_email" name="edd_self_service_email" value="' . esc_attr( $email ) . '" placeholder="' . esc_attr( $self_service['placeholder'] ) . '" required>' .
					'</div>' .
					'<div class="edd-self-service-grid-button">' .
						'<button class="edd-self-service-button">' . $self_service['label'] . '</button>' .
					'</div>' .
				'</div>' .
				( empty( $status ) ? '' : '<p><em>' . __( 'Please use a valid email address.', 'edd_self_service' ) . '</em></p>' ) .
			'</form>';

	}
	add_shortcode( 'edd_self_service', 'gmt_edd_self_service_form' );



	/**
	 * Process self-service form
	 */
	function gmt_edd_self_service_process_form() {

		// Check that form was submitted
		if ( !isset( $_POST['edd_self_service_submit'] ) ) return;

		// Verify data came from proper screen
		if ( strcmp( $_POST['edd_self_service_submit'], get_site_option( 'gmt_edd_self_service_submit_hash' ) ) !== 0 ) return;

		// Variables
		$referrer = gmt_edd_self_service_get_url();
		// $status = $referrer . '#edd-self-service-form';
		$status = add_query_arg( 'edd-self-service', 'submitted', $referrer . '#edd-self-service-form' );

		// Sanity check
		if ( empty( $_POST['edd_self_service_email'] ) ) {
			wp_safe_redirect( $referrer, 302 );
			exit;
		}

		// Empty field honeypot
		if ( isset( $_POST['edd_self_service_email_confirm'] ) && !empty( $_POST['edd_self_service_email_confirm'] )  ) {
			wp_safe_redirect( $referrer, 302 );
			exit;
		}

		// Timestamp honeypot
		if ( !isset( $_POST['edd_self_service_tarpit_time'] ) || current_time( 'timestamp' ) - $_POST['edd_self_service_tarpit_time'] < 1 ) {
			wp_safe_redirect( $referrer, 302 );
			exit;
		}

		// If email is invalid
		if ( empty( filter_var( $_POST['edd_self_service_email'], FILTER_VALIDATE_EMAIL ) ) ) {
			gmt_edd_self_service_set_session( 'edd_self_service_status', true );
			gmt_edd_self_service_set_session( 'edd_self_service_email', $_POST['edd_self_service_email'], 'post' );
			wp_safe_redirect( $status, 302 );
			exit;
		}

		// Send receipts
		$purchases = edd_get_users_purchases( $_POST['edd_self_service_email'], 99, false );
		foreach( $purchases as $purchase ) {
			edd_email_purchase_receipt( $purchase->ID, false, $_POST['edd_self_service_email'] );
		}

		// Success message
		gmt_edd_self_service_set_session( 'edd_self_service_success', true );
		wp_safe_redirect( $status, 302 );
		exit;

	}
	add_action( 'init', 'gmt_edd_self_service_process_form' );