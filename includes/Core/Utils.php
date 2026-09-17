<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Schema {
    public static function install(): void {
        global $wpdb;

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';

        $charset = $wpdb->get_charset_collate();
        $prefix  = $wpdb->prefix . 'kh_lms_';

        dbDelta(
            "CREATE TABLE {$prefix}courses (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                course_code VARCHAR(32) NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(200) NOT NULL,
                description LONGTEXT NOT NULL,
                short_description TEXT NOT NULL,
                thumbnail BIGINT UNSIGNED NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'draft',
                type VARCHAR(20) NOT NULL DEFAULT 'free',
                author_id BIGINT UNSIGNED NOT NULL,
                product_id BIGINT UNSIGNED NULL,
                completion_percent TINYINT UNSIGNED NOT NULL DEFAULT 90,
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY course_code (course_code),
                UNIQUE KEY slug (slug),
                KEY status_index (status)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$prefix}chapters (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                course_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                description TEXT NOT NULL,
                sort_order INT NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'publish',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY course_index (course_id),
                KEY sort_index (course_id, sort_order)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$prefix}lessons (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                course_id BIGINT UNSIGNED NOT NULL,
                chapter_id BIGINT UNSIGNED NOT NULL,
                title VARCHAR(255) NOT NULL,
                slug VARCHAR(200) NOT NULL,
                description LONGTEXT NOT NULL,
                content_type VARCHAR(20) NOT NULL DEFAULT 'text',
                content LONGTEXT NOT NULL,
                video_url TEXT NULL,
                duration INT UNSIGNED NOT NULL DEFAULT 0,
                sort_order INT NOT NULL DEFAULT 0,
                is_preview TINYINT(1) NOT NULL DEFAULT 0,
                is_free TINYINT(1) NOT NULL DEFAULT 0,
                status VARCHAR(20) NOT NULL DEFAULT 'publish',
                created_at DATETIME NOT NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                KEY course_index (course_id),
                KEY chapter_index (chapter_id),
                KEY sort_index (chapter_id, sort_order)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$prefix}enrollments (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                course_id BIGINT UNSIGNED NOT NULL,
                order_id BIGINT UNSIGNED NULL,
                product_id BIGINT UNSIGNED NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'active',
                expires_at DATETIME NULL,
                started_at DATETIME NULL,
                completed_at DATETIME NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY user_course (user_id, course_id),
                KEY course_status_index (course_id, status),
                KEY user_status_index (user_id, status)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$prefix}progress (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                course_id BIGINT UNSIGNED NOT NULL,
                lesson_id BIGINT UNSIGNED NOT NULL,
                watched_seconds INT UNSIGNED NOT NULL DEFAULT 0,
                duration INT UNSIGNED NOT NULL DEFAULT 0,
                percentage DECIMAL(5,2) NOT NULL DEFAULT 0,
                last_position INT UNSIGNED NOT NULL DEFAULT 0,
                completed TINYINT(1) NOT NULL DEFAULT 0,
                completed_at DATETIME NULL,
                updated_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY user_lesson (user_id, lesson_id),
                KEY course_index (course_id),
                KEY user_index (user_id)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$prefix}tokens (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                lesson_id BIGINT UNSIGNED NOT NULL,
                token_hash CHAR(64) NOT NULL,
                expires_at DATETIME NOT NULL,
                ip_hash CHAR(64) NULL,
                user_agent_hash CHAR(64) NULL,
                created_at DATETIME NOT NULL,
                PRIMARY KEY (id),
                UNIQUE KEY token_hash (token_hash),
                KEY user_lesson_index (user_id, lesson_id),
                KEY expires_index (expires_at)
            ) {$charset};"
        );

        dbDelta(
            "CREATE TABLE {$prefix}certificates (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                course_id BIGINT UNSIGNED NOT NULL,
                certificate_number VARCHAR(40) NOT NULL,
                verification_code CHAR(32) NOT NULL,
                issued_at DATETIME NOT NULL,
                file_path VARCHAR(255) NULL,
                status VARCHAR(20) NOT NULL DEFAULT 'issued',
                PRIMARY KEY (id),
                UNIQUE KEY certificate_number (certificate_number),
                UNIQUE KEY verification_code (verification_code),
                KEY user_course_index (user_id, course_id)
            ) {$charset};"
        );

        update_option( 'kh_lms_version', KH_LMS_VERSION );
    }
}
