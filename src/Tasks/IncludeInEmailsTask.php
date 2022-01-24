<?php
namespace NSWDPC\SpamProtection;

use Silverstripe\Core\Config\Config;
use SilverStripe\Dev\BuildTask;
use SilverStripe\Core\Injector\Injector;
use SilverStripe\ORM\DB;

/**
 * Update the IncludeInEmails value for historical field values
 *
 * This is a manual migration you can optionally run if you wish to retain
 * Recaptchav3 verification values in emails
 *
 * Provide a before value to update fields created before that datetime eg. 2022-01-01
 *
 * This task will be removed or disabled in future releases
 *
 * @author James
 */
class IncludeInEmailsTask extends BuildTask
{

    /**
     * @inheritdoc
     */
    protected $title = "reCAPTCHAv3 include in emails task for historical fields";

    /**
     * @inheritdoc
     */
    protected $description = 'Retain the reCAPTCHAv3 verification values in emails sent. If you do not need the values in emails, do not run this task.';

    /**
     * @var string
     */
    private static $segment = 'Recaptchav3IncludeInEmailsTask';

    /**
     * @var string
     */
    public function run($request) {

        $before = $request->getVar('before');
        $publish = $request->getVar('publish') == 1;

        if(!$before) {
            DB::alteration_message("You must provide a date/time as the 'before' param, to update all fields before that date/time. The value is anything understood by DateTime", "error");
        }

        try {
            $dt = new \DateTime($before);
            $beforeFormatted = $dt->format('Y-m-d');
        } catch(\Exception $e) {
            DB::alteration_message("Could not understand the before value '{$before}'", "error");\
            return;
        }

        $fields = EditableRecaptchaV3Field::get()->filter([
            'Created:LessThan' => $beforeFormatted
        ]);

        if($fields->count() == 0) {
            DB::alteration_message("No fields found to change before {$beforeFormatted}", "noop");
            return;
        }

        if(!$publish) {
            DB::alteration_message("Provide publish=1 to change the published field as well", "noop");
        }

        foreach($fields as $field) {
            try {
                $field->IncludeInEmails = 1;
                $field->write();
                DB::alteration_message("Changed field #{$field->ID} {$field->Title}", "changed");
                if($publish) {
                    $field->doPublish();
                    DB::alteration_message("Published field #{$field->ID} {$field->Title}", "changed");
                }
            } catch (\Exception $e) {
                DB::alteration_message("Failed to change field #{$field->ID} {$field->Title}. Error:{$e->getMessage()}", "error");
            }
        }
    }

}
