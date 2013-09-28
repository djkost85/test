<?php
    //require_once ("../init.php");
    require_once ("classes/facebook/src/facebook.php");
    
    $facebook = new Facebook(array(
        'appId'  => APP_ID,
        'secret' => APP_SECRET,
    ));
    
    $user = $facebook->getUser();

    if ($user) {
        try {
            // Proceed knowing you have a logged in user who's authenticated.
            $user_profile = $facebook->api('/me');
        } 
        catch (FacebookApiException $e) {
            error_log($e);
            $user = null;
        }
    }
    
    // Login or logout url will be needed depending on current user state.
    if ($user) 
      $logoutUrl = $facebook->getLogoutUrl();
    else
      $loginUrl = $facebook->getLoginUrl();
?>

    <h1>Facebook profile</h1>

    <?php if ($user): ?>
      <a href="<?php echo $logoutUrl; ?>">Logout</a>
    <?php else: ?>
      <div>
        Login using OAuth 2.0 handled by the PHP SDK:
        <a href="<?php echo $loginUrl; ?>">Login with Facebook</a>
      </div>
    <?php endif ?>

    <?php if ($user): ?>
      <h3>You</h3>
      <img src="https://graph.facebook.com/<?php echo $user; ?>/picture">

      <h3>Your User Object (/me)</h3>
      <pre><?php print_r($user_profile); ?></pre>
    <?php else: ?>
      <strong><em>You are not Connected.</em></strong>
    <?php endif ?>