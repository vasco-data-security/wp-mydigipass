<?php

/*
  Plugin Name: Vasco MyDIGIPASS
  Plugin URI: http://mydigipass.com/
  Description: Integration with Vasco's MyDIGIPASS platform
  Version: 0.0.2
  Author: PHPro
  Author URI: http://phpro.be
  License: GPLv2
 */
// include the helper before anything else so we can already use it
include_once(plugin_dir_path(__FILE__) . 'digipass-helper.php');

class Digipass
{
    protected $_digipassHelper;

    public function digipassHelper() {
        if (is_null($this->_digipassHelper)) {
            $this->_digipassHelper = new DigipassHelper();
        }
        return $this->_digipassHelper;
    }

    public function init_sessions() {
        if (!session_id()) {
            session_start();
        }
    }

    public function mydigipass_install() {
        if (get_option('mydigipass_settings') == null) {            
            // Creates new database fields
            $mydigipass_options = array(
                'client_id' => '',
                'client_secret' => '',
                'callback' => '',
                'mode' => 'test',
                'local_auth' => false,
                'button_login' => 'default',
                'button_login_text' => 'connect',
                'button_comment' => 'default',
                'button_comment_text' => 'connect',
                'button_profile' => 'default',
                'button_profile_text' => 'connect',
            );

            add_option('mydigipass_settings', $mydigipass_options);
        }
    }

    public function mydigipass_remove() {
        // Deletes the database fields
        delete_option('mydigipass_settings');
    }

    public function mydigipass_settings_link($links, $file) {
        if ($file == plugin_basename(__FILE__)) {
            $links[] = '<a href="' . admin_url('options-general.php?page=mydigipass-options') . '">' . __('Settings', 'mydigipass') . '</a>';
        }
        return $links;
    }

    public function mydigipass_login_add_login_button() {
        global $action;
        if ($action == 'login') {
            echo $this->digipassHelper()->getButtonHtml('login');
            echo $this->digipassHelper()->getButtonJs();
        }
    }

}
$digipass = new Digipass();

// enable sessions to store the digipass tokens. Using hidden fields or URL variables is too insecure.
add_action('init', array($digipass, 'init_sessions'));

// Runs when plugin is activated
register_activation_hook(__FILE__, array($digipass, 'mydigipass_install'));
// Runs on plugin deactivation
register_uninstall_hook(__FILE__, array($digipass, 'mydigipass_remove'));

add_filter('plugin_row_meta', array($digipass, 'mydigipass_settings_link'), 10, 2);

add_action('login_form', array($digipass, 'mydigipass_login_add_login_button'));

include_once(plugin_dir_path(__FILE__) . 'digipass-settings.php');
include_once(plugin_dir_path(__FILE__) . 'digipass-comments.php');
include_once(plugin_dir_path(__FILE__) . 'digipass-callback.php');
?>