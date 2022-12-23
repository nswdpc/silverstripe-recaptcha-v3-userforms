<?php
namespace NSWDPC\SpamProtection;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;

/**
 * A field that adds Turnstile captcha support to a user defined form
 * @author James
 */
class EditableTurnstileField extends EditableRecaptchaV3Field
{

    /**
     * @inheritdoc
     */
    private static $singular_name = 'Turnstile challenge field';

    /**
     * @inheritdoc
     */
    private static $plural_name = 'Turnstile challenge fields';

    /**
     * @inheritdoc
     */
    private static $has_placeholder = false;

    /**
     * Return the submitted field instance, with the IncludeInEmails value set as a boolean property
     * @inheritdoc
     */
    public function getSubmittedFormField()
    {
        $field = SubmittedTurnstileField::create();
        $field->setIncludeValueInEmails( $this->IncludeInEmails == 1 );
        return $field;
    }

    /**
     * Format action string based on implementation rules
     */
    protected function formatCaptchaAction() : string {
        return TurnstileTokenResponse::formatAction( $this->Action );
    }

    /**
     * Turnstile does not support a threshold setting
     * @return int|null
     */
    public function getDefaultThreshold() : ?int {
        return null;
    }

    /**
     * Turnstile does not support a threshold setting
     * @return array
     */
    protected function getRange() : array {
        return [];
    }

    /**
     * Set captcha defaults based on implementation
     */
    protected function setCaptchaDefaults() : void {

        // the threshold score
        $this->Score = null;

        if(!$this->Action) {
            $this->Action = parent::DEFAULT_ACTION;
        }

        // remove disallowed characters
        $this->Action = $this->formatCaptchaAction();

        /**
         * never require this field as it could cause weirdness with frontend validators
         */
        $this->Required = 0;

        // no placeholder
        $this->Placeholder = "";

        // always require a default title
        if(!$this->Title) {
            $this->Title = _t( 'NSWDPC\SpamProtection.TURNSTILE_CHALLENGE', 'Turnstile Challenge');
        }
    }

    /**
     * Add captcha configuration fields for administration area
     */
    public function setCaptchaCMSFields(FieldList $fields) {

        if(!$this->Action) {
            $this->Action = parent::DEFAULT_ACTION;
        }

        $fields->addFieldToTab(
            "Root.Main",
            CompositeField::create(
                TurnstileSpamProtector::getActionField('Action', $this->Action)->setMaxLength(32),
                CheckboxField::create(
                    'IncludeInEmails',
                    _t( 'NSWDPC\SpamProtection.INCLUDE_CAPTCHA_RESULT_IN_EMAILS', 'Include captcha result in recipient emails')
                )
            )->setTitle(
                _t( 'NSWDPC\SpamProtection.TURNSTILE_SETTINGS', 'Turnstile settings')
            )
        );
    }

    /**
     * Return the form field with configured action based on current context
     * @return TurnstileField
     */
    public function getFormField()
    {
        $executeAction = $this->Action;
        if($this->Title) {
            $executeAction = strtolower($this->Title) . "_" . $executeAction;
        }
        $fieldTemplate = EditableTurnstileField::class;
        $fieldHolderTemplate = EditableTurnstileField::class . '_holder';
        $field = TurnstileField::create($this->Name, $this->Title)
            ->setExecuteAction($executeAction, true)
            ->setFieldHolderTemplate($fieldHolderTemplate)
            ->setTemplate($fieldTemplate);
        $this->doUpdateFormField($field);
        return $field;
    }

}
