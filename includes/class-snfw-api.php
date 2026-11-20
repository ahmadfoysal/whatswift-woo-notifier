<?php
/**
 * SwiftNotify / WhatSwift HTTP client.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Talks to https://whatswift.com/api/create-message.
 */
class SNFW_API {

	const ENDPOINT = 'https://whatswift.com/api/create-message';

	/**
	 * Send one text message, optionally with a file.
	 *
	 * @param string $to      Full number with country code, digits only.
	 * @param string $message Message text.
	 * @param string $file    Optional public file URL (jpg, jpeg, png, webp, pdf, docx, xlsx, csv, txt).
	 * @return array success (bool), retry (bool: worth trying again later), detail (string for the log).
	 */
	public static function send( $to, $message, $file = '' ) {
		$body = array(
			'appkey'  => (string) SNFW_Settings::get( 'app_key' ),
			'authkey' => (string) SNFW_Settings::get( 'auth_key' ),
			'to'      => $to,
			'message' => $message,
			'sandbox' => 'false',
		);
		if ( $file ) {
			$body['file'] = $file;
		}

		$response = wp_remote_post(
			apply_filters( 'snfw_api_endpoint', self::ENDPOINT ),
			array(
				'headers' => array( 'Accept' => 'application/json' ),
				'body'    => $body,
				'timeout' => 20,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'success' => false,
				'retry'   => true,
				/* translators: %s: error message */
				'detail'  => sprintf( __( 'Could not reach the SwiftNotify server: %s', 'whatswift-woo-notifier' ), $response->get_error_message() ),
			);
		}

		$code = (int) wp_remote_retrieve_response_code( $response );
		$raw  = (string) wp_remote_retrieve_body( $response );
		$json = json_decode( $raw, true );

		$ok = $code >= 200 && $code < 300
			&& is_array( $json )
			&& isset( $json['message_status'] )
			&& 'success' === strtolower( (string) $json['message_status'] );

		if ( $ok ) {
			$from = isset( $json['data']['from'] ) ? (string) $json['data']['from'] : '';
			return array(
				'success' => true,
				'retry'   => false,
				/* translators: %s: sender WhatsApp number */
				'detail'  => $from ? sprintf( __( 'Accepted by SwiftNotify (sender %s).', 'whatswift-woo-notifier' ), $from ) : __( 'Accepted by SwiftNotify.', 'whatswift-woo-notifier' ),
			);
		}

		return array(
			'success' => false,
			'retry'   => $code >= 500 || 429 === $code || 408 === $code,
			'detail'  => self::describe_failure( $code, $json, $raw ),
		);
	}

	/**
	 * Build a readable failure reason from whatever the API returned.
	 *
	 * @param int        $code HTTP status.
	 * @param array|null $json Decoded body.
	 * @param string     $raw  Raw body.
	 * @return string
	 */
	private static function describe_failure( $code, $json, $raw ) {
		$reason = '';
		if ( is_array( $json ) ) {
			foreach ( array( 'error', 'message', 'msg', 'message_status' ) as $key ) {
				if ( ! empty( $json[ $key ] ) ) {
					$reason = is_scalar( $json[ $key ] ) ? (string) $json[ $key ] : wp_json_encode( $json[ $key ] );
					break;
				}
			}
			if ( '' === $reason ) {
				$reason = wp_json_encode( $json );
			}
		} else {
			$reason = wp_strip_all_tags( $raw );
		}
		$reason = mb_substr( trim( preg_replace( '/\s+/', ' ', $reason ) ), 0, 300 );

		if ( 401 === $code || 403 === $code ) {
			/* translators: %s: reason from the API */
			return sprintf( __( 'SwiftNotify rejected the App Key / Auth Key. %s', 'whatswift-woo-notifier' ), $reason );
		}
		/* translators: 1: HTTP status code, 2: reason from the API */
		return sprintf( __( 'SwiftNotify did not accept the message (HTTP %1$d). %2$s', 'whatswift-woo-notifier' ), $code, $reason );
	}
}
