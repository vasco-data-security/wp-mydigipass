<?php

class Digipass_Callback
{
    protected $_digipassHelper;
    protected $_digipassAuth;

    public function digipassHelper() {
        if (is_null($this->_digipassHelper)) {
            $this->_digipassHelper = new DigipassHelper();
        }
        return $this->_digipassHelper;
    }

    private function loader($class) {
        require str_replace('\\', DIRECTORY_SEPARATOR, $class) . '.php';
    }

    public function digipassAuth($code = null) {
        if (is_null($this->_digipassAuth)) {
            spl_autoload_register(array($this, 'loader'));

            $client = new OAuth2\Client(
                            $this->digipassHelper()->getClientId(),
                            $this->digipassHelper()->getClientSecret(),
                            $this->digipassHelper()->getCallback()
            );
            $config = new OAuth2\Service\Configuration(
                            $this->digipassHelper()->getBaseUri() . '/authenticate',
                            $this->digipassHelper()->getBaseUri() . '/token'
            );
            $dataStore = new OAuth2\DataStore\Session();
            $scope = null;

            $this->_digipassAuth = new OAuth2\Service($client, $config, $dataStore, $scope);
            $this->_digipassAuth->getAccessToken($code);

            spl_autoload_unregister('loader');
        }
        return $this->_digipassAuth;
    }

    public function mydigipass_insert_query_vars($vars) {
        array_push($vars, 'mydigipass-action');
        array_push($vars, 'code');
        array_push($vars, 'state');
        return $vars;
    }

    public function mydigipass_callback() {
        global $wp_query;
        $wp_query->is_404 = false;
        global $current_user;
        $current_user = wp_get_current_user();
        $action = $wp_query->get('mydigipass-action');
        if (!empty($action)) {
            $explode = explode('?', $action);
            $pagename = $explode[0];
            if (isset($explode[1])) {
                $wp_query->set('code', substr($explode[1], 5));
            }
        } else {
            $pagename = $wp_query->get('pagename');
        }
        
        switch ($pagename) {
            case 'callback':
                if (is_user_logged_in()) {
                    $response = $this->digipassAuth($wp_query->get('code'))->callApiEndpoint($this->digipassHelper()->getBaseUri() . '/user_data');
                    $userData = json_decode($response, true);

                   // if (isset($userData['email']) && $current_user->user_email == $userData['email']) { // link flow
				   $users = get_users(array('meta_key' => 'digipass_uuid', 'meta_value' => $userData['uuid']));
					foreach ($users as $suser) {
						$userID=  $suser->ID;
					}
					if(!$userID){ 
                        update_user_meta($current_user->ID, 'digipass_uuid', $userData['uuid']);
                        wp_redirect(admin_url('profile.php'));
                        $_SESSION['link'] = true;
                        exit;
                   } else {
                       //wp_redirect(site_url());
					   wp_redirect(admin_url('profile.php'));
                        $_SESSION['link'] = false;
                   }
                } else {
                    $_SESSION['code'] = $wp_query->get('code');
                    $_SESSION['state'] = $wp_query->get('state');

                    wp_redirect(site_url('wp-login.php', 'login'));
                    echo 'Please wait, logging in.';
                    exit;
                }
                break;
            case 'unlink':
                $id = (isset($_GET['id'])) ? $_GET['id'] : $current_user->ID;
                if (is_user_logged_in()) {
                    $removed = delete_user_meta($id, 'digipass_uuid');
                    $_SESSION['unlink'] = true;
                }
                if (isset($_GET['id'])) {
                    wp_redirect(admin_url('user-edit.php?user_id=' . $id));
                } else {
                    wp_redirect(admin_url('profile.php'));
                }
                exit;
                break;
        }
    }

    public function mydigipass_authenticate($user, $username) {
        if (isset($_SESSION['code'])) {
            try {
                $response = $this->digipassAuth($_SESSION['code'])->callApiEndpoint($this->digipassHelper()->getBaseUri() . '/user_data');
                $userData = json_decode($response, true);

                if (isset($userData['email'])) { // MyDIGIPASS data is valid
                    
					$users = get_users(array('meta_key' => 'digipass_uuid', 'meta_value' => $userData['uuid']));
					foreach ($users as $suser) {
						$userID=  $suser->ID;
					}
					if($userID){
						$userSearch = get_user_by('id', $userID);
					}
                    if ($userSearch) { // user exists
                        $uuid = get_user_meta($userSearch->ID, 'digipass_uuid', true);
                        if ($uuid == $userData['uuid']) { // user is linked
							//connect funcionality													
							
							$HClient = new OAuth2\HttpClient(
								str_replace("/oauth","/api/uuids/connected",$this->digipassHelper()->getBaseUri()),
								"POST",
								"uuids=".$uuid,
								array(),
								$this->digipassHelper()->getClientId().":".$this->digipassHelper()->getClientSecret()
							);
							$HClient->execute();
														
                            $user = new WP_User($userSearch->ID);
                            update_usermeta($userSearch->ID, 'first_name', $userData['first_name']);
                            update_usermeta($userSearch->ID, 'last_name', $userData['last_name']);
							
							
                            if (isset($_SESSION['state']) && $_SESSION['state'] != site_url('wp-login.php', 'login')) {
                                add_action('login_redirect', array($this, 'redirectToStateUrl'));
                            }
                        } else { // user is not linked
                            $_SESSION['digipass-data'] = serialize($userData);
                            $_SESSION['username'] = $userSearch->user_login;
                            add_action('login_head', array($this, 'login_form_username')); // pre-fill the username
                            $user = new WP_Error('denied', __('You already have an account with this email on this blog. Please log in to link this account with MyDIGIPASS.'), 'message');
                            remove_action('authenticate', 'wp_authenticate_username_password', 20);
							
                        }
                    } else { // user does not exist
                        $state = explode('::', $_SESSION['state']);
                        if ($state[0] == "comment") {
                            $digipassComment = array(
                                'name' => $userData['first_name'] . ' ' . $userData['last_name'],
                                'email' => $userData['email']
                            );
                            $_SESSION['digipass-comment'] = serialize($digipassComment);
                            $_SESSION['digipass-loggedin'] = true;
                            wp_redirect($state[1]);
                            unset($_SESSION['code']);
                            exit;
                        }
                        $user = new WP_Error('invalid_username', sprintf(__('<strong>ERROR</strong>: Invalid username. <a href="%s" title="Password Lost and Found">Lost your password</a>?'), site_url('wp-login.php?action=lostpassword', 'login')));
                        remove_action('authenticate', 'wp_authenticate_username_password', 20);
                    }
                }
                unset($_SESSION['code']);
            } catch (Exception $e) {
                unset($_SESSION['code']);
                wp_die('Something went wrong trying to authenticate you using MyDIGIPASS. Please <a href="' . site_url('wp-login.php', 'login') . '">try again</a>.');
            }
        } else { // local login
            $userSearch = get_user_by('login', $username);

            if ($userSearch) {
                $uuid = get_user_meta($userSearch->ID, 'digipass_uuid', true);

                if (!isset($_SESSION['digipass-data']) && !empty($uuid) && !$this->digipassHelper()->getlocalAuthAllowed()) {
                    $user = new WP_Error('denied', __('Local login disabled. Please use MyDIGIPASS to log in.'), 'message');
                    remove_action('authenticate', 'wp_authenticate_username_password', 20);
                } else {
                    add_filter('login_redirect', array($this, 'save_digipass_uuid'), 10, 3);
                }
            }
        }

        return $user;
    }
	

    public function save_digipass_uuid($redirect_to, $request, $user) {
        if (isset($_SESSION['digipass-data']) && is_a($user, 'WP_User')) {
            $userData = unserialize($_SESSION['digipass-data']);
            if ($user->user_email == $userData['email']) {
                update_usermeta($user->ID, 'digipass_uuid', $userData['uuid']);
                update_usermeta($user->ID, 'first_name', $userData['first_name']);
                update_usermeta($user->ID, 'last_name', $userData['last_name']);
            }
            unset($_SESSION['digipass-data']);
        }
        return $redirect_to;
    }

    public function redirectToStateUrl($redirect_to) {
        $state = explode('::', $_SESSION['state']);
        unset($_SESSION['state']);

        return (isset($state[1])) ? $state[1] : $state[0];
    }

    public function link_message() {
        if (isset($_SESSION['link'])) {
			if($_SESSION['link']) {
				echo __('<div class="updated"><p>Sucessfully linked your account to MyDIGIPASS.</p></div>');
				unset($_SESSION['link']);
			} else {
				echo __('<div class="error"><p>An account is already linked to this MyDIGIPASS.</p></div>');
				unset($_SESSION['link']);
			}
        }
    }

    public function unlink_message() {
        if (isset($_SESSION['unlink']) && $_SESSION['unlink']) {
            echo __('<div class="updated"><p>Sucessfully unlinked account from MyDIGIPASS.</p></div>');
            unset($_SESSION['unlink']);
        } else {
            echo '';
        }
    }

    public function login_form_username() {
        global $user_login;
        $user_login = $_SESSION['username'];
        unset($_SESSION['username']);
        return $user_login;
    }

}
$digipassCallback = new Digipass_Callback();

add_filter('query_vars', array($digipassCallback, 'mydigipass_insert_query_vars'));

// process the callback
add_action('template_redirect', array($digipassCallback, 'mydigipass_callback'));
// intercept the login flow
add_filter('authenticate', array($digipassCallback, 'mydigipass_authenticate'), 10, 2);
// show link/unlink messages
add_action('admin_notices', array($digipassCallback, 'link_message'));
add_action('admin_notices', array($digipassCallback, 'unlink_message'));
?>