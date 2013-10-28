<?php
    include_once ("lib/external/facebook/src/facebook.php");
    include_once ("lib/include/exceptions.php");

    /**
    * Allows manipulations with users data.
    */
    class User
    {
        private $db;
        public $fb;
        public $userData;
        
        function __construct($db)
        {
            $this->db = $db;
            
            $this->fb = new Facebook(array(
                'appId'  => APP_ID,
                'secret' => APP_SECRET,
            ));
        }
        
        /**
        * Authenticates user against facebook API and saves data into the database.
        * 
        * @return array An object representing the users's information.
        */
        public function fb_login()
        {
            $user = $this->fb->getUser();

            if ($user) {
                try
                {
                    // Proceed knowing you have a logged in user who's authenticated.
                    $user_profile = $this->fb->api('/me');

                    $userID = $this->checkUser($user_profile);
                    if (!$userID)
                        $userID = $this->registerUser($user_profile);
                    
                    $user_data = array(
                        "first_name" => $user_profile['first_name'],
                        "last_name" => $user_profile['last_name'],
                        "loginToken" => getParameterString('code'),
                        "email" => isset($user_profile['email']) ? $user_profile['email'] : "",
                        "gender" => isset($user_profile['gender']) ? $user_profile['gender'] : "",
                        "location" => isset($user_profile['location']['name']) ? $user_profile['location']['name'] : "",
                        "languages" => isset($user_profile['languages']) ? $this->convertArrayToString($user_profile['languages'], "name") : "",
                        "fb_username" => $user_profile['username'],
                    );

                   $this->setUserData($userID, $user_data);                    
                } 
                catch (FacebookApiException $e) {
                    $this->throwException($e);
                    $user = null;
                }
            }
            
            return $user_profile;
        }
        
        public function getDetails($userID = "", $username = "", $email = "")
        {
            $sql = "SELECT * FROM `switched_on`.`users` WHERE ";
            if (!empty($userID))
            {
                ParameterException::checkPositiveNumber($userID, "userID");
                $sql .= "`userID` = $userID";
            }
            elseif (!empty($username))
            {
                $username = ParameterException::checkStringEsc($username, "Username", $this->db);
                $sql .= "`fb_username` = '$username'";
            }
            elseif (!empty($email))
            {
                $email = ParameterException::checkStringEsc($email, "email", $this->db);
                $sql .= "`email` = '$email'";
            }
            else
            {
                $this->throwException(new Exception("No identity parameters were provided"));
            }
            
            try 
            {
                $userDetails = $this->db->getSingleRowAssoc($sql);
                
                return $userDetails;
            }
            catch (Exception $e)
            {
                $this->throwException($e, false);
                return null;
            }
        }
        
        private function registerUser($user_data)
        {
            $username = $this->db->escape($user_data['username']);
            $first_name = $this->db->escape($user_data['first_name']);
            $last_name = $this->db->escape($user_data['last_name']);
            
            $sql = "INSERT INTO `switched_on`.`users` (`fb_username`, `first_name`, `last_name`) VALUES ('$username', '$first_name', '$last_name')";
            
            $userID = $this->db->insert($sql);
            
            return $userID;
        }
        
        private function setUserData($userID, $userData)
        {
            $this->userData = $userData;
            $this->userData['userID'] = $userID;
            
            $setSQL = "";
            foreach ($userData as $key => $value)
            {
                $value = $this->db->escape($value);
                $setSQL .= "`$key` = '$value',";
            }
            $setSQL = rtrim($setSQL, ",");
            
            $sql = "UPDATE `switched_on`.users SET $setSQL WHERE `userID` = $userID";
            
            $this->db->update($sql);
        }
        
        private function checkUser($user_data)
        {
            $username = $this->db->escape($user_data['username']);
            $first_name = $this->db->escape($user_data['first_name']);
            $last_name = $this->db->escape($user_data['last_name']);
            
            $sql = "SELECT * FROM `switched_on`.`users` WHERE `fb_username` = '$username' AND `first_name` = '$first_name' AND `last_name` = '$last_name'";
            
            try 
            {
                $users = $this->db->getMultiDimensionalArray($sql);
                
                if (count($users) == 1)
                    return $users[0]['userID'];
                elseif (count($users) == 0)
                    return false;
                else
                    $this->throwException(new Exception("Duplicate users found."));
            }
            catch (Exception $e)
            {
                $this->throwException($e);
            }
        }
        
        private function convertArrayToString($arr, $key=null)
        {
            $str = "";
            foreach ($arr as $item)
            {
                if ($key != null)
                    $str .= "$item[$key], ";
                else
                    $str .= "$item, ";
            }
            
            $str = rtrim($str, ", ");
            
            return $str;
        }
        
        private function throwException($e, $throw = true)
        {
            error_log("User Class: {$e->getMessage()}");
            
            if ($throw)
                throw new Exception ("{$e->getMessage()} Please refer to the error log for further details");
        }
    }
?>
