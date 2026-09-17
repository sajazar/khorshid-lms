<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class VideoStream {
    public static function hooks(): void {
        add_action( 'rest_api_init', [ self::class, 'routes' ] );
    }

    public static function routes(): void {
        register_rest_route(
            'kh-lms/v1',
            '/video/(?P<token>[^/]+)',
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'stream' ],
                'permission_callback' => '__return_true',
            ]
        );
    }

    public static function stream( \WP_REST_Request $request ) {
        $token = sanitize_text_field( urldecode( (string) $request['token'] ) );

        if ( '' === $token || ! is_user_logged_in() ) {
            return new \WP_Error( 'forbidden', 'دسترسی مجاز نیست.', [ 'status' => 403 ] );
        }

        $payload = VideoSecurity::decode_payload( $token );
        if ( ! $payload ) {
            return new \WP_Error( 'invalid_token', 'توکن نامعتبر است.', [ 'status' => 403 ] );
        }

        $lesson_id = (int) ( $payload['lesson_id'] ?? 0 );
        $user_id   = (int) ( $payload['user_id'] ?? 0 );

        if ( ! VideoSecurity::validate_token( $token, $user_id, $lesson_id ) ) {
            return new \WP_Error( 'invalid_token', 'توکن منقضی یا نامعتبر است.', [ 'status' => 403 ] );
        }

        if ( get_current_user_id() !== $user_id ) {
            return new \WP_Error( 'forbidden', 'این ویدئو متعلق به کاربر دیگری است.', [ 'status' => 403 ] );
        }

        $lesson = Repository::lesson( $lesson_id );
        if ( ! $lesson ) {
            return new \WP_Error( 'not_found', 'درس پیدا نشد.', [ 'status' => 404 ] );
        }

        if ( ! Utils::user_can_access_lesson( $user_id, $lesson_id ) ) {
            return new \WP_Error( 'forbidden', 'دسترسی درس مجاز نیست.', [ 'status' => 403 ] );
        }

        $video_url = esc_url_raw( $lesson->video_url ?? '' );
        if ( '' === $video_url ) {
            return new \WP_Error( 'missing_video', 'ویدیویی برای این درس ثبت نشده است.', [ 'status' => 404 ] );
        }

        $response = wp_remote_get(
            $video_url,
            [
                'timeout' => 30,
                'headers' => [
                    'Range' => $_SERVER['HTTP_RANGE'] ?? '',
                ],
            ]
        );

        if ( is_wp_error( $response ) ) {
            return new \WP_Error( 'stream_error', 'پخش ویدئو ممکن نیست.', [ 'status' => 502 ] );
        }

        $body = wp_remote_retrieve_body( $response );
        $type = wp_remote_retrieve_header( $response, 'content-type' ) ?: 'video/mp4';
        $length = strlen( $body );

        nocache_headers();
        header( 'Content-Type: ' . $type );
        header( 'Content-Length: ' . $length );
        header( 'Accept-Ranges: bytes' );
        echo $body;
        exit;
    }
}
