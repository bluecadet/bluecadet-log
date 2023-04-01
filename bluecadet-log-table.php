<?php

class BluecadetLog_Table extends WP_List_Table {

  /**
  * Constructor, we override the parent to pass our own arguments
  * We usually focus on three parameters: singular and plural labels, as well as whether the class supports AJAX.
  */
  function __construct() {
    parent::__construct( array(
      'singular'=> 'wp_list_text_link', //Singular label
      'plural' => 'wp_list_test_links', //plural label, also this well be one of the table css class
      'ajax'   => false //We won't support Ajax for this table
    ) );
  }

  /**
  * Add extra markup in the toolbars before or after the list
  * @param string $which, helps you decide if you add the markup after (bottom) or before (top) the list
  */
  function extra_tablenav( $which ) {
    if ( $which == "top" ){
        //The code that goes before the table is here
        echo "Hello, I'm before the table";
    }
    if ( $which == "bottom" ){
        //The code that goes after the table is there
        echo "Hi, I'm after the table";
    }
  }

  /**
  * Define the columns that are going to be used in the table
  * @return array $columns, the array of columns to use with the table
  */
  function get_columns() {
    $columns = array(
      'log_id' => __('Log ID'),
      'user_id' => __('User ID'),
      'type' => __('Type'),
      'message' => __('Message'),
      'severity' => __('Severity'),
      'timestamp' => __('Timestamp'),
    );

    return $columns;
  }
  function get_hidden_columns() {
    $columns = [];

    return $columns;
  }

  /**
  * Decide which columns to activate the sorting functionality on
  * @return array $sortable, the array of columns that can be sorted by the user
  */
  public function get_sortable_columns() {
    $sortable = array(
      'log_id' => array('log_id', true ),
      'user_id' => array('user_id', true ),
      'type' => array('type', true ),
      'severity' => array('severity', true ),
      'timestamp' => array('timestamp', true ),
    );
    return $sortable;
  }

  /**
  * Retrieve log data from the database
  *
  * @param int $per_page
  * @param int $page_number
  *
  * @return mixed
  */
  public static function get_logs( $per_page = 5, $page_number = 1 ) {

    global $wpdb;

    $sql = "SELECT * FROM $wpdb->bc_log_activity_log";

    if ( ! empty( $_REQUEST['orderby'] ) ) {
      $sql .= ' ORDER BY ' . esc_sql( $_REQUEST['orderby'] );
      $sql .= ! empty( $_REQUEST['order'] ) ? ' ' . esc_sql( $_REQUEST['order'] ) : ' ASC';
    }

    $sql .= " LIMIT $per_page";

    $sql .= ' OFFSET ' . ( $page_number - 1 ) * $per_page;


    $result = $wpdb->get_results( $sql );

    return $result;
  }

  // Get Full Count.
  public static function get_count() {

    global $wpdb;

    $sql = "SELECT COUNT(*) as count FROM $wpdb->bc_log_activity_log";

    $result = $wpdb->get_results( $sql );

    return current($result)->count;
  }

  /**
  * Prepare the table with different parameters, pagination, columns and table elements
  */
  function prepare_items() {
    $columns = $this->get_columns();
    $hidden = $this->get_hidden_columns();
    $sortable = $this->get_sortable_columns();
    $perPage = 50;
    $currentPage = $this->get_pagenum();
    $this->set_pagination_args( array(
        'total_items' => $this->get_count(),
        'per_page'    => $perPage
    ) );
    $this->_column_headers = array($columns, $hidden, $sortable);

    $this->items = self::get_logs( $perPage, $currentPage );
  }

  /**
   * Generate the table rows
   *
   * @since 3.1.0
   * @access public
   */
  public function display_rows() {
    //Get the records registered in the prepare_items method
    $records = $this->items;

    list( $columns, $hidden, $sortable, $primary ) = $this->get_column_info();

    $severity_labels = bcl_severity_labels();

    //Loop for each record
   if(!empty($records)){
     foreach($records as $rec){

      //Open the line
        echo '<tr id="record_'.$rec->log_id.'">';
      foreach ( $columns as $column_name => $column_display_name ) {

         //Style attributes for each col
         $class = "class='$column_name column-$column_name'";
         $style = "";
         if ( in_array( $column_name, $hidden ) ) $style = ' style="display:none;"';
         $attributes = $class . $style;

         //edit link
         $editlink  = '/wp-admin/link.php?action=edit&link_id='.(int)$rec->log_id;

         //Display the cell
         switch ( $column_name ) {
            case "log_id":  echo '<td '.$attributes.'>'.stripslashes($rec->log_id).'</td>';   break;
            case "user_id":  echo '<td '.$attributes.'>'.stripslashes($rec->user_id).'</td>';   break;
            case "type":  echo '<td '.$attributes.'>'.stripslashes($rec->type).'</td>';   break;
            case "message":  echo '<td '.$attributes.'>'.stripslashes($rec->message).'</td>';   break;
            case "severity":  echo '<td '.$attributes.'>'.$severity_labels[$rec->severity].'</td>';   break;
            case "timestamp":  echo '<td '.$attributes.'>'.date('Y-m-d H:i:s', $rec->timestamp).'</td>';   break;
         }
      }

      //Close the line
      echo'</tr>';
    }
   }
  }

}


?>
