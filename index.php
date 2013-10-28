<?php
    # Includes
    require_once ("init.php");
    
    Flight::route('*(/@action)', function($action){
        $action = (empty($action)) ? "login" : $action; 

        Flight::register('template', 'Template', array($action));
        Flight::template()->startPage();
    });
    
    Flight::start();
?>

   