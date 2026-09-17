<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Rest {
    public static function hooks(): void {
        add_action( 'rest_api_init', [ self::class, 'routes' ] );
    }

    public static function routes(): void {
        register_rest_route(
            'kh-lms/v1',
            '/courses/(?P<id>\d+)',
            [
                'methods'             => 'GET',
                'callback'            => static function ( \WP_REST_Request $request ) {
                    return Repository::course( (int) $request['id'] );
                },
                'permission_callback' => '__return_true',
            ]
        );

        register_rest_route(
            'kh-lms/v1',
            '/progress',
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'handle_progress' ],
                'permission_callback' => static function (): bool {
                    return is_user_logged_in();
                },
            ]
        );

        register_rest_route(
            'kh-lms/v1',
            '/video-token/(?P<lesson>\d+)',
            [
                'methods'             => 'POST',
                'callback'            => [ self::class, 'issue_video_token' ],
                'permission_callback' => static function (): bool {
                    return is_user_logged_in();
                },
            ]
        );
    }

    public static function issue_video_token( \WP_REST_Request $request ) {
        $lesson_id = absint( $request['lesson'] );
        $user_id   = get_current_user_id();

        $lesson = Repository::lesson( $lesson_id );
        if ( ! $lesson || ! Utils::user_can_access_lesson( $user_id, $lesson_id ) ) {
            return new \WP_Error( 'forbidden', 'دسترسی به این درس مجاز نیست.', [ 'status' => 403 ] );
        }

        $token = VideoSecurity::issue_token( $user_id, $lesson_id, (int) $lesson->course_id );

        return [
            'token' => $token,
            'expires_in' => 180,
        ];
    }

    public static function handle_progress( \WP_REST_Request $request ) {
        $params = $request->get_json_params();
        if ( ! is_array( $params ) ) {
            return new \WP_Error( 'invalid_request', 'درخواست نامعتبر است.', [ 'status' => 400 ] );
        }

        $lesson_id = absint( $params['lesson_id'] ?? 0 );
        $seconds   = max( 0, absint( $params['watched_seconds'] ?? 0 ) );
        $duration  = max( 1, absint( $params['duration'] ?? 0 ) );
        $user_id   = get_current_user_id();

        $lesson = Repository::lesson( $lesson_id );
        if ( ! $lesson || ! Utils::user_can_access_lesson( $user_id, $lesson_id ) ) {
            return new \WP_Error( 'forbidden', 'دسترسی مجاز نیست.', [ 'status' => 403 ] );
        }

        $percentage = min( 100, ( $seconds / $duration ) * 100 );
        $completed  = $percentage >= 90 ? 1 : 0;
        $now        = current_time( 'mysql', true );

        global $wpdb;

        $wpdb->replace(
            Repository::table( 'progress' ),
            [
                'user_id'        => $user_id,
                'course_id'      => $lesson->course_id,
                'lesson_id'      => $lesson_id,
                'watched_seconds'=> $seconds,
                'duration'       => $duration,
                'percentage'     => number_format( $percentage, 2, '.', '' ),
                'last_position'  => $seconds,
                'completed'      => $completed,
                'completed_at'   => $completed ? $now : null,
                'updated_at'     => $now,
            ],
            [ '%d', '%d', '%d', '%d', '%d', '%f', '%d', '%d', '%s', '%s' ]
        );

        return [
            'saved'     => true,
            'completed' => (bool) $completed,
            'percentage'=> number_format( $percentage, 2, '.', '' ),
        ];
    }
}
