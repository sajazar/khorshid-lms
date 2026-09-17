<?php
namespace KhorshidLMS\Core;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class FrontendAccess {
    public static function hooks(): void {
        add_filter( 'woocommerce_account_menu_items', [ self::class, 'account_menu' ] );
        add_action( 'init', [ self::class, 'endpoint' ] );
        add_action( 'woocommerce_account_my-courses_endpoint', [ self::class, 'render_my_courses' ] );
    }
    public static function endpoint(): void { add_rewrite_endpoint( 'my-courses', EP_ROOT | EP_PAGES ); }
    public static function account_menu( array $items ): array { $logout = $items['customer-logout'] ?? null; unset( $items['customer-logout'] ); $items['my-courses'] = 'دوره‌های شما'; if ( null !== $logout ) { $items['customer-logout'] = $logout; } return $items; }
    public static function render_my_courses(): void { echo do_shortcode( '[kh_lms_my_courses]' ); }
}
