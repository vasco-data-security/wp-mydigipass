<?php

class Digipass_Comments
{
    protected $_digipassHelper;

    public function digipassHelper() {
        if (is_null($this->_digipassHelper)) {
            $this->_digipassHelper = new DigipassHelper();
        }
        return $this->_digipassHelper;
    }

    public function addButton() {
        echo $this->digipassHelper()->getButtonHtml('comment');
        echo $this->digipassHelper()->getButtonJs();
    }

    public function setCommenterData($fields) {
        if (isset($_SESSION['digipass-comment'])) {
            $digipassData = unserialize($_SESSION['digipass-comment']);
            unset($_SESSION['digipass-comment']);
            $authorHtml = $fields['author'];
            $emailHtml = $fields['email'];

            $author = new SimpleXMLElement($authorHtml);
            $email = new SimpleXMLElement($emailHtml);
            $author->input['value'] = $digipassData['name'];
            $email->input['value'] = $digipassData['email'];

            $fields['author'] = preg_replace('/<\?xml version="1.0"\?>/', '', $author->asXML());
            $fields['email'] = preg_replace('/<\?xml version="1.0"\?>/', '', $email->asXML());
        }
        return $fields;
    }

}
$digipassComments = new Digipass_Comments();

add_action('comment_form_before_fields', array($digipassComments, 'addButton'));
add_filter('comment_form_default_fields', array($digipassComments, 'setCommenterData'));
?>