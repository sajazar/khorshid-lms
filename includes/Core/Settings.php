<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class Settings {
    public static function hooks(): void {
        add_action( 'admin_post_kh_lms_save_settings', [ self::class, 'save' ] );
    }

    public static function defaults(): array {
        return [
            'player_theme'           => 'dark',
            'completion_percentage'  => '90',
            'token_lifetime'         => '180',
            'rate_limit'             => '60',
            'allow_preview'          => '1',
            'certificate_enabled'    => '1',
            'currency'               => 'IRR',
            'storage_path'           => 'wp-content/uploads/khorshid-lms-storage',
            'enable_video_logging'   => '1',
        ];
    }

    public static function get( string $key, $default = null ) {
        $current = get_option( 'kh_lms_settings', [] );
        if ( ! is_array( $current ) ) {
            $current = [];
        }

        return $current[ $key ] ?? ( self::defaults()[ $key ] ?? $default );
    }

    public static function render_page(): string {
        $settings = array_merge( self::defaults(), (array) get_option( 'kh_lms_settings', [] ) );

        ob_start();
        ?>
        <div class="wrap kh-lms-admin">
            <h1>تنظیمات Khorshid LMS</h1>
            <form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
                <input type="hidden" name="action" value="kh_lms_save_settings">
                <?php wp_nonce_field( 'kh_lms_update_settings', '_wpnonce' ); ?>
                <div class="kh-card">
                    <h2>تنظیمات عمومی</h2>
                    <label>تم پلیر
                        <select name="player_theme">
                            <option value="dark" <?php selected( $settings['player_theme'], 'dark' ); ?>>تیره</option>
                            <option value="light" <?php selected( $settings['player_theme'], 'light' ); ?>>روشن</option>
                        </select>
                    </label>
                    <label>درصد تکمیل دوره
                        <input type="number" name="completion_percentage" value="<?php echo esc_attr( $settings['completion_percentage'] ); ?>" min="50" max="100">
                    </label>
                    <label>طول عمر توکن ویدئو (ثانیه)
                        <input type="number" name="token_lifetime" value="<?php echo esc_attr( $settings['token_lifetime'] ); ?>" min="30" max="600">
                    </label>
                    <label>محدودیت نرخ درخواست (در دقیقه)
                        <input type="number" name="rate_limit" value="<?php echo esc_attr( $settings['rate_limit'] ); ?>" min="10" max="500">
                    </label>
                    <label>فعال‌سازی گواهی
                        <select name="certificate_enabled">
                            <option value="1" <?php selected( $settings['certificate_enabled'], '1' ); ?>>فعال</option>
                            <option value="0" <?php selected( $settings['certificate_enabled'], '0' ); ?>>غیرف��ال</option>
                        </select>
                    </label>
                </div>
                <div class="kh-card">
                    <h2>تنظیمات امنیتی</h2>
                    <label>پیش‌نمایش درس رایگان
                        <select name="allow_preview">
                            <option value="1" <?php selected( $settings['allow_preview'], '1' ); ?>>فعال</option>
                            <option value="0" <?php selected( $settings['allow_preview'], '0' ); ?>>غیرفعال</option>
                        </select>
                    </label>
                    <label>ثبت لاگ ویدئو
                        <select name="enable_video_logging">
                            <option value="1" <?php selected( $settings['enable_video_logging'], '1' ); ?>>فعال</option>
                            <option value="0" <?php selected( $settings['enable_video_logging'], '0' ); ?>>غیرفعال</option>
                        </select>
                    </label>
                </div>
                <p><button class="button button-primary" type="submit">ذخیره تنظیمات</button></p>
            </form>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function save(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'دسترسی غیرمجاز' );
        }

        check_admin_referer( 'kh_lms_update_settings' );

        $input = [
            'player_theme'         => sanitize_key( wp_unslash( $_POST['player_theme'] ?? 'dark' ) ),
            'completion_percentage'=> absint( $_POST['completion_percentage'] ?? 90 ),
            'token_lifetime'       => absint( $_POST['token_lifetime'] ?? 180 ),
            'rate_limit'           => absint( $_POST['rate_limit'] ?? 60 ),
            'allow_preview'        => absint( $_POST['allow_preview'] ?? 1 ),
            'certificate_enabled'  => absint( $_POST['certificate_enabled'] ?? 1 ),
            'enable_video_logging' => absint( $_POST['enable_video_logging'] ?? 1 ),
        ];

        update_option( 'kh_lms_settings', $input );
        wp_safe_redirect( admin_url( 'admin.php?page=kh-lms-settings&updated=1' ) );
        exit;
    }
}
