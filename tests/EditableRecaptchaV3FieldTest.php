<?php

namespace NSWDPC\SpamProtection\Tests;

use NSWDPC\SpamProtection\RecaptchaV3Rule;
use NSWDPC\SpamProtection\RecaptchaV3Field;
use NSWDPC\SpamProtection\EditableRecaptchaV3Field;
use NSWDPC\SpamProtection\SubmittedRecaptchaV3Field;
use SilverStripe\Dev\SapphireTest;

/**
 * Tests for the {@link NSWDPC\SpamProtection\EditableRecaptchaV3Field}
 * @author James
 */
class EditableRecaptchaV3FieldTest extends SapphireTest
{

    /**
     * @var bool
     */
    protected $usesDatabase = true;

    /**
     * Test field return from getFormField
     */
    public function testGetFormField(): void
    {
        $fieldScore = 32;
        $minRefreshTime = 9;
        $fieldAction = "testgetformfield/submit";

        $field = EditableRecaptchaV3Field::create();
        $field->Title = "Test spam protection";
        $field->IncludeInEmails = 0;
        $field->Score = $fieldScore;
        $field->Action = $fieldAction;
        $field->MinRefreshTime = $minRefreshTime;
        $field->write();

        $formField = $field->getFormField();

        $this->assertInstanceOf(RecaptchaV3Field::class, $formField, "Field is a RecaptchaV3Field");

        $score = $formField->getScore();
        $this->assertEquals(round($fieldScore / 100, 2), $score, "Score matches");

        $action = $formField->getRecaptchaAction();
        $this->assertEquals($fieldAction, $action, "Action matches");

        $rule = $formField->getRecaptchaV3Rule();
        $this->assertEmpty($rule, "No rule for field");

        $template = $formField->forTemplate();
        $this->assertStringNotContainsString('data-rule="', $template);

        $this->assertEquals( $minRefreshTime, ($formField->getMinRefreshTime() / 1000) );
    }

    /**
     * Test actions being returned from form field created
     */
    public function testActions(): void
    {

        $field = EditableRecaptchaV3Field::create();
        $field->Title = "Test field action changes";

        $actions = [
            'test1' => 'test1',
            'prefix/test1' => 'prefix/test1',
            '/test1' => '/test1',
            '1009' => '1009',
            'prefix/1009' => 'prefix/1009',
            'test2/' => 'test2/',
            '0' => RecaptchaV3Field::getDefaultAction(),
            1 => '1',
            '' => RecaptchaV3Field::getDefaultAction(),
            null => RecaptchaV3Field::getDefaultAction(),
            'form=test1' => 'formtest1'
        ];

        foreach($actions as $action => $expectedFieldAction) {
            $field->Action = $action;
            $field->write();
            $formField = $field->getFormField();
            $fieldAction = $formField->getRecaptchaAction();
            $this->assertEquals($expectedFieldAction, $fieldAction, "Action {$action} matches {$expectedFieldAction}");
        }
    }

    /**
     * Test field return from getFormField when the field has a rule
     */
    public function testGetFormFieldWithRule(): void
    {
        $fieldScore = 32;
        $fieldAction = "testgetformfield/submit";
        $ruleTag = "testruletag";
        $ruleAction = "testruletag/submit";
        $ruleScore = 56;

        $rule = RecaptchaV3Rule::create([
            'Tag' => $ruleTag,
            'Score' => $ruleScore,
            'Action' => $ruleAction,
            'Enabled' => 1,
            'ActionToTake' => RecaptchaV3Rule::TAKE_ACTION_BLOCK,
            'AutoCreated' => 0
        ]);
        $id = $rule->write();

        $this->assertNotEmpty($id, "Rule was created");

        $field = EditableRecaptchaV3Field::create();
        $field->Title = "Test spam protection";
        $field->IncludeInEmails = 0;
        $field->Score = $fieldScore;
        $field->Action = $fieldAction;
        $field->RuleID = $id;
        $field->write();

        $formField = $field->getFormField();

        $this->assertInstanceOf(RecaptchaV3Field::class, $formField, "Field is a RecaptchaV3Field");

        $score = $formField->getScore();
        $this->assertEquals(round($ruleScore / 100, 2), $score, "Score matches");

        $action = $formField->getRecaptchaAction();
        $this->assertEquals($ruleAction, $action, "Action matches");

        $foundRule = $formField->getRecaptchaV3Rule();
        $this->assertEquals($rule->ID, $foundRule->ID, "Rule matches");


        $template = $formField->forTemplate();
        $this->assertStringContainsString("data-rule=\"{$rule->ID}\"", $template);
    }

    /**
     * Test field value inclusion/exclusion
     */
    public function testVerificationValue(): void
    {
        $field = EditableRecaptchaV3Field::create();
        $field->Title = "Test spam protection";
        $field->IncludeInEmails = 1;

        $submittedField = $field->getSubmittedFormField();
        $this->assertInstanceOf(SubmittedRecaptchaV3Field::class, $submittedField);
        $this->assertTrue($submittedField->getIncludeValueInEmails(), "getIncludeValueInEmails should be true when IncludeInEmails=1");

        $field->IncludeInEmails = 0;

        $submittedField = $field->getSubmittedFormField();
        $this->assertInstanceOf(SubmittedRecaptchaV3Field::class, $submittedField);
        $this->assertFalse($submittedField->getIncludeValueInEmails(), "getIncludeValueInEmails should be false when IncludeInEmails=0");
    }
}
