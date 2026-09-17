<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class VideoSecurity {
    public static function issue_token( int $user_id, int $lesson_id, int $course_id ): string {
        $secret = self::secret();
        $nonce  = wp_generate_uuid4();
        $now    = time();
        $exp    = $now + (int) Settings::get( 'token_lifetime', 180 );

        $payload = [
            'user_id'    => $user_id,
            'lesson_id'  => $lesson_id,
            'course_id'  => $course_id,
            'timestamp'  => $now,
            'expiration' => $exp,
            'nonce'      => $nonce,
        ];

        $encoded = wp_json_encode( $payload );
        $hash    = hash_hmac( 'sha256', $encoded, $secret );

        return base64_encode( $encoded . '.' . $hash );
    }

    public static function decode_payload( string $token ): ?array {
        $parts = explode( '.', $token );
        if ( count( $parts ) !== 2 ) {
            return null;
        }

        $payload_raw = base64_decode( $parts[0], true );
        if ( false === $payload_raw ) {
            return null;
        }

        $payload = json_decode( $payload_raw, true );
        if ( ! is_array( $payload ) ) {
            return null;
        }

        $expected = hash_hmac( 'sha256', $parts[0], self::secret() );
        if ( ! hash_equals( $expected, $parts[1] ) ) {
            return null;
        }

        return $payload;
    }

    public static function validate_token( string $token, int $user_id, int $lesson_id ): bool {
        $payload = self::decode_payload( $token );
        if ( ! $payload ) {
            return false;
        }

        if ( (int) ( $payload['user_id'] ?? 0 ) !== $user_id ) {
            return false;
        }

        if ( (int) ( $payload['lesson_id'] ?? 0 ) !== $lesson_id ) {
            return false;
        }

        if ( ! isset( $payload['expiration'] ) || (int) $payload['expiration'] < time() ) {
            return false;
        }

        return true;
    }

    private static function secret(): string {
        $secret = get_option( 'kh_lms_secret' );
        if ( ! $secret ) {
            $secret = wp_generate_password( 64, true, true );
            update_option( 'kh_lms_secret', $secret, false );
        }

        return $secret;
    }
}
