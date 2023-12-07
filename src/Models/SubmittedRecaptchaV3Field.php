<?php

namespace NSWDPC\SpamProtection;

use SilverStripe\UserForms\Model\Submission\SubmittedFormField;

/**
 * SubmittedRecaptchaV3Field
 * Used to allow determination of whether a value is a recaptcha score
 * @author James
 */
class SubmittedRecaptchaV3Field extends SubmittedFormField
{

    /**
     * @var bool
     */
    protected $includeValueInEmails = false;

    /**
     * Setter
     */
    public function setIncludeValueInEmails(bool $include) : self
    {
        $this->includeValueInEmails = $include;
        return $this;
    }

    /**
     * Getter
     */
    public function getIncludeValueInEmails() : bool
    {
        return $this->includeValueInEmails;
    }
}
