# H5P Import Plugin for Chamilo

## Overview
This plugin enables the loading and display of H5P packages within the Chamilo Learning Management System (LMS). 
With this plugin, users can import and view interactive H5P content seamlessly within Chamilo courses (tool shown as an 
H5P icon on the course homepage).

This plugin is currently in beta phase and may have some limitations or bugs. Feedback and bug reports are welcome to help improve the plugin.

Differences with the previous H5P plugin: this plugin allows for the upload of H5P packages in course-specific
environments, avoiding a long list of H5P activities shared between all teachers. It allows for the management of H5P 
activities outside and inside of learning paths and tracks progress inside learning paths as if it was a Chamilo test.

H5P activities cannot be edited at this time, they are all visible by the students (cannot be hidden individually 
inside the tool, but the tool can be hidden and the H5P activities made visible one by one in a learning path).
Activities do not show in the list of new elements "since your last visit".

## Requirements
- The 'h5p/h5p-php-library' library requirement has been added to Chamilo's composer.json, so no additional steps are needed to install it.

## Installation
If you have updated your Chamilo software from an official package, you don't need to do anything except enabling the plugin from the plugins page.

Otherwise (Git update), you will need to:
1. Update the Chamilo library and namespace by running `composer update` within the Chamilo root directory.
2. Add the plugin to your Chamilo installation's `main/admin/settings.php?category=Plugins` url.
3. If the namespace has not been updated, run`composer dump-autoload`.

## Configuration
1. Activate the plugin in the Chamilo administration panel.
2. Configure the plugin options. The only required setting is to enable the plugin. The remaining settings are optional and can be customized based on your needs.

## Usage

Once the plugin is activated and configured, users will see an H5P tool icon in the course and be able to import and display H5P packages within Chamilo courses.
Inside a course, select the H5P Import tool to upload H5P content to the course.
Users can interact with the H5P content directly within the tool or through a learning path.

## Support and Feedback
For any issues, questions, or feedback, please create an issue on the Chamilo-lms's GitHub repository at https://github.com/chamilo/chamilo-lms/.
