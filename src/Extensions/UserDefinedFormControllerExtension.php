<?php

namespace NSWDPC\SpamProtection;

use SilverStripe\Core\Extension;
use SilverStripe\ORM\ArrayList;
use SilverStripe\Control\Email\Email;
use SilverStripe\UserForms\Model\Recipient\EmailRecipient;

/**
 * Extension to handle email modification
 * @author James
 */
class UserDefinedFormControllerExtension extends Extension
{

    /**
     * Modify email data to take into account whether the reCAPTCHA value
     * is to be included in the list of fields added to the email for all
     * recipients
     */
    public function updateEmailData(&$emailData, $attachments)
    {
        if (!isset($emailData['Fields']) || !($emailData['Fields'] instanceof ArrayList)) {
            // invalid field data
            return;
        }

        foreach ($emailData['Fields'] as $field) {
            if (($field instanceof SubmittedRecaptchaV3Field) && (!$field->getIncludeValueInEmails())) {
                $emailData['Fields']->remove($field);
            }
        }
    }
}
