# Chamilo ONLYOFFICE integration plugin

This app enables users to edit office documents from [Chamilo](https://chamilo.org) using ONLYOFFICE Docs packaged as Document Server - [Community or Enterprise Edition](#onlyoffice-docs-editions).

The app is compatible with Chamilo v1.11.16 or newer. 

## Features

The plugin allows teachers to:

* Create and edit text documents, spreadsheets, and presentations.
* Co-edit documents in real-time: use two co-editing modes (Fast and Strict), Track Changes, comments, and built-in chat.

Supported formats:

* For editing: DOCX, XLSX, PPTX, DOCXF, OFORM.

## Installing ONLYOFFICE Docs

You will need an instance of ONLYOFFICE Docs (Document Server) that is resolvable and connectable both from Chamilo and any end clients. ONLYOFFICE Document Server must also be able to POST to Chamilo directly.

ONLYOFFICE Document Server and Chamilo can be installed either on different computers, or on the same machine. If you use one machine, set up a custom port for Document Server as by default both ONLYOFFICE Document Server and Chamilo work on port 80.

You can install the free Community version of ONLYOFFICE Docs or scalable Enterprise Edition with pro features.

To install the free Community version, use [Docker](https://github.com/onlyoffice/Docker-DocumentServer) (recommended) or follow [these instructions](https://helpcenter.onlyoffice.com/installation/docs-community-install-ubuntu.aspx) for Debian, Ubuntu, or derivatives.  

To install the Enterprise Edition, follow instructions [here](https://helpcenter.onlyoffice.com/installation/docs-enterprise-index.aspx).

The Community Edition vs Enterprise Edition comparison can be found [here](#onlyoffice-docs-editions).

To use ONLYOFFICE behind a proxy, please refer to [this article](https://helpcenter.onlyoffice.com/installation/docs-community-proxy.aspx).

## Collect Chamilo ONLYOFFICE integration plugin

1. Get the latest version of this repository running the command:
    ```
    git clone https://github.com/ONLYOFFICE/onlyoffice-chamilo
    cd onlyoffice-chamilo
    ```

2. Get a submodule:
    ```
    git submodule update --init --recursive
    ```

3. Collect all files
    ```
    mkdir /tmp/onlyoffice-deploy
    mkdir /tmp/onlyoffice-deploy/onlyoffice
    cp -r ./ /tmp/onlyoffice-deploy/onlyoffice
    cd /tmp/onlyoffice-deploy/onlyoffice
    rm -rf ./.git*
    ```

4. Archive
    ```
    cd ../
    zip onlyoffice.zip -r onlyoffice
    ```

## Installing Chamilo ONLYOFFICE integration plugin

The plugin has been integrated into Chamilo since version 1.11.16.

To enable, go to the plugins list, select the ONLYOFFICE plugin, and click _Enable_ the selected plugins.

If you want more up-to-date versions of the plugin, you need to replace the pre-installed default plugin folder with the newly collected plugin: 

`/var/www/html/chamilo-1.11.16/plugin/onlyoffice`

where `chamilo-1.11.16` is your current Chamilo version.

If your Chamilo version is lower than 1.11.16, go to Chamilo Administration -> Plugins -> Upload plugin.

Upload `onlyoffice.zip` (you'll find it in the Releases section). You'll see the plugin list.

Then launch `composer install` from the Chamilo root folder. 

Return to the plugin list, select the ONLYOFFICE plugin, and click Enable the selected plugins.

## Configuring Chamilo ONLYOFFICE integration plugin

On the Plugins page, find ONLYOFFICE and click _Configure_. You'll see the _Settings_ page. Enable the plugin and specify the _Document Server address_. 

## How it works

* To create a new file, the teacher opens the necessary folder and clicks the ONLYOFFICE icon "Create new".
* The user is redirected to the file creation page where they need to enter the file name and format (text document, spreadsheet, or presentation). The browser calls `/plugin/onlyoffice/create.php` method. It adds the copy of the empty file to the course folder.
* To open an existing file, the user chooses the _Open with ONLYOFFICE_ icon.
* The request is being sent to `/plugin/onlyoffice/editor.php?docId=«document identificator»`. The server processes the request, generates the editor initialization configuration with the properties:

  * **url** - the URL that ONLYOFFICE Document Server uses to download the document;
  * **callbackUrl** - the URL at which ONLYOFFICE Document Server informs Chamilo about the status of the document editing;
  * **documentServerUrl** - the URL that the client needs to respond to ONLYOFFICE Document Server (can be set at the administrative settings page);
  * **key** - the etag to instruct ONLYOFFICE Document Server whether to download the document again or not;

* The server returns a page with a script to open the editor.
* The browser opens this page and loads the editor.
* The browser makes a request to Document Server and passes the document configuration to it.
* Document Server loads the document and the user starts editing. 
* Document Server sends a POST request to **callbackUrl** to inform Chamilo that the user is editing the document.
* When all users have finished editing, they close the editor window.
* After 10 seconds, Document Server makes a POST request to **callbackUrl** with the information that editing has ended and sends a link to the new document version.
* Chamilo loads a new version of the document and overwrites the file.

More information on integration ONLYOFFICE Docs can be found in the [API documentation](https://api.onlyoffice.com/editors/basic). 

## ONLYOFFICE Docs editions

ONLYOFFICE offers different versions of its online document editors that can be deployed on your own servers.

* Community Edition (`onlyoffice-documentserver` package)
* Enterprise Edition (`onlyoffice-documentserver-ee` package)

The table below will help you to make the right choice.

| Pricing and licensing | Community Edition | Enterprise Edition |
| ------------- | ------------- | ------------- |
| | [Get it now](https://www.onlyoffice.com/download-docs.aspx?utm_source=github&utm_medium=cpc&utm_campaign=GitHubChamilo#docs-community)  | [Start Free Trial](https://www.onlyoffice.com/download-docs.aspx?utm_source=github&utm_medium=cpc&utm_campaign=GitHubChamilo#docs-enterprise)  |
| Cost  | FREE  | [Go to the pricing page](https://www.onlyoffice.com/docs-enterprise-prices.aspx?utm_source=github&utm_medium=cpc&utm_campaign=GitHubChamilo)  |
| Simultaneous connections | up to 20 maximum  | As in chosen pricing plan |
| Number of users | up to 20 recommended | As in chosen pricing plan |
| License | GNU AGPL v.3 | Proprietary |
| **Support** | **Community Edition** | **Enterprise Edition** |
| Documentation | [Help Center](https://helpcenter.onlyoffice.com/installation/docs-community-index.aspx) | [Help Center](https://helpcenter.onlyoffice.com/installation/docs-enterprise-index.aspx) |
| Standard support | [GitHub](https://github.com/ONLYOFFICE/DocumentServer/issues) or paid | One year support included |
| Premium support | [Contact us](mailto:sales@onlyoffice.com) | [Contact us](mailto:sales@onlyoffice.com) |
| **Services** | **Community Edition** | **Enterprise Edition** |
| Conversion Service                | + | + |
| Document Builder Service          | + | + |
| **Interface** | **Community Edition** | **Enterprise Edition** |
| Tabbed interface                       | + | + |
| Dark theme                             | + | + |
| 125%, 150%, 175%, 200% scaling         | + | + |
| White Label                            | - | - |
| Integrated test example (node.js)      | + | + |
| Mobile web editors                     | - | +* |
| **Plugins & Macros** | **Community Edition** | **Enterprise Edition** |
| Plugins                           | + | + |
| Macros                            | + | + |
| **Collaborative capabilities** | **Community Edition** | **Enterprise Edition** |
| Two co-editing modes              | + | + |
| Comments                          | + | + |
| Built-in chat                     | + | + |
| Review and tracking changes       | + | + |
| Display modes of tracking changes | + | + |
| Version history                   | + | + |
| **Document Editor features** | **Community Edition** | **Enterprise Edition** |
| Font and paragraph formatting   | + | + |
| Object insertion                | + | + |
| Adding Content control          | + | + | 
| Editing Content control         | + | + | 
| Layout tools                    | + | + |
| Table of contents               | + | + |
| Navigation panel                | + | + |
| Mail Merge                      | + | + |
| Comparing Documents             | + | + |
| **Spreadsheet Editor features** | **Community Edition** | **Enterprise Edition** |
| Font and paragraph formatting   | + | + |
| Object insertion                | + | + |
| Functions, formulas, equations  | + | + |
| Table templates                 | + | + |
| Pivot tables                    | + | + |
| Data validation           | + | + |
| Conditional formatting          | + | + |
| Sparklines                   | + | + |
| Sheet Views                     | + | + |
| **Presentation Editor features** | **Community Edition** | **Enterprise Edition** |
| Font and paragraph formatting   | + | + |
| Object insertion                | + | + |
| Transitions                     | + | + |
| Presenter mode                  | + | + |
| Notes                           | + | + |
| **Form creator features** | **Community Edition** | **Enterprise Edition** |
| Adding form fields           | + | + |
| Form preview                    | + | + |
| Saving as PDF                   | + | + |
| | [Get it now](https://www.onlyoffice.com/download-docs.aspx?utm_source=github&utm_medium=cpc&utm_campaign=GitHubChamilo#docs-community)  | [Start Free Trial](https://www.onlyoffice.com/download-docs.aspx?utm_source=github&utm_medium=cpc&utm_campaign=GitHubChamilo#docs-enterprise)  |

\* If supported by DMS.

## Note on SSL

As for all SSL to non-SSL communication, this plugin will not work fully if your
Chamilo portal works in HTTP and your OnlyOffice Document server works in HTTPS, or vice-versa.
You will need to ensure the same protocol on both sides. 