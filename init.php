<?php
   
    set_include_path ("/srv/xposed");
    
    # Including main config
    require_once("config/common.php");
    
    # Display errors on page if DEBUG is set to true
    if (DEBUG)
        ini_set('display_errors', 1);
    
    # General includes
    require_once ("template/template.php");
    require_once ("lib/include/utils.php");
    require_once ("lib/external/flight/Flight.php");
    require_once ("lib/include/dbTools.php");
?>
