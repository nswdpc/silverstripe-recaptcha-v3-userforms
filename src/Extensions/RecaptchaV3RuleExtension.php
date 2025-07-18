<?php

namespace NSWDPC\SpamProtection;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\ORM\DataExtension;

/**
 * Extension to add EditableRecaptchaV3Fields to a rule
 * @author James
 * @method \SilverStripe\ORM\HasManyList<\NSWDPC\SpamProtection\EditableRecaptchaV3Field> EditableRecaptchaV3Fields()
 * @extends \SilverStripe\ORM\DataExtension<(\NSWDPC\SpamProtection\RecaptchaV3Rule & static)>
 */
class RecaptchaV3RuleExtension extends \SilverStripe\Core\Extension
{
    private static array $has_many = [
        'EditableRecaptchaV3Fields' => EditableRecaptchaV3Field::class
    ];

    /**
     * Update CMS fields
     */
    public function updateCmsFields($fields)
    {
        $fields->removeByName('EditableRecaptchaV3Fields');
        $field = GridField::create(
            'EditableRecaptchaV3Fields',
            _t("NSWDPC\SpamProtection.FIELD_USING_THIS_RULE", 'Fields using this rule'),
            $this->getOwner()->EditableRecaptchaV3Fields()
        );
        $config = GridFieldConfig_RecordViewer::create();
        $field->setConfig($config);

        if ($field) {
            $fields->addFieldsToTab(
                'Root.FormFields',
                $field
            );
        }
    }
}
