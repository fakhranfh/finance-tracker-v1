---
paths:
  - 'app/Models/**'
---

# Models

## Use HasUserTimezone trait for datetime conversion
Every model handling datetime fields must use the `HasUserTimezone` trait to automatically convert datetime values to the authenticated user's timezone stored in the users table `timezone` column. The trait should provide seamless accessor/mutator conversion.
