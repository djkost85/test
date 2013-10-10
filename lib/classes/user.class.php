<?php
    include_once ("lib/external/facebook/src/facebook.php");
    
    class User
    {
        private $db;
        public $fb;
        
        function __construct($db)
        {
            $this->db = $db;
            
            $this->fb = new Facebook(array(
                'appId'  => APP_ID,
                'secret' => APP_SECRET,
            ));
        }
        
        public function fb_login()
        {
            $user = $this->fb->getUser();

            if ($user) {
                try 
                
{                    // Proceed knowing you have a logged in user who's authenticated.
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
                    error_log($e);
                    $user = null;
                }
            }
            
            return $user_profile;
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
            
            $sql = "SELECT * FROM switched_on.users WHERE `fb_username` = '$username' AND `first_name` = '$first_name' AND `last_name` = '$last_name'";
            
            try 
            {
                $users = $this->db->getMultiDimensionalArray($sql);
                
                if (count($users) == 1)
                    return $users[0]['userID'];
                elseif (count($users) == 0)
                    return false;
                else
                    throw new Exception ("Duplicate users found.");
            }
            catch (Exception $e)
            {
                throw new Exception ("Error occured. {$e->getMessage()})");
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
    }
?>
