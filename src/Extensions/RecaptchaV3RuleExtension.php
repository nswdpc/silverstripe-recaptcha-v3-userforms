<?php

namespace NSWDPC\SpamProtection;

use SilverStripe\Forms\GridField\GridField;
use SilverStripe\Forms\GridField\GridFieldConfig_RecordViewer;
use SilverStripe\ORM\DataExtension;

/**
 * Extension to handle email modification
 * @author James
 */
class RecaptchaV3RuleExtension extends DataExtension {

    /**
     * @var array
     */
    private static $has_many = [
        'EditableRecaptchaV3Fields' => EditableRecaptchaV3Field::class
    ];

    public function updateCmsFields($fields) {

        $fields->removeByName('EditableRecaptchaV3Fields');
        $field = GridField::create(
            'EditableRecaptchaV3Fields',
            _t("NSWDPC\SpamProtection.FIELD_USING_THIS_RULE", 'Fields using this rule'),
            $this->owner->EditableRecaptchaV3Fields()
        );
        $config = new GridFieldConfig_RecordViewer();
        $field->setConfig( $config );

        if($field) {
            $fields->addFieldsToTab(
                'Root.FormFields',
                $field
            );
        }
    }

}
