<?php
    require_once ("lib/classes/events.class.php");
    require_once ("lib/external/facebook/src/facebook.php");
    
    $db = new dbTool(true);
    $db->connectContent(true);
    $events = new Events($db);
    
    $submitted = getParameterNumber("submitted");
    
    echo "<h1>Add new event </h1>";
    
    if (!$submitted)
    {
        echo <<<EVENT_FORM_HTML

<h3> Please enter event details below: </h3>
<form>
    <div class='clearSides leftFloated'>
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
        $title = getParameterString("title");
        $adminID = getParameterNumber("adminID");
        $location = getParameterNumber("location");
        
        try
        {
            $eventID = $events->addNew($title, $adminID, $location); 
        }
        catch (Exception $e)
        {
            $eventID = -1;
            echo "<h3> Error occured </h3>";
            echo $e->getMessage();
        }
        if ($eventID > 0)
        {
            $description = getParameterString("description");
            $type = getParameterString('type');
            
            $fields = array();
            
            if (!empty($description))
                $fields['description'] = $description;
            
            if (!empty($type))
                $fields['type'] = $type;
            
            $events->update($eventID, $fields);
            echo "<h3> New event (id=$eventID) has been succesfully created. </h3> ";
        }
    }
?>
