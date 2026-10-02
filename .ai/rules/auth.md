---
paths:
  - 'app/Http/Controllers/Auth/**'
---

# Auth

## Detect timezone on login from IP address
On every user login, detect and set the user's timezone based on their IP address using a geolocation library. Save the detected timezone to the database in the `timezone` column of the users table for application-wide use.
