<?php
    require_once ("lib/classes/events.class.php");
    
    $db = new dbTool(true);
    $db->connectContent(true);
    $eventsAdmin = new Events($db);
    
    $events = $eventsAdmin->listEvents("2000-00-00 00:00:00", "2020-00-00 00:00:00");
    
    echo "<pre>";
    print_r($events);
    echo "</pre>";
?>
