<?php
namespace KhorshidLMS\Core;
if ( ! defined( 'ABSPATH' ) ) exit;
final class WooCommerce {
 public static function hooks(): void { add_action('woocommerce_order_status_processing',[self::class,'grant']);add_action('woocommerce_order_status_completed',[self::class,'grant']);add_action('woocommerce_order_status_refunded',[self::class,'revoke']);add_action('woocommerce_product_options_general_product_data',[self::class,'field']);add_action('woocommerce_process_product_meta',[self::class,'save']); }
 public static function field(): void { woocommerce_wp_text_input(['id'=>'kh_lms_course_id','label'=>'شناسه دوره LMS','desc_tip'=>true,'description'=>'محصول را به یک دوره متصل کنید.']); }
 public static function save($id): void { if(isset($_POST['kh_lms_course_id']))update_post_meta($id,'_kh_lms_course_id',absint($_POST['kh_lms_course_id'])); }
 public static function grant($order_id): void { if(!function_exists('wc_get_order'))return;$o=wc_get_order($order_id);$uid=$o->get_user_id();if(!$uid)return;global $wpdb;$now=gmdate('Y-m-d H:i:s');foreach($o->get_items() as $item){$pid=$item->get_product_id();$cid=absint(get_post_meta($pid,'_kh_lms_course_id',true));if($cid)$wpdb->query($wpdb->prepare('INSERT INTO '.Repository::table('enrollments').'(user_id,course_id,order_id,product_id,status,created_at) VALUES(%d,%d,%d,%d,%s,%s) ON DUPLICATE KEY UPDATE status=VALUES(status)', $uid,$cid,$order_id,$pid,'active',$now));}}
 public static function revoke($order_id): void {if(!function_exists('wc_get_order'))return;$o=wc_get_order($order_id);global $wpdb;foreach($o->get_items() as $item){$cid=absint(get_post_meta($item->get_product_id(),'_kh_lms_course_id',true));if($cid)$wpdb->update(Repository::table('enrollments'),['status'=>'revoked'],['user_id'=>$o->get_user_id(),'course_id'=>$cid]);}}
}
