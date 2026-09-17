<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) { exit; }

final class Admin {
    public static function hooks(): void {
        add_action( 'admin_menu', [ self::class, 'menu' ] );
        add_action( 'admin_post_kh_lms_save_course', [ self::class, 'save_course' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );
    }

    public static function assets( string $hook ): void {
        if ( false === strpos( $hook, 'kh-lms' ) ) { return; }
        wp_enqueue_style( 'kh-lms-admin', KH_LMS_URL . 'assets/css/admin.css', [], KH_LMS_VERSION );
        wp_enqueue_script( 'kh-lms-admin', KH_LMS_URL . 'assets/js/admin.js', [], KH_LMS_VERSION, true );
    }

    public static function menu(): void {
        add_menu_page( 'مدیریت دوره‌ها', 'مدیریت دوره‌ها', 'manage_options', 'kh-lms', [ self::class, 'dashboard' ], 'dashicons-welcome-learn-more', 26 );
        add_submenu_page( 'kh-lms', 'افزودن دوره جدید', 'افزودن دوره جدید', 'manage_options', 'kh-lms-edit', [ self::class, 'edit_course_page' ] );
        add_submenu_page( 'kh-lms', 'تنظیمات', 'تنظیمات', 'manage_options', 'kh-lms-settings', [ self::class, 'settings_page' ] );
        add_submenu_page( 'kh-lms', 'گواهی‌ها', 'گواهی‌ها', 'manage_options', 'kh-lms-certificates', [ self::class, 'certificates_page' ] );
    }

    public static function dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی غیرمجاز' ); }
        global $wpdb;
        $courses_table = Repository::table( 'courses' );
        $enrollments_table = Repository::table( 'enrollments' );
        $certificates_table = Repository::table( 'certificates' );
        $courses = Repository::courses();
        $count_total = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $courses_table );
        $count_publish = (int) $wpdb->get_var( $wpdb->prepare( 'SELECT COUNT(*) FROM ' . $courses_table . ' WHERE status = %s', 'publish' ) );
        $count_enrolled = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $enrollments_table );
        $count_certificates = (int) $wpdb->get_var( 'SELECT COUNT(*) FROM ' . $certificates_table );
        echo '<div class="wrap kh-lms-admin"><h1>مدیریت دوره‌ها</h1><div class="kh-grid">';
        foreach ( [ 'تعداد کل دوره‌ها' => $count_total, 'دوره‌های منتشرشده' => $count_publish, 'تعداد ثبت‌نام‌ها' => $count_enrolled, 'تعداد گواهی‌ها' => $count_certificates ] as $label => $value ) {
            echo '<div class="kh-card"><h3>' . esc_html( $label ) . '</h3><p>' . esc_html( (string) $value ) . '</p></div>';
        }
        echo '</div><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=kh-lms-edit' ) ) . '">افزودن دوره جدید</a></p><div class="kh-grid">';
        foreach ( $courses as $course ) {
            echo '<div class="kh-card"><h2>' . esc_html( $course->title ) . '</h2><p>' . esc_html( $course->course_code ) . '</p><a href="' . esc_url( admin_url( 'admin.php?page=kh-lms-edit&id=' . (int) $course->id ) ) . '">ویرایش دوره</a></div>';
        }
        echo '</div></div>';
    }

    public static function settings_page(): void { echo Settings::render_page(); }
    public static function certificates_page(): void { echo CertificateManager::render_admin_page(); }

    public static function edit_course_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی غیرمجاز' ); }
        $course_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $course = $course_id ? Repository::course( $course_id ) : null;
        $curriculum = $course ? Repository::curriculum( $course_id ) : [];
        echo '<div class="wrap kh-lms-admin"><h1>' . esc_html( $course ? 'ویرایش دوره' : 'افزودن دوره جدید' ) . '</h1><form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="kh_lms_save_course"><input type="hidden" name="course_id" value="' . esc_attr( (string) $course_id ) . '">';
        wp_nonce_field( 'kh_lms_save_course' );
        echo '<div class="kh-card"><h2>اطلاعات اصلی</h2><label>عنوان دوره<input required name="title" value="' . esc_attr( $course->title ?? '' ) . '"></label><label>Slug<input name="slug" value="' . esc_attr( $course->slug ?? '' ) . '"></label><label>توضیح کوتاه<textarea name="short_description">' . esc_textarea( $course->short_description ?? '' ) . '</textarea></label><label>توضیحات کامل<textarea name="description" rows="6">' . esc_textarea( $course->description ?? '' ) . '</textarea></label><label>نوع دوره<select name="type"><option value="free" ' . selected( $course->type ?? 'free', 'free', false ) . '>رایگان</option><option value="paid" ' . selected( $course->type ?? 'free', 'paid', false ) . '>پولی</option></select></label><label>وضعیت<select name="status"><option value="draft" ' . selected( $course->status ?? 'draft', 'draft', false ) . '>پیش‌نویس</option><option value="publish" ' . selected( $course->status ?? 'draft', 'publish', false ) . '>منتشر شده</option></select></label></div>';
        echo '<div class="kh-card"><h2>ساختار دوره</h2><div id="kh-curriculum">';
        if ( empty( $curriculum ) ) {
            echo self::chapter_markup( 0, null );
        } else {
            foreach ( $curriculum as $chapter_index => $chapter ) { echo self::chapter_markup( $chapter_index, $chapter ); }
        }
        echo '</div><button type="button" class="button" id="kh-add-chapter">افزودن فصل</button></div><p><button class="button button-primary">ذخیره دوره</button></p></form></div>';
    }

    private static function chapter_markup( int $index, ?object $chapter ): string {
        $html = '<div class="kh-chapter" data-order="' . esc_attr( (string) $index ) . '"><div class="kh-chapter-header"><input required name="chapters[' . $index . '][title]" placeholder="عنوان فصل" value="' . esc_attr( $chapter->title ?? '' ) . '"></div><div class="kh-lessons">';
        $lessons = $chapter && ! empty( $chapter->lessons ) ? $chapter->lessons : [ null ];
        foreach ( $lessons as $lesson_index => $lesson ) {
            $html .= '<div class="kh-lesson"><input required name="chapters[' . $index . '][lessons][' . $lesson_index . '][title]" placeholder="عنوان درس" value="' . esc_attr( $lesson->title ?? '' ) . '"><select name="chapters[' . $index . '][lessons][' . $lesson_index . '][content_type]"><option value="text" ' . selected( $lesson->content_type ?? 'text', 'text', false ) . '>متن</option><option value="video" ' . selected( $lesson->content_type ?? '', 'video', false ) . '>ویدئو</option></select><input name="chapters[' . $index . '][lessons][' . $lesson_index . '][video_url]" placeholder="URL ویدئو" value="' . esc_attr( $lesson->video_url ?? '' ) . '"><label><input type="checkbox" name="chapters[' . $index . '][lessons][' . $lesson_index . '][is_preview]" value="1" ' . checked( (int) ( $lesson->is_preview ?? 0 ), 1, false ) . '> پیش‌نمایش</label></div>';
        }
        return $html . '</div><button type="button" class="button kh-add-lesson">افزودن درس</button></div>';
    }

    public static function save_course(): void {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kh_lms_save_course' ) ) { wp_die( 'درخواست نامعتبر' ); }
        global $wpdb;
        $course_id = absint( $_POST['course_id'] ?? 0 );
        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        if ( '' === $title ) { wp_die( 'عنوان دوره الزامی است.' ); }
        $now = current_time( 'mysql', true );
        $data = [ 'title' => $title, 'slug' => sanitize_title( wp_unslash( $_POST['slug'] ?? $title ) ), 'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ), 'short_description' => sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) ), 'type' => sanitize_key( wp_unslash( $_POST['type'] ?? 'free' ) ), 'status' => sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) ), 'author_id' => get_current_user_id(), 'updated_at' => $now ];
        if ( $course_id ) { $wpdb->update( Repository::table( 'courses' ), $data, [ 'id' => $course_id ] ); } else { $data['course_code'] = Utils::course_code(); $data['created_at'] = $now; $wpdb->insert( Repository::table( 'courses' ), $data ); $course_id = (int) $wpdb->insert_id; }
        $wpdb->delete( Repository::table( 'chapters' ), [ 'course_id' => $course_id ] );
        $wpdb->delete( Repository::table( 'lessons' ), [ 'course_id' => $course_id ] );
        foreach ( (array) ( $_POST['chapters'] ?? [] ) as $chapter_index => $chapter_data ) {
            $chapter_title = sanitize_text_field( wp_unslash( $chapter_data['title'] ?? '' ) );
            if ( '' === $chapter_title ) { continue; }
            $wpdb->insert( Repository::table( 'chapters' ), [ 'course_id' => $course_id, 'title' => $chapter_title, 'description' => '', 'sort_order' => absint( $chapter_index ), 'status' => 'publish', 'created_at' => $now, 'updated_at' => $now ] );
            $chapter_id = (int) $wpdb->insert_id;
            foreach ( (array) ( $chapter_data['lessons'] ?? [] ) as $lesson_index => $lesson_data ) {
                $lesson_title = sanitize_text_field( wp_unslash( $lesson_data['title'] ?? '' ) );
                if ( '' === $lesson_title ) { continue; }
                $wpdb->insert( Repository::table( 'lessons' ), [ 'course_id' => $course_id, 'chapter_id' => $chapter_id, 'title' => $lesson_title, 'slug' => sanitize_title( $lesson_title . '-' . $course_id . '-' . $lesson_index ), 'description' => '', 'content_type' => sanitize_key( wp_unslash( $lesson_data['content_type'] ?? 'text' ) ), 'content' => '', 'video_url' => esc_url_raw( wp_unslash( $lesson_data['video_url'] ?? '' ) ), 'duration' => 0, 'sort_order' => absint( $lesson_index ), 'is_preview' => isset( $lesson_data['is_preview'] ) ? 1 : 0, 'is_free' => 0, 'status' => 'publish', 'created_at' => $now, 'updated_at' => $now ] );
            }
        }
        wp_safe_redirect( admin_url( 'admin.php?page=kh-lms-edit&id=' . $course_id . '&updated=1' ) ); exit;
    }
}
