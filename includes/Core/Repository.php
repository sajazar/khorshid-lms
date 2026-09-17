<?php
namespace KhorshidLMS\Core;
if ( ! defined( 'ABSPATH' ) ) exit;
final class Repository {
    public static function table(string $name): string { global $wpdb; return $wpdb->prefix . 'kh_lms_' . $name; }
    public static function courses(array $args=[]): array { global $wpdb; $t=self::table('courses'); $status=$args['status']??'publish'; return $wpdb->get_results($wpdb->prepare("SELECT * FROM $t WHERE status=%s ORDER BY id DESC",$status)); }
    public static function course(int $id): ?object { global $wpdb; $r=$wpdb->get_row($wpdb->prepare('SELECT * FROM '.self::table('courses').' WHERE id=%d',$id)); return $r ?: null; }
    public static function curriculum(int $course): array { global $wpdb; $ct=self::table('chapters');$lt=self::table('lessons');$out=[]; foreach($wpdb->get_results($wpdb->prepare("SELECT * FROM $ct WHERE course_id=%d ORDER BY sort_order,id",$course)) as $ch){$ch->lessons=$wpdb->get_results($wpdb->prepare("SELECT * FROM $lt WHERE chapter_id=%d ORDER BY sort_order,id",$ch->id));$out[]=$ch;} return $out; }
    public static function enrolled(int $user,int $course): bool { global $wpdb; return (bool)$wpdb->get_var($wpdb->prepare('SELECT id FROM '.self::table('enrollments').' WHERE user_id=%d AND course_id=%d AND status=%s AND (expires_at IS NULL OR expires_at>UTC_TIMESTAMP())',$user,$course,'active')); }
}
