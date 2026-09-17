<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class CertificateManager {
    public static function hooks(): void {
        add_shortcode( 'kh_lms_certificate', [ self::class, 'certificate_shortcode' ] );
        add_shortcode( 'kh_lms_verify_certificate', [ self::class, 'verify_shortcode' ] );
    }

    public static function maybe_issue_for_user( int $user_id, int $course_id ): ?string {
        if ( ! $user_id || ! $course_id ) {
            return null;
        }

        if ( '1' !== Settings::get( 'certificate_enabled', '1' ) ) {
            return null;
        }

        global $wpdb;
        $exists = $wpdb->get_var(
            $wpdb->prepare(
                'SELECT verification_code FROM ' . Repository::table( 'certificates' ) . ' WHERE user_id = %d AND course_id = %d LIMIT 1',
                $user_id,
                $course_id
            )
        );

        if ( $exists ) {
            return (string) $exists;
        }

        if ( ! Repository::is_course_completed( $user_id, $course_id ) ) {
            return null;
        }

        $course = Repository::course( $course_id );
        if ( ! $course ) {
            return null;
        }

        $verification_code = wp_generate_password( 16, false, false );
        $number            = 'KH-' . gmdate( 'Y' ) . '-' . str_pad( (string) ( $wpdb->get_var( 'SELECT COUNT(*) FROM ' . Repository::table( 'certificates' ) ) + 1 ), 6, '0', STR_PAD_LEFT );

        $file_path = 'khorshid-lms-storage/certificates/' . $user_id . '-' . $course_id . '-' . time() . '.html';
        $full_path = wp_upload_dir()['basedir'] . '/' . $file_path;

        wp_mkdir_p( dirname( $full_path ) );

        $html = '<!doctype html><html><head><meta charset="utf-8"><title>Certificate</title><style>body{font-family:Tahoma,sans-serif;text-align:center;padding:80px;background:#f8fafc;color:#111827;} .box{border:2px solid #4f46e5;border-radius:16px;padding:40px;max-width:800px;margin:auto;}.small{color:#555;}</style></head><body><div class="box"><h1>گواهی پایان دوره</h1><p>این گواهی به نام</p><h2>' . esc_html( get_userdata( $user_id )->display_name ?? 'کاربر' ) . '</h2><p>برای تکمیل دوره</p><h3>' . esc_html( $course->title ) . '</h3><p class="small">شماره گواهی: ' . esc_html( $number ) . '</p><p class="small">کد اعتبارسنجی: ' . esc_html( $verification_code ) . '</p></div></body></html>';

        file_put_contents( $full_path, $html );

        $wpdb->insert(
            Repository::table( 'certificates' ),
            [
                'user_id'          => $user_id,
                'course_id'        => $course_id,
                'certificate_number'=> $number,
                'verification_code'=> $verification_code,
                'issued_at'        => current_time( 'mysql', true ),
                'file_path'        => $file_path,
                'status'           => 'issued',
            ],
            [ '%d', '%d', '%s', '%s', '%s', '%s', '%s' ]
        );

        return $verification_code;
    }

    public static function certificate_shortcode( array $atts = [] ): string {
        $user_id = get_current_user_id();
        $course_id = isset( $atts['course_id'] ) ? absint( $atts['course_id'] ) : ( isset( $atts['id'] ) ? absint( $atts['id'] ) : 0 );

        if ( ! $user_id || ! $course_id ) {
            return '<p>داده‌های گواهی نامعتبر است.</p>';
        }

        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . Repository::table( 'certificates' ) . ' WHERE user_id = %d AND course_id = %d LIMIT 1',
                $user_id,
                $course_id
            )
        );

        if ( ! $row ) {
            return '<p>گواهی برای این دوره صادر نشده است.</p>';
        }

        $url = esc_url( home_url( '/certificate/verify/' . rawurlencode( $row->verification_code ) ) );

        return '<div class="kh-card"><h3>گواهی شما</h3><p>شماره گواهی: ' . esc_html( $row->certificate_number ) . '</p><p><a href="' . $url . '">مشاهده و تایید گواهی</a></p></div>';
    }

    public static function verify_shortcode(): string {
        $code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';
        if ( '' === $code ) {
            return '<p>کد اعتبارسنجی وارد نشده است.</p>';
        }

        global $wpdb;
        $row = $wpdb->get_row(
            $wpdb->prepare(
                'SELECT * FROM ' . Repository::table( 'certificates' ) . ' WHERE verification_code = %s LIMIT 1',
                $code
            )
        );

        if ( ! $row ) {
            return '<p>گواهی نامعتبر است.</p>';
        }

        return '<div class="kh-card"><h3>گواهی معتبر</h3><p>این گواهی معتبر است.</p><p>نام کاربر: ' . esc_html( get_userdata( $row->user_id )->display_name ?? 'کاربر' ) . '</p><p>شماره گواهی: ' . esc_html( $row->certificate_number ) . '</p></div>';
    }

    public static function render_admin_page(): string {
        global $wpdb;
        $rows = $wpdb->get_results( 'SELECT * FROM ' . Repository::table( 'certificates' ) . ' ORDER BY issued_at DESC LIMIT 50' );

        ob_start();
        ?>
        <div class="wrap kh-lms-admin">
            <h1>گواهی‌ها</h1>
            <table class="widefat striped">
                <thead>
                    <tr>
                        <th>شماره گواهی</th>
                        <th>کاربر</th>
                        <th>دوره</th>
                        <th>تاریخ صدور</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ( $rows as $row ) : ?>
                        <?php $course = Repository::course( (int) $row->course_id ); ?>
                        <tr>
                            <td><?php echo esc_html( $row->certificate_number ); ?></td>
                            <td><?php echo esc_html( get_userdata( (int) $row->user_id )->display_name ?? 'کاربر' ); ?></td>
                            <td><?php echo esc_html( $course ? $course->title : '' ); ?></td>
                            <td><?php echo esc_html( mysql2date( get_option( 'date_format' ), $row->issued_at ) ); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php
        return ob_get_clean();
    }
}
