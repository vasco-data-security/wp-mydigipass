<?php

class DigipassHelper
{
    protected $_options;

    public function __construct() {
        $this->_options = get_option('mydigipass_settings');
    }

    public function getClientId() {
        return $this->_options['client_id'];
    }

    public function getClientSecret() {
        return $this->_options['client_secret'];
    }

    public function getCallback() {
        return $this->_options['callback'];
    }

    public function getlocalAuthAllowed() {
        return $this->_options['local_auth'];
    }

    public function getMode() {
        return $this->_options['mode'];
    }

    public function getBaseUri() {
        if ($this->_options['mode'] == 'test') {
            return 'https://sandbox.mydigipass.com/oauth';
        } else {
            return 'https://www.mydigipass.com/oauth';
        }
    }

    public function getButtonHtml($location) {
        switch ($location) {
            case 'comment':
                $state = 'comment::' . $this->getCurrentPageURL();
                break;
            default:
                $state = $this->getCurrentPageURL();
                break;
        }
        $style = $this->getButtonStyle($location);
        $help = ($style[1]) ? 'true' : 'false';
        
        $origin = "https://www.mydigipass.com";
        if ($this->_options['mode'] == 'test') {
            $origin = "https://sandbox.mydigipass.com";
        }

        return '<a class="dpplus-connect" data-style="' . $style[0] . '" data-origin="' . $origin . '" data-help="' . $help . '" data-text="' . $style[2] . '" data-client-id="' . $this->_options['client_id'] . '" data-redirect-uri="' . $this->_options['callback'] . '" data-state="' . $state . '" href="#">Connect with MYDIGIPASS.COM</a>';
    }

    public function getButtonJs() {
        return '<script type="text/javascript" src="https://static.mydigipass.com/dp_connect.js"></script>';
    }
    // public function getButtonJs() {
    //     if ($this->_options['mode'] == 'test') {
    //         return '<script type="text/javascript" src="https://sandbox.mydigipass.com/dp_connect.js"></script>';
    //     } else {
    //         return '<script type="text/javascript" src="https://mydigipass.com/dp_connect.js"></script>';
    //     }
    // }

    public function getButtonStyle($location) {
        $config = $this->getButtonConfig($location);
        $text = $this->getButtonText($location);
        $buttonStyle = explode(':', $config . ':' . $text);
        
        return $buttonStyle;
    }

    public function getButtonConfig($location) {
        return $this->_options['button_' . $location];
    }

    public function getButtonText($location) {
        return $this->_options['button_' . $location . '_text'];
    }

    private function getCurrentPageURL() {
        $pageURL = 'http';
        if (isset($_SERVER["HTTPS"]) && strtolower($_SERVER["HTTPS"]) == "on") {
            $pageURL .= "s";
        }
        $pageURL .= "://";
        if ($_SERVER["SERVER_PORT"] != "80") {
            $pageURL .= $_SERVER["SERVER_NAME"] . ":" . $_SERVER["SERVER_PORT"] . $_SERVER["REQUEST_URI"];
        } else {
            $pageURL .= $_SERVER["SERVER_NAME"] . $_SERVER["REQUEST_URI"];
        }
        return $pageURL;
    }

}
?>
