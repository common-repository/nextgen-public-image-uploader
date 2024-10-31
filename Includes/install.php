<?php function ngg_Pup_uninstall() { // if they want to uninstall the plugin :(
}?>
<?php function ngg_Pup_plugin_first_run() { // sets default options and also creates our DB table. 
$options = get_option('NGG_User_upload_option_array');
if ($options['first_run'] != "yes") { //checks to see if this is the first time activating
// if it's not then we run our init. 
$defaults = array(
  'Upload_directory' => 'queue',
  'Limit_Upload_by_user_role' => '',
  'Allow_user_moderation' => '',
  'Administrator_can_upload' => 'checked',
  'Administrator_can_moderate' => 'checked',
  'allowed_roles_upload' => array("Administrator"),
  'allowed_roles_moderate' => array("Administrator"),
  'allowed_extensions' => array("jpg","png","gif","jpeg","bmp"),
  'jpg_allowed' => 'checked',
  'png_allowed' => 'checked',
  'jpeg_allowed' => 'checked',
  'gif_allowed' => 'checked',
  'Upload_Size_Limit' => '100000',  
  'first_run' => 'yes',
  );
update_option('NGG_User_upload_option_array', $defaults);
ngg_pup_db_install(); 
} 
}?>
<?php function ngg_pup_db_install() {
$ngg_db_version = "1.0";
   global $wpdb;
   $table_name = $wpdb->prefix . "NGG_Upload_queue";
   $sql = "CREATE TABLE $table_name (
  picture_id mediumint(9) NOT NULL AUTO_INCREMENT,
  file_name varchar(100) NOT NULL,
  file_size INTEGER,
  uploader_name varchar(50), 
  uploader_ip INTEGER,
  upload_date TIMESTAMP,
  file_path TEXT NOT NULL,
  file_url TEXT NOT NULL,
  moderation_status TEXT,
  dimensions TEXT,
  UNIQUE KEY picture_id (picture_id)
    );";
   require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
   dbDelta( $sql );
   add_option( "ngg_pup_db_version", $ngg_db_version );
}?>