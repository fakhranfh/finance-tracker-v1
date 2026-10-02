---
paths:
  - 'app/Http/Requests/**'
  - 'app/Providers/AppServiceProvider.php'
---

# Password Validation

## Always use strong passwords
Every rule that validates a new password (register, reset, store/update user, change password) must use `Password::defaults()`. Never hardcode `min:8` or a custom `Password::min()` chain in a FormRequest.

The strong policy is defined once in `AppServiceProvider::boot()`: min 8 chars, letters, mixed case, numbers, and symbols. Change the policy there only.

Login is the exception: only validate `required|string`, so existing users are never locked out by policy changes.
