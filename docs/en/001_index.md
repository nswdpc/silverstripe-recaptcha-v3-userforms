# Documentation

## For CMS editors

You can add two options to the reCAPTCHAv3 field when creating a form in the CMS:

### Threshold

This is a value between 0 and 100, in 5 step increments.

reCAPTCHA returns a score between 0 and 1 based on visitor interaction with the website. A score of 1 translates to the '100' option in the drop-down field.

+ If you set the threshold to 100, all submissions on the form will be blocked
+ If you set the threshold to zero, all submissions will be allowed

### Action

This is a value used for analytics purposes in the [reCAPTCHA admin](https://www.google.com/recaptcha/admin/).
