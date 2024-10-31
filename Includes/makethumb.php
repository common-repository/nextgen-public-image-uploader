<?php function ngg_pup_create_thumb($file, $args){
// this expects $file to include:
// dir - where the $file is located
// name - file name with extension
$file_info = ngg_pup_get_true_extension($file['dir'].$file['name']);
$file['ext'] = $file_info['ext'];

if (file_exists($args['target_dir'].$args['target_name'].$file['ext'])) { //checks to make sure thumb file doesn't exist. 
return array(
msg=> "thumbs_$file[name] could not be created, file already exists.",
code=> 1);
}

if (!file_exists($file['dir'].$file['name'])) { //checks to make sure source image exists. 
return array(
msg=> "thumbs_$file[name] could not be created source image does not exist.",
code=> 1);  
}

if (!file_exists($args['target_dir'])) {
return array(
msg=> "thumbs_$file[name] could not be created, destination directory does not exist or is not writeable.",
code=> 1);	
}

 if($file['ext'] == 'gif'){
  $new_file = imagecreatefromgif($file['dir'].$file['name']); 
 }elseif($file['ext'] == 'jpg'){
  $new_file = imagecreatefromjpeg($file['dir'].$file['name']);
 }elseif($file['ext'] == 'png'){
  $new_file = imagecreatefrompng($file['dir'].$file['name']);
 }else{
  return array(
msg=> "thumbs_$file[name] could not be created, unknown file extension $file[ext].",
code=> 1);
 }
 
 $width = imagesx($new_file);
 $height = imagesy($new_file);
 
 # Ratio width/height
 $ratio = $width/$height;
 
 if($args['max_width']/$args['max_height'] > $ratio){
  $args['max_width'] = floor($args['max_height']*$ratio);
 }else{
  $args['max_height'] = floor($args['max_width']/$ratio); 
 }
 
 # Destination image link resource
 $dst_image = imagecreatetruecolor($args['max_width'],$args['max_height']);
 imagecopyresampled($dst_image, $new_file, 0, 0, 0, 0, $args['max_width'], $args['max_height'], $width, $height);
 
 if($file['ext'] == 'gif'){
  imagegif($dst_image, $args['target_dir'].$args['target_name'].'.'.$file['ext']);
 }elseif($file['ext'] == 'jpg'){
  imagejpeg($dst_image,$args['target_dir'].$args['target_name'].'.'.$file['ext'], $args['quality']);
 }elseif($file['ext'] == 'png'){
  imagepng($dst_image, $args['target_dir'].$args['target_name'].'.'.$file['ext']);
 }
 
 # Clean Up Images
 imagedestroy($new_file);        
 imagedestroy($dst_image);
return array(
msg=> "thumbs_$file[name] thumbnail successfully created",
code=> 0);

 
} ?>
<?php function thumbcreatefrombmp( $filename )
{
    $file = fopen( $filename, "rb" );
    $read = fread( $file, 10 );
    while( !feof( $file ) && $read != "" )
    {
        $read .= fread( $file, 1024 );
    }
    $temp = unpack( "H*", $read );
    $hex = $temp[1];
    $header = substr( $hex, 0, 104 );
    $body = str_split( substr( $hex, 108 ), 6 );
    if( substr( $header, 0, 4 ) == "424d" )
    {
        $header = substr( $header, 4 );
        // Remove some stuff?
        $header = substr( $header, 32 );
        // Get the width
        $width = hexdec( substr( $header, 0, 2 ) );
        // Remove some stuff?
        $header = substr( $header, 8 );
        // Get the height
        $height = hexdec( substr( $header, 0, 2 ) );
        unset( $header );
    }
    $x = 0;
    $y = 1;
    $image = imagecreatetruecolor( $width, $height );
    foreach( $body as $rgb )
    {
        $r = hexdec( substr( $rgb, 4, 2 ) );
        $g = hexdec( substr( $rgb, 2, 2 ) );
        $b = hexdec( substr( $rgb, 0, 2 ) );
        $color = imagecolorallocate( $image, $r, $g, $b );
        imagesetpixel( $image, $x, $height-$y, $color );
        $x++;
        if( $x >= $width )
        {
            $x = 0;
            $y++;
        }
    }
    return "Thumbnail created successfully.";
}?>