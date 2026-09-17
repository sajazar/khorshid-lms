<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Frontend {
    public static function hooks(): void {
        add_shortcode( 'kh_lms_course', [ self::class, 'course_shortcode' ] );
        add_shortcode( 'kh_lms_my_courses', [ self::class, 'my_courses_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
        add_filter( 'query_vars', [ self::class, 'query_vars' ] );
    }

    public static function query_vars( array $vars ): array {
        $vars[] = 'my-courses';
        return $vars;
    }

    public static function assets(): void {
        if ( is_admin() || ( ! is_singular() && ! is_account_page() ) ) {
            return;
        }

        wp_enqueue_style( 'kh-lms-frontend', KH_LMS_URL . 'assets/css/frontend.css', [], KH_LMS_VERSION );
        wp_enqueue_script( 'kh-lms-frontend', KH_LMS_URL . 'assets/js/frontend.js', [], KH_LMS_VERSION, true );
        wp_localize_script(
            'kh-lms-frontend',
            'khLms',
            [
                'api'   => esc_url_raw( rest_url( 'kh-lms/v1/' ) ),
                'nonce' => wp_create_nonce( 'wp_rest' ),
            ]
        );
    }

    public static function course_shortcode( array $atts = [] ): string {
        $atts      = shortcode_atts( [ 'id' => 0, 'course' => 0 ], $atts, 'kh_lms_course' );
        $course_id = absint( $atts['id'] ?: $atts['course'] );
        $course    = Repository::course( $course_id );

        if ( ! $course ) {
            return '<p>دوره‌ای پیدا نشد.</p>';
        }

        $user_id    = get_current_user_id();
        $has_access = $user_id ? Repository::is_enrolled( $user_id, $course_id ) : false;
        $html       = '<article class="kh-course"><header><h1>' . esc_html( $course->title ) . '</h1><p>' . esc_html( $course->short_description ) . '</p>';

        if ( 'paid' === $course->type && ! $has_access ) {
            $url   = is_user_logged_in() && function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : wp_login_url( get_permalink() );
            $label = is_user_logged_in() ? 'خرید دوره' : 'ورود برای خرید دوره';
            $html .= '<a class="kh-button" href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
        }

        $html .= '</header><div class="kh-curriculum">';

        foreach ( Repository::curriculum( $course_id ) as $chapter ) {
            $html .= '<section class="kh-chapter-box"><h2>' . esc_html( $chapter->title ) . '</h2><ul>';
            foreach ( $chapter->lessons as $lesson ) {
                $locked = ( 'paid' === $course->type && ! $has_access && ! (int) $lesson->is_preview );
                $html  .= '<li>' . ( $locked ? '🔒' : '▶' ) . ' ' . esc_html( $lesson->title ) . '</li>';
            }
            $html .= '</ul></section>';
        }

        return $html . '</div></article>';
    }

    public static function my_courses_shortcode(): string {
        if ( ! is_user_logged_in() ) {
            return '<p>برای مشاهده دوره‌های شما باید وارد حساب کاربری شوید.</p>';
        }

        global $wpdb;
        $rows = $wpdb->get_results(
            $wpdb->prepare(
                'SELECT c.* FROM ' . Repository::table( 'courses' ) . ' c INNER JOIN ' . Repository::table( 'enrollments' ) . ' e ON e.course_id = c.id WHERE e.user_id = %d AND e.status = %s ORDER BY c.id DESC',
                get_current_user_id(),
                'active'
            )
        );

        if ( empty( $rows ) ) {
            return '<p>هنوز دوره‌ای خریداری نکرده‌اید.</p>';
        }

        $output = '<div class="kh-courses">';
        foreach ( $rows as $course ) {
            $output .= '<div class="kh-card"><h3>' . esc_html( $course->title ) . '</h3>' . self::course_shortcode( [ 'id' => $course->id ] ) . '</div>';
        }

        return $output . '</div>';
    }
}
