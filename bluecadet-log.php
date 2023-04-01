<?php
/*
Plugin Name: Bluecadet Log
Description: a plugin to log Bluecadet activities
Version: 1.0
Author: Pete Inge, Bluecadet
*/

/**
 * Log message severity -- Emergency: system is unusable.
 */
define('BCL_EMERGENCY', 0);

/**
 * Log message severity -- Alert: action must be taken immediately.
 */
define('BCL_ALERT', 1);

/**
 * Log message severity -- Critical conditions.
 */
define('BCL_CRITICAL', 2);

/**
 * Log message severity -- Error conditions.
 */
define('BCL_ERROR', 3);

/**
 * Log message severity -- Warning conditions.
 */
define('BCL_WARNING', 4);

/**
 * Log message severity -- Normal but significant conditions.
 */
define('BCL_NOTICE', 5);

/**
 * Log message severity -- Informational messages.
 */
define('BCL_INFO', 6);

/**
 * Log message severity -- Debug-level messages.
 */
define('BCL_DEBUG', 7);

function bcl_severity_labels(){
  return [
    BCL_EMERGENCY => 'Emergency',
    BCL_ALERT => 'Alert',
    BCL_CRITICAL => 'Critical',
    BCL_ERROR => 'Error',
    BCL_WARNING => 'Warning',
    BCL_NOTICE => 'Notice',
    BCL_INFO => 'Info',
    BCL_DEBUG => 'Debug',
  ];
}

function bcl_severity_colors(){
  return [
    BCL_EMERGENCY => '',
    BCL_ALERT => '',
    BCL_CRITICAL => '',
    BCL_ERROR => '',
    BCL_WARNING => '',
    BCL_NOTICE => '',
    BCL_INFO => '',
    BCL_DEBUG => '',
  ];
}

add_action( 'init', 'bc_log_register_activity_log_table', 1 );
add_action( 'switch_blog', 'bc_log_register_activity_log_table' );

function bc_log_register_activity_log_table() {
  global $wpdb;
  $wpdb->bc_log_activity_log = "{$wpdb->prefix}bc_log_activity_log";
}

function bc_log_create_tables() {
  require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
  global $wpdb;
  global $charset_collate;
  // Call this manually as we may have missed the init hook
  bc_log_register_activity_log_table();

  $sql_create_table = "CREATE TABLE {$wpdb->bc_log_activity_log} (
    log_id bigint(20) unsigned NOT NULL auto_increment,
    user_id bigint(20) unsigned NOT NULL default '0',
    type varchar(255) NOT NULL default '',
    message varchar(255) NOT NULL default '',
    severity tinyint(3) unsigned NOT NULL DEFAULT '0',
    timestamp int(11) NOT NULL DEFAULT '0',
    PRIMARY KEY  (log_id),
    KEY user_id (user_id),
    KEY type (type),
    KEY severity (severity)
  ) $charset_collate; ";

  dbDelta( $sql_create_table );
}

// Create tables on plugin activation
register_activation_hook( __FILE__, 'bc_log_create_tables' );


// Log item.
function bc_log_data($type = '', $message = '', $severity = BCL_INFO) {
  global $wpdb;

  $user_id = get_current_user_id();
  $timestamp = time();

  $inserted = $wpdb->insert(
    "{$wpdb->prefix}bc_log_activity_log",
    array(
      'user_id'=>$user_id,
      'type'=>$type,
      'message'=>$message,
      'severity'=>$severity,
      'timestamp'=>$timestamp,
    ),
    array (
      '%d',
      '%s',
      '%s',
      '%d',
      '%d',
    )
  );

  if( $inserted ){
    return $wpdb->insert_id;
  }
  else {
    return FALSE;
  }
}

/**
 * top level menu
 */
function bluecadet_log_options_page() {
  // add top level menu page
  add_menu_page(
    'Bluecadet Log',
    'Bluecadet Log',
    'manage_options',
    'bcl_admin',
    'bcl_admin_page_html',
    'dashicons-editor-table',
    100
  );
}

/**
 * register our bluecadet_log_options_page to the admin_menu action hook
 */
add_action( 'admin_menu', 'bluecadet_log_options_page' );

function bcl_admin_page_html() {
  if(!class_exists('WP_List_Table')){
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
  }
  include_once( __DIR__ . '/bluecadet-log-table.php');

  //Prepare Table of elements
  $wp_list_table = new BluecadetLog_Table();
  $wp_list_table->prepare_items();

  //Table of elements
  $wp_list_table->display();
}

/**
 * Self cleanup
 *
 * Deletes logs over 5,000, 50K at a time.
 */
function bcl_cronjob_cleanup() {
  global $wpdb;

  $sql = $wpdb->prepare(
    "
    SELECT log_id
    FROM {$wpdb->prefix}bc_log_activity_log
    ORDER BY log_id DESC
    LIMIT %d, %d
    ", [5000, 1] );

  $results = $wpdb->get_results( $sql );

  $below_id = isset($results[0]->log_id)? $results[0]->log_id : 0;

  $sql = $wpdb->prepare(
    "
    DELETE
    FROM {$wpdb->prefix}bc_log_activity_log
    WHERE log_id <= %d
    ORDER BY log_id ASC
    LIMIT %d
    ", [$below_id, 50000] );

  $results = $wpdb->get_results( $sql );

}
if ( ! wp_next_scheduled( 'bcl_cronjob' ) ) {
  wp_schedule_event( time(), 'twicedaily', 'bcl_cronjob' );
}
add_action( 'bcl_cronjob', 'bcl_cronjob_cleanup', 1 );

?>
