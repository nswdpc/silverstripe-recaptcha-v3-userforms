<?php
namespace NSWDPC\SpamProtection;

use SilverStripe\Forms\CheckBoxField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\HeaderField;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\Control\Controller;

/**
 * EditableRecaptchaV3Field
 * A field that adds reCAPTCHAv3 support to a user defined form
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class EditableRecaptchaV3Field extends EditableFormField
{
    private static $singular_name = 'reCAPTCHA v3 field';

    private static $plural_name = 'reCAPTCHA v3 fields';

    private static $has_placeholder = false;

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Score' => 'Int',// 0-100
        'Action' => 'Varchar(255)',// custom action
        'IncludeInEmails' => 'Boolean'
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'Action' => 'submit',
        'IncludeInEmails' => 0
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'EditableRecaptchaV3Field';

    /**
     * The reCAPTCHA verification value is always stored
     * Use the IncludeInEmails value to determine whether the reCAPTCHA value is included in emails
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
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // use the default threshold score from config if the saved score is out of bounds
        if(is_null($this->Score) || $this->Score < 0 || $this->Score > 100) {
            $this->Score = $this->getDefaultThreshold();
        }

        if(!$this->Action) {
            $this->Action = $this->config()->get('defaults')['Action'];
        }

        // remove disallowed characters
        $this->Action = TokenResponse::formatAction($this->Action);

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

    public function onAfterWrite()
    {
        parent::onAfterWrite();
        $this->DisplayRules()->removeAll();
    }

    /**
     * Get default threshold score as a float from configuration
     * @return int
     */
    public function getDefaultThreshold() {
        return RecaptchaV3SpamProtector::getDefaultThreshold();
    }

    /**
     * Return range of allowed thresholds
     * @return array
     */
    protected function getRange() {
        return RecaptchaV3SpamProtector::getRange();
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
            'DisplayRules'// this field is always required, therefore no display rules
        ]);

        // if there is no score yet, use the default
        if(is_null($this->Score) || $this->Score < 0 || $this->Score > 100) {
            $this->Score = $this->getDefaultThreshold();
        }
        $range_field = RecaptchaV3SpamProtector::getRangeField('Score', $this->Score);

        if(!$this->Action) {
            $this->Action = $this->config()->get('defaults')['Action'];
        }

        $fields->addFieldsToTab(
                "Root.Main", [
                    HeaderField::create(
                        'reCAPTCHAv3Header',
                        _t( 'NSWDPC\SpamProtection.RECAPTCHA_SETTINGS', 'reCAPTCHA v3 settings')
                    ),
                    $range_field,
                    RecaptchaV3SpamProtector::getActionField('Action', $this->Action),
                    CheckboxField::create(
                        'IncludeInEmails',
                        _t( 'NSWDPC\SpamProtection.INCLUDE_IN_EMAILS', 'Include reCAPTCHAv3 verification information in emails')
                    )
                ]
        );
        return $fields;
    }

    /**
     * Return the form field with configured score and action
     * @return RecaptchaV3Field
     */
    public function getFormField()
    {
        $parent_form_identifier = "";
        if($parent = $this->Parent()) {
            $parent_form_identifier = $parent->URLSegment;
        }
        $field_template = EditableRecaptchaV3Field::class;
        $field_holder_template = EditableRecaptchaV3Field::class . '_holder';
        $field = RecaptchaV3Field::create($this->Name, $this->Title)
            ->setScore( round( ($this->Score / 100), 2) ) // format for the reCAPTCHA API 0.00->1.00
            ->setExecuteAction($parent_form_identifier . "/" . $this->Action, true)
            ->setFieldHolderTemplate($field_holder_template)
            ->setTemplate($field_template);
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
