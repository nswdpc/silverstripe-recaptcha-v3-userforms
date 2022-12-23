<?php

namespace NSWDPC\SpamProtection\Tests;

use NSWDPC\SpamProtection\EditableTurnstileField;
use NSWDPC\SpamProtection\SubmittedTurnstileField;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link NSWDPC\SpamProtection\EditableTurnstileField}
 * @author James
 */
class EditableTurnstileFieldTest extends SapphireTest
{

    /**
     * @var bool
     */
    protected $usesDatabase = false;

    /**
     * Test field value inclusion/exclusion
     */
    public function testVerificationValue()
    {
        $field = EditableTurnstileField::create();
        $field->Title = "Test spam protection";
        $field->IncludeInEmails = 1;

        $submittedField = $field->getSubmittedFormField();
        $this->assertTrue( $submittedField->getIncludeValueInEmails(), "getIncludeValueInEmails should be true when IncludeInEmails=1" );

        $field->IncludeInEmails = 0;

        $submittedField = $field->getSubmittedFormField();
        $this->assertFalse( $submittedField->getIncludeValueInEmails(), "getIncludeValueInEmails should be false when IncludeInEmails=0" );
    }



}
