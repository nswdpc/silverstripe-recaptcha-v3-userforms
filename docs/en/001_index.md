# Documentation

## For CMS editors

You can either select a rule or add custom settings to the field. A rule is a preset configuration that can be added to a field.

### Rule

Select a rule from the "Select an existing captcha rule" select field. The rule will take precedence over the threshold and custom action set for the field.

### Threshold

This is a value between 0 and 100, in 5 step increments.

reCAPTCHA returns a score between 0 and 1 based on visitor interaction with the website. A score of 1 translates to the '100' option in the drop-down field.

+ If you set the threshold to 100, all submissions on the form will be blocked
+ If you set the threshold to zero, all submissions will be allowed

### Custom action

This is a value used for analytics purposes in the [reCAPTCHA admin](https://www.google.com/recaptcha/admin/). You can use this to track scores per-form.

## Minimum refresh time

The captcha token will update based on user activity in the form every 30 seconds by default. You can set this to a higher or lower number, based on your requirements. Remember that a token has a lifetime of 2 minutes.

## Including the reCAPTCHAv3 verification value in email

Before v0.1.2, the reCAPTCHAv3 verification information for the submitted form was included in all emails:

```json
{"score":0.9,"hostname":"my.site","action":"\/some\/action"}
```

From v0.1.2, this is conditionally **excluded** by default for all emails recipients.

The reCAPTCHAv3 action feature provides the ability to report per-action analytics (Set a custom action).

Use the 'Include reCAPTCHAv3 verification information in emails' checkbox in the field settings to enable the value in emails.

Submitted reCAPTCHAv3 verification values are always saved and can be viewed in the stored submissions, if enabled.

Due to the way the userforms module includes email field data and merge fields, the reCAPTCHAv3 verification information value cannot currently be included on a per-recipient basis.

> You are using > v0.1.2 if you can see the 'Include reCAPTCHAv3 verification information in emails' checkbox

If you wish to retain the verification value in emails for historical fields, ask an administrator to run the 'IncludeInEmailsTask'.
