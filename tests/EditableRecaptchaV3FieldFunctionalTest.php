<?php

namespace NSWDPC\SpamProtection\Tests;

use NSWDPC\SpamProtection\Logger;
use NSWDPC\SpamProtection\Verifier;
use NSWDPC\SpamProtection\RecaptchaV3Field;
use NSWDPC\SpamProtection\RecaptchaV3Rule;
use NSWDPC\SpamProtection\EditableRecaptchaV3Field;
use NSWDPC\SpamProtection\SubmittedRecaptchaV3Field;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\UserForms\Control\UserDefinedFormController;
use SilverStripe\Dev\FunctionalTest;
use SilverStripe\UserForms\Model\Recipient\EmailRecipient;
use SilverStripe\UserForms\Model\UserDefinedForm;
use SilverStripe\Security\SecurityToken;

/**
 * Functional tests for {@link NSWDPC\SpamProtection\EditableRecaptchaV3Field}
 * @author James
 */
class EditableRecaptchaV3FieldFunctionalTest extends FunctionalTest
{

    /**
     * @var string
     */
    protected static $fixture_file = 'EditableRecaptchaV3FieldFunctionalTest.yml';

    /**
     * @var bool
     */
    protected static $use_draft_site = false;

    /**
     * @var bool
     */
    protected static $disable_themes = true;

    /**
     * @var bool
     */
    protected $usesDatabase = true;

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

    /**
     * Functional test for IncludeInEmails=1 value
     */
    public function testProcessIncludeInEmails()
    {

        // Use the test verifier
        $verifier = TestVerifier::create();
        $verifier->setIsHuman(true);
        Injector::inst()->registerService(
            $verifier,
            Verifier::class
        );

        $userDefinedForm = $this->setupFormFrontend('include-in-emails');

        $recipients = $userDefinedForm->EmailRecipients();
        $this->assertEquals(1, $recipients->count(), "UserDefinedForm has one EmailRecipient");

        $recipient = $recipients->first();
        $this->assertEquals('test.include@example.com', $recipient->EmailAddress, "EmailRecipient has correct address");

        $this->assertInstanceOf(UserDefinedForm::class, $userDefinedForm, "Form is a UserDefinedForm");

        $controller = new UserDefinedFormController($userDefinedForm);

        $this->autoFollowRedirection = true;
        $this->clearEmails();

        // load the form
        $page = $this->get($userDefinedForm->Link());

        // check the field exists
        $form = $controller->Form();
        $captchaField = $form->HiddenFields()->fieldByName('include_in_emails');

        $this->assertInstanceOf(RecaptchaV3Field::class, $captchaField, "include_in_emails is a RecaptchaV3Field");

        $this->assertInstanceOf(TestVerifier::class, $captchaField->getVerifier(), "Verifier is the TestVerifier");

        $token = 'token_include_in_emails';
        $data = [
            $captchaField->getName() => $token,
            'SecurityID' => SecurityToken::getSecurityID()
        ];
        // Have to post with token value as submitForm does not allow HiddenField values to be set
        $response = $this->post($form->FormAction(), $data);

        $submittedFields = SubmittedRecaptchaV3Field::get()->filter(['Name' => 'include_in_emails']);

        $this->assertEquals(1, $submittedFields->count(), "One SubmittedRecaptchaV3Field for include_in_emails");

        $submittedField = $submittedFields->first();

        $value = $submittedField->Value;
        $title = $submittedField->Title;

        $this->assertNotEmpty($value, "Submitted verification field value empty");
        $this->assertNotEmpty($title, "Submitted verification field title empty");

        $decodedValue = json_decode($value, true);

        $this->assertNotEmpty($decodedValue);
        $this->assertEquals(TestVerifier::RESPONSE_HUMAN_SCORE, $decodedValue['score'], "Score is " . TestVerifier::RESPONSE_HUMAN_SCORE);
        $this->assertEquals('localhost', $decodedValue['hostname'], "Hostname is localhost");
        $this->assertEquals('includeinemails/functionaltest', $decodedValue['action'], "Action is includeinemails/functionaltest");

        $email = $this->findEmail($recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject);

        // check emails
        $this->assertEmailSent($recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject);

        $this->assertTrue(strpos($email['Content'], $recipient->EmailBodyHtml) !== false, 'Email contains the expected HTML string');
        $this->assertTrue(strpos($email['Content'], $title) !== false, 'Email contains the field name');
        $this->assertTrue(strpos($email['Content'], $value) !== false, 'Email contains the field value');
    }

    /**
     * Functional test for IncludeInEmails=0 value
     */
    public function testProcessNotIncludeInEmails()
    {

        // and test verifier
        $verifier = TestVerifier::create();
        $verifier->setIsHuman(true);
        Injector::inst()->registerService(
            $verifier,
            Verifier::class
        );

        $userDefinedForm = $this->setupFormFrontend('not-include-in-emails');

        $recipients = $userDefinedForm->EmailRecipients();
        $this->assertEquals(1, $recipients->count(), "UserDefinedForm has one EmailRecipient");

        $recipient = $recipients->first();
        $this->assertEquals('test.notinclude@example.com', $recipient->EmailAddress, "EmailRecipient has correct address");

        $this->assertInstanceOf(UserDefinedForm::class, $userDefinedForm, "Form is a UserDefinedForm");

        $controller = new UserDefinedFormController($userDefinedForm);

        $this->autoFollowRedirection = true;
        $this->clearEmails();

        // load the form
        $page = $this->get($userDefinedForm->Link());

        // check the form exists
        $form = $controller->Form();

        $captchaField = $form->HiddenFields()->fieldByName('not_include_in_emails');

        $this->assertInstanceOf(RecaptchaV3Field::class, $captchaField, "not_include_in_emails is a RecaptchaV3Field");

        $token = 'token_not_include_in_emails';
        $data = [
            $captchaField->getName() => $token,
            'SecurityID' => SecurityToken::getSecurityID()
        ];
        $response = $this->post($form->FormAction(), $data);

        $submittedFields = SubmittedRecaptchaV3Field::get()->filter(['Name' => 'not_include_in_emails']);

        $this->assertEquals(1, $submittedFields->count(), "One SubmittedRecaptchaV3Field for not_include_in_emails");

        $submittedField = $submittedFields->first();

        $value = $submittedField->Value;
        $title = $submittedField->Title;

        $this->assertNotEmpty($value, "Submitted verification field value empty");
        $this->assertNotEmpty($title, "Submitted verification field title empty");

        $decodedValue = json_decode($value, true);

        $this->assertNotEmpty($decodedValue);
        $this->assertEquals(TestVerifier::RESPONSE_HUMAN_SCORE, $decodedValue['score'], "Score is " . TestVerifier::RESPONSE_HUMAN_SCORE);
        $this->assertEquals('localhost', $decodedValue['hostname'], "Hostname is localhost");
        $this->assertEquals('notincludeinemails/functionaltest', $decodedValue['action'], "Action is notincludeinemails/functionaltest");

        $email = $this->findEmail($recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject);

        // check emails
        $this->assertEmailSent($recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject);

        $this->assertTrue(strpos($email['Content'], $recipient->EmailBodyHtml) !== false, 'Email contains the expected HTML string');
        $this->assertFalse(strpos($email['Content'], $title) !== false, 'Email contains the field name');
        $this->assertFalse(strpos($email['Content'], $value) !== false, 'Email contains the field value');
    }


    /**
     * Functional test for IncludeInEmails=1 value
     */
    public function testProcessWithRuleAttachedToEditableField()
    {

        // create a rule
        $rule = RecaptchaV3Rule::create([
            'Tag' => 'editable-field-rule',
            'Score' => 55,
            'Action' => 'editablefieldrule/submit',
            'ActionToTake' => 'Block',
            'Enabled' => 1,
            'AutoCreated' =>  0
        ]);
        $rule->write();

        // Use the test verifier
        $verifier = TestVerifier::create();
        $verifier->setIsHuman(true);
        Injector::inst()->registerService(
            $verifier,
            Verifier::class
        );

        $userDefinedForm = $this->setupFormFrontend('test-field-with-rule');

        $recipients = $userDefinedForm->EmailRecipients();
        $this->assertEquals(1, $recipients->count(), "UserDefinedForm has one EmailRecipient");

        $recipient = $recipients->first();
        $this->assertEquals('test.include.rule@example.com', $recipient->EmailAddress, "EmailRecipient has correct address");

        $this->assertInstanceOf(UserDefinedForm::class, $userDefinedForm, "Form is a UserDefinedForm");

        $editableRecaptchaV3Field = $userDefinedForm->Fields()->filter(['Name' => 'field_with_rule'])->first();
        $this->assertInstanceOf(EditableRecaptchaV3Field::class, $editableRecaptchaV3Field, "Field is a EditableRecaptchaV3Field");

        $editableRecaptchaV3Field->RuleID = $rule->ID;
        $editableRecaptchaV3Field->write();
        $this->actWithPermission('ADMIN', function () use ($editableRecaptchaV3Field) {
            $editableRecaptchaV3Field->doPublish();
        });

        $checkRule = $editableRecaptchaV3Field->Rule();
        $this->assertEquals($rule->ID, $checkRule->ID, "Rules match");

        $controller = new UserDefinedFormController($userDefinedForm);

        $this->autoFollowRedirection = true;
        $this->clearEmails();

        // load the form
        $page = $this->get($userDefinedForm->Link());

        // check the field exists
        $form = $controller->Form();
        $captchaField = $form->HiddenFields()->fieldByName('field_with_rule');

        $this->assertInstanceOf(RecaptchaV3Field::class, $captchaField, "field_with_rule is a RecaptchaV3Field");

        $this->assertInstanceOf(TestVerifier::class, $captchaField->getVerifier(), "Verifier is the TestVerifier");

        $token = 'token_check_rule';
        $data = [
            $captchaField->getName() => $token,
            'SecurityID' => SecurityToken::getSecurityID()
        ];

        // Have to post with token value as submitForm does not allow HiddenField values to be set
        $response = $this->post($form->FormAction(), $data);

        $submittedFields = SubmittedRecaptchaV3Field::get()->filter(['Name' => 'field_with_rule']);

        $this->assertEquals(1, $submittedFields->count(), "One SubmittedRecaptchaV3Field for include_in_emails");

        $submittedField = $submittedFields->first();

        $value = $submittedField->Value;
        $title = $submittedField->Title;

        $this->assertNotEmpty($value, "Submitted verification field value empty");
        $this->assertNotEmpty($title, "Submitted verification field title empty");

        $decodedValue = json_decode($value, true);

        $this->assertNotEmpty($decodedValue);
        $this->assertEquals(TestVerifier::RESPONSE_HUMAN_SCORE, $decodedValue['score'], "Score is " . TestVerifier::RESPONSE_HUMAN_SCORE);
        $this->assertEquals(round($rule->Score/100, 2), $decodedValue['threshold'], "Threshold used in verification is the Rule score");
        $this->assertEquals('localhost', $decodedValue['hostname'], "Hostname is localhost");
        $this->assertEquals($rule->Action, $decodedValue['action'], "Action is the Rule Action");

        $email = $this->findEmail($recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject);

        // check emails
        $this->assertEmailSent($recipient->EmailAddress, $recipient->EmailReplyTo, $recipient->EmailSubject);

        $this->assertTrue(strpos($email['Content'], $recipient->EmailBodyHtml) !== false, 'Email contains the expected HTML string');
        $this->assertTrue(strpos($email['Content'], $title) !== false, 'Email contains the field name');
        $this->assertTrue(strpos($email['Content'], $value) !== false, 'Email contains the field value');
    }


    /**
     * Functional test for IncludeInEmails=1 value
     */
    public function testProcessFailVerificationWithRuleAttachedToEditableField()
    {

        // create a rule
        $rule = RecaptchaV3Rule::create([
            'Tag' => 'editable-field-rule',
            'Score' => 90,
            'Action' => 'editablefieldruletofail/submit',
            'ActionToTake' => 'Block',
            'Enabled' => 1,
            'AutoCreated' =>  0
        ]);
        $rule->write();

        // Use the test verifier
        $verifier = TestVerifier::create();
        $verifier->setIsHuman(false);
        Injector::inst()->registerService(
            $verifier,
            Verifier::class
        );

        $userDefinedForm = $this->setupFormFrontend('test-field-with-rule');

        $recipients = $userDefinedForm->EmailRecipients();
        $this->assertEquals(1, $recipients->count(), "UserDefinedForm has one EmailRecipient");

        $recipient = $recipients->first();
        $this->assertEquals('test.include.rule@example.com', $recipient->EmailAddress, "EmailRecipient has correct address");

        $this->assertInstanceOf(UserDefinedForm::class, $userDefinedForm, "Form is a UserDefinedForm");

        $editableRecaptchaV3Field = $userDefinedForm->Fields()->filter(['Name' => 'field_with_rule'])->first();
        $this->assertInstanceOf(EditableRecaptchaV3Field::class, $editableRecaptchaV3Field, "Field is a EditableRecaptchaV3Field");

        $editableRecaptchaV3Field->RuleID = $rule->ID;
        $editableRecaptchaV3Field->write();
        $this->actWithPermission('ADMIN', function () use ($editableRecaptchaV3Field) {
            $editableRecaptchaV3Field->doPublish();
        });

        $checkRule = $editableRecaptchaV3Field->Rule();
        $this->assertEquals($rule->ID, $checkRule->ID, "Rules match");

        $controller = new UserDefinedFormController($userDefinedForm);

        $this->autoFollowRedirection = true;
        $this->clearEmails();

        // load the form
        $page = $this->get($userDefinedForm->Link());

        // check the field exists
        $form = $controller->Form();
        $captchaField = $form->HiddenFields()->fieldByName('field_with_rule');

        $this->assertInstanceOf(RecaptchaV3Field::class, $captchaField, "field_with_rule is a RecaptchaV3Field");

        $this->assertInstanceOf(TestVerifier::class, $captchaField->getVerifier(), "Verifier is the TestVerifier");

        $token = 'token_check_rule';
        $data = [
            $captchaField->getName() => $token,
            'SecurityID' => SecurityToken::getSecurityID()
        ];

        // Have to post with token value as submitForm does not allow HiddenField values to be set
        $response = $this->post($form->FormAction(), $data);

        $this->assertTrue(strpos($response->getBody(), RecaptchaV3Field::getMessagePossibleSpam()) !== false, "Message contains possible spam response");
    }
}
