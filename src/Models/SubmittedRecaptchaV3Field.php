<?php

namespace NSWDPC\SpamProtection;

use SilverStripe\UserForms\Model\Submission\SubmittedFormField;

/**
 * The submitted value of an EditableRecaptchaV3Field
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
