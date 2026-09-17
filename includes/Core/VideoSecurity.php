<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class VideoSecurity {
    public static function issue_token( int $user_id, int $lesson_id, int $course_id ): string {
        $payload = wp_json_encode(
            [
                'user_id'    => $user_id,
                'lesson_id'  => $lesson_id,
                'course_id'  => $course_id,
                'timestamp'  => time(),
                'expiration' => time() + (int) Settings::get( 'token_lifetime', 180 ),
                'nonce'      => wp_generate_uuid4(),
            ]
        );

        $encoded = self::base64url_encode( $payload );
        $signature = hash_hmac( 'sha256', $encoded, self::secret() );
        return $encoded . '.' . $signature;
    }

    public static function decode_payload( string $token ): ?array {
        $parts = explode( '.', $token, 2 );
        if ( 2 !== count( $parts ) ) {
            return null;
        }

        [ $encoded, $signature ] = $parts;
        $expected = hash_hmac( 'sha256', $encoded, self::secret() );
        if ( ! hash_equals( $expected, $signature ) ) {
            return null;
        }

        $raw     = self::base64url_decode( $encoded );
        $payload = json_decode( $raw, true );
        return is_array( $payload ) ? $payload : null;
    }

    public static function validate_token( string $token, int $user_id, int $lesson_id ): bool {
        $payload = self::decode_payload( $token );
        return is_array( $payload )
            && (int) ( $payload['user_id'] ?? 0 ) === $user_id
            && (int) ( $payload['lesson_id'] ?? 0 ) === $lesson_id
            && (int) ( $payload['expiration'] ?? 0 ) >= time();
    }

    private static function base64url_encode( string $value ): string {
        return rtrim( strtr( base64_encode( $value ), '+/', '-_' ), '=' );
    }

    private static function base64url_decode( string $value ): string {
        $padding = strlen( $value ) % 4;
        if ( $padding ) {
            $value .= str_repeat( '=', 4 - $padding );
        }

        $decoded = base64_decode( strtr( $value, '-_', '+/' ), true );
        return false === $decoded ? '' : $decoded;
    }

    private static function secret(): string {
        $secret = get_option( 'kh_lms_secret' );
        if ( ! is_string( $secret ) || '' === $secret ) {
            $secret = wp_generate_password( 64, true, true );
            update_option( 'kh_lms_secret', $secret, false );
        }

        return $secret;
    }
}
