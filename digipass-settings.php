<?php

class Digipass_Settings
{
    protected $_digipassHelper;

    public function digipassHelper() {
        if (is_null($this->_digipassHelper)) {
            $this->_digipassHelper = new DigipassHelper();
        }
        return $this->_digipassHelper;
    }

    public function mydigipass_add_profile_button($user) {
        $url = home_url();
        $url .= (get_option('permalink_structure')) ? '/mydigipass/unlink' : '/index.php?mydigipass-action=unlink';
        ?>
        <table class="form-table">
            <tr>
                <th>
                    <label for="mydigipass"><?php _e('MyDIGIPASS Account', 'mydigipass'); ?>
                    </label>
                </th>
                <?php if (get_user_meta($user->ID, 'digipass_uuid', true) != '') : ?>
                    <td>
                        <a href="<?php echo $url ?>"><?php _e('Unlink account from MyDIGIPASS', 'mydigipass') ?></a>
                    </td>
                <?php else: ?>
                    <td>
                        <span><?php echo $this->digipassHelper()->getButtonHtml('profile') ?></span>
                        <?php echo $this->digipassHelper()->getButtonJs() ?>
                    </td>
                <?php endif; ?>
            </tr>
        </table>
        <?php
    }

    public function mydigipass_add_profile_button_edit($user) {
        if (get_user_meta($user->ID, 'digipass_uuid', true) != '' && current_user_can('manage_options')) :
            $url = (get_option('permalink_structure')) ? 'mydigipass/unlink?id=' : 'index.php?mydigipass-action=unlink&id=';
            $url .= $user->ID;
            ?>
            <table class="form-table">
                <tr>
                    <th>
                        <label for="mydigipass"><?php _e('MyDIGIPASS Account', 'mydigipass'); ?>
                        </label>
                    </th>
                    <td>
                        <a href="<?php echo $url ?>"><?php _e('Unlink account from MyDIGIPASS', 'mydigipass') ?></a>
                    </td>

                </tr>
            </table>
            <?php
        endif;
    }

    public function mydigipass_menu() {
        // render menu page
        add_options_page('Vasco MyDIGIPASS Options', 'Vasco MyDIGIPASS', 'manage_options', 'mydigipass-options', array($this, 'mydigipass_options_page'));

        // register settings
        add_action('admin_init', array($this, 'register_mydigipass_settings'));
    }

    public function register_mydigipass_settings() {
        //register our settings
        register_setting('mydigipass-settings-group', 'mydigipass_settings', array($this, 'mydigipass_prepare_save'));
        // divide them in sections
        add_settings_section('mydigipass-main', 'General Settings', array($this, 'mydigipass_section_general'), 'mydigipass');
        add_settings_section('mydigipass-button', 'Button Styles', array($this, 'mydigipass_section_button'), 'mydigipass');
        // define the fields and add them to a section
        add_settings_field('mydigipass_client_id', 'Client ID', array($this, 'mydigipass_client_id'), 'mydigipass', 'mydigipass-main');
        add_settings_field('mydigipass_client_secret', 'Client Secret', array($this, 'mydigipass_client_secret'), 'mydigipass', 'mydigipass-main');
        add_settings_field('mydigipass_callback', 'Callback URL', array($this, 'mydigipass_callback_field'), 'mydigipass', 'mydigipass-main');
        add_settings_field('mydigipass_mode', 'Mode', array($this, 'mydigipass_mode'), 'mydigipass', 'mydigipass-main');
        add_settings_field('mydigipass_local_auth', 'Allow local Authentication', array($this, 'mydigipass_local_auth'), 'mydigipass', 'mydigipass-main');
        add_settings_field('mydigipass_button_login', 'Login form', array($this, 'mydigipass_button_login'), 'mydigipass', 'mydigipass-button');
        add_settings_field('mydigipass_button_login_text', 'Login form text', array($this, 'mydigipass_button_login_text'), 'mydigipass', 'mydigipass-button');
        add_settings_field('mydigipass_button_comment', 'Comment form', array($this, 'mydigipass_button_comment'), 'mydigipass', 'mydigipass-button');
        add_settings_field('mydigipass_button_comment_text', 'Comment form text', array($this, 'mydigipass_button_comment_text'), 'mydigipass', 'mydigipass-button');
        add_settings_field('mydigipass_button_profile', 'Profile page', array($this, 'mydigipass_button_profile'), 'mydigipass', 'mydigipass-button');
        add_settings_field('mydigipass_button_profile_text', 'Profile page text', array($this, 'mydigipass_button_profile_text'), 'mydigipass', 'mydigipass-button');
    }

    // section descriptions
    public function mydigipass_section_general() {
        echo ''; // description of this section
    }

    public function mydigipass_section_button() {
        echo ''; // description of this section
    }

    // fields rendering
    public function mydigipass_client_id() {
        echo "<input id='mydigipass_client_id' name='mydigipass_settings[client_id]' size='40' type='text' value='{$this->digipassHelper()->getClientId()}' />";
    }

    public function mydigipass_client_secret() {
        echo "<input id='mydigipass_client_secret' name='mydigipass_settings[client_secret]' size='40' type='text' value='{$this->digipassHelper()->getClientSecret()}' />";
    }

    public function mydigipass_callback_field() {
        echo "<input id='mydigipass_callback' name='mydigipass_settings[callback]' size='40' type='text' value='{$this->digipassHelper()->getCallback()}' />";
    }

    public function mydigipass_mode() {
        if ($this->digipassHelper()->getMode() == 'test') {
            $testSelected = 'selected="selected"';
            $liveSelected = '';
        } else {
            $testSelected = '';
            $liveSelected = 'selected="selected"';
        }

        $select = '<select id="mydigipass_mode" name="mydigipass_settings[mode]">';
        $select .= '<option value="test" ' . $testSelected . '>Test</option>';
        $select .= '<option value="live" ' . $liveSelected . '>Live</option>';
        $select .= '</select>';

        echo $select;
    }

    public function mydigipass_local_auth() {
        if ($this->digipassHelper()->getLocalAuthAllowed()) {
            $trueSelected = 'selected="selected"';
            $falseSelected = '';
        } else {
            $trueSelected = '';
            $falseSelected = 'selected="selected"';
        }

        $select = '<select id="mydigipass_local_auth" name="mydigipass_settings[local_auth]">';
        $select .= '<option value="1" ' . $trueSelected . '>Yes</option>';
        $select .= '<option value="0" ' . $falseSelected . '>No</option>';
        $select .= '</select>';

        echo $select;
    }

    public function mydigipass_button_login() {
        $config = array(
            'default' => '',
            'default-help' => '',
            'large' => '',
            'large-help' => '',
            'medium' => '',
            'medium-help' => '',
            'small' => '',
            'small-help' => ''
        );
        switch ($this->digipassHelper()->getButtonConfig('login')) {
            case 'default:help':
                $config['default-help'] = 'selected="selected"';
                break;
            case 'large:':
                $config['large'] = 'selected="selected"';
                break;
            case 'large:help':
                $config['large-help'] = 'selected="selected"';
                break;
            case 'medium:':
                $config['medium'] = 'selected="selected"';
                break;
            case 'medium:help':
                $config['medium-help'] = 'selected="selected"';
                break;
            case 'small:':
                $config['small'] = 'selected="selected"';
                break;
            case 'small:help':
                $config['small-help'] = 'selected="selected"';
                break;
            default:
                $config['default'] = 'selected="selected"';
                break;
        }

        $select = '<select id="mydigipass_button_login" name="mydigipass_settings[button_login]">';
        $select .= '<option value="default:" ' . $config['default'] . '>Default</option>';
        $select .= '<option value="default:help" ' . $config['default-help'] . '>Default with help</option>';
        $select .= '<option value="large:" ' . $config['large'] . '>Large</option>';
        $select .= '<option value="large:help" ' . $config['large-help'] . '>Large with help</option>';
        $select .= '<option value="medium:" ' . $config['medium'] . '>Medium</option>';
        $select .= '<option value="medium:help" ' . $config['medium-help'] . '>Medium with help</option>';
        $select .= '<option value="small:" ' . $config['small'] . '>Small</option>';
        $select .= '<option value="small:help" ' . $config['small-help'] . '>Small with help</option>';
        $select .= '</select>';

        echo $select;
    }

    public function mydigipass_button_login_text() {
        $config = array(
            'connect' => '',
            'secure-login' => ''
        );
        switch ($this->digipassHelper()->getButtonText('login')) {
            case 'secure-login':
                $config['secure-login'] = 'selected="selected"';
                break;
            default:
                $config['connect'] = 'selected="selected"';
                break;
        }

        $select = '<select id="mydigipass_button_login_text" name="mydigipass_settings[button_login_text]">';
        $select .= '<option value="connect" ' . $config['connect'] . '>Connect</option>';
        $select .= '<option value="secure-login" ' . $config['secure-login'] . '>Secure Login</option>';
        $select .= '</select>';

        echo $select;
    }

    public function mydigipass_button_comment() {
        $config = array(
            'default' => '',
            'default-help' => '',
            'large' => '',
            'large-help' => '',
            'medium' => '',
            'medium-help' => '',
            'small' => '',
            'small-help' => ''
        );
        file_put_contents('wp.log', $this->digipassHelper()->getButtonConfig('comment') . "\n", FILE_APPEND);
        switch ($this->digipassHelper()->getButtonConfig('comment')) {
            case 'default:help':
                $config['default-help'] = 'selected="selected"';
                break;
            case 'large:':
                $config['large'] = 'selected="selected"';
                break;
            case 'large:help':
                $config['large-help'] = 'selected="selected"';
                break;
            case 'medium:':
                $config['medium'] = 'selected="selected"';
                break;
            case 'medium:help':
                $config['medium-help'] = 'selected="selected"';
                break;
            case 'small:':
                $config['small'] = 'selected="selected"';
                break;
            case 'small:help':
                $config['small-help'] = 'selected="selected"';
                break;
            default:
                $config['default'] = 'selected="selected"';
                break;
        }

        $select = '<select id="mydigipass_button_comment" name="mydigipass_settings[button_comment]">';
        $select .= '<option value="default:" ' . $config['default'] . '>Default</option>';
        $select .= '<option value="default:help" ' . $config['default-help'] . '>Default with help</option>';
        $select .= '<option value="large:" ' . $config['large'] . '>Large</option>';
        $select .= '<option value="large:help" ' . $config['large-help'] . '>Large with help</option>';
        $select .= '<option value="medium:" ' . $config['medium'] . '>Medium</option>';
        $select .= '<option value="medium:help" ' . $config['medium-help'] . '>Medium with help</option>';
        $select .= '<option value="small:" ' . $config['small'] . '>Small</option>';
        $select .= '<option value="small:help" ' . $config['small-help'] . '>Small with help</option>';
        $select .= '</select>';

        echo $select;
    }

    public function mydigipass_button_comment_text() {
        $config = array(
            'connect' => '',
            'secure-login' => ''
        );
        switch ($this->digipassHelper()->getButtonText('comment')) {
            case 'secure-login':
                $config['secure-login'] = 'selected="selected"';
                break;
            default:
                $config['connect'] = 'selected="selected"';
                break;
        }

        $select = '<select id="mydigipass_button_comment_text" name="mydigipass_settings[button_comment_text]">';
        $select .= '<option value="connect" ' . $config['connect'] . '>Connect</option>';
        $select .= '<option value="secure-login" ' . $config['secure-login'] . '>Secure Login</option>';
        $select .= '</select>';

        echo $select;
    }

    public function mydigipass_button_profile() {
        $config = array(
            'default' => '',
            'default-help' => '',
            'large' => '',
            'large-help' => '',
            'medium' => '',
            'medium-help' => '',
            'small' => '',
            'small-help' => ''
        );
        switch ($this->digipassHelper()->getButtonConfig('profile')) {
            case 'default:help':
                $config['default-help'] = 'selected="selected"';
                break;
            case 'large:':
                $config['large'] = 'selected="selected"';
                break;
            case 'large:help':
                $config['large-help'] = 'selected="selected"';
                break;
            case 'medium:':
                $config['medium'] = 'selected="selected"';
                break;
            case 'medium:help':
                $config['medium-help'] = 'selected="selected"';
                break;
            case 'small:':
                $config['small'] = 'selected="selected"';
                break;
            case 'small:help':
                $config['small-help'] = 'selected="selected"';
                break;
            default:
                $config['default'] = 'selected="selected"';
                break;
        }

        $select = '<select id="mydigipass_button_profile" name="mydigipass_settings[button_profile]">';
        $select .= '<option value="default:" ' . $config['default'] . '>Default</option>';
        $select .= '<option value="default:help" ' . $config['default-help'] . '>Default with help</option>';
        $select .= '<option value="large:" ' . $config['large'] . '>Large</option>';
        $select .= '<option value="large:help" ' . $config['large-help'] . '>Large with help</option>';
        $select .= '<option value="medium:" ' . $config['medium'] . '>Medium</option>';
        $select .= '<option value="medium:help" ' . $config['medium-help'] . '>Medium with help</option>';
        $select .= '<option value="small:" ' . $config['small'] . '>Small</option>';
        $select .= '<option value="small:help" ' . $config['small-help'] . '>Small with help</option>';
        $select .= '</select>';

        echo $select;
    }

    public function mydigipass_button_profile_text() {
        $config = array(
            'connect' => '',
            'secure-login' => ''
        );
        switch ($this->digipassHelper()->getButtonText('profile')) {
            case 'secure-login':
                $config['secure-login'] = 'selected="selected"';
                break;
            default:
                $config['connect'] = 'selected="selected"';
                break;
        }

        $select = '<select id="mydigipass_button_profile_text" name="mydigipass_settings[button_profile_text]">';
        $select .= '<option value="connect" ' . $config['connect'] . '>Connect</option>';
        $select .= '<option value="secure-login" ' . $config['secure-login'] . '>Secure Login</option>';
        $select .= '</select>';

        echo $select;
    }

    // validation of the settings
    public function mydigipass_prepare_save($input) {
        file_put_contents('wp.log', print_r($input, true), FILE_APPEND);
        $validated = $input;

        return $validated;
    }

    // rendering of the page
    public function mydigipass_options_page() {
        global $wp_query;
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        ?>
        <div class="wrap">
            <h2>Vasco MyDIGIPASS</h2>
            <form method="post" action="options.php">
                <?php settings_fields('mydigipass-settings-group'); ?>
                <?php do_settings_sections('mydigipass'); ?>

                <p class="submit">
                    <input type="submit" name="submit" value="<?php _e('Update options &raquo;'); ?>" />
                </p>
            </form>
        </div>
        <?php
    }

}
$digipassSettings = new Digipass_Settings();
add_action('admin_menu', array($digipassSettings, 'mydigipass_menu'));
add_action('show_user_profile', array($digipassSettings, 'mydigipass_add_profile_button'));
add_action('edit_user_profile', array($digipassSettings, 'mydigipass_add_profile_button_edit'));
?>
