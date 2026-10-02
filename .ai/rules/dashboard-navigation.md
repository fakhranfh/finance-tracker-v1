---
paths:
  - 'resources/views/components/organisms/sidebar.blade.php'
  - 'resources/views/components/organisms/topbar.blade.php'
  - 'app/View/Components/Organisms/Sidebar.php'
  - 'app/View/Components/Organisms/Topbar.php'
  - 'config/sidebar.php'
  - 'config/topbar.php'
---

# Dashboard Navigation

## Keep sidebar and topbar items in their own config files
Never inline the sidebar's nav-item list or icon set (or the topbar's icon set) directly inside the Blade component. Sidebar items/icons live in `config/sidebar.php`; topbar icons live in `config/topbar.php`. The backing component classes (`app/View/Components/Organisms/Sidebar.php`, `app/View/Components/Organisms/Topbar.php`) read that data via `config('sidebar.*')` / `config('topbar.*')` and expose it as public properties; the Blade views only reference those properties, never the config array or item literals directly. When adding a new sidebar nav entry or topbar icon, add it to the matching config file instead of editing the Blade markup's data.
