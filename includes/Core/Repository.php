<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Utils {
    public static function course_code(): string {
        $prefix = 'KH-LMS-';
        $next   = self::next_course_number();

        return $prefix . str_pad( (string) $next, 5, '0', STR_PAD_LEFT );
    }

    public static function next_course_number(): int {
        global $wpdb;

        $table = $wpdb->prefix . 'kh_lms_courses';
        $sql   = $wpdb->prepare( 'SELECT MAX(CAST(SUBSTRING(course_code, 9) AS UNSIGNED)) FROM ' . $table );
        $max   = (int) $wpdb->get_var( $sql );

        return $max + 1;
    }

    public static function enforce_nonce(): bool {
        return isset( $_REQUEST['_wpnonce'] ) && wp_verify_nonce( sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ), 'kh_lms_save_course' );
    }

    public static function user_can_access_course( int $user_id, int $course_id ): bool {
        if ( ! $user_id || ! $course_id ) {
            return false;
        }

        $course = Repository::course( $course_id );
        if ( ! $course ) {
            return false;
        }

        if ( current_user_can( 'manage_options' ) ) {
            return true;
        }

        return Repository::is_enrolled( $user_id, $course_id );
    }

    public static function user_can_access_lesson( int $user_id, int $lesson_id ): bool {
        global $wpdb;

        $lesson = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT course_id FROM ' . Repository::table( 'lessons' ) . ' WHERE id = %d',
                $lesson_id
            )
        );

        if ( ! $lesson ) {
            return false;
        }

        return self::user_can_access_course( $user_id, (int) $lesson->course_id );
    }
}
