<?php
if (!defined('WP_UNINSTALL_PLUGIN')) exit;
if (get_option('kh_lms_delete_data') !== '1') return;
global $wpdb;
foreach(['progress','tokens','certificates','enrollments','lessons','chapters','courses'] as $t) $wpdb->query('DROP TABLE IF EXISTS '.$wpdb->prefix.'kh_lms_'.$t);
delete_option('kh_lms_version');
