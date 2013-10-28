<?php
class Template {
    public function __construct($action) {
        $this->action = $action;
        $this->host = "http://ec2-54-252-237-204.ap-southeast-2.compute.amazonaws.com";
    }
    
    public function startPage()
    {
        echo <<<PAGE_START
    <html>
        <head>
            <title>Switched on API</title>
            <link rel="stylesheet" type='text/css' href="{$this->host}/resources/css/common.css">
        </head>
        <body>
            
            
PAGE_START;

        $this->writeMenu();
        $this->writeBody();
        $this->endPage();
    }
    
    private function endPage()
    {
     echo <<<PAGE_END
        </body>
    </html>   
PAGE_END;
    }
    
    private function writeMenu() 
    {   
        echo <<<LEFT_SECTION
    <div id='leftSection'>
        <ul>
            <li><a href='/login'>Login</a></li>
            <li><a href='/getUser'>Get user</a></li>
            <li><a href='/listEvents'>List events</a></li>
            <li><a href='/addEvent'>Add event</a></li>
            <li><a href='/getEvent'>Get event</a></li>
            <li><a href='/editEvent'>Edit event</a></li>
            <li><a href='/listFriends'>List friends</a></li>
            <li><a href='/addFriend'>Add friend</a></li>
            <li><a href='/deleteFriend'>Delete friend</a></li>
        </ul>
    </div>
    
LEFT_SECTION;
    }
    
    private function writeBody()
    {
        echo "<div id='rightSection'>";
        include_once ("api/{$this->action}.php");
        echo "</div>";
    }
   
}
?>