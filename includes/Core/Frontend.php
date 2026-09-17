<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Admin {
    public static function hooks(): void {
        add_action( 'admin_menu', [ self::class, 'menu' ] );
        add_action( 'admin_post_kh_lms_save_course', [ self::class, 'save_course' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'assets' ] );
    }

    public static function menu(): void {
        add_menu_page(
            'مدیریت دوره‌ها',
            'مدیریت دوره‌ها',
            'manage_options',
            'kh-lms',
            [ self::class, 'dashboard' ],
            'dashicons-welcome-learn-more',
            26
        );

        add_submenu_page(
            'kh-lms',
            'افزودن دوره جدید',
            'افزودن دوره جدید',
            'manage_options',
            'kh-lms-edit',
            [ self::class, 'edit_course_page' ]
        );
    }

    public static function assets( string $hook ): void {
        if ( false === strpos( $hook, 'kh-lms' ) ) {
            return;
        }

        wp_enqueue_style( 'kh-lms-admin', KH_LMS_URL . 'assets/css/admin.css', [], KH_LMS_VERSION );
        wp_enqueue_script( 'kh-lms-admin', KH_LMS_URL . 'assets/js/admin.js', [], KH_LMS_VERSION, true );
    }

    public static function dashboard(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'دسترسی غیرمجاز' );
        }

        $courses = Repository::courses();

        echo '<div class="wrap kh-lms-admin"><h1>مدیریت دوره‌ها</h1><div style="margin:20px 0;"><a class="button button-primary" href="' . esc_url( admin_url( 'admin.php?page=kh-lms-edit' ) ) . '">افزودن دوره جدید</a></div>';
        echo '<div class="kh-grid">';

        foreach ( $courses as $course ) {
            echo '<div class="kh-card">';
            echo '<h2>' . esc_html( $course->title ) . '</h2>';
            echo '<p><strong>کد دوره:</strong> ' . esc_html( $course->course_code ) . '</p>';
            echo '<p><strong>وضعیت:</strong> ' . esc_html( $course->status ) . '</p>';
            echo '<p><strong>نوع:</strong> ' . esc_html( $course->type ) . '</p>';
            echo '<p><strong>تاریخ:</strong> ' . esc_html( mysql2date( get_option( 'date_format' ), $course->created_at ) ) . '</p>';
            echo '<p><a href="' . esc_url( admin_url( 'admin.php?page=kh-lms-edit&id=' . (int) $course->id ) ) . '">ویرایش دوره</a></p>';
            echo '</div>';
        }

        echo '</div></div>';
    }

    public static function edit_course_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'دسترسی غیرمجاز' );
        }

        $course_id = isset( $_GET['id'] ) ? absint( $_GET['id'] ) : 0;
        $course    = $course_id ? Repository::course( $course_id ) : null;
        $curriculum = $course ? Repository::curriculum( $course_id ) : [];
        $nonce = wp_create_nonce( 'kh_lms_save_course' );

        echo '<div class="wrap kh-lms-admin">';
        echo '<h1>' . ( $course ? 'ویرایش دوره' : 'افزودن دوره جدید' ) . '</h1>';
        echo '<form method="post" action="' . esc_url( admin_url( 'admin-post.php' ) ) . '">';
        echo '<input type="hidden" name="action" value="kh_lms_save_course">';
        echo '<input type="hidden" name="course_id" value="' . esc_attr( (string) $course_id ) . '">';
        echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';

        echo '<div class="kh-card">';
        echo '<h2>اطلاعات اصلی</h2>';
        echo '<label>عنوان دوره<input type="text" name="title" value="' . esc_attr( $course->title ?? '' ) . '" required></label>';
        echo '<label>Slug<input type="text" name="slug" value="' . esc_attr( $course->slug ?? '' ) . '"></label>';
        echo '<label>توضیح کوتاه<textarea name="short_description">' . esc_textarea( $course->short_description ?? '' ) . '</textarea></label>';
        echo '<label>توضیحات کامل<textarea name="description" rows="6">' . esc_textarea( $course->description ?? '' ) . '</textarea></label>';
        echo '<label>مدرس<input type="text" name="author_name" value="' . esc_attr( get_userdata( $course->author_id ?? get_current_user_id() )->display_name ?? '' ) . '"></label>';
        echo '<label>نوع دوره<select name="type"><option value="free" ' . selected( $course->type ?? 'free', 'free', false ) . '>رایگان</option><option value="paid" ' . selected( $course->type ?? 'free', 'paid', false ) . '>پولی</option></select></label>';
        echo '<label>وضعیت<select name="status"><option value="draft" ' . selected( $course->status ?? 'draft', 'draft', false ) . '>پیش‌نویس</option><option value="publish" ' . selected( $course->status ?? 'draft', 'publish', false ) . '>منتشر شده</option></select></label>';
        echo '</div>';

        echo '<div class="kh-card">';
        echo '<h2>ساختار دوره</h2>';
        echo '<div id="kh-curriculum">';

        if ( empty( $curriculum ) ) {
            echo '<div class="kh-chapter" data-order="0">';
            echo '<div class="kh-chapter-header"><input type="text" name="chapters[0][title]" placeholder="عنوان فصل" required></div>';
            echo '<div class="kh-lessons">';
            echo '<div class="kh-lesson" data-order="0">';
            echo '<input type="text" name="chapters[0][lessons][0][title]" placeholder="عنوان درس" required>';
            echo '<select name="chapters[0][lessons][0][content_type]"><option value="text">متن</option><option value="video">ویدئو</option></select>';
            echo '<input type="text" name="chapters[0][lessons][0][video_url]" placeholder="URL ویدئو">';
            echo '<label><input type="checkbox" name="chapters[0][lessons][0][is_preview]" value="1"> پیش‌نمایش</label>';
            echo '</div>';
            echo '</div>';
            echo '<button type="button" class="button kh-add-lesson">افزودن درس</button>';
            echo '</div>';
        } else {
            foreach ( $curriculum as $chapter_index => $chapter ) {
                echo '<div class="kh-chapter" data-order="' . esc_attr( (string) $chapter_index ) . '">';
                echo '<div class="kh-chapter-header"><input type="text" name="chapters[' . (int) $chapter_index . '][title]" value="' . esc_attr( $chapter->title ) . '" required></div>';
                echo '<div class="kh-lessons">';

                foreach ( $chapter->lessons as $lesson_index => $lesson ) {
                    echo '<div class="kh-lesson" data-order="' . esc_attr( (string) $lesson_index ) . '">';
                    echo '<input type="text" name="chapters[' . (int) $chapter_index . '][lessons][' . (int) $lesson_index . '][title]" value="' . esc_attr( $lesson->title ) . '" required>';
                    echo '<select name="chapters[' . (int) $chapter_index . '][lessons][' . (int) $lesson_index . '][content_type]"><option value="text" ' . selected( $lesson->content_type, 'text', false ) . '>متن</option><option value="video" ' . selected( $lesson->content_type, 'video', false ) . '>ویدئو</option></select>';
                    echo '<input type="text" name="chapters[' . (int) $chapter_index . '][lessons][' . (int) $lesson_index . '][video_url]" value="' . esc_attr( $lesson->video_url ?? '' ) . '" placeholder="URL ویدئو">';
                    echo '<label><input type="checkbox" name="chapters[' . (int) $chapter_index . '][lessons][' . (int) $lesson_index . '][is_preview]" value="1" ' . checked( (int) $lesson->is_preview, 1, false ) . '> پیش‌نمایش</label>';
                    echo '</div>';
                }

                echo '</div>';
                echo '<button type="button" class="button kh-add-lesson">افزودن درس</button>';
                echo '</div>';
            }
        }

        echo '</div>';
        echo '<button type="button" class="button" id="kh-add-chapter">افزودن فصل</button>';
        echo '</div>';

        echo '<p><button type="submit" class="button button-primary">ذخیره دوره</button></p>';
        echo '</form></div>';
    }

    public static function save_course(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'دسترسی غیرمجاز' );
        }

        if ( ! Utils::enforce_nonce() ) {
            wp_die( 'درخواست نامعتبر' );
        }

        global $wpdb;

        $course_id = absint( $_POST['course_id'] ?? 0 );
        $title     = sanitize_text_field( wp_unslash( $_POST['title'] ?? '' ) );
        $slug      = sanitize_title( wp_unslash( $_POST['slug'] ?? $title ) );

        if ( '' === $title ) {
            wp_die( 'عنوان دوره الزامی است.' );
        }

        $now     = current_time( 'mysql', true );
        $type    = sanitize_key( wp_unslash( $_POST['type'] ?? 'free' ) );
        $status  = sanitize_key( wp_unslash( $_POST['status'] ?? 'draft' ) );
        $desc    = wp_kses_post( wp_unslash( $_POST['description'] ?? '' ) );
        $short   = sanitize_textarea_field( wp_unslash( $_POST['short_description'] ?? '' ) );
        $author_id = get_current_user_id();

        $course_data = [
            'title'            => $title,
            'slug'             => $slug,
            'description'      => $desc,
            'short_description'=> $short,
            'type'             => $type,
            'status'           => $status,
            'author_id'        => $author_id,
            'updated_at'       => $now,
        ];

        if ( $course_id ) {
            $wpdb->update( Repository::table( 'courses' ), $course_data, [ 'id' => $course_id ] );
        } else {
            $course_data['course_code'] = Utils::course_code();
            $course_data['created_at']  = $now;
            $course_data['author_id']   = $author_id;
            $course_data['status']      = $status;
            $wpdb->insert( Repository::table( 'courses' ), $course_data );
            $course_id = (int) $wpdb->insert_id;
        }

        $wpdb->delete( Repository::table( 'chapters' ), [ 'course_id' => $course_id ] );
        $wpdb->delete( Repository::table( 'lessons' ), [ 'course_id' => $course_id ] );

        $chapters = $_POST['chapters'] ?? [];
        if ( is_array( $chapters ) ) {
            foreach ( $chapters as $chapter_index => $chapter_data ) {
                $chapter_title = sanitize_text_field( wp_unslash( $chapter_data['title'] ?? '' ) );
                if ( '' === $chapter_title ) {
                    continue;
                }

                $wpdb->insert(
                    Repository::table( 'chapters' ),
                    [
                        'course_id'  => $course_id,
                        'title'      => $chapter_title,
                        'description'=> '',
                        'sort_order' => absint( $chapter_index ),
                        'status'     => 'publish',
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );

                $chapter_id = (int) $wpdb->insert_id;
                $lessons    = $chapter_data['lessons'] ?? [];
                if ( ! is_array( $lessons ) ) {
                    continue;
                }

                foreach ( $lessons as $lesson_index => $lesson_data ) {
                    $lesson_title = sanitize_text_field( wp_unslash( $lesson_data['title'] ?? '' ) );
                    if ( '' === $lesson_title ) {
                        continue;
                    }

                    $content_type = sanitize_key( wp_unslash( $lesson_data['content_type'] ?? 'text' ) );
                    $video_url    = sanitize_url( wp_unslash( $lesson_data['video_url'] ?? '' ) );
                    $is_preview   = isset( $lesson_data['is_preview'] ) ? 1 : 0;

                    $wpdb->insert(
                        Repository::table( 'lessons' ),
                        [
                            'course_id'    => $course_id,
                            'chapter_id'   => $chapter_id,
                            'title'        => $lesson_title,
                            'slug'         => sanitize_title( $lesson_title . '-' . ( $course_id + $lesson_index ) ),
                            'description'  => '',
                            'content_type' => $content_type,
                            'content'      => '',
                            'video_url'    => $video_url,
                            'duration'     => 0,
                            'sort_order'   => absint( $lesson_index ),
                            'is_preview'   => $is_preview,
                            'is_free'      => 0,
                            'status'       => 'publish',
                            'created_at'   => $now,
                            'updated_at'   => $now,
                        ]
                    );
                }
            }
        }

        wp_safe_redirect( admin_url( 'admin.php?page=kh-lms-edit&id=' . $course_id . '&updated=1' ) );
        exit;
    }
}
