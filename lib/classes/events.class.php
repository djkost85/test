<?php
    include_once ("lib/include/exceptions.php");
    
    class Events
    {
        private $db;
        
        function __construct($db)
        {
            $this->db = $db;
        }
        
        public function addNew($title, $adminID, $location)
        {
            $title = ParameterException::checkStringEsc($title, "Event title", $this->db);
            ParameterException::checkPositiveNumber($location, "Event location");
            ParameterException::checkPositiveNumber($adminID, "AdminID");
            
            $sql = "INSERT INTO `switched_on`.`events` (`title`, `location`, `adminID`) VALUES ('$title', $location, $adminID) ON DUPLICATE KEY UPDATE `title`='$title', `location` = $location";
            
            $eventID = $this->db->insert($sql);
            
            return $eventID;
        }
        
        public function update($eventID, $fields)
        {
            ParameterException::checkPositiveNumber($eventID, "eventID");
            
            $setSQL = "";
            foreach ($fields as $key => $value)
            {
                if (!empty($value))
                {
                    $value = ParameterException::checkStringEsc($value, "$value", $this->db);
                    $setSQL .= "`$key` = '$value',";
                }
            }
            $setSQL = rtrim($setSQL, ",");
            
            $sql = "UPDATE `switched_on`.`events` SET $setSQL WHERE `eventID` = $eventID";
            
            $this->db->update($sql);
            
            return true;
        }
        
        public function getDetails($eventID)
        {
            ParameterException::checkPositiveNumber($eventID, "Event ID");
            
            $sql = "SELECT * FROM `switched_on`.`events` WHERE `eventID` = $eventID";
            
            $details = $this->db->getMultiDimensionalArray($sql);
            
            return $details;
        }
        
        public function listEvents($startDate, $endDate)
        {
            $startDate = ParameterException::checkStringEsc($startDate, "Start Date", $this->db);
            $endDate = ParameterException::checkStringEsc($endDate, "End Date", $this->db);
             
            $sql = "SELECT * FROM `switched_on`.`events` WHERE `date` >= '$startDate' AND `date` <= '$endDate'";
            
            $events = $this->db->getMultiDimensionalArray($sql);
            
            return $events;
        }
        
        private function throwException($e, $throw = true)
        {
            error_log("Events Class: {$e->getMessage()}");
            
            if ($throw)
                throw new Exception ("{$e->getMessage()} Please refer to the error log for further details");
        }
    }
?>
