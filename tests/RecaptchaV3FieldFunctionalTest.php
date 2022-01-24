<?php

namespace NSWDPC\SpamProtection\Tests;

use NSWDPC\SpamProtection\Verifier;
use NSWDPC\SpamProtection\RecaptchaV3Field;
use NSWDPC\SpamProtection\EditableRecaptchaV3Field;
use NSWDPC\SpamProtection\SubmittedRecaptchaV3Field;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\UserForms\Control\UserDefinedFormController;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\UserForms\Model\Recipient\EmailRecipient;
use SilverStripe\UserForms\Model\UserDefinedForm;

/**
 * @package userforms
 */
class Recaptchav3FieldFunctionalTest extends FunctionalTest
{
    protected static $fixture_file = 'RecaptchaV3FieldFunctionalTest.yml';

    protected static $use_draft_site = false;

    protected static $disable_themes = true;

    protected $usesDatabase = true;

    protected function setUp(): void
    {
        parent::setUp();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
    }

    /**
     * Publish a form for use on the frontend
     *
     * @param string $fixtureName
     * @return UserDefinedForm
     */
    protected function setupFormFrontend($fixtureName)
    {
        $form = $this->objFromFixture(UserDefinedForm::class, $fixtureName);

        $this->actWithPermission('ADMIN', function () use ($form) {
            $form->publishRecursive();
        });

        return $form;
    }

    public function testProcessIncludeInEmails()
    {

        $field = new TestRecaptchaV3Field('include_in_emails');
        $field->setIsSuccess(true);

        // use the test field
        Injector::inst()->registerService(
            $field,
            RecaptchaV3Field::class
        );

        // and test verifier
        Injector::inst()->registerService(
            new TestVerifier(), Verifier::class
        );

        $userDefinedForm = $this->setupFormFrontend('include-in-emails');

        $recipients = $userDefinedForm->EmailRecipients();
        $this->assertEquals(1, $recipients->count(), "UserDefinedForm has one EmailRecipient");

        $recipient = $recipients->first();
        $this->assertEquals('test.include@example.com', $recipient->EmailAddress, "EmailRecipient has correct address");

        $this->assertInstanceOf(UserDefinedForm::class,  $userDefinedForm, "Form is a UserDefinedForm");

        $controller = new UserDefinedFormController($userDefinedForm);

        $this->autoFollowRedirection = true;
        $this->clearEmails();

        // load the form
        $page = $this->get( $userDefinedForm->Link() );

        // check the form exists
        $form = $controller->Form();

        $captchaField = $form->HiddenFields()->fieldByName('include_in_emails');

        $this->assertInstanceOf( TestRecaptchaV3Field::class, $captchaField, "include_in_emails is a TestRecaptchaV3Field" );

        $data = [];
        $response = $this->submitForm('UserForm_Form_' . $userDefinedForm->ID, null, $data);

        $submittedFields = SubmittedRecaptchaV3Field::get()->filter(['Name' => 'include_in_emails']);

        $this->assertTrue($submittedFields->count() == 1, "One SubmittedRecaptchaV3Field for include_in_emails");

        $submittedField = $submittedFields->first();

        $value = $submittedField->Value;
        $title = $submittedField->Title;

        $this->assertNotEmpty( $value, "Submitted verification field value empty" );
        $this->assertNotEmpty( $title, "Submitted verification field title empty" );

        $decodedValue = json_decode($value, true);

        $this->assertNotEmpty($decodedValue);
        $this->assertEquals( TestVerifier::RESPONSE_HUMAN_SCORE, $decodedValue['score'], "Score is " . TestVerifier::RESPONSE_HUMAN_SCORE);
        $this->assertEquals( 'localhost',  $decodedValue['hostname'], "Hostname is localhost");
        $this->assertEquals( 'includeinemails/functionaltest',  $decodedValue['action'], "Action is includeinemails/functionaltest");

        $email = $this->findEmail( $recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject );

        // check emails
        $this->assertEmailSent( $recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject );

        $this->assertTrue(strpos($email['Content'], $recipient->EmailBodyHtml) !== false, 'Email contains the expected HTML string');
        $this->assertTrue(strpos($email['Content'], $title) !== false, 'Email contains the field name');
        $this->assertTrue(strpos($email['Content'], $value) !== false, 'Email contains the field value');

    }

    public function testProcessNotIncludeInEmails() {
        $field = new TestRecaptchaV3Field('not_include_in_emails');
        $field->setIsSuccess(true);

        // use the test field
        Injector::inst()->registerService(
            $field,
            RecaptchaV3Field::class
        );

        // and test verifier
        Injector::inst()->registerService(
            new TestVerifier(), Verifier::class
        );

        $userDefinedForm = $this->setupFormFrontend('not-include-in-emails');

        $recipients = $userDefinedForm->EmailRecipients();
        $this->assertEquals(1, $recipients->count(), "UserDefinedForm has one EmailRecipient");

        $recipient = $recipients->first();
        $this->assertEquals('test.notinclude@example.com', $recipient->EmailAddress, "EmailRecipient has correct address");

        $this->assertInstanceOf(UserDefinedForm::class,  $userDefinedForm, "Form is a UserDefinedForm");

        $controller = new UserDefinedFormController($userDefinedForm);

        $this->autoFollowRedirection = true;
        $this->clearEmails();

        // load the form
        $page = $this->get( $userDefinedForm->Link() );

        // check the form exists
        $form = $controller->Form();

        $captchaField = $form->HiddenFields()->fieldByName('not_include_in_emails');

        $this->assertInstanceOf( TestRecaptchaV3Field::class, $captchaField, "not_include_in_emails is a TestRecaptchaV3Field" );

        $data = [];
        $response = $this->submitForm('UserForm_Form_' . $userDefinedForm->ID, null, $data);

        $submittedFields = SubmittedRecaptchaV3Field::get()->filter(['Name' => 'not_include_in_emails']);

        $this->assertTrue($submittedFields->count() == 1, "One SubmittedRecaptchaV3Field for not_include_in_emails");

        $submittedField = $submittedFields->first();

        $value = $submittedField->Value;
        $title = $submittedField->Title;

        $this->assertNotEmpty( $value, "Submitted verification field value empty" );
        $this->assertNotEmpty( $title, "Submitted verification field title empty" );

        $decodedValue = json_decode($value, true);

        $this->assertNotEmpty($decodedValue);
        $this->assertEquals( TestVerifier::RESPONSE_HUMAN_SCORE, $decodedValue['score'], "Score is " . TestVerifier::RESPONSE_HUMAN_SCORE);
        $this->assertEquals( 'localhost',  $decodedValue['hostname'], "Hostname is localhost");
        $this->assertEquals( 'notincludeinemails/functionaltest',  $decodedValue['action'], "Action is notincludeinemails/functionaltest");

        $email = $this->findEmail( $recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject );

        // check emails
        $this->assertEmailSent( $recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject );

        $this->assertTrue(strpos($email['Content'], $recipient->EmailBodyHtml) !== false, 'Email contains the expected HTML string');
        $this->assertFalse(strpos($email['Content'], $title) !== false, 'Email contains the field name');
        $this->assertFalse(strpos($email['Content'], $value) !== false, 'Email contains the field value');
    }

}
