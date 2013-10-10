<?php

/**
 * Defines a user object for VKONTAKTE
 */
class VK_User {
    
    # Connection to Vkontakte
    private $vk;
    
    # Vkontakte user ID
    public $userID;
    
    private $defaultUserFields = 'first_name,last_name,nickname,screen_name,sex,bdate,city,country,timezone,photo,photo_medium,photo_big,has_mobile,rate,contacts,education,online,counters';
    
    public function __construct($vk)
    {
        $this->vk = $vk;
        $this->userID = $this->vk->getUserID();
    }
    
    public function loadData()
    {
        $user_fields = array("fields" => $this->defaultUserFields);
        $user_fields_array = explode(",", $user_fields['fields']);
        $data = $this->vk->api('users.get', $user_fields);
        
        foreach ($user_fields_array as $field)
        { 
            $this->userData->$field = $data["response"][0][trim($field)];
        }
        
        $this->loadFriends();
        
        return;
    }
    
    public function loadFriends()
    {
        $fields = array('fields' => $this->defaultUserFields, "name_case" => "nom");
        $data = $this->vk->api('friends.get', $fields);
        
        foreach ($data["response"] as $friend)
        { 
            $this->friends[$friend['uid']] = $friend;
        }

        return;
    }
    
}
?>
