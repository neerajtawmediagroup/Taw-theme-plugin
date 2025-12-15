# TAW Theme Plugin

A modular WordPress plugin for managing and displaying content through shortcodes. Built with a flexible module architecture that makes it easy to add new functionality.

## Overview

TAW Theme Plugin provides a modular system for creating and managing content modules. Each module is self-contained and works through shortcodes, allowing you to display content anywhere on your WordPress site.

## Features

- **Modular Architecture**: Easy to extend with new modules
- **Shortcode-Based**: All modules work through shortcodes for frontend display
- **Self-Contained Modules**: Each module manages its own assets and functionality
- **Admin UI**: User-friendly admin interface for managing content
- **No Code Duplication**: Shared functionality through base classes

## Plugin Structure

```
TAW-Theme-Plugin/
├── taw-theme.php                      # Main plugin file / entry point
├── classes/
│   ├── class-taw-admin.php            # Global admin menu + top-level TAW Theme page
│   ├── class-taw-init.php             # Plugin initialization + module loading
│   ├── class-taw-helper.php           # Helper functions
│   ├── class-taw-module-base.php      # Base class for all modules
│   ├── class-taw-module-manager.php   # Module registration and management
│   └── modules/
│       └── realestate/                # Real Estate module
│           ├── class-taw-module-realestate.php  # Module class
│           ├── module-loader.php      # Module registration
│           └── assets/
│               ├── css/
│               │   └── admin.css      # Real Estate admin styles
│               └── js/
│                   └── admin.js       # Real Estate admin JavaScript
├── templates/
│   ├── taw-theme-dashboard.php        # Main TAW Theme dashboard
│   └── realestate/                    # Real Estate admin views
│       ├── taw-properties-list.php    # All Listings page
│       ├── taw-property-add.php       # Add New Property page
│       ├── taw-views-list.php         # Manage View (shortcode list)
│       └── taw-shortcode-add.php      # Create New Shortcode page
├── assets/
│   └── css/
│       └── taw-admin.css              # Shared admin layout and styling
├── MODULE-STRUCTURE.md                # Detailed module structure documentation
└── README.md                          # This file
```

## Installation

1. Upload the plugin files to `/wp-content/plugins/taw-theme-plugin/`
2. Activate the plugin through the 'Plugins' menu in WordPress
3. Navigate to 'Real Estate' in the WordPress admin menu

## Core Components

### Main Plugin File

#### `taw-theme.php`
**Purpose**: Main plugin entry point that initializes the plugin and defines constants.

**Key Responsibilities**:
- Defines plugin constants (`TAW_MODULES_DIR`, `TAW_MODULES_URL`, `TAW_MODULES_VERSION`)
- Initializes the main `TAW_Theme` class
- Ensures proper plugin structure

**Constants Defined**:
- `TAW_MODULES_DIR`: Absolute path to the plugin directory
- `TAW_MODULES_URL`: URL to the plugin directory
- `TAW_MODULES_VERSION`: Current plugin version (1.0.0)

---

### Core Classes

#### `classes/class-taw-admin.php`
**Purpose**: Provides the top-level **TAW Theme** admin menu and routes to all custom admin pages.

**Key Responsibilities**:
- Registers the main `TAW Theme` top-level menu in the WordPress admin sidebar.
- Registers internal admin pages:
  - Dashboard
  - All Listings (Real Estate)
  - Add New Property
  - Manage View (shortcode list)
  - Add New Shortcode
- Hides individual sub‑menu entries so only the top‑level `TAW Theme` item is visible.
- Enqueues shared admin CSS on all TAW plugin pages.

---

#### `classes/class-taw-init.php`
**Purpose**: Handles plugin initialization and module loading.

**Key Methods**:
- `__construct()`: Initializes the plugin.
- `includes()`: Loads helper, admin and module classes; initializes the module manager.
- `load_modules()`: Dynamically loads all registered modules (e.g. Real Estate).

**Flow**:
1. Loads helper and core classes.
2. Instantiates `TAW_Admin`.
3. Instantiates `TAW_Module_Manager`.
4. Calls `load_modules()` → requires each module loader.

---

#### `classes/class-taw-module-base.php`
**Purpose**: Base class that all modules extend.

**Key Methods**:
- `get_module_id()`: Get module ID
- `get_module_name()`: Get module name
- `get_meta()`: Get meta value with default
- `update_meta()`: Update meta value
- `register_post_types()`: Override to register custom post types
- `register_shortcodes()`: Override to register shortcodes
- `enqueue_admin_assets()`: Override to enqueue admin assets
- `enqueue_frontend_assets()`: Override to enqueue frontend assets
- `register_admin_menu()`: Override to register admin menu items
- `render_shortcode()`: **Required** - Render shortcode output

---

#### `classes/class-taw-module-manager.php`
**Purpose**: Manages registration and initialization of all modules.

**Key Methods**:
- `register_module()`: Register a new module
- `get_modules()`: Get all registered modules
- `get_module()`: Get a specific module instance
- `load_modules()`: Load all registered modules
- `register_module_menus()`: Register admin menus for all modules

---

#### `classes/class-taw-helper.php`
**Purpose**: Contains helper functions used throughout the plugin.

**Key Functions**:

1. **`taw__($text, $domain)`**
   - Wrapper for WordPress `__()` function
   - Provides linter compatibility
   - Returns translated text

2. **`taw_get_registered_post_types()`**
   - Returns array of post types for use in settings
   - Filters out unwanted post types (pages, attachments, etc.)
   - Only includes 'post' and custom post types
   - Sorts with 'post' first, then custom post types alphabetically

---

## Modules

### Real Estate Module

**Purpose**: Complete real estate property management system with shortcode support.

#### Features

- **Property Management**:
  - Custom post type for properties
  - Property categories taxonomy
  - Detailed property fields (type, price, area, bedrooms, bathrooms, address)
  - Gallery image support
  - Contact information fields

- **Shortcode System**:
  - Default shortcode: `[taw_property_list]` - Lists all properties
  - Custom shortcodes: Create custom shortcodes with selected properties
  - Shortcode management interface

- **Admin Interface**:
  - Property listing page
  - Add/Edit property page with tabbed interface
  - Shortcode management page
  - Custom menu structure

#### Usage

**Default Shortcode**:
```
[taw_property_list category="apartments" posts_per_page="6"]
```

**Custom Shortcodes**:
1. Go to Real Estate → Manage View
2. Create a new shortcode
3. Enter a name and select properties
4. Copy the generated shortcode (e.g., `[taw_property_shortcode_123]`)
5. Paste it anywhere on your site

#### File Structure

```
classes/modules/realestate/
├── class-taw-module-realestate.php   # Main module class
├── module-loader.php                 # Module registration
├── assets/
│   ├── css/
│   │   └── admin.css                 # Real Estate admin styles
│   └── js/
│       └── admin.js                  # Real Estate admin JavaScript
└── templates/
    ├── views/                        # Frontend view templates (grid/list, etc.)
    ├── shared/                       # Shared partials between views
    └── admin/                        # Admin UI templates for this module
        ├── taw-properties-list.php   # All Listings (summary + filters)
        ├── taw-property-add.php      # Custom Add New Property form
        ├── taw-views-list.php        # Manage View (shortcode list UI)
        └── taw-shortcode-add.php     # Create New Shortcode UI

templates/
└── taw-theme-dashboard.php           # Top-level TAW Theme dashboard
```

---

## Plugin Flow

### Initialization Flow

``+
1. WordPress loads taw-theme.php
   ↓
2. Plugin defines constants (DIR, URL, VERSION)
   ↓
3. TAW_Theme class is instantiated
   ↓
4. Loads class-taw-init.php
   ↓
5. TAW_Init class is instantiated
   ↓
6. Calls includes() → loads helper, admin, and module system classes
   ↓
7. Instantiates TAW_Admin (registers TAW Theme menu and admin pages)
   ↓
8. Initializes TAW_Module_Manager
   ↓
9. Calls load_modules() → loads module loaders
   ↓
10. Module loaders register modules with manager
    ↓
11. Module manager instantiates module classes
    ↓
12. Modules register post types, shortcodes, and any extra menus
    ↓
13. Plugin is ready
```

### Shortcode Rendering Flow

```
1. User adds shortcode to page/post (e.g., [taw_property_list])
   ↓
2. WordPress calls registered shortcode handler
   ↓
3. Module's render_shortcode() method is called
   ↓
4. Method builds query arguments
   ↓
5. Executes WP_Query
   ↓
6. Loops through results
   ↓
7. Renders HTML with proper escaping
   ↓
8. Returns output to WordPress
```

---

## Adding New Modules

To add a new module, follow these steps:

### Step 1: Create Module Directory

Create a new directory under `classes/modules/` with your module name:
```
classes/modules/your-module/
```

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
        add_shortcode( 'your_shortcode', array( $this, 'render_shortcode' ) );
    }
    
    public function render_shortcode( $atts, $content = '' ) {
        // Your shortcode output
        return 'Your output';
    }
    
    public function enqueue_admin_assets( $hook ) {
        // Enqueue admin CSS/JS from $this->module_url
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

Add the module loader to `classes/class-taw-init.php` in the `load_modules()` method:

```php
$your_module = TAW_MODULES_DIR . 'classes/modules/your-module/module-loader.php';
if ( file_exists( $your_module ) ) {
    require_once $your_module;
}
```

For detailed documentation, see [MODULE-STRUCTURE.md](MODULE-STRUCTURE.md).

---

## Key Features

### 1. Modular Architecture
- Each module is self-contained in its own directory
- Easy to add new modules by following the structure
- Consistent file organization
- No code duplication through base classes

### 2. Shortcode-Based
- All modules work through shortcodes
- Easy to use anywhere on the site
- Supports attributes for customization
- Dynamic shortcode generation

### 3. Admin Interface
- User-friendly admin UI
- Custom menu structure per module
- Integrated with WordPress admin
- Consistent design patterns

### 4. Security
- All output is properly escaped
- Input validation
- Nonce verification
- Capability checks

### 5. Performance
- Efficient WordPress queries
- Minimal JavaScript
- Optimized CSS
- Lazy loading support

### 6. Developer-Friendly
- Well-commented code
- Consistent naming conventions
- Helper functions for common tasks
- Easy to extend

---

## Requirements

- **WordPress**: 5.0+
- **PHP**: 7.4+
- **MySQL**: 5.6+

---

## Version History

### 1.0.0
- Initial release
- Modular architecture
- Real Estate module
- Shortcode management system
- Admin UI for property management

---

## Support

For issues, questions, or contributions, please contact the development team.

---

## License

This plugin is proprietary software. All rights reserved.
