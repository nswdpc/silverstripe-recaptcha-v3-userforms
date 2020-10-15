# Documentation


## For CMS editors

You can add two options to the reCAPTCHAv3 field when creating a form in the CMS:

### Score

This is a value between 0 and 1. reCAPTCHA returns score between 0 and 1 based on visitor interaction with the website.

If the score returned is **above** the score you add to the field, the submission will not be allowed.

The default value is 0.7, meaning any submission with a score above 0.7 will be treated as spam.


### Action

This is a value used for analytics purposes in the [reCAPTCHA admin](https://www.google.com/recaptcha/admin/).

The default value is "submit". When the form is rendered, the action will be attached the field ID to ensure uniqueness.
