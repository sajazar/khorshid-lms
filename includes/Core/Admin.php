<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Repository {
    public static function table( string $name ): string {
        global $wpdb;

        return $wpdb->prefix . 'kh_lms_' . $name;
    }

    public static function course( int $id ): ?object {
        global $wpdb;

        $table = self::table( 'courses' );
        $row   = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . $table . ' WHERE id = %d', $id ) );

        return $row ?: null;
    }

    public static function courses( array $args = [] ): array {
        global $wpdb;

        $table  = self::table( 'courses' );
        $status = isset( $args['status'] ) ? sanitize_key( $args['status'] ) : 'publish';
        $limit  = isset( $args['limit'] ) ? absint( $args['limit'] ) : 0;

        $sql = 'SELECT * FROM ' . $table . ' WHERE status = %s ORDER BY id DESC';
        if ( $limit > 0 ) {
            $sql .= ' LIMIT %d';
            return $wpdb->get_results( $wpdb->prepare( $sql, $status, $limit ) );
        }

        return $wpdb->get_results( $wpdb->prepare( $sql, $status ) );
    }

    public static function curriculum( int $course_id ): array {
        global $wpdb;

        $chapters_table = self::table( 'chapters' );
        $lessons_table  = self::table( 'lessons' );

        $chapters = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT * FROM ' . $chapters_table . ' WHERE course_id = %d ORDER BY sort_order ASC, id ASC',
                $course_id
            )
        );

        foreach ( $chapters as $chapter ) {
            $chapter->lessons = $wpdb->get_results(
                $wpdb->prepare(
                    'SELECT * FROM ' . $lessons_table . ' WHERE chapter_id = %d ORDER BY sort_order ASC, id ASC',
                    $chapter->id
                )
            );
        }

        return $chapters;
    }

    public static function is_enrolled( int $user_id, int $course_id ): bool {
        global $wpdb;

        $table = self::table( 'enrollments' );

        $count = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $table . ' WHERE user_id = %d AND course_id = %d AND status = %s AND (expires_at IS NULL OR expires_at > UTC_TIMESTAMP())',
                $user_id,
                $course_id,
                'active'
            )
        );

        return (bool) $count;
    }

    public static function lesson( int $id ): ?object {
        global $wpdb;

        $row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . self::table( 'lessons' ) . ' WHERE id = %d', $id ) );

        return $row ?: null;
    }

    public static function is_course_completed( int $user_id, int $course_id ): bool {
        global $wpdb;

        $courses_table = self::table( 'courses' );
        $progress_table = self::table( 'progress' );

        $required = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . self::table( 'lessons' ) . ' WHERE course_id = %d AND status = %s',
                $course_id,
                'publish'
            )
        );

        if ( ! $required ) {
            return false;
        }

        $completed = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT COUNT(*) FROM ' . $progress_table . ' WHERE user_id = %d AND course_id = %d AND completed = 1',
                $user_id,
                $course_id
            )
        );

        return (int) $completed >= (int) $required;
    }
}
