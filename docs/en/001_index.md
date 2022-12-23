# Documentation

Audience: CMS editors

You can choose either a Turnstile or reCAPTCHA field when adding spam protection to forms.

## reCAPTCHA v3 

You can add two options to the reCAPTCHAv3 field when creating a form in the CMS:

### Threshold

You can choose a value between 0 and 100, in 5 step increments. If a visitor/bot interacts with a form and receives a high score, reCAPTCHA will consider it a 'good' interaction. A low score will be considered a 'bad' interaction.

+ If you set the threshold to 100, all submissions on the form will be blocked
+ If you set the threshold to 50, submissions on the form getting a score less than this will be blocked
+ If you set the threshold to zero, all submissions will be allowed

### Custom action

This is a value used for analytics purposes in the [reCAPTCHA admin](https://www.google.com/recaptcha/admin/). You can use this to track scores per-form.

Do not use personally identifiable information in this value.


## Turnstile

Turnstile does not support scores, only custom actions.

### Custom action

This is a value used for analytics purposes in a Turnstile dashboard. You can use this to track solve-rates per-form.

Do not use personally identifiable information in this value.


## All fields

### Including the verification value in email

This option is turned off by default, meaning verification results are not included in emails sent to recipients assigned to a form.

Before v0.1.2, the captcha verification information for the submitted form was included in all emails:

```json
{"score":0.9,"hostname":"my.site","action":"\/some\/action"}
```

From v0.1.2, this is conditionally **excluded** by default for all email recipients.

The reCAPTCHAv3 action feature provides the ability to report per-action analytics (Set a custom action).

Use the 'Include captcha result in recipient emails' checkbox in the field settings to enable the value in emails.

Submitted verification values are always saved and can be viewed in the stored submissions, if enabled.

Due to the way the userforms module includes email field data and merge fields, the verification information value cannot currently be included on a per-recipient basis.

> You are using > v0.1.2 if you can see the 'Include captcha result in recipient emails' checkbox

If you wish to retain the verification value in emails for historical fields, ask your developer to run the 'IncludeInEmailsTask'.
