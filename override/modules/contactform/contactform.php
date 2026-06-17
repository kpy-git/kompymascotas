<?php
class ContactformOverride extends Contactform
{
    public function sendMessage()
    {
        Hook::exec('actionContactFormSubmitBefore');
        if (empty($this->context->controller->errors)) {
            parent::sendMessage();
        }
    }
}
