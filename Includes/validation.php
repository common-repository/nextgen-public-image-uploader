<?php function ngg_pup_validate_files($options, $stored_info) { //File upload section. Validates and returns results. 

// First we get all the files, and set them in an easy to manage array //
// then we run each file individually through the validation checks, and at the end process
// and return the results. 
$temp_files_list = ngg_pup_make_files_array($_FILES['imgs']); 
$uploader_ip = ngg_pup_get_user_ip();
$user_info = wp_get_current_user(); 
$user_name = $user_info->user_login;


foreach($temp_files_list as $file) {
$uploaded_directory = $stored_info['uploaded_directory'];    
$errors = null; // we have to clear this array every loop because we reuse it. 
$file['uploader_name']= $user_name; // don't need to filter this, because it comes right from a wp function. 
$file['uploader_ip'] = filter_var($uploader_ip, FILTER_VALIDATE_IP); //sanitize ip.  
$file['size'] = filter_var($file['size'], FILTER_SANITIZE_STRING, FILTER_FLAG_STRIP_LOW);
$file['name'] = sanitize_file_name( $file['name'] ); 
// first we sanitize the actual file name, to ensure that the file name is valid
// and that we're storing a filename that's safe for the database. We do this first, because we're going to store it either in the error log
// if it fails, or if it passes we need to choose a safe name so we can save it as a valid file. We also do this first, because one of our checks is to see 
// if the file already exists, and this ensures that the sanitized file name will be what's checked since that's what would be stored. 
if ($file['tmp_name'] != "") { // just in case the tmp file dissapears or has an unreadable file name, we throw an error
$errors[0] = ngg_pup_compare_file_mime ($file);
$errors[1] = ngg_pup_check_is_real_mime ($file);
$errors[2] = ngg_pup_check_file_mime_from_query($file);
// $mime_info is used later on to store HxW and other data, and we also use it to validate mime type.
$mime_info = ngg_pup_check_if_extension_allowed($file, $options['allowed_extensions']);
$errors[3] = $mime_info['status']; 
$errors[4] = ngg_pup_check_if_file_exists($file, $uploaded_directory);
$errors[5] = ngg_pup_check_file_size($file, $options['Upload_Size_Limit']);
$errors[6] = ngg_pup_check_role_allowed($user_info, $options['allowed_roles_upload']); // checks if user role level is allowed to upload. 
//$errors[] = check_ip_allowed($file, $options['Upload_Size_Limit']); checks if IP is in ban list. 
//$errors[] = check_user_allowed($file, $options['Upload_Size_Limit']); // checks if specific user is in ban list.
// have to add another check to make sure the user/IP isn't banned here. 
} else { // if the tmp name is blank, we don't do the checks and automatically flag the file as bad.
$errors[99] = "fails";
}
if (!in_array('fails', $errors, true)) { //checks to make sure all error codes are "passes", if not, the file fails the check
// yay it passes, let's set status to "passes" and add it to the array.
// we also do one last check, to make sure the image extension is the proper mime type.
$file['name'] = preg_replace("/\\.[^.\\s]{3,4}$/", "", $file['name']).".".$mime_info['ext'];
$file['status']="successfully uploaded";
$file['url'] = $stored_info['url'];
$file['stored'] = $stored_info['stored'];
$slug = explode(".", $file['name']);
$file['dimensions'] = array(
width => $mime_info['img_info'][0], 
height => $mime_info['img_info'][1], 
bits=>$mime_info['img_info']['bits'], 
channels=>$mime_info['img_info']['channels'], 
mime_type=>$mime_info['img_info']['mime'],
slug=>$slug[0],
ext=>$mime_info['ext']
);
$final_list[]=$file; 
} else { 
// if the file fails one of our checks, we add it to the list, but we set a "failed" status and add the errors 
//array to identify why it failed. We'll log all errors, but discard the file completely from the server so we won't have any security risks.
$file['error']=1; // we use this variable for our usort, to push errored files to the bottom of the array.
$file['status']="failed to upload";
$file['errors']=$errors;
$final_list[] = $file;
}
} 
 usort($final_list, 'ngg_pup_sort_file_list'); // sorts our array so all the errored files are last, so we get a nice clean layout when we show the users. 
 return $final_list; 
} ?>
<?php function ngg_pup_check_role_allowed($user_info, $options) {
if (in_array(strtolower($user_info->roles[0]), array_map('strtolower', $options))) { 
//because for some stupid reason, the $wp_roles and $current_user stores store roles in different cases, we use strtolower to compare.
return "passes";
} else {
return "fails";
}
}?>
<?Php function ngg_pup_check_file_size($file, $options) {
if($file['size'] <= $options) {
return "passes";
} else {
return "fails";
}
}?>
<?php function ngg_pup_check_if_extension_allowed($file, $options) { // Double checks if the extension is allowed based on the backend options.
$ext = ngg_pup_get_true_extension($file['tmp_name']);
if ((in_array($ext['ext'], $options, true))) {
return array (status=>"passes", img_info=>$ext['img_info'] , ext=>$ext['ext']);
} else {
return array (status=>"fails", img_info=>$ext['img_info'],ext=>$ext['ext']);
}
}?>
<?php function ngg_pup_check_is_real_mime ($file) { // checks if the temp file type is an image, 
$is_image = getimagesize($file['tmp_name']); 
if ($is_image['mime'] != "") {
return "passes";
} else { return "fails"; }
} ?>
<?php function ngg_pup_check_file_mime_from_query($file) { //What this does, is check the mime type, strips after the / and returns "image" if it's actually
// an image mime type. Typically the string would look like image/-jpeg, image/png etc. This doesn't check the file itself, only what the user tries to
// tell us the file type is. For example if the user uploads image.php.jpg, this won't catch it. Seems redunant to use this, but it's an extra
// step worth taking just because we can do it. 
$checked_type = $file['type'];
$checked_type = substr($checked_type, 0, stripos($checked_type, "/") );
if ($checked_type =="image") {
return "passes";
} else {
return "fails";
}
}?>
<?php function ngg_pup_compare_file_mime ($file) { // this function uses php's getimagesize function to check if the file is actually mime-type image. Say
// for example the user tries to get cute and upload a file like image.php.jpg in an attempt to get a script/php file through the extension check
// this will catch it. 
//$image_mime = image_type_to_mime_type(exif_imagetype($file['tmp_name']));
//$is_image = getimagesize($file['tmp_name']); 
//if($image_mime == $is_image['mime']) {
return "passes";
//} else {
//return "fails";
//} 
} ?>
<?php function ngg_pup_check_if_file_exists($file, $path) { // one of the checks, to ensure the file name doesn't already exist in our upload destination.
if (file_exists($path."/".$file['name'])) {
return "fails";
} else {
return "passes";
}
}?>
<?php function ngg_pup_get_true_extension($file) { 
$imagetype = getimagesize($file);
if(empty($imagetype['mime'])) return false;
switch($imagetype['mime']) {
case 'image/bmp': return array(ext=>'bmp', img_info=>$imagetype);
case 'image/gif': return  array(ext=>'gif',img_info=>$imagetype);
case 'image/jpeg': return array(ext=>'jpg',img_info=>$imagetype);
case 'image/png': return array(ext=>'png',img_info=>$imagetype);
default: return false;
}
}
?>
<?php function ngg_pup_sanitize_to_numbers($array) { // takes an array, checks that everything is a number, and returns the array
foreach ($array as $arr) {
$arr = (int)$arr; 
if(is_numeric($arr)) {
 $made_array[] = $arr;
} else {
return "problem with the array";
}
}
return $made_array;
} ?>
<?php function ngg_pup_sanitize_and_build_results() { //for the moderation portion.  Validates and returns results. 
// this bit here takes the POSTED results, sanitizes, builds one nice array, and retruns for 
// processing and displaying the results. 
$batch_radio = ngg_pup_sanitize_to_numbers($_POST['batch_radio']);
$batch_gid = ngg_pup_sanitize_to_numbers($_POST['batch_gid']);
$pid = ngg_pup_sanitize_to_numbers($_POST['pid']); 
$radio = ngg_pup_sanitize_to_numbers($_POST['radio']); 
$gid = ngg_pup_sanitize_to_numbers($_POST['gid']); 
$delete_form = ngg_pup_sanitize_to_numbers($_POST['delete_form']); 
$batch_tags = ngg_pup_sanitize_tags($_POST['batch_tags']);
$cnt = 0;
//foreach ($_POST['tags'] as $dummy) {
//$tags[]= ngg_pup_sanitize_tags ($dummy);
//$cnt++;
//}  this section currently disabled until I figure out a better tagging system.
// end sanitzation routine. 
$array = implode(', ',$pid); //used to build query to db
$file_list = ngg_pup_get_queue_info('where picture_id IN ('.$array.')'); //rebuilds file list passed on the pids passed through post
$gids = array_merge((array)$gid, (array)$batch_gid); //because we want to do a batch DB query for both the batched gids and the indivual ones. 
$array = implode(', ',$gids); //used to build query to db
$gallery_list = ngg_pup_get_gallery_list('where gid IN ('.$array.')'); //rebuilds gall list passed on the gids passed through post
$cnt=0; //for looping and re-building array 
foreach ($file_list as $file) { //organizes the array, so that the sanitized input coincides with the file
if ($radio[$cnt] == 0) { // if the files radio button is batch proccess then we retad the file based on what the batch proccess is. 
$file->f_gid = $batch_gid[0];
$file->f_radio = $batch_radio[0];
$file->f_delete_form = 0;//default setting of just delete. May change this to include more options later. 
$file->tags = $batch_tags;
} else {
$file->f_gid = $gid[$cnt];
$file->f_radio = $radio[$cnt];
$file->f_delete_form = $delete_form[$cnt];
$file->tags = $tags[$cnt];
}
$file->f_pid = $gid[$cnt]; //because the pid stays the same regardless of whether it's batched or not. 
$files_array[]=$file;
$cnt ++;
}
$files_array = array($files_array, $gallery_list);
return $files_array;
} ?>
<?php function ngg_pup_moderate_inital_checks ($options) {
$token_id = stripslashes( $_POST['token'] ); 
if (get_transient( 'token_' . $token_id )) { // checks for and kills token if it's been used. 
delete_transient( 'token_' . $token_id );
if ($_POST['batch_radio'][0]=="3") { // instead of proccessing results, we make a new gallery
$results = pup_create_gallery ($options); ?>  
<span class="ngg_pup_lockout_message">
<?php echo $results['error_msg']; ?>
</span>
<?php 
} else {
$files_array = ngg_pup_sanitize_and_build_results();//sanitize
$results = ngg_pup_process_results($files_array); // process and siaply
}
} else { 
$token_id = md5( uniqid( "", true ) ); //generates a new MD5 token to prevent form resubmission. we'll put this in a hidden feld in the upload form
set_transient( 'token_' . $token_id, 'safe_to_delete', 60*10 ); // Sets it to expire in 10 minute. 
ngg_pup_create_mod_page($token_id, $options); // builds a moderation session page.
}

} ?>
<?php function ngg_pup_check_transfer($file, $destination) { //double checks to make sure we have a file to move, and it doesnt already exist in the destination.
$target = $destination->path."/".$file->file_name;
$file_to_move = $file->file_path.$file->file_name;
if (file_exists($file_to_move)) { //checks if file we're moving exists. It should. 
$a = "yes proceed";
} else {
$a = "error";
}
if (!file_exists($target)) { // checks if the destination doesn't already have a file with the same name. It shouldn't. 
$b = "yes proceed";
} else {
$b = "error";
}
if  (file_exists($destination->path."/")) {
$c = "yes proceed";	
} else {
$c = "error";
}
return array($a, $b, $c);
}?>
<?php function ngg_pup_sanitize_tags ($tag_string) { // sanitizes and builds tag list;
// takes tag string, removes white space, erroneous commas and anything but alphanumeric
// charachters, then builds it back into a string. 
$tag_string = preg_replace("/[^a-zA-Z0-9,\s]/", "", $tag_string);
$array = explode(',', $tag_string);
foreach ($array as $arr) {
$replace = str_replace(',', '', $arr );
$replace = str_replace(' ', '', $arr);
if(($replace !="") && ($replace != " ")) {
$new_array[] = $replace;;
}
}
if ($new_array !="") {
$tag_list = implode(",", $new_array);
}
return $tag_list;
} ?>
<?php function ngg_pup_get_user_ip() { // grab the uploaders IP for logging purposes.
$user_ip=long2ip(ip2long($_SERVER['REMOTE_ADDR']));
if(filter_var($user_ip, FILTER_VALIDATE_IP)) {
// do nothing, we keep it the way it is if it's valid;
} else {
$uploader_ip = "0"; // sets to 0 because we couldn't get the proper IP and we need to store something. 
} 
return $uploader_ip;
}?>
<?php function ngg_pup_sanitize_gal_name ($name) {
$name = mysql_real_escape_string($name);
$name = preg_replace("/[^a-zA-Z0-9-_]+/", "", $name);
return $name;
}?>