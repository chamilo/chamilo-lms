# H5P Import Plugin for Chamilo

## Overview
This plugin enables the loading and display of H5P packages within the Chamilo Learning Management System (LMS). With this plugin, users can import and view interactive H5P content seamlessly within Chamilo courses.

Please note that this plugin is currently in beta phase and may have some limitations or bugs. Feedback and bug reports are welcome to help improve the plugin.

## Requirements
- The 'h5p/h5p-php-library' library is required. It is already included in the composer.json file, so no additional steps are needed to install it.

## Installation
1. Update the Chamilo library and namespace by running `composer update` within the Chamilo root directory.
2. Add the plugin to your Chamilo installation's `main/admin/settings.php?category=Plugins` url.
3. If the namespace has not been updated, run`composer dump-autoload`.

## Configuration
1. Activate the plugin in the Chamilo administration panel.
2. Configure the plugin options. The only required option is to enable the plugin. The remaining options are optional and can be customized based on your needs.

## Usage
1. Once the plugin is activated and configured, users will be able to import and display H5P packages within Chamilo courses.
2. Inside a course, select the H5P Import tool to add H5P content to the course.
3. Users can interact with the H5P content directly within the course interface.

## Support and Feedback
For any issues, questions, or feedback, please create an issue on the Chamilo-lms's GitHub repository.