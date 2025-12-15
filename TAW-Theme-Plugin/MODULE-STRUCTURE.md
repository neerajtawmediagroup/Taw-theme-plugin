# TAW Theme Plugin - Module Structure

## Overview

The plugin has been restructured into a modular architecture that allows for easy addition of new modules. Each module is self-contained and follows a consistent structure.

## Directory Structure (current)

```
TAW-Theme-Plugin/
├── classes/
│   ├── class-taw-admin.php            # Global admin menu + top-level TAW Theme page
│   ├── class-taw-helper.php           # Helper functions
│   ├── class-taw-init.php             # Plugin initialization + module loading
│   ├── class-taw-module-base.php      # Base class for all modules
│   ├── class-taw-module-manager.php   # Module registration and management
│   └── modules/                       # Module directory
│       └── realestate/                # Real Estate module
│           ├── class-taw-module-realestate.php  # Module class
│           ├── module-loader.php      # Module registration with manager
│           └── assets/                # Module assets (admin-side)
│               ├── css/
│               │   └── admin.css
│               └── js/
│                   └── admin.js
├── templates/
│   ├── taw-theme-dashboard.php        # Main TAW Theme dashboard (high-level cards)
│   └── realestate/                    # Real Estate admin pages (custom UI)
│       ├── taw-properties-list.php    # All Listings page
│       ├── taw-property-add.php       # Add New Property page
│       ├── taw-views-list.php         # Manage View (shortcode list) page
│       └── taw-shortcode-add.php      # Create New Shortcode page
├── assets/
│   └── css/
│       └── taw-admin.css              # Shared admin styles (layout, cards, etc.)
├── MODULE-STRUCTURE.md                # This documentation file
├── README.md                          # Plugin README
└── taw-theme.php                      # Main plugin file / entry point
```

## How to Add a New Module

### Step 1: Create Module Directory

Create a new directory under `classes/modules/` with your module name (e.g., `classes/modules/your-module/`).

### Step 2: Create Module Class

Create a class file that extends `TAW_Module_Base`:

```php
<?php
require_once TAW_MODULES_DIR . 'classes/class-taw-module-base.php';

class TAW_Module_YourModule extends TAW_Module_Base {
    
    protected function init() {
        parent::init();
        // Your custom initialization
    }
    
    public function register_post_types() {
        // Register your post types
    }
    
    public function register_shortcodes() {
        // Register your shortcodes
        add_shortcode( 'your_shortcode', array( $this, 'render_shortcode' ) );
    }
    
    public function render_shortcode( $atts, $content = '' ) {
        // Your shortcode output
        return 'Your output';
    }
    
    public function enqueue_admin_assets( $hook ) {
        // Enqueue admin CSS/JS
    }
    
    public function register_admin_menu() {
        // Register admin menu items
    }
}
```

### Step 3: Create Module Loader

Create `module-loader.php` in your module directory:

```php
<?php
require_once __DIR__ . '/class-taw-module-yourmodule.php';

TAW_Module_Manager::register_module(
    'yourmodule',
    'TAW_Module_YourModule',
    __( 'Your Module Name', 'taw-theme' )
);
```

### Step 4: Load Module

Add the module loader to `class-taw-init.php` in the `load_modules()` method:

```php
$your_module = TAW_MODULES_DIR . 'classes/modules/your-module/module-loader.php';
if ( file_exists( $your_module ) ) {
    require_once $your_module;
}
```

## Module Base Class Methods

All modules extend `TAW_Module_Base` which provides:

- `get_module_id()` - Get module ID
- `get_module_name()` - Get module name
- `get_meta( $post_id, $key, $default )` - Get meta value
- `update_meta( $post_id, $key, $value )` - Update meta value

## Required Methods

Each module must implement:

- `render_shortcode( $atts, $content )` - Render shortcode output

## Optional Methods

Modules can override:

- `register_post_types()` - Register custom post types
- `register_shortcodes()` - Register shortcodes
- `enqueue_admin_assets( $hook )` - Enqueue admin assets
- `enqueue_frontend_assets()` - Enqueue frontend assets
- `register_admin_menu()` - Register admin menu items

## Benefits

1. **No Code Duplication** - Shared functionality in base class
2. **Easy to Extend** - Just create a new module directory
3. **Self-Contained** - Each module has its own assets and includes
4. **Consistent Structure** - All modules follow the same pattern
5. **Shortcode-Based** - All modules work with shortcodes for frontend display

## Real Estate Module Example (current)

The Real Estate module demonstrates the full structure and how it plugs into the shared admin UI:

- **Custom post types**
  - `taw_property` (properties)
  - `taw_prop_shortcode` (property views / shortcodes)
- **Custom taxonomy**
  - `taw_property_category` (categories for properties)
- **Meta boxes**
  - Property details (price, size, bedrooms, bathrooms, address, gallery, contact info)
  - Shortcode configuration (selected properties, grid/list layout)
- **Admin pages (under the top-level “TAW Theme” menu)**
  - Dashboard (`templates/taw-theme-dashboard.php`)
  - All Listings (`classes/modules/realestate/templates/admin/taw-properties-list.php`)
  - Add New Property (`classes/modules/realestate/templates/admin/taw-property-add.php`)
  - Manage View / Views List (`classes/modules/realestate/templates/admin/taw-views-list.php`)
  - Create New Shortcode (`classes/modules/realestate/templates/admin/taw-shortcode-add.php`)
- **Frontend**
  - Shortcode rendering for lists, galleries, and individual property fields
- **Styling / UX**
  - Shared admin layout and cards via `assets/css/taw-admin.css`
  - Module-specific styling / JS via `classes/modules/realestate/assets/`

The same pattern can be followed for any new module: define your CPTs and shortcodes in a module class, optionally add custom admin pages in `classes/modules/<module>/templates/admin/`, and use the base class hooks to register menus and assets.

