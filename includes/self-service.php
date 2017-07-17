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
		define('DONOTCACHEPAGE', TRUE);

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
				wp_nonce_field( 'edd_self_service_form_nonce', 'edd_self_service_form_process', true, false ) .
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
	 * Send the self-service purchase receipts
	 * @param  string $email The buyer's email address
	 */
	function gmt_edd_self_service_send_emails( $email ) {

		// Check for an email first
		if ( empty($email) ) return;

		// Get the user's purchases
		$purchases = edd_get_users_purchases( $email, 99, false );

		// Resend purchase receipts
		foreach( $purchases as $purchase ) {
			edd_email_purchase_receipt( $purchase->ID, false, $email );
		}

	}
	add_action('wp_async_gmt_edd_self_service_send_emails', 'gmt_edd_self_service_send_emails');



	/**
	 * Process self-service form
	 */
	function gmt_edd_self_service_process_form() {

		// Check that form was submitted
		if ( !isset( $_POST['edd_self_service_form_process'] ) ) return;

		// Verify data came from proper screen
		if ( !wp_verify_nonce( $_POST['edd_self_service_form_process'], 'edd_self_service_form_nonce' ) ) {
			die( 'Security check' );
		}

		// Variables
		$referrer = gmt_edd_self_service_get_url();
		$status = $referrer . '#edd-self-service-form';

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
		do_action( 'gmt_edd_self_service_send_emails', sanitize_email( $_POST['edd_self_service_email'] ) );

		// Success message
		gmt_edd_self_service_set_session( 'edd_self_service_success', true );
		wp_safe_redirect( $status, 302 );
		exit;

	}
	add_action( 'init', 'gmt_edd_self_service_process_form' );


	/**
	 * Asynchronously send purchase receipts
	 */
	class EDD_Async_Self_Service extends WP_Async_Task {

		protected $action = 'gmt_edd_self_service_send_emails';

		/**
		 * Prepare data for the asynchronous request
		 *
		 * @throws Exception If for any reason the request should not happen
		 *
		 * @param array $data An array of data sent to the hook
		 *
		 * @return array
		 */
		protected function prepare_data( $data ) {
			return array( 'email' => $data[0] );
		}

		/**
		 * Run the async task action
		 */
		protected function run_action() {
			do_action( "wp_async_$this->action", $_POST['email'] );
		}

	}


	/**
	 * Initialize our extended class
	 */
	function init_edd_self_service() {
	    new EDD_Async_Self_Service();
	}
	add_action( 'plugins_loaded', 'init_edd_self_service' );