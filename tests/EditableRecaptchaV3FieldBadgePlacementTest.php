<?php

namespace NSWDPC\SpamProtection\Tests;

use NSWDPC\SpamProtection\EditableRecaptchaV3Field;
use NSWDPC\SpamProtection\RecaptchaV3SpamProtector;
use SilverStripe\Core\Config\Config;
use SilverStripe\Dev\SapphireTest;

/**
 * Test badge placement for editable recaptcha fields
 * @author James
 */
class EditableRecaptchaV3FieldBadgePlacementTest extends SapphireTest
{

    /**
     * @inheritdoc
     */
    protected $usesDatabase = false;

    public function testDefaultBadgePlacement(): void
    {
        Config::modify()->set(RecaptchaV3SpamProtector::class, 'badge_display', RecaptchaV3SpamProtector::BADGE_DISPLAY_DEFAULT);
        $field = EditableRecaptchaV3Field::create([
            'Score' => 50,
            'Action' => 'test/default'
        ]);
        $field->write();

        $displayOption = RecaptchaV3SpamProtector::get_badge_display();
        $this->assertEquals(RecaptchaV3SpamProtector::BADGE_DISPLAY_DEFAULT, $displayOption, "ShowRecaptchaV3Badge returned empty");

        $template = $field->getFormField()->FieldHolder();

        $this->assertTrue(!str_contains($template, "https://policies.google.com/privacy"), "Recaptcha policy link not in template");

        $this->assertTrue(!str_contains($template, "https://policies.google.com/terms"), "Recaptcha T&C link not in template");
    }

    public function testFieldBadgePlacement(): void
    {
        Config::modify()->set(RecaptchaV3SpamProtector::class, 'badge_display', RecaptchaV3SpamProtector::BADGE_DISPLAY_FIELD);
        $field = EditableRecaptchaV3Field::create([
            'Score' => 60,
            'Action' => 'test/field'
        ]);
        $displayOption = RecaptchaV3SpamProtector::get_badge_display();
        $this->assertEquals(RecaptchaV3SpamProtector::BADGE_DISPLAY_FIELD, $displayOption, "ShowRecaptchaV3Badge returned field setting");

        $template = $field->getFormField()->FieldHolder();

        $this->assertTrue(str_contains($template, "https://policies.google.com/privacy"), "Recaptcha policy link in template");

        $this->assertTrue(str_contains($template, "https://policies.google.com/terms"), "Recaptcha T&C link in template");
    }

    public function testFormBadgePlacement(): void
    {
        Config::modify()->set(RecaptchaV3SpamProtector::class, 'badge_display', RecaptchaV3SpamProtector::BADGE_DISPLAY_FORM);
        $field = EditableRecaptchaV3Field::create([
            'Score' => 20,
            'Action' => 'test/form'
        ]);
        $displayOption = RecaptchaV3SpamProtector::get_badge_display();
        $this->assertEquals(RecaptchaV3SpamProtector::BADGE_DISPLAY_FORM, $displayOption, "ShowRecaptchaV3Badge returned page setting");

        $template = $field->getFormField()->FieldHolder();

        $this->assertTrue(!str_contains($template, "https://policies.google.com/privacy"), "Recaptcha policy link not in template");

        $this->assertTrue(!str_contains($template, "https://policies.google.com/terms"), "Recaptcha T&C link not in template");
    }

    public function testPageBadgePlacement(): void
    {
        Config::modify()->set(RecaptchaV3SpamProtector::class, 'badge_display', RecaptchaV3SpamProtector::BADGE_DISPLAY_PAGE);
        $field = EditableRecaptchaV3Field::create([
            'Score' => 40,
            'Action' => 'test/page'
        ]);
        $displayOption = RecaptchaV3SpamProtector::get_badge_display();
        $this->assertEquals(RecaptchaV3SpamProtector::BADGE_DISPLAY_PAGE, $displayOption, "ShowRecaptchaV3Badge returned page setting");

        $template = $field->getFormField()->FieldHolder();

        $this->assertTrue(!str_contains($template, "https://policies.google.com/privacy"), "Recaptcha policy link not in template");

        $this->assertTrue(!str_contains($template, "https://policies.google.com/terms"), "Recaptcha T&C link not in template");
    }
}
