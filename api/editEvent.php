<?php
    require_once ("lib/classes/events.class.php");
    require_once ("lib/external/facebook/src/facebook.php");
    
    $db = new dbTool(true);
    $db->connectContent(true);
    $eventsAdmin = new Events($db);
    
    $submitted = getParameterNumber("submitted");
    
    echo "<h1>Edit an event </h1>";
    
    if (!$submitted)
    {
        
        echo <<<EVENT_FORM_HTML

<h3> Please enter event details below: </h3>
<form>
    <div class='clearSides leftFloated'>
        EventID: <input style='margin-left: 56px' type='text' name='eventID' placeholder='Event id'/>
    </div>

    <div class='clearSides leftFloated' style='margin-top:10px;'>
        Title: <input style='margin-left: 80px' type='text' name='title' placeholder='Event title'/>
    </div>
    <div class='clearSides leftFloated' style='margin-top:10px;'>
        <label style='position: relative; bottom: 45px;'>Description:</label> <textarea style='margin-left: 25px' name='description' rows='4' cols='50'> </textarea>
    </div>
    <div class='clearSides leftFloated' style='margin-top:10px;'>
        Location: <input style='margin-left: 50px' type='text' name='location' placeholder='Location'/>
    </div>
    <div class='clearSides leftFloated' style='margin-top:10px;'>
        Event type: <select style='margin-left: 35px' name='type'>
            <option>Type 1</option>
            <option>Type 2</option>
            <option>Type 3</option>
            <option>Type 4</option>
        </select>
    </div>
    <div class='clearSides leftFloated' style='margin-top:10px;'>
        AdminID: <input style='margin-left: 46px' type='text' name='adminID' placeholder='Admin ID'/>
    </div>
    <input class='clearSides leftFloated' style='margin-top:15px; margin-bottom:35px;' type='submit' name='Submit' />
    <input type='hidden' name='submitted' value='1' />
</form>

EVENT_FORM_HTML;
    }
    else
    {
        $startDate = getParameterString("startDate");
        $endDate = getParameterString("endDate");
        
        $startDate = "2013-08-08 00:00:00";
        $endDate = "2014-08-08 00:00:00";
        
        $eventID = getParameterNumber("eventID");

        if ($eventID > 0)
        {
            $events = new Events($db);
            
            $title = getParameterString("title");
            //$adminID = getParameterNumber("adminID");
            //$location = getParameterNumber("location");
            $description = getParameterString("description");
            $type = getParameterString('type');
            
            $fields = array();
            
            if (!empty($title))
                $fields['title'] = $title;
            
            if (!empty($description))
                $fields['description'] = $description;
            
            if (!empty($type))
                $fields['type'] = $type;
            
            $events->update($eventID, $fields);
            echo "<h3> Event (id=$eventID) has been succesfully updated. </h3> ";
        }
    }
?>
