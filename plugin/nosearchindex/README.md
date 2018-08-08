No search index
===

This plugin allows administrators to hide the portal from search engines using the robots.txt file and adding a meta tag "noindex". It modifies the robots.txt file to add 'Disallow: /' and also adds ```<meta name="robots" content="noindex" />``` to the file app/home/header_extra_content.txt that correspond to the content of the variable "Extra content in header"
