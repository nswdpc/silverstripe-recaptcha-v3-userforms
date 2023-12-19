<?php

namespace NSWDPC\SpamProtection;

use SilverStripe\Forms\CheckBoxField;
use SilverStripe\Forms\CompositeField;
use SilverStripe\Forms\TextField;
use SilverStripe\Forms\DropdownField;
use SilverStripe\Forms\NumericField;
use SilverStripe\UserForms\Model\EditableFormField;
use SilverStripe\Control\Controller;

/**
 * EditableRecaptchaV3Field
 * A field that adds reCAPTCHAv3 support to a user defined form
 * @author James <james.ellis@dpc.nsw.gov.au>
 */
class EditableRecaptchaV3Field extends EditableFormField
{

    /**
     * @var string
     */
    private static $singular_name = 'Captcha field';

    /**
     * @var string
     */
    private static $plural_name = 'Captcha fields';

    /**
     * @var bool
     */
    private static $has_placeholder = false;

    /**
     * Database fields
     * @var array
     */
    private static $db = [
        'Score' => 'Int',// 0-100
        'Action' => 'Varchar(255)',// custom action
        'IncludeInEmails' => 'Boolean',
        'MinRefreshTime' => 'Int' // in seconds
    ];

    /**
     * @var array
     */
    private static $has_one = [
        'Rule' =>  RecaptchaV3Rule::class
    ];

    /**
     * Add default values to database
     * @var array
     */
    private static $defaults = [
        'Action' => '',
        'IncludeInEmails' => 0,
        'MinRefreshTime' => 30
    ];

    /**
     * Summary fields
     * @var array
     */
    private static $summary_fields = [
        'Title' => 'Title',
        'FieldScore' => 'Threshold',
        'FieldAction' => 'Action'
    ];

    /**
     * Defines the database table name
     * @var string
     */
    private static $table_name = 'EditableRecaptchaV3Field';


    /**
     * Used as fallback value for default, it specified value is not valid
     * @var string
     * @deprecated see RecaptchaV3Field::DEFAULT_ACTION
    */
    const DEFAULT_ACTION = 'submit';

    /**
     * Used as fallback value for default, it specified value is not valid
     * @var int
    */
    const DEFAULT_THRESHOLD = 50;

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
        $field->setIncludeValueInEmails($this->IncludeInEmails == 1);
        return $field;
    }

    /**
     * Event handler called before writing to the database.
     */
    public function onBeforeWrite()
    {
        parent::onBeforeWrite();

        // use the default threshold score from config if the saved score is out of bounds
        if (is_null($this->Score) || $this->Score < 0 || $this->Score > 100) {
            $this->Score = $this->getDefaultThreshold();
        }

        if (TokenResponse::isEmptyAction($this->Action)) {
            $this->Action = RecaptchaV3Field::getDefaultAction();
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
        if (!$this->Title) {
            $this->Title = _t('NSWDPC\SpamProtection.FORM_SPAM_PROTECTION', 'Form spam protection');
        }

        if($this->MinRefreshTime <= 0) {
            $this->MinRefreshTime = self::config()->get('defaults')['MinRefreshTime'];
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
    public function getDefaultThreshold() : int
    {
        $threshold =  RecaptchaV3SpamProtector::getDefaultThreshold();
        return is_int($threshold) ? $threshold : self::DEFAULT_THRESHOLD;
    }

    /**
     * @deprecated use RecaptchaV3Field::getDefaultAction()
     * @return string
     */
    public function getDefaultAction() : string
    {
        return RecaptchaV3Field::getDefaultAction();
    }

    /**
     * Return range of allowed thresholds
     */
    protected function getRange() : array
    {
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
        if (is_null($this->Score) || $this->Score < 0 || $this->Score > 100) {
            $this->Score = $this->getDefaultThreshold();
        }
        $range_field = RecaptchaV3SpamProtector::getRangeCompositeField('Score', $this->Score);

        if (TokenResponse::isEmptyAction($this->Action)) {
            $this->Action = RecaptchaV3Field::getDefaultAction();
        }

        $fields->findOrMakeTab(
            "Root.FormSpam",
            _t(
                'NSWDPC\SpamProtection.RECAPTCHA_TAB_NAME',
                'Form spam'
            )
        );

        $fields->addFieldsToTab(
            "Root.FormSpam",
            [
                DropdownField::create(
                    'RuleID',
                    _t('NSWDPC\SpamProtection.RECAPTCHA_RULE_SELECT_TITLE', 'Select an existing captcha rule'),
                    RecaptchaV3Rule::getEnabledRules()->map('ID', 'TagDetailed')
                )->setDescription(
                    _t(
                        'NSWDPC\SpamProtection.RECAPTCHA_RULE_SELECT_DESCRIPTION',
                        'This will take precedence over the threshold and custom action, if provided below'
                    )
                )->setEmptyString(''),
                $range_field,
                RecaptchaV3SpamProtector::getActionField('Action', $this->Action),
                NumericField::create(
                    'MinRefreshTime',
                    _t(
                        'NSWDPC\SpamProtection.MINIMUM_REFRESH_TIME',
                        'Minimum refresh time before a new captcha token is requested during form completion'
                    )
                )->setHtml5(true)
                ->setAttribute('min', 0)
                ->setDescription(
                    _t(
                        'NSWDPC\SpamProtection.UNIT_SECONDS',
                        'In seconds'
                    )
                ),
                CheckboxField::create(
                    'IncludeInEmails',
                    _t('NSWDPC\SpamProtection.INCLUDE_IN_EMAILS', 'Include form spam verification information in emails')
                )
            ]
        );
        return $fields;
    }

    /**
     * Return the Rule, if enabled, or NULL if not
     */
    public function getEnabledRule() : ?RecaptchaV3Rule
    {
        $rule = $this->Rule;
        if ($rule && $rule->exists() && $rule->Enabled) {
            return $rule;
        }
        return null;
    }

    /**
     * Return the threshold score from either the Rule or the field here
     * @return int
     */
    public function getFieldScore() : int
    {
        $score = null;
        if ($this->exists()) {
            $score = $this->Score;
        }
        return is_int($score) ? $score : $this->getDefaultThreshold();
    }

    /**
     * Return the action configured for this field, or the default action
     * @return string
     */
    public function getFieldAction() : string
    {
        $action = '';
        if ($this->exists()) {
            $action = $this->Action;
        }
        if(TokenResponse::isEmptyAction($action)) {
            $action = RecaptchaV3Field::getDefaultAction();
        }
        return $action;
    }

    /**
     * Return the form field with configured score and action
     * @return RecaptchaV3Field
     */
    public function getFormField()
    {

        // rule for this field. If set, overrides Score/Action set
        $rule = $this->getEnabledRule();
        $field_template = EditableRecaptchaV3Field::class;
        $field_holder_template = EditableRecaptchaV3Field::class . '_holder';
        // the score used as a threshold
        $score = $this->getFieldScore();
        $score = round(($score / 100), 2);
        // the action
        $action = $this->getFieldAction();
        $field = RecaptchaV3Field::create($this->Name, $this->Title)
            ->setScore($score) // format for the reCAPTCHA API 0.00->1.00
            ->setExecuteAction($action, true)
            ->setFieldHolderTemplate($field_holder_template)
            ->setTemplate($field_template);
        if ($rule) {
            $field = $field->setRecaptchaV3RuleTag($rule->Tag);
        }
        if($this->MinRefreshTime > 0) {
            $field = $field->setMinRefreshTime($this->MinRefreshTime * 1000);
        }
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
