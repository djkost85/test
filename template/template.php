<?php
class Template {
    public function __construct($action) {
        $this->action = $action;
        $this->host = "http://ec2-54-200-3-111.us-west-2.compute.amazonaws.com/";
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
            <li><a href='index.php/login'>Login</a></li>
            <li><a href='index.php/getUser'>Get user</a></li>
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