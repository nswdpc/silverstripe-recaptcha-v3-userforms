<?php

namespace NSWDPC\SpamProtection;

use SilverStripe\Dev\BuildTask;
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

    private static string $segment = 'RecaptchaV3IncludeInEmailsTask';

    public function run($request)
    {
        $before = $request->getVar('before');
        $publish = $request->getVar('publish') == '1';
        $commit = $request->getVar('commit') == '1';

        if (!$commit) {
            DB::alteration_message("Pass commit=1 to make changes", "info");
        }

        if (!$publish) {
            DB::alteration_message("Pass publish=1 to publish changes", "info");
        }

        if (!$before) {
            DB::alteration_message("You must provide a date/time as the 'before' param, to update all fields before that date/time. The value is anything understood by DateTime", "error");
            return;
        }

        try {
            $dt = new \DateTime($before);
            $beforeFormatted = $dt->format('Y-m-d');
        } catch (\Exception) {
            DB::alteration_message("Could not understand the before value '{$before}'", "error");
            return;
        }

        $fields = EditableRecaptchaV3Field::get()->filter([
            'Created:LessThan' => $beforeFormatted
        ]);

        if ($fields->count() == 0) {
            DB::alteration_message("No fields found to change before {$beforeFormatted}", "noop");
            return;
        }

        foreach ($fields as $field) {
            try {
                if ($commit) {
                    $field->IncludeInEmails = 1;
                    $field->write();
                    DB::alteration_message("Changed field #{$field->ID} '{$field->Title}', created:{$field->Created}", "changed");
                    if ($publish) {
                        $field->publishSingle();
                        DB::alteration_message("Published field #{$field->ID} '{$field->Title}', created:{$field->Created}", "changed");
                    }
                } else {
                    DB::alteration_message("Would have changed field #{$field->ID} '{$field->Title}', created:{$field->Created}", "info");
                }
            } catch (\Exception $e) {
                DB::alteration_message("Failed to change field #{$field->ID} '{$field->Title}', created:{$field->Created}. Error:{$e->getMessage()}", "error");
            }
        }
    }
}
