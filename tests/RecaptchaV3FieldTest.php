<?php

namespace NSWDPC\SpamProtection\Tests;

use NSWDPC\SpamProtection\EditableRecaptchaV3Field;
use NSWDPC\SpamProtection\SubmittedRecaptchaV3Field;
use SilverStripe\Dev\SapphireTest;

/**
 * Test the EditableRecaptchaV3Field
 * @author James
 */
class Recaptchav3FieldTest extends SapphireTest
{

    protected $usesDatabase = false;

    public function setUp() {
        parent::setUp();
    }

    /**
     * Test field value inclusion/exclusion
     */
    public function testVerificationValue()
    {
        $field = EditableRecaptchaV3Field::create();
        $field->Title = "Test spam protection";
        $field->IncludeInEmails = 1;

        $submittedField = $field->getSubmittedFormField();
        $this->assertTrue( $submittedField->getIncludeValueInEmails(), "getIncludeValueInEmails should be true when IncludeInEmails=1" );

        $field->IncludeInEmails = 0;

        $submittedField = $field->getSubmittedFormField();
        $this->assertFalse( $submittedField->getIncludeValueInEmails(), "getIncludeValueInEmails should be false when IncludeInEmails=0" );
    }



}
