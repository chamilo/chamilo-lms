**Chamidoc** is a powerful visual e-learning content creation plugin for Chamilo LMS. It provides educators with an intuitive drag-and-drop interface to create interactive educational resources directly within the Chamilo platform, eliminating the need for external authoring tools.

### Key Highlights

- **Visual Editor**: Drag-and-drop interface powered by GrapesJS
- **No Coding Required**: Create professional content without technical skills
- **Seamless Integration**: Native integration with Chamilo LMS
- **Responsive Design**: Content automatically adapts to all devices
- **SCORM Compatible**: Export content as SCORM packages
- **Real-time Preview**: See changes instantly while editing

---

## Features

### Visual Content Editor
- **Drag & Drop Interface**: Intuitive visual editor powered by GrapesJS
- **Pre-built Components**: Library of educational components and blocks
- **Custom Styling**: Advanced style manager with visual controls
- **Responsive Preview**: Test content on desktop, tablet, and mobile views
- **Template System**: Pre-designed templates for quick start

### Educational Components
- **Interactive Elements**: Buttons, forms, multimedia components
- **Text Components**: Headers, paragraphs, lists with rich formatting
- **Media Support**: Images, videos, audio with optimization
- **Layout Tools**: Containers, columns, grids for content organization
- **Navigation Elements**: Breadcrumbs, tabs, accordions

### Learning Path Integration
- **Seamless Publishing**: Direct integration with Chamilo learning paths
- **Progress Tracking**: Monitor student progress through content
- **Assessment Integration**: Connect with Chamilo's quiz system
- **Gradebook Sync**: Automatic grade synchronization

### Content Management
- **Version Control**: Track changes and revert to previous versions
- **Content Library**: Reusable components and templates
- **Asset Management**: Centralized media and resource management
- **Collaboration Tools**: Share projects with other educators

---

## 🛠️ Technical Specifications

### Technology Stack

| Component | Technology | Purpose |
|-----------|------------|---------|
| **Editor Engine** | GrapesJS | Visual page builder |
| **Backend** | PHP 7.1+ | Server-side processing |
| **Database** | MySQL/MariaDB | Content storage |
| **Frontend** | HTML5, CSS3, JavaScript | User interface |
| **Assets** | Webpack | Asset compilation |

### System Requirements

#### Minimum Requirements
- **Chamilo LMS**: Version 1.11.x or higher
- **PHP**: 7.1 or higher
- **Database**: MySQL 5.6+ or MariaDB 10.0+
- **Web Server**: Apache 2.4+ with mod_rewrite
- **Browser**: Modern browsers (Chrome 60+, Firefox 60+, Safari 12+)

#### Recommended Requirements
- **PHP**: 7.4 or 8.0
- **Database**: MariaDB 10.4+
- **Memory**: 4 GB RAM
- **Storage**: 2 GB free space for media files

### Database Schema

The plugin creates two main tables:

#### `plugin_oel_tools_teachdoc`
```sql
CREATE TABLE plugin_oel_tools_teachdoc (
    id INT NOT NULL AUTO_INCREMENT,
    id_user INT,                    -- User who created the content
    title VARCHAR(255) NOT NULL,    -- Content title
    id_parent INT,                  -- Parent content for hierarchy
    order_lst INT,                  -- Display order
    type_node INT,                  -- Node type identifier
    type_base INT,                  -- Base type identifier
    colors VARCHAR(25),             -- Color scheme
    quizztheme VARCHAR(25),         -- Quiz theme
    id_url INT,                     -- URL identifier
    lp_id INT,                      -- Learning path ID
    behavior TINYINT DEFAULT 0,     -- Behavior settings
    leveldoc TINYINT DEFAULT 0,     -- Document level
    local_folder VARCHAR(60),       -- Local folder path
    date_create VARCHAR(12),        -- Creation date
    base_html LONGTEXT,                 -- HTML content
    base_css LONGTEXT,                  -- CSS styles
    gpscomps LONGTEXT,                  -- GrapesJS components
    gpsstyle LONGTEXT,                  -- GrapesJS styles
    recent_save TINYINT DEFAULT 0,  -- Recent save flag
    options VARCHAR(1080),          -- Additional options
    PRIMARY KEY (id)
);
```

#### `plugin_oel_tools_token`
```sql
CREATE TABLE plugin_oel_tools_token (
    id INT NOT NULL AUTO_INCREMENT,
    id_user INT,                    -- User ID
    token VARCHAR(50),              -- Authentication token
    PRIMARY KEY (id)
);
```

---

## 📦 Installation

### Prerequisites

Before installing Chamidoc, ensure you have:
- Chamilo LMS 1.11.x or higher installed and configured
- Administrative access to the Chamilo installation
- Basic understanding of Chamilo plugin management

### Step-by-Step Installation

#### 1. Download Plugin
```bash
# Navigate to Chamilo plugins directory
cd /path/to/chamilo/public/plugin/

# Clone or extract Chamidoc plugin
# (assuming you have the plugin files)
```

#### 2. Install Plugin in Chamilo
1. **Access Plugin Management**
   - Log in as Chamilo administrator
   - Navigate to **Administration** → **Plugins**

2. **Install Chamidoc**
   - Find "C-Studio Open eLearning Tools" in the plugin list
   - Click **Install**
   - Configure initial settings

#### 3. Enable Plugin
1. **Configure Plugin**
   - Mark the plugin as **Enable**
   - Set the plugin region to `pre_footer` on **Administration** -> **Regions**

2. **Verify Installation**
   - Check that database tables were created
   - Verify plugin appears in course tools

### Configuration Options

#### Basic Configuration
```php
// Configuration in plugin settings
$_configuration['chamidoc_enable'] = true;
$_configuration['chamidoc_region'] = 'pre_footer';
$_configuration['chamidoc_default_theme'] = 'default';
```

#### Advanced Configuration
- **Custom Themes**: Configure custom color schemes
- **Component Library**: Enable/disable specific components
- **SCORM Export**: Configure SCORM packaging options
- **Media Settings**: Set upload limits and allowed file types

---

## 🎯 Usage Guide

### Getting Started

#### 1. Access Chamidoc
1. **From Course Home**
   - Enter any Chamilo course
   - Look for the Chamidoc icon in the course tools
   - Click to launch the editor

2. **From Learning Paths**
   - Create or edit a learning path
   - Add a new learning object
   - Select Chamidoc as the content type

#### 2. Create Your First Content

##### Basic Content Creation
1. **Start New Project**
   - Click "New Content" or "Create"
   - Choose a template or start from scratch
   - Enter content title and description

2. **Use the Visual Editor**
   - Drag components from the left panel
   - Drop them onto the canvas
   - Configure properties in the right panel

3. **Add Educational Elements**
   - **Text**: Add headings, paragraphs, lists
   - **Media**: Insert images, videos, audio
   - **Interactive**: Add buttons, forms, quizzes
   - **Layout**: Organize with containers and grids

#### 3. Content Styling

##### Visual Style Manager
- **Typography**: Font family, size, weight, color
- **Layout**: Margins, padding, positioning
- **Background**: Colors, images, gradients
- **Borders**: Style, width, radius, color
- **Effects**: Shadows, opacity, transforms

##### Responsive Design
- **Desktop View**: Full-screen layout optimization
- **Tablet View**: Medium screen adaptations
- **Mobile View**: Small screen optimizations


## 🔧 Customization

### Theme Development

#### Create Custom Theme
1. **Theme Structure**
   ```
   engine/css/themes/
   ├── custom-theme/
   │   ├── theme.css
   │   ├── variables.css
   │   └── components/
   │       ├── buttons.css
   │       ├── text.css
   │       └── media.css
   ```

#### Custom Commands
```javascript
// Add custom command
editor.Commands.add('save-content', {
    run: function(editor, sender) {
        // Save logic
        const html = editor.getHtml();
        const css = editor.getCss();
        // Send to server
    }
});
```

---

## 🔗 Integration

### Chamilo LMS Integration


## 🧪 Development

### Code Structure

#### Directory Organization
```
plugin/CStudio/
├── 0_dal/                  # Database abstraction layer
├── ajax/                   # AJAX endpoints
├── editor/                 # Visual editor files
│   ├── dist/              # Compiled assets
│   ├── inc/               # PHP includes
│   ├── jscss/             # JavaScript and CSS
│   └── templates/         # HTML templates
├── inc/                    # PHP includes
├── lang/                   # Language files
├── resources/              # Static resources
└── view/                   # View files
```

#### Key Files
- `plugin.php` - Plugin configuration and setup
- `teachdoc_hub.php` - Main plugin class
- `index.php` - Plugin entry point
- `editor/index.php` - Visual editor launcher

## Internationalization

### Language Support

#### Languages

2. **Register Language**
   ```php
   // In plugin.php
   $supported_languages = ['english', 'french', 'spanish','deutch'];
   ```

## 🐛 Troubleshooting

### Common Issues

#### Installation Problems

**visit url plugin/CStudio/controlinstall.php**

## Roadmap

### Versions (Planned)

#### New Features
- **AI-Powered Content**: Automatic content suggestions
- **Advanced Analytics**: Machine learning insights
- **Mobile App**: Native mobile editing
- **Real-time Collaboration**: Multiple users editing simultaneously
- **Advanced Templates**: Industry-specific templates

#### Technical Improvements
- **Modern Framework**: Migration to modern JavaScript framework
- **API Redesign**: RESTful API with better documentation
- **Cloud Integration**: Cloud storage and CDN support
- **Performance**: Significant performance improvements

### Long-term Vision

#### 3-5 Year Goals
- **VR/AR Support**: Immersive content creation
- **Blockchain Integration**: Content verification and certificates
- **Global Marketplace**: Template and component marketplace
- **Enterprise Features**: Advanced enterprise management tools

---

---

## Support & Community

### Getting Help

#### Official Resources
- **Website**: [https://www.chamidoc.com/](https://www.chamidoc.com/)
- **Documentation**: Available in plugin directory
- **Support Forum**: Chamilo community forum
- **GitHub Issues**: Report bugs and request features

#### Commercial Support
- **Professional Services**: Available through certified partners
- **Custom Development**: Tailored solutions for specific needs
- **Training Services**: User and developer training programs
- **SLA Support**: Enterprise support with guaranteed response times

### Community

#### Join the Community
- **User Groups**: Local Chamidoc user groups
- **Online Forums**: Active community discussions
- **Social Media**: Follow updates and news
- **Conferences**: Present at educational technology conferences

#### Share Your Experience
- **Case Studies**: Share success stories
- **Blog Posts**: Write about your experience
- **Presentations**: Present at conferences and meetups
- **Video Tutorials**: Create educational content

---

## License & Legal

### License Information

Chamidoc is released under the **GNU General Public License v3.0** (GPL-3.0), the same license as Chamilo LMS.

```
Copyright (C) 2018 - 2025 Bâtisseurs Numériques

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.
```

### Third-party Components

Chamidoc includes various third-party libraries:
- **GrapesJS**: BSD 3-Clause License
- **jQuery**: MIT License
- **Bootstrap**: MIT License
- **Font Awesome**: Font Awesome Free License

---

## Acknowledgments

### Core Team
- **Bâtisseurs Numériques**: Development Team
- **Chamilo Community**: Testing and feedback

### Special Thanks
- **GrapesJS Team**: For the excellent visual editor framework
- **Chamilo Core Team**: For the robust LMS platform
- **Beta Testers**: Educators who tested early versions
- **Contributors**: Community members who contributed code and feedback

---

*This comprehensive documentation is maintained by the Chamidoc development team. For the most up-to-date information, visit [chamidoc.com](https://www.chamidoc.com/).*

---

**Version**: 2.0.0 | **Last Updated**: April 2026 | **License**: GPL-3.0
