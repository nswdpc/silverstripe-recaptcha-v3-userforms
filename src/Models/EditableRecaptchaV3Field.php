<?php
namespace NSWDPC\SpamProtection;

use SilverStripe\Forms\CheckboxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\FieldList;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\Control\Controller;

/**
 * A field that adds reCAPTCHAv3 support to a user defined form
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class EditableRecaptchaV3Field extends EditableFormField
{

    const DEFAULT_ACTION = 'submit';

    /**
     * @inheritdoc
     */
    private static $singular_name = 'reCAPTCHA v3 field';

    /**
     * @inheritdoc
     */
    private static $plural_name = 'reCAPTCHA v3 fields';

    /**
     * @inheritdoc
     */
    private static $has_placeholder = false;

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Score' => 'Int',// 0-100
        'Action' => 'Varchar(255)',// custom action
        'IncludeInEmails' => 'Boolean' // whether to include submitted value in userform recipient emails
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'Action' => self::DEFAULT_ACTION,
        'IncludeInEmails' => 0
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'EditableRecaptchaV3Field';

    /**
     * The verification value is always stored
     * Use the IncludeInEmails value to determine whether the submitted value is included in emails
     * along with being saved to the submitted field
     * @inheritdoc
     */
    public function showInReports()
    {
        return true;
    }

    /**
     * Return the submitted field instance, with the IncludeInEmails value set as a boolean property
     * @inheritdoc
     */
    public function getSubmittedFormField()
    {
        $field = SubmittedRecaptchaV3Field::create();
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
     * Set captcha defaults based on implementation
     */
    protected function setCaptchaDefaults() : void {
        // use the default threshold score from config if the saved score is out of bounds
        if(is_null($this->Score) || $this->Score < 0 || $this->Score > 100) {
            $this->Score = RecaptchaV3SpamProtector::getDefaultThreshold();
        }

        if(!$this->Action) {
            $this->Action = self::DEFAULT_ACTION;
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
            $this->Title = _t( 'NSWDPC\SpamProtection.RECAPTCHAv3', 'Recaptcha v3');
        }
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();
        $this->setCaptchaDefaults();
    }

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $this->DisplayRules()->removeAll();
    }

    /**
     * Add captcha configuration fields for administration area
     */
    public function setCaptchaCMSFields(FieldList $fields) {
        // if there is no score yet, use the default
        if(is_null($this->Score) || $this->Score < 0 || $this->Score > 100) {
            $this->Score = RecaptchaV3SpamProtector::getDefaultThreshold();
        }
        $scoreField = RecaptchaV3SpamProtector::getRangeField('Score', $this->Score);

        if(!$this->Action) {
            $this->Action = self::DEFAULT_ACTION;
        }

        $fields->addFieldToTab(
            "Root.Main",
            CompositeField::create(
                $scoreField,
                RecaptchaV3SpamProtector::getActionField('Action', $this->Action),
                CheckboxField::create(
                    'IncludeInEmails',
                    _t( 'NSWDPC\SpamProtection.INCLUDE_CAPTCHA_RESULT_IN_EMAILS', 'Include captcha result in recipient emails')
                )
            )->setTitle(
                _t( 'NSWDPC\SpamProtection.RECAPTCHA_SETTINGS', 'reCAPTCHA v3 settings')
            )
        );
    }

    /**
     * CMS Fields
     * @return FieldList
     */
    public function getCMSFields()
    {
        $fields = parent::getCMSFields();

        $fields->removeByName([
            'ExtraClass', // this field can't have extra CSS stuff as it is invisible
            'Default',// there is no default value for this field
            'RightTitle',// there is no right title for this field
            'Required',// this field is always required for the form submission
            'DisplayRules',// this field is always required, therefore no display rules
            'Score'// allow implementations to set a score/threshold field
        ]);

        $this->setCaptchaCMSFields( $fields );

        return $fields;
    }

    /**
     * Return the form field with configured score and action
     * @return RecaptchaV3Field
     */
    public function getFormField()
    {

        $executeAction = $this->Action;
        if($this->Title) {
            $executeAction = strtolower($this->Title) . "/" . $executeAction;
        }

        $fieldTemplate = EditableRecaptchaV3Field::class;
        $fieldHolderTemplate = EditableRecaptchaV3Field::class . '_holder';
        $field = RecaptchaV3Field::create($this->Name, $this->Title)
            ->setScore( round( ($this->Score / 100), 2) ) // format for the reCAPTCHA API 0.00->1.00
            ->setExecuteAction($executeAction, true)
            ->setFieldHolderTemplate($fieldHolderTemplate)
            ->setTemplate($fieldTemplate);
        $this->doUpdateFormField($field);
        return $field;
    }

    /**
     * Store the score/action/hostname (except token) as the submitted value
     * We don't need or want the token
     * @return string
     */
    public function getValueFromData($data)
    {
        // this is a new instance of the field
        $response = $this->getFormField()->getResponseFromSession();
        unset($response['token']);
        $value = json_encode($response);
        return $value;
    }

}
