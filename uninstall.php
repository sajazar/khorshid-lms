<?php
namespace KhorshidLMS\Core;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class WooCommerce {
    public static function hooks(): void {
        if ( ! class_exists( 'WooCommerce' ) ) {
            add_action( 'admin_notices', [ self::class, 'missing_notice' ] );
            return;
        }

        add_action( 'woocommerce_product_options_general_product_data', [ self::class, 'product_meta_field' ] );
        add_action( 'woocommerce_process_product_meta', [ self::class, 'save_product_meta' ] );
        add_action( 'woocommerce_order_status_completed', [ self::class, 'grant_access' ] );
        add_action( 'woocommerce_order_status_processing', [ self::class, 'grant_access' ] );
        add_action( 'woocommerce_order_status_refunded', [ self::class, 'revoke_access' ] );
    }

    public static function missing_notice(): void {
        echo '<div class="notice notice-warning"><p>برای استفاده از WooCommerce Integration افزونه Khorshid LMS نیاز به نصب WooCommerce دارد.</p></div>';
    }

    public static function product_meta_field(): void {
        woocommerce_wp_text_input(
            [
                'id'          => 'kh_lms_course_id',
                'label'       => 'شناسه دوره LMS',
                'description' => 'شماره دوره‌ای که این محصول به آن متصل می‌شود.',
                'desc_tip'    => true,
            ]
        );
    }

    public static function save_product_meta( int $product_id ): void {
        if ( isset( $_POST['kh_lms_course_id'] ) ) {
            update_post_meta( $product_id, '_kh_lms_course_id', absint( wp_unslash( $_POST['kh_lms_course_id'] ) ) );
        }
    }

    public static function grant_access( int $order_id ): void {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $user_id = (int) $order->get_user_id();
        if ( ! $user_id ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product_id = (int) $item->get_product_id();
            $course_id  = (int) get_post_meta( $product_id, '_kh_lms_course_id', true );
            if ( ! $course_id ) {
                continue;
            }

            global $wpdb;
            $wpdb->query(
                $wpdb->prepare(
                    'INSERT INTO ' . Repository::table( 'enrollments' ) . ' (user_id, course_id, order_id, product_id, status, created_at) VALUES (%d, %d, %d, %d, %s, %s) ON DUPLICATE KEY UPDATE status = %s',
                    $user_id,
                    $course_id,
                    $order_id,
                    $product_id,
                    'active',
                    current_time( 'mysql', true ),
                    'active'
                )
            );
        }
    }

    public static function revoke_access( int $order_id ): void {
        if ( ! function_exists( 'wc_get_order' ) ) {
            return;
        }

        $order = wc_get_order( $order_id );
        if ( ! $order ) {
            return;
        }

        $user_id = (int) $order->get_user_id();
        if ( ! $user_id ) {
            return;
        }

        foreach ( $order->get_items() as $item ) {
            $product_id = (int) $item->get_product_id();
            $course_id  = (int) get_post_meta( $product_id, '_kh_lms_course_id', true );
            if ( ! $course_id ) {
                continue;
            }

            global $wpdb;
            $wpdb->update(
                Repository::table( 'enrollments' ),
                [ 'status' => 'revoked' ],
                [
                    'user_id'   => $user_id,
                    'course_id' => $course_id,
                ],
                [ '%s' ],
                [ '%d', '%d' ]
            );
        }
    }
}
