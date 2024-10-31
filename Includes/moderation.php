<?php function NGG_pup_Moderation () { 
$options = get_option('NGG_User_upload_option_array'); 
$user_info = wp_get_current_user(); 
if(ngg_pup_check_role_allowed($user_info, $options['allowed_roles_moderate'])!= "passes") { ?>
<span class="ngg_pup_lockout_message">    
Sorry you do not have access to this page.
</span>
<?php } else { 
if ($options['enable_moderation_queue'] =="checked") {
ngg_pup_moderate_inital_checks ($options); ?>	
<?php } else { ?>
<span class="ngg_pup_lockout_message">
Sorry, the moderation queue is currently disabled.	
</span>
<?php } ?>
<?php } 
} ?>
<?php function ngg_pup_process_results($files_array) {
$options = get_option('NGG_User_upload_option_array');     
foreach ($files_array[0] as $file) {
switch ($file->f_radio) { //checks what status of radio button is, either Batch process, move or delete. 
case 0: //batch proccess
// this shouldn't ever happen. If it does, something bad happened, because before we get to this step, we process to make sure
// all files flagged under "batch process" are reflagged based on what the batch proccess command was ie: delete or move/leave in queue. 
// this is left in for error handling. 
ngg_pup_return_file_display (array(
'url'=>$file->file_url, 
'name'=>$file->file_name,
'status'=>"There was a problem processing this file as part of the batch procces. Please try again.",
'error_code'=>1,
));
break;
case 1: //move
if ($file->f_gid == 0) { //if 0, then "Leave in queue was selected and we do nothing"
ngg_pup_return_file_display (array(
'url'=>$file->file_url, 
'name'=>$file->file_name,
'status'=>"No gallery selected. The file was left in the queue.",
'error_code'=>1,
));
} else {
ngg_pup_moderate_process_move($file, $files_array);
}
break;
case 2: //delete
ngg_pup_return_file_display (array(
'url'=>$file->file_url, 
'name'=>$file->file_name,
'status'=>"Successfully deleted.",
'error_code'=>0,
));
    
if (file_exists($file->file_path."/".$file->file_name)) { //else it's already gone for some reason. 
unlink($file->file_path.DIRECTORY_SEPARATOR.$file->file_name);
}
ngg_pup_remove_from_db ($file);
break;



}
}
}?>
<?php function ngg_pup_create_mod_page($token_id, $options) { //starts building mod page here. ?>
<?php  
$queue = ngg_pup_get_queue_info($optional); // builds file list;
if (count($queue)==0) { ?> 
<span class="ngg_pup_lockout_message">
Sorry, there are currently no files in the moderation queue at this time.     
</span>
<?php 
return;
} ?>
<?php 
$gallery_list = ngg_pup_get_gallery_list($optional); //builds list of all galleries. 
$cnt = 0; //used to make our forms. This makes each form have it's own identifier, to work
// in conjuction with what we'll do with the files. ?>
<form name="moderation" action="" method="POST"  enctype="multipart/form-data">
<input type="hidden" name="token" value="<?php echo $token_id;//for ensuring refreshes don't screw things up?>">
<div class="ngg_pup_moderate_batch_bar">
<div class="ngg_pup_moderate_bar_box_1">
<input type="radio" name="batch_radio[]" class="ngg_pup_radio" id="batch_processed1" name="batch_radio[]" value="2" />
<label for="batch_processed1">
Delete Files
</label>
</div>
<div class="ngg_pup_moderate_bar_box_2">
<input type="radio" name="batch_radio[]" class="ngg_pup_radio" checked="checked" id="batch_processed2" name="batch_radio[]" value="1" />
<label for="batch_processed2">
Move files 
<?php NGG_pup_gal_pulldown($gallery_list,"batch_") ?>
</label>
</div>
<?php if (($options['Allow_user_gal_create']=="checked") &&(ngg_pup_check_role_allowed(wp_get_current_user(), $options['allowed_roles_create_gal'])=="passes")) {?>
<div class="ngg_pup_moderate_bar_box_3"> 
<label for="create_gallery">
<input type="radio" name="batch_radio[]" class="ngg_pup_radio" id="create_gallery" name="batch_radio[]" value="3" />
<span style="float:left;">
Create Gallery 
</span>
<input type="text" name="create_gallery_name" class="ngg_pup_moderate_bar_text_box" placeholder="Enter Gallery name">
</label>
</div>
<?php } ?>
<?php //currently disabled. 
// until I can figure out tagging. 
//<input type="text" name="batch_tags" class="ngg_pup_moderate_bar_text_box" placeholder="Comma seperated tags">
?>
<div class="ngg_pup_moderate_bar_box7">
<input type="submit" value="Submit" class="ngg_pup_submit_button">
</div>
</div>  

<div class="ngg_pup_moderate_wrapper">
<?php foreach ($queue as $file) { 
ngg_pup_build_mod_list($file, $gallery_list, $cnt);
$cnt ++;
} ?>
</div>
</form>
<?php } ?>
<?php function ngg_pup_build_mod_list ($file, $gallery_list, $cnt) { ?>
<input type="hidden" name="pid[]" value="<?php echo $file->picture_id;?>">
<div class="moderate_pic_wrap">
<label for="batch_process<?php echo $cnt;?>">
<div class="moderate_pic">
<div class="NggPup_Float_text">
<img src = "<?php echo $file->file_url."/".$file->file_name ?>" height="200" width="200" />
<div class="NggPup_Floated_text_top">
<?php echo $file->file_name;?>
</div>
<div class="NggPup_Floated_text_bot" style="bottom:0;">
Uploaded by: <?Php echo $file->uploader_name;?>
</div>
</div>
</div>
</label>
<?php /////////////////////////////////////////////////////////?>
<div class="moderate_pic_menu_wrap">
<label for="batch_process<?php echo $cnt;?>">
<div class="moderate_pic_menu">
<input type="radio" name="radio[<?php echo $cnt;?>]" class="ngg_pup_radio" checked="checked" id="batch_process<?php echo $cnt;?>" name="radio[]" value="0" />
Batch process
</div>
</label>
<div class="moderate_pic_menu">
<input type="radio" name="radio[<?php echo $cnt;?>]" class="ngg_pup_radio" id="gall<?php echo $cnt;?>" value="1" checked />
<label for="gall<?php echo $cnt;?>">
<?php NGG_pup_gal_pulldown($gallery_list, null) ?>
</label>
</div>
<div class="moderate_pic_menu">
<input type="radio" name="radio[<?php echo $cnt;?>]" value="2" class="ngg_pup_radio" id="delete<?php echo $cnt;?>">
<label for="delete<?php echo $cnt;?>">
<?php ngg_pup_delete_menu() ?>
</label>
</div>
<div class="moderate_pic_menu">
<?php // current disabled until I can figure out an alternative tagging system //
//<input type="text" name="tags[<?php echo $cnt;]" class="ngg_pup_text_box" placeholder="Comma seperated tags list">
?>
</div>
</div>
</div>
<?php } ?>
<?php function ngg_pup_delete_menu() { // want to add delete and ban user but for now this will do. ?>
<select name="delete_form[]" class="ngg_pup_pulldown">
<selected><option value="0">Delete</option></selected>
</select>
<?php } ?>
<?php function NGG_pup_gal_pulldown($gallery_list, $optional) { //the $optional is to build a unique form name if we want. ?>
<select name="<?php echo $optional;?>gid[]" class="<?php echo $optional;?>ngg_pup_pulldown">
<selected><option value="00">Leave in queue.</option></selected>
<?php foreach ($gallery_list as $gall_list) { // starts loop for each gallery ?>
<option value="<?php echo $gall_list->gid;?>">Move to - <?php echo $gall_list->title;?></option>
<?php } // ends loop and then closes form ?>  
</select>
<?Php }?>
<?php function ngg_pup_search_for_gid ($array, $search) { 
// used to iterate the stored gallery list and return the file path
// based on what's stored in the gall_list portion of the array when
// matched with the file_list portion based on the coresponding gid keys. 
foreach ($array as $obj => $value) {
if ($value->gid == $search) {
return $value;
} 
}
}?>
<?php function ngg_pup_moderate_process_move($file, $files_array) {
$options = get_option('NGG_User_upload_option_array');   
$gal_info = ngg_pup_search_for_gid ($files_array[1], $file->f_gid); //searches for and builds gallery info from stored array. 
$gal_info->path = $options['Nextgen_full_directory'].$gal_info->slug."/";
$errors = ngg_pup_check_transfer($file, $gal_info); //checks if sources exists, and makes sure target doesnt.  
if (!in_array('error', $errors, true)) { // makes sure no error codes are returned from check_transfer.  
$thumb_result = ngg_pup_create_thumb( //builds our thumbnails.
array(name => $file->file_name, dir =>$file->file_path), 
array(
max_width=> 250, 
max_height=> 250, 
quality=>85, 
target_name=> "thumbs_".preg_replace("/\\.[^.\\s]{3,4}$/", "", $file->file_name),
target_dir=> $gal_info->path."thumbs/" //ngg gallery stores its thumbnails in a subdirectory of the gallery dir. 
));
$file->meta_data = ngg_pup_build_meta_data ($file, $files_array);

ngg_pup_return_file_display (array(
'url'=>$file->file_url, 
'name'=>$file->file_name,
'status'=>"Successfully moved to $gal_info->title",
'error_code'=>0,
));
ngg_pup_move_file ($file, $gal_info);
ngg_pup_add_to_ngg_db($file); //adds to NGG_Gallery DB.
ngg_pup_remove_from_db ($file); // removes entry from queue.
} else {
if ($errors[0] == "error") {
ngg_pup_return_file_display (array(
'url'=>$file->file_url, 
'name'=>$file->file_name,
'status'=>"Could not be moved, because the image could not be found.",
'error_code'=>1,
));    
} 
if ($errors[1] == "error") {
ngg_pup_return_file_display (array(
'url'=>$file->file_url, 
'name'=>$file->file_name,
'status'=>"Could not be moved, because it already exists in $gal_info->title",
'error_code'=>1,
));
}
if ($errors[2] == "error") {
ngg_pup_return_file_display (array(
'url'=>$file->file_url, 
'name'=>$file->file_name,
'status'=>"Could not be moved, because the target directory does not exist.",
'error_code'=>1,
));    

}
}
} ?>