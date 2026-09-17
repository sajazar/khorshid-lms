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
        $courses = Repository::courses( [ 'status' => 'any' ] );
        $table = Repository::table( 'courses' );
        $published = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status=%s", 'publish' ) );
        echo '<div class="wrap kh-lms-admin"><h1>مدیریت دوره‌ها</h1><div class="kh-grid">';
        foreach ( [ 'کل دوره‌ها' => count( $courses ), 'منتشرشده' => $published ] as $label => $value ) {
            echo '<div class="kh-card"><h3>' . esc_html( $label ) . '</h3><p>' . esc_html( (string) $value ) . '</p></div>';
        }
        echo '</div><p><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=kh-lms-edit' ) ) . '">افزودن دوره جدید</a></p><div class="kh-grid">';
        foreach ( $courses as $course ) {
            echo '<div class="kh-card"><h2>' . esc_html( $course->title ) . '</h2><p>' . esc_html( $course->course_code ) . '</p><p>وضعیت: ' . esc_html( $course->status ) . '</p><a href="' . esc_url( admin_url( 'admin.php?page=kh-lms-edit&id=' . (int) $course->id ) ) . '">ویرایش دوره</a></div>';
        }
        echo '</div></div>';
    }

    public static function settings_page(): void { echo Settings::render_page(); }
    public static function certificates_page(): void { echo CertificateManager::render_admin_page(); }

    public static function edit_course_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) { wp_die( 'دسترسی غیرمجاز' ); }
        $id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $course = $id ? Repository::course( $id ) : null;
        $curriculum = $course ? Repository::curriculum( $id ) : [];
        echo '<div class="wrap kh-lms-admin"><h1>' . esc_html( $course ? 'ویرایش دوره' : 'افزودن دوره جدید' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '"><input type="hidden" name="action" value="kh_lms_save_course"><input type="hidden" name="course_id" value="' . esc_attr( $id ) . '">';
        wp_nonce_field( 'kh_lms_save_course' );
        echo '<div class="kh-card"><h2>اطلاعات دوره</h2><label>عنوان<input required name="title" value="' . esc_attr( $course->title ?? '' ) . '"></label><label>Slug<input name="slug" value="' . esc_attr( $course->slug ?? '' ) . '"></label><label>توضیح کوتاه<textarea name="short_description">' . esc_textarea( $course->short_description ?? '' ) . '</textarea></label><label>توضیحات کامل<textarea name="description" rows="6">' . esc_textarea( $course->description ?? '' ) . '</textarea></label><label>نوع دوره<select name="type"><option value="free" ' . selected( $course->type ?? 'free', 'free', false ) . '>رایگان</option><option value="paid" ' . selected( $course->type ?? 'free', 'paid', false ) . '>پولی</option></select></label><label>وضعیت<select name="status"><option value="draft" ' . selected( $course->status ?? 'draft', 'draft', false ) . '>پیش‌نویس</option><option value="publish" ' . selected( $course->status ?? 'draft', 'publish', false ) . '>منتشر شده</option></select></label></div>';
        echo '<div class="kh-card"><h2>فصل‌ها و درس‌ها</h2><p class="description">هر درس حتماً داخل فصل خودش ذخیره می‌شود. ترتیب و نام‌ها قبل از ارسال در سمت سرور نرمال‌سازی می‌شوند.</p><div id="kh-curriculum">';
        if ( empty( $curriculum ) ) { echo self::chapter_markup( 0, null ); } else { foreach ( $curriculum as $i => $chapter ) { echo self::chapter_markup( $i, $chapter ); } }
        echo '</div><button type="button" class="button" id="kh-add-chapter">افزودن فصل</button></div><p><button type="submit" class="button button-primary">ذخیره دوره و درس‌ها</button></p></form></div>';
    }

    private static function chapter_markup( int $index, ?object $chapter ): string {
        $html = '<div class="kh-chapter" data-order="' . esc_attr( (string) $index ) . '"><div class="kh-chapter-header"><strong>فصل ' . esc_html( (string) ( $index + 1 ) ) . '</strong><input required name="chapters[' . $index . '][title]" placeholder="عنوان فصل" value="' . esc_attr( $chapter->title ?? '' ) . '"></div><div class="kh-lessons">';
        $lessons = ( $chapter && ! empty( $chapter->lessons ) ) ? $chapter->lessons : [ null ];
        foreach ( $lessons as $j => $lesson ) {
            $html .= self::lesson_markup( $index, $j, $lesson );
        }
        return $html . '</div><button type="button" class="button kh-add-lesson">افزودن درس به این فصل</button></div>';
    }

    private static function lesson_markup( int $chapter, int $lesson_index, ?object $lesson ): string {
        return '<div class="kh-lesson"><input required name="chapters[' . $chapter . '][lessons][' . $lesson_index . '][title]" placeholder="عنوان درس" value="' . esc_attr( $lesson->title ?? '' ) . '"><select name="chapters[' . $chapter . '][lessons][' . $lesson_index . '][content_type]"><option value="text" ' . selected( $lesson->content_type ?? 'text', 'text', false ) . '>متن</option><option value="video" ' . selected( $lesson->content_type ?? '', 'video', false ) . '>ویدئو</option></select><input name="chapters[' . $chapter . '][lessons][' . $lesson_index . '][video_url]" placeholder="URL ویدئو (فقط سمت سرور)" value="' . esc_attr( $lesson->video_url ?? '' ) . '"><label><input type="checkbox" name="chapters[' . $chapter . '][lessons][' . $lesson_index . '][is_preview]" value="1" ' . checked( (int) ( $lesson->is_preview ?? 0 ), 1, false ) . '> پیش‌نمایش</label></div>';
    }

    public static function save_course(): void {
        if ( ! current_user_can( 'manage_options' ) || ! check_admin_referer( 'kh_lms_save_course' ) ) { wp_die( 'درخواست نامعتبر' ); }
        global $wpdb;
        $id = absint( $_POST['course_id'] ?? 0 );
        $title = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        if ( '' === $title ) { wp_die( 'عنوان دوره الزامی است.' ); }
        $now = current_time( 'mysql', true );
        $data = [ 'title' => $title, 'slug' => sanitize_title( wp_unslash( $_POST['slug'] ?? $title ) ), 'description' => wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) ), 'short_description' => sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) ), 'type' => in_array( $_POST['type'] ?? 'free', [ 'free', 'paid' ], true ) ? sanitize_key( $_POST['type'] ) : 'free', 'status' => in_array( $_POST['status'] ?? 'draft', [ 'draft', 'publish' ], true ) ? sanitize_key( $_POST['status'] ) : 'draft', 'author_id' => get_current_user_id(), 'updated_at' => $now ];
        if ( $id ) { $wpdb->update( Repository::table( 'courses' ), $data, [ 'id' => $id ] ); } else { $data['course_code'] = Utils::course_code(); $data['created_at'] = $now; $wpdb->insert( Repository::table( 'courses' ), $data ); $id = (int) $wpdb->insert_id; }
        if ( ! $id ) { wp_die( 'ذخیره دوره انجام نشد.' ); }

        // Delete children in the correct order, then rebuild the submitted curriculum atomically.
        $wpdb->delete( Repository::table( 'lessons' ), [ 'course_id' => $id ] );
        $wpdb->delete( Repository::table( 'chapters' ), [ 'course_id' => $id ] );
        $chapters = isset( $_POST['chapters'] ) && is_array( $_POST['chapters'] ) ? wp_unslash( $_POST['chapters'] ) : [];
        foreach ( array_values( $chapters ) as $chapter_index => $chapter_data ) {
            if ( ! is_array( $chapter_data ) ) { continue; }
            $chapter_title = sanitize_text_field( $chapter_data['title'] ?? '' );
            if ( '' === $chapter_title ) { continue; }
            $wpdb->insert( Repository::table( 'chapters' ), [ 'course_id' => $id, 'title' => $chapter_title, 'description' => '', 'sort_order' => $chapter_index, 'status' => 'publish', 'created_at' => $now, 'updated_at' => $now ], [ '%d', '%s', '%s', '%d', '%s', '%s', '%s' ] );
            $chapter_id = (int) $wpdb->insert_id;
            if ( ! $chapter_id ) { continue; }
            $lessons = isset( $chapter_data['lessons'] ) && is_array( $chapter_data['lessons'] ) ? $chapter_data['lessons'] : [];
            foreach ( array_values( $lessons ) as $lesson_index => $lesson_data ) {
                if ( ! is_array( $lesson_data ) ) { continue; }
                $lesson_title = sanitize_text_field( $lesson_data['title'] ?? '' );
                if ( '' === $lesson_title ) { continue; }
                $type = in_array( $lesson_data['content_type'] ?? 'text', [ 'text', 'video' ], true ) ? sanitize_key( $lesson_data['content_type'] ) : 'text';
                $wpdb->insert( Repository::table( 'lessons' ), [ 'course_id' => $id, 'chapter_id' => $chapter_id, 'title' => $lesson_title, 'slug' => sanitize_title( $lesson_title . '-' . $id . '-' . $lesson_index ), 'description' => '', 'content_type' => $type, 'content' => '', 'video_url' => esc_url_raw( $lesson_data['video_url'] ?? '' ), 'duration' => 0, 'sort_order' => $lesson_index, 'is_preview' => empty( $lesson_data['is_preview'] ) ? 0 : 1, 'is_free' => 0, 'status' => 'publish', 'created_at' => $now, 'updated_at' => $now ] );
            }
        }
        wp_safe_redirect( admin_url( 'admin.php?page=kh-lms-edit&id=' . $id . '&updated=1' ) ); exit;
    }
}
