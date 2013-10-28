<?php
    require_once ("lib/classes/events.class.php");
    
    $db = new dbTool(true);
    $db->connectContent(true);
    $events = new Events($db);
    
    $eventID = getParameterNumber("eventID");
    
    echo "<h1>Get user details </h1>";
    
    if (empty($eventID))
    {
        echo <<<USER_FORM_HTML
   
   
<h3> Please provide eventID below: </h3>
<form>
    <input type='text' name='eventID' placeholder='Event ID'/>
    <br />
    <input type='submit' name='Submit' />
</form>

USER_FORM_HTML;
    }
    else
    {
        $eventDetails = $events->getDetails($eventID);
        
        if (!empty($eventDetails))
        {
            echo "<h3> Event details: </h3> <pre>";
            print_r($eventDetails);
            echo "</pre>";
        }
        else
            echo "No user was found";
    }
?>
