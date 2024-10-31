<?Php // all DB and file functions are here // ?>
<?php function ngg_pup_move_file ($file, $destination) { // Physically moves a file both are single entries from the full array. 
$target = $destination->path.$file->file_name;
$file_to_move = $file->file_path.$file->file_name;
if (!file_exists($target) && (file_exists($file_to_move))) {
rename($file_to_move, $target); // moves the file to wherever it's going.	
} else {
echo "something went wrong here.";	
}
}?>
<?php function ngg_pup_remove_from_db ($file) { // uses array of picture_ids. 
global $wpdb;
$picture_id = $file->picture_id;
$table = $wpdb->prefix."NGG_Upload_queue"; 
$wpdb->query(
$wpdb->prepare(
" DELETE FROM $table WHERE picture_id = $picture_id ",13));
}?>
<?php function update_db_with_file($file) {
   global $wpdb;
   $table_name = $wpdb->prefix . "NGG_Upload_queue";
   $rows_affected = $wpdb->insert( $table_name, array( 
   'file_name' => $file['name'], 
   'file_size' => $file['size'], 
   'uploader_name' => $file['uploader_name'], 
   'uploader_ip' => $file['uploader_ip'], 
   'upload_date' => current_time('mysql'), 
   'file_path' => $file['stored'], 
   'file_url' => $file['url'], 
   'moderation_status' => $file['moderation_status'],
   'dimensions' => serialize($file['dimensions'])
   ) );
}?>
<?php function ngg_pup_add_to_ngg_db($file) {
   global $wpdb;
   $table_name = $wpdb->prefix . "ngg_pictures";
   $rows_affected = $wpdb->insert( $table_name, array( 
   'image_slug' => $file->dimensions['slug'], 
   'galleryid' => $file->f_gid, 
   'filename' => $file->file_name, 
   'description' => " ",
   'alttext' => $file->dimensions['slug'],    
   'imagedate' => $file->upload_date, 
   'exclude' => "0", 
   'sortorder' => "0",    
   'meta_data' => $file->meta_data, 
      ) );
   
}?>
<?php function ngg_pup_save_the_files($array) { //Saves files passed to it. 
// source = source path with filename
// dest = target path without filename
// file = filename plust extension
move_uploaded_file($array['source'], $array['dest'].$array['file']);
} ?>
<?php function ngg_pup_get_queue_info ($optional) { //builds our queue The $optional is an optional where clause. 
global $wpdb;
$queue = $wpdb->get_results(
$wpdb->prepare("
SELECT *
FROM ".$wpdb->prefix."NGG_Upload_queue
$optional
limit 20
",13));
$cnt=0;
foreach ($queue as $dummy) {
$queue[$cnt]->dimensions = unserialize($dummy->dimensions); 
$cnt++;
}
return $queue;
}?>
<?php function ngg_pup_direct_to_gallery_precheck($shortcodes) { // because certain things will be handled differently, we have to prepare some of the data. 
$gal = ngg_pup_get_gallery_list('where gid IN ('.$shortcodes['gid'].')'); 
if(!empty($gal)) {
return array(gal_id=>$shortcodes['gid'], gal_info=>$gal);
} else {
return array(gal_id=>0);
}
}?>
<?php function ngg_build_abs_path() { //constructs a true file path.
if (!defined(ABSPATH)) {
$path = ABSPATH;
$path = rtrim($path, '/\\');// removes all trailing slashes /\        
return $path;        
} else {
return FALSE;    
}
} ?>
<?Php function ngg_pup_get_abs_dir($path) {
            
        $path = str_replace(array('/', '\\'), DIRECTORY_SEPARATOR, $path);
        $parts = array_filter(explode(DIRECTORY_SEPARATOR, $path), 'strlen');
        $absolutes = array();
        foreach ($parts as $part) {
            if ('.' == $part) continue;
            if ('..' == $part) {
                array_pop($absolutes);
            } else {
                $absolutes[] = $part;
            }
        }
        return implode(DIRECTORY_SEPARATOR, $absolutes);
} ?>
<?php function ngg_pup_build_meta_data($file, $files_array) {
$array = array(width=>$file->dimensions['width'],
               height=>$file->dimensions['height'],
               full=>array(
                           filename=>$file->file_name,
                           width=>$file->dimensions['width'],
                           height=>$file->dimensions['height']
                           ),                          
               thumbnail=>array(
                           width=>120,
                           height=>90,
                           filename=>'thumbs_'.$file->file_name,
                           generated=>'0.62859300 1377499003' //fudging this bit because I have no clue what it is. 
                           ),
                aperature=>false,          
                credit=>false,
                camera=>false,
                caption=>false,
                created_timestamp=>false,
                copyright=>false,
                focal_length=>false,
                iso=>false,
                shutter_speed=>false,
                flash=>false,
                title=>false,
                keywords=>$file->tags,
                saved=>false,
                );
                $array = serialize($array);
                return $array;
}?>
<?php function ngg_pup_get_gallery_list($optional) { // gets all the NGG_gallery info we need. The $optional is an optional where clause. 
global $wpdb;
$gallery_list = $wpdb->get_results(
$wpdb->prepare("
SELECT *
FROM ".$wpdb->prefix."ngg_gallery
$optional
ORDER BY gid",13));
return $gallery_list;
} ?>
<?php function ngg_pup_get_temporary_upload_url($path) { // builds relative file URL. 
if(!defined(abspath)) {
$tmp_upload_dir = site_url();
 $tmp_upload_dir = $tmp_upload_dir."/".$path;       
return $tmp_upload_dir;    
} else {
$tmp_upload_dir = wp_upload_dir(); 
$tmp_upload_dir = $tmp_upload_dir['baseurl']."/".$path;    
return $tmp_upload_dir;
}
}?>
<?php function ngg_pup_sort_file_list($a, $b) { // sorts our $final_list out so the error files are last
 return $a['error'] > $b['error'];
}?>
<?php function ngg_pup_get_temporary_upload_dir() { // builds relative upload path. 
$tmp_upload_dir = ngg_build_abs_path();
if ($tmp_upload_dir !="FALSE") {
return $tmp_upload_dir; 
} else {
$tmp_upload_dir = wp_upload_dir(); 
$tmp_upload_dir = $tmp_upload_dir['basedir'];
return $tmp_upload_dir; 
}
} ?>
<?php function ngg_pup_make_files_array($files = array()) // creates our file array for us //
{    $temp_files_list = array();
    if(is_array($files) && count($files) > 0)
    {
        foreach($files as $key => $file)
        {
            foreach($file as $index => $attr)
            {
                $temp_files_list[$index][$key] = $attr;
            }
        }
    } 
    return $temp_files_list;
}?>
<?php function ngg_pup_send_email($final_list, $options) {
if ($options['email_notification_on_upload']=="checked") {
$count = count($final_list);
$date = date('d-m-Y');   
$ip = $final_list[0]['uploader_ip']; 
$uploader = $final_list[0]['uploader_name'];
$email = $options['email_notification_address'];    
$subject = "A user has uploaded new images to your site";
$message = "On $date, $uploader uploaded $count new images from IP $ip .";
wp_mail( $email, $subject, $message );	
} else {
/// don't do anything because option isn't set. 	
}
}?>
<?php function ngg_pup_make_new_gallery($gal_info) {
   global $wpdb;
   $table_name = $wpdb->prefix . "ngg_gallery";
   $rows_affected = $wpdb->insert( $table_name, array( 
   'name' => $gal_info['gal_name'], 
   'slug' => $gal_info['gal_slug'],
   'path' => $gal_info['gal_path'],    
   'title' => $gal_info['gal_title'], 
   'galdesc' => $gal_info['gal_desc'], 
   'pageid' => $gal_info['gal_page_id'],    
   'previewpic' => $gal_info['gal_preview_pic_id'],
   'author' => $gal_info['user_name'],  
      ) );
   
}?>
<?php function pup_create_gallery ($options) {
$user_ID = get_current_user_id();
if (($options['Allow_user_gal_create']=="checked") &&(ngg_pup_check_role_allowed(wp_get_current_user(), $options['allowed_roles_create_gal'])=="passes")) {
//do nothing, proceed;    
} else {
return array('error_msg'=>"Sorry, you do not have access to create galleries at this time.", 'code'=>1);    
}        
//initial check to see if role has access as extra security. 
$name = ngg_pup_sanitize_gal_name ($_POST['create_gallery_name']);
$gal_check = ngg_pup_get_gallery_list("where name like '$name%%' OR title like '$name%%' OR slug like '$name%%'");
if (!empty($gal_check)) {
foreach ($gal_check as $gal) {
$titles[]=$gal->title;
$names[]=$gal->name;
$slugs[]=$gal->slug;
}
$a=1;
while ($a <= 11) { //tries to find a clean name we can use. 
if (!in_array($name."-".$a, $titles)&&(!in_array($name."-".$a, $names)) && (!in_array($name."-".$a, $slugs))) {
$name=$name."-".$a;
break;
}
$a++;   
}    
if ($a==11) {
return array('error_msg'=>"Could not create new gallery, because there are already galleries with that name.
Please try a different name.", 'code'=>1);
}
}   // if gal_list comes back empty, no gallery with the name exists and we can just create normally.

$path=$options['Nextgen_full_directory'].$name."/";
$thumbs_path = $path."/thumbs";
if ((!is_dir($path)) && (!file_exists($path)) && (!is_dir($path)) && (!file_exists($path)))  {
if(!mkdir($path, 0777, true)) {
return array('error_msg'=>"Could not create gallery directory.", 'code'=>1);
}
if(!mkdir($thumbs_path, 0777, true)) {
return array('error_msg'=>"Could not create thumbnail directory.", 'code'=>1);
}    
} else {
return array('error_msg'=>"Could not make new directory, because it already exists. Try a different name.", 'code'=>1);  
}

$path = $options['Nextgen_base_directory']."/".$name."/";    
   $gal_info = array(
   'gal_name' => $name,
   'gal_slug' => $name,
   'gal_path' => $path,
   'gal_desc' => "none",
   'gal_title' => $name,
   'gal_page_id' => '', //will likely want to add an option for users to pick a default page. 
   'gal_preview_pic_id' =>"",
   'user_name' => $user_ID,
   );
ngg_pup_make_new_gallery($gal_info);
return array('error_msg'=>"Gallery $name created successfully", 'code'=>0, 'gal_info'=>$gal_info);
} ?>