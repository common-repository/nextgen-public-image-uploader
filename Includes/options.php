<?php class wctest {

 
public function getoptions() {
     return $this->options; 
} 
public function setoptions() {
$this->options = get_option('NGG_User_upload_option_array'); 
} 
public function getextensions() {
     return $this->extensions; 
} 
public function setextensions() {
     $this->extensions = array("jpg","png","gif","jpeg","bmp"); 
} 

public function __construct() {
$dummy = $this->setoptions();
$extensions=$this->setextensions();
		
		if ( is_admin() ){
            add_action( 'admin_menu', array( $this, 'add_plugin_page' ) );
            add_action( 'admin_init', array( $this, 'page_init' ) );
        }
    }
	
    public function add_plugin_page(){
        // This page will be under "Settings"
        add_options_page( 'Settings Admin', 'NGG User Uploader', 'manage_options', 'NGG_user_upload_settings', array( $this, 'create_admin_page' ) );
    }

    public function create_admin_page() {
	
	?>
	<div class="wrap">
	     
	    <h2>Welcome to Next Gen Public Uploader</h2>		
		
	    <form method="post" action="options.php">
	        <?php
                    // This prints out all hidden setting fields
		    settings_fields( 'NGG_User_upload_option_array' );	
			do_settings_sections( 'NGG_user_upload_settings' );
			
		?>
	        <?php submit_button(); ?>
	    </form>
	</div>
	<?php
    }
	
    public function page_init() {		
        register_setting( 'NGG_User_upload_option_array', 'NGG_User_upload_option_array', array( $this, 'check_ID' ) );
            
            add_settings_section(
            'setting_section_id',
            'Setting',
            array( $this, 'print_section_info' ),
            'NGG_user_upload_settings'
        );	
            
        add_settings_field(
            'Field 1', 
            'Default Upload folder', 
            array( $this, 'settings1' ), 
            'NGG_user_upload_settings',
            'setting_section_id'			
        );		
		add_settings_field(
            'Field 2', 
            'Limit User uploads by role', 
            array( $this, 'settings2' ), 
            'NGG_user_upload_settings',
            'setting_section_id'			
        );		
	
		add_settings_field(
            'Field 3', 
            'Allowed image extensions', 
            array( $this, 'settings3' ), 
            'NGG_user_upload_settings',
            'setting_section_id'			
        );
		add_settings_field(
            'Field 4', 
            'Limit Upload File Size', 
            array( $this, 'settings4' ), 
            'NGG_user_upload_settings',
            'setting_section_id'			
        );
        add_settings_field(
            'Field 5', 
            'Misc. Settings', 
            array( $this, 'settings5' ), 
            'NGG_user_upload_settings',
            'setting_section_id'            
        );
            add_settings_field(
            'Field 6', 
            'NGG Gallery Directory', 
            array( $this, 'settings6' ), 
            'NGG_user_upload_settings',
            'setting_section_id'            
        );  
          add_settings_field(
            'Field 7', 
            'Allow users to moderate queue', 
            array( $this, 'settings7' ), 
            'NGG_user_upload_settings',
            'setting_section_id'            
        );
        add_settings_field(
            'Field 8', 
            'Allow users to create galleries.', 
            array( $this, 'settings8' ), 
            'NGG_user_upload_settings',
            'setting_section_id'            
        );
    }
	
    public function check_ID( $input ) { //validation and sanitizing here, then we update the DB
            $extensions = $this->getextensions();
			
			$input['email_notification_address'] = sanitize_email( $input['email_notification_address']); 
			if ($input['email_notification_address'] =="") {
			$input['email_notification_address'] = get_option('admin_email');    
			}
			 
			
			$input['Upload_directory'] = preg_replace('~[^A-Za-z0-9/-]~','',$input['Upload_directory']);
			$input['Nextgen_base_directory'] = preg_replace('~[^A-Za-z0-9/-]~','',$input['Nextgen_base_directory']);
            $input['Nextgen_base_directory'] = rtrim($input['Nextgen_base_directory'], '/\\');
            $input['Nextgen_base_directory'] = ltrim($input['Nextgen_base_directory'], '/\\');
            $input['Nextgen_full_directory'] = ngg_build_abs_path()."/".$input['Nextgen_base_directory']."/"; 
			$input['Nextgen_url_path'] = site_url()."/".$input['Nextgen_base_directory']."/";
			$input['Upload_directory_base'] = ngg_pup_get_temporary_upload_dir();
			// once we've figured out where the user wants to store files, we check and see if the directory
            // exists, and if it's not make it.
            
            
			if ($input['Upload_directory'] == "") {
			$input['Upload_directory'] = "queue/"; //in case nothings set, we set a default option. Also useful for when we initialize plugin for the first time. 
			} else {
			$input['Upload_directory'] = rtrim($input['Upload_directory'], '/\\');
            $input['Upload_directory'] = ltrim($input['Upload_directory'], '/\\');
			}
    
            
			$path = $input['Upload_directory_base']."/".$input['Upload_directory'];
			$input['Upload_full_directory']=$path."/";
            
			if ((!is_dir($path)) && (!file_exists($path)))  {
			    
                       if(!mkdir($path, 0777, true)){
                       echo "Sorry, could not create $path . Please check to make sure the directory
                       does not exist, and you have proper access to create directories.";    
                       }
                
			            }
			         
			$input['enable_moderation_queue'] = $this->check_form_box($input['enable_moderation_queue']);      
			$input['Limit_Upload_by_user_role'] = $this->check_form_box($input['Limit_Upload_by_user_role']); //checks status of option first, because we use it below
			$input['Allow_user_moderation'] = $this->check_form_box($input['Allow_user_moderation']);  //checks status of option first, because we use it below
			$input['email_notification_on_upload'] = $this->check_form_box($input['email_notification_on_upload']);
            $input['enable_NGG_support'] = $this->check_form_box($input['enable_NGG_support']);
            $input['Allow_user_gal_create'] = $this->check_form_box($input['Allow_user_gal_create']);
            
			global $wp_roles; 
			$roles = $wp_roles->get_names();
            foreach ($roles as $role) {
			    
            if ($input['Allow_user_gal_create'] != "checked") {
            $input[$role."_can_create_gal"] = "";                
            } else {
            $input[$role."_can_create_gal"] = $this->check_form_box($input[$role."_can_create_gal"]);    
            }  
             
			if ($input[$role."_can_create_gal"]=="checked") { // checks to see if role can create galleries. If so stores it in array. 
            $allowed_roles_create_gal[]=$role;
            }
            
			if($input['Limit_Upload_by_user_role'] !="checked") { //if enabled, disables uploads, except for admin
            $input[$role."_can_upload"] = "";
			} else { //if option is checked, then we proceed with normal role checking
            $input[$role."_can_upload"] = $this->check_form_box($input[$role."_can_upload"]);
			}			

            if($input['Allow_user_moderation'] !="checked") { // if enabled, disables moderation, except for admin
			$input[$role."_can_moderate"] = "";
			} else { //if option is checked, then we proceed with normal role checking
			$input[$role."_can_moderate"] = $this->check_form_box($input[$role."_can_moderate"]);
			}
			
			if ($role=="Administrator") { // because admin should ALWAYS be allowed. Just in case we miss something, re-enables options for admins only
			$input["Administrator_can_moderate"] = "checked";
			$input["Administrator_can_upload"] = "checked";
			}
						
			if ($input[$role."_can_upload"]=="checked") { // checks to see if role can upload, if so stores the role in an array we can check against on the front end. 
			$allowed_roles_upload[]=$role;
			}
                        					
			if ($input[$role."_can_moderate"]=="checked") { // checks to see if role can moderate, if so stores the role in an array we can check against on the front end. 
			$allowed_roles_moderate[]=$role;
			}
					
			}
			
			$input['allowed_roles_upload'] = $allowed_roles_upload; //updates option array after loop
			$input['allowed_roles_moderate'] = $allowed_roles_moderate; //updates option array after loop 
			$input['allowed_roles_create_gal'] = $allowed_roles_create_gal;
			foreach ($extensions as $ext) {
			$input[$ext."_allowed"] = $this->check_form_box($input[$ext."_allowed"]);
			
			if (isset($input[$ext."_allowed"])&& ($input[$ext."_allowed"] == "checked")) {
			$allowed_extensions[]=$ext;
			}
			
			}
			$input['allowed_extensions'] = $allowed_extensions;
			
			if((!is_numeric($input['Upload_Size_Limit']) OR ($input['Upload_Size_Limit'] <= 0))) {
			$input['Upload_Size_Limit'] = 100000; // if not a number, or lower than 100kb then set it to 100kb(100000)
			} else {
			$input['Upload_Size_Limit'] = ($input['Upload_Size_Limit'] * 1000); 
			}
			$input['first_run'] = "yes"; //used for activation/deactivation check. Don't change this.
		return $input;	//updates options in db.
        }
        
     function check_form_box($mid) { // checking if a box is checked or not
	 if(isset($mid)) {
	 return "checked";
	 } else {
	 return "";
	}
	}
	
	
	
    public function print_section_info(){
        echo 'Enter your setting below:';
		
    }
	
public function settings1(){ //upload directory setting?>
<?php $options = $this->getoptions();?> 
<div class="options_box">
<span class="options_box_header">
<?php echo ngg_pup_get_temporary_upload_dir()."/"; ?>
<input type="textbox" id="NGGpup_textbox" name="NGG_User_upload_option_array[Upload_directory]" value="<?php echo $options['Upload_directory']?>" <?php echo $options['Upload_directory']?>/>
</span>
<br>Please choose where you want uploads to be stored. This is a temporary directory, until they are moved from the 
moderation queue. If this folder does not already exist, it will be created for you automatically when you hit save.
</div>
<?php }	
	
public function settings2(){ //user upload settings ?>
<?php $options = $this->getoptions();?>
<div class="options_box">
<input type="checkbox" name="NGG_User_upload_option_array[Limit_Upload_by_user_role]" value="<?php echo $options['Limit_Upload_by_user_role']?>" <?php echo $options['Limit_Upload_by_user_role']?>/>
Enabling this option will allow you to set uploading privledges per user level. 
If this is disabled, only administrator level users will be allowed to upload. 
</div>
<?php if ($options['Limit_Upload_by_user_role'] == "checked") {?>	
<?php global $wp_roles; $roles = $wp_roles->get_names();?>
<div class="selection_box">
<span class="options_box_header"> Select which users are allowed to upload </span>
<?php foreach ($roles as $role) {?>
<div class="options_checkbox_wrap">
<input type="checkbox" name="NGG_User_upload_option_array[<?php echo $role."_can_upload]"?>]" value="<?php echo $options[$role.'_can_upload'];?>" <?php echo $options[$role.'_can_upload'];?>/>
<?php echo $role;?>
</div>
<?php }?>
</div>
<?php } ?>

<?php }

public function settings3(){ //upload directory setting?>
<?php $options = $this->getoptions();?>
<?php $extensions = $this->getextensions();?>
<div class="selection_box">
<span class="options_box_header"> Select which image types are allowed. </span>
<?php foreach ($extensions as $ext) {?>
<div class="options_checkbox_wrap">
<input type="checkbox" name="NGG_User_upload_option_array[<?php echo $ext."_allowed"?>]" value="<?php echo $options[$ext.'_allowed'];?>" <?php echo $options[$ext.'_allowed'];?>/>
<?php echo $ext;?>
</div>
<?php }?>
</div>

<?php }	
public function settings4(){ //upload directory setting?>
<?php $options = $this->getoptions();?>
<div class="options_box">
<span class="options_box_header">
<input type="textbox" id="NGGpup_textbox" name="NGG_User_upload_option_array[Upload_Size_Limit]" value="<?php echo ($options['Upload_Size_Limit']/1000)?>" <?php echo $options['Upload_Size_Limit']?>/>
KB
</span>
<br>This field is numerical, and is measured in kilobytes. For example, 50 = 50kb, 1000 = 1mb. Default is 100( 100 KB ). 
<br>
<br>Please note, many servers have an upper limit on upload sizes. If you set this option above that limit, the server limit will override this setting. 
</div>

<?php }	

public function settings5(){ //Enable/Disable Gallery/Moderation queue.?>
<?php $options = $this->getoptions();?>
<div class="options_box" style="width:100%;margin-bottom:20px">
<span class="options_box_header">
<input type="checkbox" name="NGG_User_upload_option_array[enable_moderation_queue]" value="<?php echo $options['enable_moderation_queue']?>" <?php echo $options['enable_moderation_queue'];?>/>
Enable Moderation queue.
</span>
If this is disabled, all uploads will just be stored in the upload directory you specify. 
</div>
<div class="options_box" style="width:100%;margin-bottom:20px">
<span class="options_box_header">
<input type="checkbox" name="NGG_User_upload_option_array[enable_NGG_support]" value="<?php echo $options['enable_NGG_support'];?>" <?php echo $options['enable_NGG_support'];?>/>
Enable NextGen Gallery Uploading. 
</span>
This option enables direct to gallery uploading. If you do not have NextGen Gallery Installed, do not enable this option.  
</div>
<div class="options_box" style="width:100%;margin-bottom:20px">
<span class="options_box_header">
<input type="checkbox" name="NGG_User_upload_option_array[email_notification_on_upload]" value="<?php echo $options['email_notification_on_upload'];?>" <?php echo $options['email_notification_on_upload'];?> />
Notify Me by e-mail when a user uploads images.
</span>
<input type="textbox" id="NGGpup_textbox" name="NGG_User_upload_option_array[email_notification_address]" value="<?php echo $options['email_notification_address']?>"/>
<br>
Please enter an e-mail address. If you do not enter a valid e-mail address, and this option is enabled, then e-mails will be sent to the e-mail address configured in wordpress's options instead. 
</div>
<?php } 

public function settings6(){ //NGG's upload directory setting?>
<?php $options = $this->getoptions();

$ngg_options = get_option('ngg_options');
if ($ngg_options['gallerypath']!="") {
$options['Nextgen_base_directory'] = $ngg_options['gallerypath']; 	
}
?>
<div class="options_box">
<span class="options_box_header">
<?php echo ngg_pup_get_temporary_upload_dir()."/"; ?>
<input type="textbox" id="NGGpup_textbox" name="NGG_User_upload_option_array[Nextgen_base_directory]" value="<?php echo $options['Nextgen_base_directory']?>" <?php echo $options['Nextgen_base_directory'];?> required/>
</span>
<br>This plugin attempts to populate this field automatically based on NextGens settings, but if it's blank you will have to enter it manually.
<br>
<br>If it's blank, please enter the directory in which NextGen Gallery stores its galleries. You can find this setting in NextGens option pages.
<br>
<br><b>This field is mandatory, without it you will not be able to use the upload to gallery function or moderation part of this plugin.</b>
</div>
<?php }	

public function settings7(){ //moderation queue settings?>
<?php $options = $this->getoptions();?>
<?php if ($options['enable_moderation_queue'] =="checked") {
} else {
return;    
} ?>
<div class="options_box">
<input type="checkbox" name="NGG_User_upload_option_array[Allow_user_moderation]" value="<?php echo $options['Allow_user_moderation']?>" <?php echo $options['Allow_user_moderation']?>/>
Enabling this option will allow you to set which user levels are allowed to access the moderation queue. 
The moderation queue is where all uploads go until they are reviewed and moved. Access to this queue
will allow users to move pictures into different galleries, edit galleries, tag images and more.
If this is left unchecked, only administrators will be allowed to moderate the queue. 
<br>
<br><b>Please choose who can access this feature carefully, as while it is secure, you are give your users
access to remove or move pictures as they see fit. </b>
</div>
<?php if ($options['Allow_user_moderation'] == "checked") {?>
<div class="selection_box">
<span class="options_box_header"> Select which users can use moderation queue. </span>
<?php global $wp_roles; $roles = $wp_roles->get_names();?>
<?php foreach ($roles as $role) {?>
<div class="options_checkbox_wrap">
<input type="checkbox" name="NGG_User_upload_option_array[<?php echo $role."_can_moderate"?>]" value="<?php echo $options[$role.'_can_moderate'];?>" <?php echo $options[$role.'_can_moderate'];?>/>
<?php echo $role;?>
</div>
<?php }?>
</div>
<?php } ?>

<?php }  

public function settings8(){ //gallery creation setting.?>
<?php $options = $this->getoptions();?>
<?php if ($options['enable_moderation_queue'] =="checked") {
} else {
return;    
} ?>
<div class="options_box">
<input type="checkbox" name="NGG_User_upload_option_array[Allow_user_gal_create]" value="<?php echo $options['Allow_user_gal_create']?>" <?php echo $options['Allow_user_gal_create']?>/>
Enabling this option will allow you to set which user levels are allowed to create new galleries in the moderation queue. If this is unchecked, gallery creation will be disabled.  
<br>
<br><b>As with everything else, please be careful with this option, as users will be able to create as many new galleries as they like.</b>
</div>
<?php if ($options['Allow_user_gal_create'] == "checked") {?>
<div class="selection_box">
<span class="options_box_header">Select which users can use create galleries. </span>
<?php global $wp_roles; $roles = $wp_roles->get_names();?>
<?php foreach ($roles as $role) {?>
<div class="options_checkbox_wrap">
<input type="checkbox" name="NGG_User_upload_option_array[<?php echo $role."_can_create_gal"?>]" value="<?php echo $options[$role.'_can_create_gal'];?>" <?php echo $options[$role.'_can_create_gal'];?>/>
<?php echo $role;?>
</div>
<?php }?>
</div>
<?php } ?>

<?php } 

}

$wctest = new wctest();?>