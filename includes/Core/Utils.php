<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Utils {
    public static function course_code(): string {
        global $wpdb;

        $table = Repository::table( 'courses' );
        $max   = (int) $wpdb->get_var( 'SELECT MAX(CAST(SUBSTRING(course_code, 8) AS UNSIGNED)) FROM ' . $table );
        return 'KH-LMS-' . str_pad( (string) ( $max + 1 ), 5, '0', STR_PAD_LEFT );
    }

    public static function enforce_nonce(): bool {
        $nonce = isset( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
        return (bool) wp_verify_nonce( $nonce, 'kh_lms_save_course' );
    }

    public static function user_can_access_course( int $user_id, int $course_id ): bool {
        if ( ! $user_id || ! Repository::course( $course_id ) ) {
            return false;
        }

        return current_user_can( 'manage_options' ) || Repository::is_enrolled( $user_id, $course_id );
    }

    public static function user_can_access_lesson( int $user_id, int $lesson_id ): bool {
        $lesson = Repository::lesson( $lesson_id );
        return $lesson ? self::user_can_access_course( $user_id, (int) $lesson->course_id ) : false;
    }
}
