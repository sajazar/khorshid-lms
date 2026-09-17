<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Utils {
    public static function course_code(): string {
        global $wpdb;
        $max = (int) $wpdb->get_var( 'SELECT MAX(CAST(SUBSTRING(course_code, 8) AS UNSIGNED)) FROM ' . Repository::table( 'courses' ) );
        return 'KH-LMS-' . str_pad( (string) ( $max + 1 ), 5, '0', STR_PAD_LEFT );
    }
    public static function user_can_access_course( int $user_id, int $course_id ): bool { return $user_id > 0 && ( current_user_can( 'manage_options' ) || Repository::is_enrolled( $user_id, $course_id ) ); }
    public static function user_can_access_lesson( int $user_id, int $lesson_id ): bool { $lesson = Repository::lesson( $lesson_id ); return $lesson ? self::user_can_access_course( $user_id, (int) $lesson->course_id ) : false; }
}
