<?php/*
Plugin Name: NGG Public Image Uploader
Plugin URI: http://demo.amazinglyamusing.com
Description: This plugin adds front-end user file uploads for Next Gen Gallery. It features many configurable options on the back-end, such as assigning permissions by user role, choosing whether file uploads go in a holding queue or are automatically added, automatic thumbnail creation and many other features. The front-end is also fully configurable based on user role. This plugin can also work as a standalone image uploader even if you don't use NextGen Gallery. 
Version: 1.0 Cooper
Author: Justin Lindsay
Author URI: http://amazinglyamusing.com
*/?>
<?php 
include( plugin_dir_path( __FILE__ ) . '/Includes/options.php');
include( plugin_dir_path( __FILE__ ) . '/Includes/moderation.php'); //moderation queue part.
include( plugin_dir_path( __FILE__ ) . '/Includes/validation.php'); //File validation section
include( plugin_dir_path( __FILE__ ) . '/Includes/install.php'); //only basic plugin functions here.
include( plugin_dir_path( __FILE__ ) . '/Includes/makethumb.php'); //thumbnail generator.
include( plugin_dir_path( __FILE__ ) . '/Includes/filefunc.php'); //thumbnail generator.
?>
<?php register_activation_hook(__FILE__, 'ngg_Pup_plugin_first_run');?>
<?php add_action('ngg_render_template' , 'ngg_test' );//get rid of this, useless. ?>
<?php add_action('admin_head', 'ngg_Pup_admin_register_head');?>
<?php add_shortcode( "ngg_test" , "ngg_test" );//for the front end the shortcode is [ngg_public_uploader]?>
<?php add_shortcode( "ngg_public_uploader" , "ngg_public_uploader" );//for the front end the shortcode is [ngg_public_uploader]?>
<?php add_shortcode( "NGG_pup_Moderation" , "NGG_pup_Moderation" );//for the front end the shortcode is [ngg_public_uploader]?>
<?php add_action( 'wp_enqueue_scripts', 'Ngg_public_uploader_css' ); //queues up our style sheet ?>
<?php register_uninstall_hook(__FILE__, 'ngg_Pup_uninstall'); //if they want to uninstall the plugin :( ?>
<?php function ngg_public_uploader($shortcodes) { ;//the main core of the program, this is where it starts. 
extract( shortcode_atts( array('gid' => '',), $shortcodes ) );
$options = get_option('NGG_User_upload_option_array'); 
$check = ngg_pup_check_role_allowed(wp_get_current_user(), $options['allowed_roles_upload']);
if ($check == "passes") { //checks if current user has persmission to upload. 
// this bit here generates a unique MD5 hashed checksum that we hide in one of the fields of the
// upload form. We then use this to check if the files have been uploaded and then expire it. This is to prevent form re-submission. 
// The great thing about doing it this way, is it's virtually impossible for someone to fake this in an attempt to mass resubmit the form.
// this also does not need to be sanitized, as WP does it behidn the scenes. 
$token_id = htmlspecialchars( $_POST['token'] ); 
$token_id = mysql_real_escape_string($token_id);
if (get_transient( 'token_' . $token_id )) { // checks for and kills token if it's been used. 
delete_transient( 'token_' . $token_id );
$check_sum = "show_files";
} else { 
$token_id = md5( uniqid( "", true ) ); //generates a new MD5 token to prevent form resubmission. we'll put this in a hidden feld in the upload form
set_transient( 'token_' . $token_id, 'safe_to_delete', 60*10 ); // Sets it to expire in 10 minute. 
}  //this part checks where we are, and determines what menu to show. 

if(isset($_FILES['imgs']['tmp_name']) && ($check_sum == "show_files")) { //if the user has uploaded files
if (!empty($shortcodes)&& (is_numeric($shortcodes['gid']))) {
$array = ngg_pup_direct_to_gallery_precheck($shortcodes); // does a check to see if single gallery upload used, and if gallery exists.    
} 
$stored_info = ngg_pup_pre_validation($shortcodes, $options, $array);
$final_list = ngg_pup_validate_files($options, $stored_info);//first we check if the files pass validation
ngg_pup_post_validation($final_list, $array, $options);
ngg_pup_send_email($final_list,$options); // sends email notification if set in options.
ngg_pup_upload_results($final_list, $gallery_list, $options); //then we display the results screen. 
} else { 
ngg_pup_File_upload_Menu($options, $token_id); //if the user hasn't uploaded yet, they see the upload screen first.
}

} else { ?>
<span class="ngg_pup_lockout_message">
Sorry You do not have access to upload files at this time.  
</span>
<?php }  
}?>
<?php function ngg_pup_pre_validation($shortcodes, $options, $array){ //pre-validation handler. 
if(!empty($array) && ($array['gal_id'] !="0")) { // checks if a GID is passed through short code, if it is we change the
// destination to be the relevant gallery info based on the GID and NGG's gallery.  
$stored_info['uploaded_directory'] = $options['Nextgen_full_directory'].$array['gal_info'][0]->slug."/";
$stored_info['url'] = $options['Nextgen_url_path'].$array['gal_info'][0]->slug;
$stored_info['stored']=$options['Nextgen_full_directory'].$array['gal_info'][0]->slug."/";
return $stored_info;
} else {
$stored_info['uploaded_directory'] = $options['Upload_full_directory'];
$stored_info['url'] = ngg_pup_get_temporary_upload_url($options['Upload_directory']);
$stored_info['stored']=$options['Upload_full_directory'];
return $stored_info;
}
}?>
<?php function ngg_pup_post_validation($final_list, $array, $options){
foreach ($final_list as $file) {
if ($file['error']=="0") {
ngg_pup_save_the_files(array(
                       source=>$file['tmp_name'],
                       dest=>$file['stored'],
                       file=>$file['name']
                       )); //first we save the uploaded file

if(!empty($array) && ($array['gal_id'] !="0")) { // we're processing it as a gallery upload. 
   // we build an object, because that's what ngg_pup_add_to_ngg_db($newfile) expects.
   $newfile = (object) array(
   dimensions=>$file['dimensions'],
   f_gid=>$array['gal_id'],
   file_name=>$file['name'], 
   upload_date=>$datenow,
   meta_data=>null);
   $newfile->meta_data = ngg_pup_build_meta_data($newfile, null);

ngg_pup_create_thumb( //builds our thumbnails.
array(name => $newfile->file_name, dir =>$file['stored']), 
array(
max_width=> 250, 
max_height=> 250, 
quality=>85, 
target_name=> "thumbs_".preg_replace("/\\.[^.\\s]{3,4}$/", "", $newfile->file_name),
target_dir=> $file['stored']."thumbs/" //ngg gallery stores its thumbnails in a subdirectory of the gallery dir. 
));
   
ngg_pup_add_to_ngg_db($newfile); // save it to the ngg db.

} else { // if we're not storing it in a gallery, we throw it in the upload queue. 

if ($options['enable_moderation_queue'] =="checked") { //if not, we dont update queue because we're not using it. 
update_db_with_file($file);    
}

}    

}

}   

}?>
<?php function ngg_pup_upload_results($final_list) { //this is the function that creates the screen the user sees after uploading files
foreach($final_list as $file) {
if($file['status'] == "successfully uploaded") { //iterates the array and shows the results. 
 ngg_pup_return_file_display ($file);
 } else { 
 echo ngg_pup_return_error_message($file['errors'], $file);
 } 
 } 
}?>
<?php function ngg_pup_File_upload_Menu($options, $token_id) { //the function to create the upload page ?>
<span class="ngg_pup_upload_wrapper">
<form action="" method="post" enctype="multipart/form-data">
<input type="file" name="imgs[]" id="imgs" multiple/> 
<input type="hidden" name="token" value="<?php echo $token_id;?>"> 
<input type="submit" name="submit" value="Submit" />
</form>
</span>
<?php }//the hidden field is where the token id is stored.?>
<?php function ngg_pup_return_error_message ($error, $file) { // shows all failed file uploads ?>
<div class="NggPup_error_wrapper">
<div class="NggPup_error_side_bar">
<b>File name:</b> <?php echo $file['name'];?>
</div>
<div class="NggPup_error_side_bar">
<b>Status: </b><span class="failed"><?php echo $file['status'];?></span>
</div>
<div class="NggPup_error_message">
We're sorry, there appears to be a problem uploading this file for the following reasons: 
</div>
<?php ngg_pup_return_all_error_codes($error) ?>
<div class="NggPup_error_side_bar">
</div>
</div>
<?php }?>
<?php function ngg_pup_return_file_display ($file) { // shows all successfully uploaded pictures. ?>
<div class="NggPup_upload_wrapper">
<div class="NggPup_Float_text">
<img src="<?php echo $file['url']."/".$file['name'];?>" height="150" width="150" />
<div class="NggPup_Floated_text_top">
<?php echo $file['name'];?>
</div>
<div class="NggPup_Floated_text_bot" <?php if($file['error_code'] == "1"){ echo "style='color:#BEFF18'";}?>>
<?php echo $file['status'];?>
</div>
</div>
</div>
<?Php }?>
<?php function ngg_pup_return_all_error_codes($error) { ?>
<?php if(($error['0'] == "fails") or ($error['1'] == "fails") or ($error['2'] == "fails")) { ?>
<li>The file you uploaded doesn't appear to be an image file. Please check to ensure you are uploading an image file.</li>
<?php }?>
<?php if(($error['3'] == "fails")) { ?>
<li>Sorry that file extension is not allowed. </li>
<?php }?>
<?php if(($error['4'] == "fails")) { ?>
<li>A file already exists with that file name. Please try a different file, or rename the one you are uploading.</li>
<?php }?>
<?php if(($error['5'] == "fails")) { ?>
<li>The file size exceeds the limit allowed.  Please try uploading a smaller file. </li>
<?php }?>
<?php if(($error['6'] == "fails")) { ?>
<li>We're sorry, you do not have sufficient access to upload at this time.  </li>
<?php }?>
<?php if(($error['99'] == "fails")) { ?>
<li>There was a problem receiving the file. This could be because of a connection issue, or the file was somehow corrupted. </li>
<?php }?>
<?php }?>
<?php function ngg_Pup_admin_register_head() { //registers css for settings page.
wp_register_style( 'prefix-style', plugins_url('/css/settings.css', __FILE__) );
wp_enqueue_style( 'prefix-style' );
}?>
<?php function Ngg_public_uploader_css() { // queues our style sheet for the front end. 
wp_register_style( 'prefix-style', plugins_url('/css/nggpup.css', __FILE__) );
wp_enqueue_style( 'prefix-style' );
}?>