<?php
namespace KhorshidLMS\Core;
if ( ! defined( 'ABSPATH' ) ) { exit; }
final class WooCommerce {
    public static function hooks(): void {
        add_action( 'admin_notices', [ self::class, 'maybe_show_missing_notice' ] );
        if ( ! self::available() ) { return; }
        add_action( 'woocommerce_product_options_general_product_data', [ self::class, 'product_field' ] );
        add_action( 'woocommerce_process_product_meta', [ self::class, 'save_product_field' ] );
        add_action( 'woocommerce_order_status_processing', [ self::class, 'grant_access' ] );
        add_action( 'woocommerce_order_status_completed', [ self::class, 'grant_access' ] );
        add_action( 'woocommerce_order_status_cancelled', [ self::class, 'revoke_access' ] );
        add_action( 'woocommerce_order_status_refunded', [ self::class, 'revoke_access' ] );
        add_action( 'woocommerce_order_status_failed', [ self::class, 'revoke_access' ] );
    }
    public static function available(): bool { return class_exists( 'WooCommerce' ) && function_exists( 'wc_get_order' ); }
    public static function maybe_show_missing_notice(): void { if ( current_user_can( 'manage_options' ) && ! self::available() ) { echo '<div class="notice notice-warning"><p>' . esc_html__( 'برای فروش دوره‌ها، نصب و فعال‌سازی WooCommerce لازم است.', 'khorshid-lms' ) . '</p></div>'; } }
    public static function product_field(): void {
        if ( ! function_exists( 'woocommerce_wp_select' ) ) { return; }
        $options = [ '0' => __( 'بدون اتصال به دوره', 'khorshid-lms' ) ];
        foreach ( Repository::courses( [ 'status' => 'publish' ] ) as $course ) { $options[(string) $course->id] = $course->course_code . ' — ' . $course->title; }
        woocommerce_wp_select( [ 'id' => '_kh_lms_course_id', 'label' => __( 'دوره آموزشی', 'khorshid-lms' ), 'description' => __( 'خرید این محصول دسترسی دوره را فعال می‌کند.', 'khorshid-lms' ), 'desc_tip' => true, 'options' => $options ] );
    }
    public static function save_product_field( int $product_id ): void { if ( current_user_can( 'edit_post', $product_id ) && isset( $_POST['_kh_lms_course_id'] ) ) { update_post_meta( $product_id, '_kh_lms_course_id', absint( wp_unslash( $_POST['_kh_lms_course_id'] ) ) ); } }
    public static function grant_access( int $order_id ): void {
        if ( ! self::available() ) { return; }
        $order = wc_get_order( $order_id ); $user_id = $order ? (int) $order->get_user_id() : 0; if ( ! $user_id ) { return; }
        global $wpdb; $table = Repository::table( 'enrollments' ); $now = current_time( 'mysql', true );
        foreach ( $order->get_items() as $item ) {
            $product_id = (int) $item->get_product_id(); $course_id = (int) get_post_meta( $product_id, '_kh_lms_course_id', true );
            if ( ! $course_id || ! Repository::course( $course_id ) ) { continue; }
            $wpdb->query( $wpdb->prepare( 'INSERT INTO ' . $table . ' (user_id,course_id,order_id,product_id,status,started_at,created_at) VALUES (%d,%d,%d,%d,%s,%s,%s) ON DUPLICATE KEY UPDATE status=%s,order_id=%d,product_id=%d', $user_id, $course_id, $order_id, $product_id, 'active', $now, $now, 'active', $order_id, $product_id ) );
        }
    }
    public static function revoke_access( int $order_id ): void {
        if ( ! self::available() ) { return; }
        $order = wc_get_order( $order_id ); $user_id = $order ? (int) $order->get_user_id() : 0; if ( ! $user_id ) { return; }
        global $wpdb;
        foreach ( $order->get_items() as $item ) { $course_id = (int) get_post_meta( (int) $item->get_product_id(), '_kh_lms_course_id', true ); if ( $course_id ) { $wpdb->update( Repository::table( 'enrollments' ), [ 'status' => 'revoked' ], [ 'user_id' => $user_id, 'course_id' => $course_id ], [ '%s' ], [ '%d', '%d' ] ); } }
    }
}
