<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Frontend {
    public static function hooks(): void {
        add_shortcode( 'kh_lms_course', [ self::class, 'course_shortcode' ] );
        add_shortcode( 'kh_lms_my_courses', [ self::class, 'my_courses_shortcode' ] );
        add_action( 'wp_enqueue_scripts', [ self::class, 'assets' ] );
        add_filter( 'query_vars', static function ( array $vars ): array { $vars[] = 'my-courses'; return $vars; } );
    }

    public static function assets(): void {
        if ( is_admin() ) { return; }
        wp_enqueue_style( 'kh-lms-frontend', KH_LMS_URL . 'assets/css/frontend.css', [], KH_LMS_VERSION );
        wp_enqueue_script( 'kh-lms-frontend', KH_LMS_URL . 'assets/js/frontend.js', [], KH_LMS_VERSION, true );
        wp_localize_script( 'kh-lms-frontend', 'khLms', [ 'api' => esc_url_raw( rest_url( 'kh-lms/v1/' ) ), 'nonce' => wp_create_nonce( 'wp_rest' ) ] );
    }

    public static function course_shortcode( array $atts = [] ): string {
        $atts = shortcode_atts( [ 'id' => 0, 'course' => 0 ], $atts, 'kh_lms_course' );
        $id = absint( $atts['id'] ?: $atts['course'] );
        $course = Repository::course( $id );
        if ( ! $course ) { return '<div class="kh-lms-notice">دوره‌ای پیدا نشد.</div>'; }
        $user_id = get_current_user_id();
        $access = 'free' === $course->type || ( $user_id && Repository::is_enrolled( $user_id, $id ) );
        $html = '<article class="kh-course" data-course="' . esc_attr( $id ) . '"><header class="kh-course-hero"><span class="kh-course-badge">' . esc_html( 'free' === $course->type ? 'رایگان' : 'دوره پولی' ) . '</span><h1>' . esc_html( $course->title ) . '</h1><p>' . esc_html( $course->short_description ) . '</p>';
        if ( ! $access && 'paid' === $course->type ) { $html .= '<a class="kh-button" href="' . esc_url( is_user_logged_in() && function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : wp_login_url( get_permalink() ) ) . '">' . esc_html( is_user_logged_in() ? 'خرید دوره' : 'ورود برای خرید' ) . '</a>'; }
        $html .= '</header><div class="kh-curriculum">';
        foreach ( Repository::curriculum( $id ) as $chapter ) {
            $html .= '<details class="kh-accordion"><summary><span>' . esc_html( $chapter->title ) . '</span><small>' . esc_html( count( $chapter->lessons ) . ' درس' ) . '</small></summary><div class="kh-lesson-list">';
            foreach ( $chapter->lessons as $lesson ) {
                $locked = ! $access && ! (int) $lesson->is_preview;
                $icon = $locked ? '🔒' : '▶';
                $html .= '<div class="kh-lesson-item"><span class="kh-lesson-icon">' . $icon . '</span><span>' . esc_html( $lesson->title ) . '</span>' . ( $locked ? '<em>پس از خرید</em>' : '<em>قابل مشاهده</em>' ) . '</div>';
            }
            $html .= '</div></details>';
        }
        return $html . '</div></article>';
    }

    public static function my_courses_shortcode(): string {
        if ( ! is_user_logged_in() ) { return '<div class="kh-lms-notice">برای مشاهده دوره‌های خود وارد شوید.</div>'; }
        global $wpdb;
        $rows = $wpdb->get_results( $wpdb->prepare( 'SELECT c.* FROM ' . Repository::table( 'courses' ) . ' c INNER JOIN ' . Repository::table( 'enrollments' ) . ' e ON e.course_id=c.id WHERE e.user_id=%d AND e.status=%s ORDER BY c.id DESC', get_current_user_id(), 'active' ) );
        if ( empty( $rows ) ) { return '<div class="kh-lms-notice">هنوز دوره‌ای در حساب شما ثبت نشده است.</div>'; }
        $out = '<div class="kh-courses">'; foreach ( $rows as $course ) { $out .= self::course_shortcode( [ 'id' => $course->id ] ); } return $out . '</div>';
    }
}
