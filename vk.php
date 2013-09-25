<?php
    //require('config.php');
    require_once ('classes/vk/vk.class.php');
    require_once ("classes/vk/user.class.php");
    require_once ("classes/include/utils.php");
    
    $action = getParameterString("action", "getUserData");
    
    $vk = new Vk(
            array (
                    'client_id' => 3826465,
                    'client_secret' => "AagVJDzzqwDxXm1tLgNc"
            )
    );

    // удалить для получения нового
    $token = '{"access_token":"b3278016ac53ecdb91d8653658c77e3f333d1eb3f58ebe707304fa3fdfc0e0bf6c0c3052a044174d381f8","expires_in":0,"user_id":3220196}';
    //$token = '{"access_token":"fb26afe907cc16fd33bdd14255deac204cd54e6563de9dcd9076307c69d5fba963e8a8c608e3630f59d64","expires_in":0,"user_id":16073089}';
    if($token) {
        header('Content-Type: text/html; charset=utf-8');
        $vk->setToken($token);

        $user = new VK_user($vk);
        
        if ($action == "getUserData") 
        {
            $user->loadData();

            foreach ($user->userData as $key => $value)
            {
                printField($key, $value);
            }
            echo "<a href='index.php?action=getUserFriends'>Посмотреть список друзей</a>";
        }
        elseif ($action == "getUserFriends")
        {
            echo "<a href='index.php'>Вернуться к данным пользователя</a>";
            echo "<br />";
            $user->loadFriends();
            
            foreach ($user->friends as $friend)
            {
                foreach($friend as $key => $value)
                {
                    printField($key, $value);
                }
                echo "<br /><hr /><br />";
            }
        }
    } else {
            $vk->setRedirectUrl('http://' . $_SERVER['HTTP_HOST'] . $_SERVER['PHP_SELF']);
            if(!isset($_GET['code'])) {
                    print '<a href="' . $vk->getLoginUrl(array('offline', 'notify', 'friends', 'photos', 'audio', 'video', 'wall', 'groups')) . '">Login</a>';
            } else {
                    $vk->getToken($_GET['code']);
                    print $vk->getTokenStr();
            }
    }
    
    function printField($key, $value, $prefix = "") {
        
        if (is_array($value))
            foreach ($value as $key => $value)
            {
                printField($key, $value, "&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;");
            }
        if (strstr($key, "photo") != false)
        {
            out("<b>$key:</b> <img src='$value' alt='$key'>", $prefix);
        }
        else
            out("<b>$key:</b> $value", $prefix);
        
        
    }
    
    function out($txt, $prefix)
    {
        echo "$prefix".$txt."</br>";
    }

    # get auth token
    #https://oauth.vk.com/access_token?code=d940e61fc5ae92c62f&redirect_uri=http://mpak-vcms.switchmedia.asia/mpak/xposed/index2.php&client_id=3826465&client_secret=AagVJDzzqwDxXm1tLgNc

    #sample api call
    #https://api.vk.com/method/users.get?fields=counters&access_token=526e475c85dd03b29d3daf8af127986f94a8b359cdc7af3681645e94d490ecd788d08b94dca4a7ebe3fb7

    #link to the vk class
    #thtp://digitorum.ru/blog/2013/07/19/Vk-php-klass-dlya-raboty-s-api.phtml
?>

