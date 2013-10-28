<?php
    require_once ("lib/classes/user.class.php");
    require_once ("lib/external/facebook/src/facebook.php");
    
    $loginToken = getParameterString("code");
    
    $db = new dbTool(true);
    $db->connectContent(true);
    $user = new User($db);
    
    // Login or logout url will be needed depending on current user state.
    if (!empty($loginToken)) 
    {
      $user_profile = $user->fb_login();
      $logoutUrl = $user->fb->getLogoutUrl();
    }
    else
      $loginUrl = $user->fb->getLoginUrl();
?>

    <h1>Facebook profile</h1>

    <?php if (!empty($loginToken)): ?>
      <a href="<?php echo $logoutUrl; ?>">Logout</a>
    <?php else: ?>
      <div>
        <a href="<?php echo $loginUrl; ?>">Login with Facebook</a>
      </div>
    <?php endif ?>

    <?php if (!empty($user_profile)): ?>
      <h3>You</h3>
      <img src="https://graph.facebook.com/<?php echo $user_profile['username']; ?>/picture">

      <h3>Your User Object</h3>
      <pre><?php print_r($user_profile); ?></pre>
    <?php else: ?>
      <strong><em>You are not Connected.</em></strong>
    <?php endif ?>