<?php
/**
 * Phone number clean-up.
 *
 * @package SwiftNotify
 */

defined( 'ABSPATH' ) || exit;

/**
 * Turns whatever the customer typed into the full international number the API expects.
 */
class SNFW_Phone {

	/**
	 * Normalise a phone number to digits with country code, or '' when unusable.
	 *
	 * @param string $number  Raw number.
	 * @param string $country Optional ISO country code (billing country) used to pick the calling code.
	 * @return string
	 */
	public static function normalize( $number, $country = '' ) {
		$raw           = trim( (string) $number );
		$international = ( '+' === substr( $raw, 0, 1 ) );
		$digits        = preg_replace( '/\D/', '', $raw );

		if ( '00' === substr( $digits, 0, 2 ) ) {
			$digits        = substr( $digits, 2 );
			$international = true;
		}

		if ( ! $international ) {
			$code = self::calling_code( $country );
			if ( '0' === substr( $digits, 0, 1 ) ) {
				// National format with trunk zero, e.g. 017XXXXXXXX.
				$digits = $code . ltrim( $digits, '0' );
			} elseif ( $code && 0 !== strpos( $digits, $code ) && strlen( $digits ) <= 10 ) {
				// National format without the zero, e.g. 17XXXXXXXX.
				$digits = $code . $digits;
			}
		}

		$digits = (string) apply_filters( 'snfw_normalized_phone', $digits, $number, $country );

		return preg_match( '/^[1-9][0-9]{7,14}$/', $digits ) ? $digits : '';
	}

	/**
	 * Calling code (digits) for a country, falling back to the default in settings.
	 *
	 * @param string $country ISO country code.
	 * @return string
	 */
	public static function calling_code( $country = '' ) {
		if ( $country && function_exists( 'WC' ) && WC()->countries ) {
			$code = WC()->countries->get_country_calling_code( $country );
			$code = is_array( $code ) ? reset( $code ) : $code;
			$code = preg_replace( '/\D/', '', (string) $code );
			if ( $code ) {
				return $code;
			}
		}
		return preg_replace( '/\D/', '', (string) SNFW_Settings::get( 'country_code' ) );
	}

	/**
	 * Hide the middle of a number for display.
	 *
	 * @param string $number Number.
	 * @return string
	 */
	public static function mask( $number ) {
		$number = (string) $number;
		return strlen( $number ) > 7 ? substr( $number, 0, 5 ) . str_repeat( '•', strlen( $number ) - 8 ) . substr( $number, -3 ) : $number;
	}
}
