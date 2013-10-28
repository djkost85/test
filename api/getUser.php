<?php
    require_once ("lib/classes/user.class.php");
    require_once ("lib/external/facebook/src/facebook.php");
    
    $db = new dbTool(true);
    $db->connectContent(true);
    $user = new User($db);
    
    $userID = getParameterNumber("userID");
    $username = getParameterString("username");
    $email = getParameterString("email");
  
    
    echo "<h1>Get user details </h1>";
    
    if (empty($userID) && empty($username) && empty($email))
    {
        echo <<<USER_FORM_HTML
   
   
<h3> Please provide one of the following: </h3>
<form>
    <input type='text' name='userID' placeholder='User ID'/>
    <input type='text' name='username' placeholder='Username'/>
    <input type='text' name='email' placeholder='Email'/>
    <br />
    <input type='submit' name='Submit' />
</form>

USER_FORM_HTML;
    }
    else
    {
        $userDetails = $user->getDetails($userID, $username, $email);
        
        if (!empty($userDetails))
        {
            echo "<h3> {$userDetails['first_name']} {$userDetails['last_name']} details: </h3> <pre>";
            print_r($userDetails);
            echo "</pre>";
        }
        else
            echo "No user was found";
    }
?>
