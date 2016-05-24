DigitalOcean
============

This PHP 5.3+ library helps you to interact with the [DigitalOcean](https://www.digitalocean.com/)
[API](https://api.digitalocean.com/) via PHP or [CLI](#cli).

[![Build Status](https://secure.travis-ci.org/toin0u/DigitalOcean.png)](http://travis-ci.org/toin0u/DigitalOcean)
[![Coverage Status](https://coveralls.io/repos/toin0u/DigitalOcean/badge.png?branch=master)](https://coveralls.io/r/toin0u/DigitalOcean)
[![Latest Stable Version](https://poser.pugx.org/toin0u/DigitalOcean/v/stable.png)](https://packagist.org/packages/toin0u/DigitalOcean)
[![Total Downloads](https://poser.pugx.org/toin0u/DigitalOcean/downloads.png)](https://packagist.org/packages/toin0u/DigitalOcean)
[![SensioLabsInsight](https://insight.sensiolabs.com/projects/1c38ce22-9430-4b47-9bc4-841226d2149f/mini.png)](https://insight.sensiolabs.com/projects/1c38ce22-9430-4b47-9bc4-841226d2149f)


> DigitalOcean is **built for Developers**, helps to **get things done faster** and to
> **deploy an SSD cloud server** in less than 55 seconds with a **dedicated IP** and **root access**.
> [Read more](https://www.digitalocean.com/features).


Installation
------------

This library can be found on [Packagist](https://packagist.org/packages/toin0u/digitalocean).
The recommended way to install this is through [composer](http://getcomposer.org).

Run these commands to install composer, the library and its dependencies:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar require toin0u/digitalocean:@stable
```

Or edit `composer.json` and add:

```json
{
    "require": {
        "toin0u/digitalocean": "@stable"
    }
}
```

**Protip:** you should browse the
[`toin0u/digitalocean`](https://packagist.org/packages/toin0u/digitalocean)
page to choose a stable version to use, avoid the `@stable` meta constraint.

And install dependencies:

```bash
$ curl -sS https://getcomposer.org/installer | php
$ php composer.phar install
```

Now you can add the autoloader, and you will have access to the library:

```php
<?php

require 'vendor/autoload.php';
```

If you don't use neither **Composer** nor a _ClassLoader_ in your application, just require the provided autoloader:

```php
<?php

require_once 'src/autoload.php';
```


Usage
-----

You need an `HttpAdapter` which is responsible to get data from DigitalOcean's RESTfull API.
You can provide your own adapter by implementing `HttpAdapter\HttpAdapterInterface`.

[Read more about HttpAdapter](https://github.com/toin0u/HttpAdapter)


```php
<?php

require 'vendor/autoload.php';

use DigitalOcean\DigitalOcean;
use DigitalOcean\Credentials;

// Set up your credentials.
$credentials = new Credentials('YOUR_CLIENT_ID', 'YOUR_API_KEY')
// Use the default adapter, CurlHttpAdapter.
$digitalOcean = new DigitalOcean($credentials);
// Or use BuzzHttpAdapter.
$digitalOcean = new DigitalOcean($credentials, new \HttpAdapter\BuzzHttpAdapter());
// Or
$digitalOcean->setAdapter(new \HttpAdapter\BuzzHttpAdapter());
```


API
---

### Droplets ###

```php
// ...
$droplets = $digitalOcean->droplets(); // alias to Droplets class.
try {
    // Returns all active droplets that are currently running in your account.
    $allActive = $droplets->showAllActive();
    printf("%s\n", $allActive->status); // OK
    $firstDroplet = $allActive->droplets[0];
    printf("%s\n", $firstDroplet->id); // 12345
    printf("%s\n", $firstDroplet->name); // foobar
    printf("%s\n", $firstDroplet->image_id); // 56789
    printf("%s\n", $firstDroplet->size_id); // 66
    printf("%s\n", $firstDroplet->region_id); // 2
    printf("%s\n", $firstDroplet->backups_active); // true
    printf("%s\n", $firstDroplet->ip_address); // 127.0.0.1
    printf("%s\n", $firstDroplet->private_ip_address); // null
    printf("%s\n", $firstDroplet->locked); // false
    printf("%s\n", $firstDroplet->status); // active
    printf("%s\n", $firstDroplet->created_at); // 2013-01-01T09:30:00Z

    // Returns full information for a specific droplet.
    printf("%s\n", $droplets->show(12345)->droplet->name); // foobar

    // Creates a new droplet. The argument should be an array with 4 required keys:
    // name, sized_id, image_id and region_id. ssh_key_ids key is optional but if any it should be a string.
    $createDroplet = $droplets->create(array(
        'name'               => 'my_new_droplet',
        'size_id'            => 123,
        'image_id'           => 456,
        'region_id'          => 789,
        'ssh_key_ids'        => '12,34,56', // 3 ssh keys
        'private_networking' => true,
        'backups_enabled'    => true,
    ));
    printf("%s\n", $createDroplet->status); // OK
    printf("%s\n", $createDroplet->droplet->event_id); // 78908

    // Reboots a droplet.
    $rebootDroplet = $droplets->reboot(12345);
    printf("%s, %s\n", $rebootDroplet->status, $rebootDroplet->event_id);

    // Power cycles a droplet.
    $powerCycleDroplet = $droplets->powerCycle(12345);
    printf("%s, %s\n", $powerCycleDroplet->status, $powerCycleDroplet->event_id);

    // Shutdowns a running droplet.
    $shutdownDroplet = $droplets->shutdown(12345);
    printf("%s, %s\n", $shutdownDroplet->status, $shutdownDroplet->event_id);

    // Powerons a powered off droplet.
    $powerOnDroplet = $droplets->powerOn(12345);
    printf("%s, %s\n", $powerOnDroplet->status, $powerOnDroplet->event_id);

    // Poweroffs a running droplet.
    $powerOffDroplet = $droplets->powerOff(12345);
    printf("%s, %s\n", $powerOffDroplet->status, $powerOffDroplet->event_id);

    // Resets the root password for a droplet.
    $resetRootPasswordDroplet = $droplets->resetRootPassword(12345);
    printf("%s, %s\n", $resetRootPasswordDroplet->status, $resetRootPasswordDroplet->event_id);

    // Resizes a specific droplet to a different size. The argument should be an array with size_id key.
    $resizeDroplet = $droplets->resize(12345, array('size_id' => 123));
    printf("%s, %s\n", $resizeDroplet->status, $resizeDroplet->event_id);

    // Takes a snapshot of the running droplet, which can later be restored or used to create a new droplet
    // from the same image. The argument can be an empty array or an array with name key.
    $snapshotDroplet = $droplets->snapshot(12345, array('name' => 'my_snapshot'));
    printf("%s, %s\n", $snapshotDroplet->status, $snapshotDroplet->event_id);

    // Restores a droplet with a previous image or snapshot. The argument should be an array with image_id key.
    $restoreDroplet = $droplets->restore(12345, array('image_id' => 123));
    printf("%s, %s\n", $restoreDroplet->status, $restoreDroplet->event_id);

    // Reinstalls a droplet with a default image. The argument should be an array with image_id key.
    $rebuildDroplet = $droplets->rebuild(12345, array('image_id' => 123));
    printf("%s, %s\n", $rebuildDroplet->status, $rebuildDroplet->event_id);

    // Enables automatic backups which run in the background daily to backup your droplet's data.
    $enableBackupsDroplet = $droplets->enableAutomaticBackups(12345);
    printf("%s, %s\n", $enableBackupsDroplet->status, $enableBackupsDroplet->event_id);

    // Disables automatic backups from running to backup your droplet's data.
    $disableBackupsDroplet = $droplets->disableAutomaticBackups(12345);
    printf("%s, %s\n", $disableBackupsDroplet->status, $disableBackupsDroplet->event_id);

    // Renames a specific droplet to a different name. The argument should be an array with name key.
    $renameDroplet = $droplets->rename(12345, array('name' => 'new_name'));
    printf("%s, %s\n", $renameDroplet->status, $renameDroplet->event_id);

    // Destroys one of your droplets - this is irreversible !
    $destroyDroplet = $droplets->destroy(12345);
    printf("%s, %s\n", $destroyDroplet->status, $destroyDroplet->event_id);
} catch (Exception $e) {
    die($e->getMessage());
}
```

### Regions ###

```php
// ...
$regions = $digitalOcean->regions(); // alias to Regions class.
try {
    // Returns all the available regions within the Digital Ocean cloud.
    $allRegions = $regions->getAll();
    printf("%s\n", $allRegions->status); // OK
    $region1 = $allRegions->regions[0];
    printf("%s, %s\n", $region1->id, $region1->name); // 1, New York 1
    $region2 = $allRegions->regions[1];
    printf("%s, %s\n", $region2->id, $region2->name); // 2, Amsterdam 1
} catch (Exception $e) {
    die($e->getMessage());
}
```

### Images ###

```php
// ...
$images = $digitalOcean->images(); // alias to Images class.
try {
    // Returns all the available images that can be accessed by your client ID. You will have access
    // to all public images by default, and any snapshots or backups that you have created in your own account.
    $allImages = $images->getAll();
    printf("%s\n", $allImages->status); // OK
    $firstImage = $allImages->images[0];
    printf("%s\n", $firstImage->id); // 12345
    printf("%s\n", $firstImage->name); // alec snapshot
    printf("%s\n", $firstImage->distribution); // Ubuntu
    // ...
    $otherImage = $allImages->images[36];
    printf("%s\n", $otherImage->id); // 32399
    printf("%s\n", $otherImage->name); // Fedora 17 x32 Desktop
    printf("%s\n", $otherImage->distribution); // Fedora

    // Returns all your images.
    $myImages = $images->getMyImages();
    printf("%s\n", $myImages->status); // OK
    $firstImage = $myImages->images[0];
    printf("%s\n", $firstImage->id); // 12345
    printf("%s\n", $firstImage->name); // my_image 2013-02-01
    printf("%s\n", $firstImage->distribution); // Ubuntu

    // Returns all global images.
    $globalImages = $images->getGlobal();
    printf("%s\n", $globalImages->status); // OK
    $anImage = $globalImages->images[9];
    printf("%s\n", $anImage->id); // 12573
    printf("%s\n", $anImage->name); // Debian 6.0 x64
    printf("%s\n", $anImage->distribution); // Debian

    // Displays the attributes of an image.
    printf("%s\n", $images->show(12574)->image->distribution); // CentOS

    // Destroys an image. There is no way to restore a deleted image so be careful and ensure
    // your data is properly backed up.
    $destroyImage = $images->destroy(12345);
    printf("%s\n", $destroyImage->status); // OK

    // Transferts a specific image to a specified region id. The argument should be an array with region_id key.
    $transfertImage = $images->transfert(12345, array('region_id' => 67890));
    printf("%s\n", $transfertImage->status); // OK
} catch (Exception $e) {
    die($e->getMessage());
}
```

### SSH Keys ###

```php
// ...
$sshKeys = $digitalOcean->sshKeys(); // alias to SSHKeys class.
try {
    // Returns all the available public SSH keys in your account that can be added to a droplet.
    $allSshKeys = $sshKeys->getAll();
    printf("%s\n", $allSshKeys->status); // OK
    $firstSshKey = $allSshKeys->ssh_keys[0];
    printf("%s\n", $firstSshKey->id); // 10
    printf("%s\n", $firstSshKey->name); // office-imac
    $otherSshKey = $allSshKeys->ssh_keys[1];
    printf("%s\n", $otherSshKey->id); // 11
    printf("%s\n", $otherSshKey->name); // macbook-air

    // Shows a specific public SSH key in your account that can be added to a droplet.
    $sshKey = $sshKeys->show(10);
    printf("%s\n", $sshKey->status); // OK
    printf("%s\n", $sshKey->ssh_key->id); // 10
    printf("%s\n", $sshKey->ssh_key->name); // office-imac
    printf("%s\n", $sshKey->ssh_key->ssh_pub_key); // ssh-dss AHJASDBVY6723bgB...I0Ow== me@office-imac

    // Adds a new public SSH key to your account. The argument should be an array with name and ssh_pub_key keys.
    $addSshKey = $sshKeys->add(array(
        'name'        => 'macbook_pro',
        'ssh_pub_key' => 'ssh-dss AHJASDBVY6723bgB...I0Ow== me@macbook_pro',
    ));
    printf("%s\n", $addSshKey->status); // OK
    printf("%s\n", $addSshKey->ssh_key->id); // 12
    printf("%s\n", $addSshKey->ssh_key->name); // macbook_pro
    printf("%s\n", $addSshKey->ssh_key->ssh_pub_key); // ssh-dss AHJASDBVY6723bgB...I0Ow== me@macbook_pro

    // Edits an existing public SSH key in your account. The argument should be an array with ssh_pub_key key.
    $editSshKey = $sshKeys->edit(12, array(
        'ssh_pub_key' => 'ssh-dss fdSDlfDFdsfRF893...jidfs8Q== me@new_macbook_pro',
    ));
    printf("%s\n", $editSshKey->status); // OK
    printf("%s\n", $editSshKey->ssh_key->id); // 12
    printf("%s\n", $editSshKey->ssh_key->name); // macbook_pro
    printf("%s\n", $editSshKey->ssh_key->ssh_pub_key); // ssh-dss fdSDlfDFdsfRF893...jidfs8Q== me@new_macbook_pro

    // Deletes the SSH key from your account.
    $destroySshKey = $sshKeys->destroy(12);
    printf("%s\n", $destroySshKey->status); // OK
} catch (Exception $e) {
    die($e->getMessage());
}
```

### Sizes ###

```php
// ...
$sizes = $digitalOcean->sizes(); // alias to Sizes class.
try {
    // Returns all the available sizes that can be used to create a droplet.
    $allSizes = $sizes->getAll();
    printf("%s\n", $allSizes->status); // OK
    $size1 = $allSizes->sizes[0];
    printf("%s, %s\n", $size1->id, $size1->name); // 33, 512MB
    // ...
    $size6 = $allSizes->sizes[5];
    printf("%s, %s\n", $size1->id, $size1->name); // 38, 16GB
} catch (Exception $e) {
    die($e->getMessage());
}
```

### Domains ###

```php
// ...
$domains = $digitalOcean->domains(); // alias to Domains class.
try {
    // Returns all of your current domains.
    $allDomains = $domains->getAll();
    printf("%s\n", $allDomains->status); // OK
    $domain1 = $allDomains->domains[0];
    printf(
        "%s, %s, %s, %s, %s, %s\n",
        $domain1->id, $domain1->name, $domain1->ttl, $domain1->live_zone_file, $domain1->error, $domain1->zone_file_with_error
    ); // 100, foo.org, 1800, $TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM...., null, null
    // ...
    $domain5 = $allDomains->domains[4];
    // ...

    // Show a specific domain id or domain name.
    $domain = $domains->show(123);
    printf(
        "%s, %s, %s, %s, %s, %s\n",
        $domain->id, $domain->name, $domain->ttl, $domain->live_zone_file, $domain->error, $domain->zone_file_with_error
    ); // 101, bar.org, 1800, $TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM...., null, null

    // Adds a new domain with an A record for the specified IP address.
    // The argument should be an array with name and ip_address keys.
    $addDomain = $domains->add(array(
        'name'       => 'baz.org',
        'ip_address' => '127.0.0.1',
    ));
    printf("%s\n", $addDomain->status); // OK
    printf("%s\n", $addDomain->domain->id); // 12
    printf("%s\n", $addDomain->domain->name); // macbook_pro

    // Deletes the specified domain id or domaine name.
    $deleteDomaine = $domains->destroy(123);
    printf("%s\n", $deleteDomaine->status); // OK

    // Returns all of your current domain records.
    $records = $domains->getRecords(123);
    printf("%s\n", $records->status); // OK
    $record1 = $records->records[0];
    printf("%s\n", $record1->id); // 33
    printf("%s\n", $record1->domain_id); // 100
    printf("%s\n", $record1->record_type); // A
    printf("%s\n", $record1->name); // foo.org
    printf("%s\n", $record1->data); // 8.8.8.8
    printf("%s\n", $record1->priority); // null
    printf("%s\n", $record1->port); // null
    printf("%s\n", $record1->weight); // null
    $record2 = $records->records[1];
    // ...

    // Adds a new record to a specific domain id or domain name.
    // The argument should be an array with record_type and data keys.
    // - record_type can be only 'A', 'CNAME', 'NS', 'TXT', 'MX' or 'SRV'.
    // - data is a string, the value of the record.
    //
    // Special cases:
    // - name is a required string if the record_type is 'A', 'CNAME', 'TXT' or 'SRV'.
    // - priority is an required integer if the record_type is 'SRV' or 'MX'.
    // - port is an required integer if the record_type is 'SRV'.
    // - weight is an required integer if the record_type is 'SRV'.
    $Addrecord = $domains->newRecord('foo.org', array(
        'record_type' => 'SRV',
        'data'        => 'data',
        'name'        => 'bar',
        'priority'    => 1,
        'port'        => 88,
        'weight'      => 2,
    ));
    printf("%s\n", $Addrecord->status); // OK
    printf("%s\n", $Addrecord->domain_record->id); // 34
    printf("%s\n", $Addrecord->domain_record->domain_id); // 100
    printf("%s\n", $Addrecord->domain_record->record_type); // SRV
    printf("%s\n", $Addrecord->domain_record->name); // bar
    printf("%s\n", $Addrecord->domain_record->data); // data
    printf("%s\n", $Addrecord->domain_record->priority); // 1
    printf("%s\n", $Addrecord->domain_record->port); // 88
    printf("%s\n", $Addrecord->domain_record->weight); // 2

    // Returns the specified domain record.
    $record = $domains->getRecord(123, 456);
    printf("%s\n", $record->status); // OK
    printf("%s\n", $record->record->id); // 456
    printf("%s\n", $record->record->domain_id); // 123
    printf("%s\n", $record->record->record_type); // CNAME
    printf("%s\n", $record->record->name); // bar.org
    printf("%s\n", $record->record->data); // 8.8.8.8
    printf("%s\n", $record->record->priority); // null
    printf("%s\n", $record->record->port); // null
    printf("%s\n", $record->record->weight); // null

    // Edits a record to a specific domain. Look at Domains::newRecord parameters.
    $editRecord = $domains->editRecord(123, 456, array(
        'record_type' => 'SRV',
        'data'        => '127.0.0.1',
        'name'        => 'baz.org',
        'priority'    => 1,
        'port'        => 8888,
        'weight'      => 20,
    ));
    printf("%s\n", $editRecord->status); // OK
    printf("%s\n", $editRecord->record->id); // 456
    printf("%s\n", $editRecord->record->domain_id); // 123
    printf("%s\n", $editRecord->record->record_type); // SRV
    printf("%s\n", $editRecord->record->name); // baz.org
    printf("%s\n", $editRecord->record->data); // 127.0.0.1
    printf("%s\n", $editRecord->record->priority); // 1
    printf("%s\n", $editRecord->record->port); // 8888
    printf("%s\n", $editRecord->record->weight); // 20

    // Deletes the specified domain record.
    $deleteRecord = $domains->destroyRecord(123);
    printf("%s\n", $deleteRecord->status); // OK


} catch (Exception $e) {
    die($e->getMessage());
}
```

### Events ###

```php
// ...
$events = $digitalOcean->events(); // alias to Events class.
try {
    // Returns the event details nr. 123
    $event = $events->show(123);
    printf("%s\n", $event->status); // OK
    printf("%s\n", $event->event->id); // 123
    printf("%s\n", $event->event->action_status); // done
    printf("%s\n", $event->event->droplet_id); // 100824
    printf("%s\n", $event->event->event_type_id); // 1
    printf("%s\n", $event->event->percentage); // 100

} catch (Exception $e) {
    die($e->getMessage());
}
```

### CLI ###

To use the [Command-Line Interface]((http://i.imgur.com/Zhvk5yr.png)), you need to copy and rename the
`credentials.yml.dist` file to `credentials.yml` in your project directory, then add your own Client ID and API key:

```yml
CLIENT_ID:  <YOUR_CLIENT_ID>
API_KEY:    <YOUR_API_KEY>
```

If you want to use another credential file just add the option `--credentials="/path/to/file"` to commands below.

Commands for `Droplets`:

```bash
$ php digitalocean list droplets

$ php digitalocean droplets:show-all-active
+----+---------+----------+---------+-----------+----------------+------------+--------------------+--------+--------+----------------------+
| ID | Name    | Image ID | Size ID | Region ID | Backups Active | IP Address | Private IP Address | Status | Locked | Created At           |
+----+---------+----------+---------+-----------+----------------+------------+--------------------+--------+--------+----------------------+
| 1  | my_drop | 54321    | 66      | 2         | 1              | 127.0.0.1  |                    | active |        | 2013-01-01T09:30:00Z |
+----+---------+----------+---------+-----------+----------------+------------+--------------------+--------+--------+----------------------+

$ php digitalocean droplets:show 12345
+-------+---------+----------+---------+-----------+----------------+---------+-----------+------------+--------------------+--------+--------+----------------------+
| ID    | Name    | Image ID | Size ID | Region ID | Backups Active | Backups | Snapshots | IP Address | Private IP Address | Status | Locked | Created At           |
+-------+---------+----------+---------+-----------+----------------+---------+-----------+------------+--------------------+--------+--------+----------------------+
| 12345 | my_drop | 54321    | 66      | 2         | 1              | 7       | 1         | 127.0.0.1  | 10.10.10.10        | active |        | 2013-01-01T09:30:00Z |
+-------+---------+----------+---------+-----------+----------------+---------+-----------+------------+--------------------+--------+--------+----------------------+

$ php digitalocean droplets:create my_new_droplet 66 1601 2
// Creates a new droplet named "my_new_droplet" with 512MB and CentOS 5.8 x64 in Amsterdam without any SSH keys.

$ php digitalocean droplets:create test_droplet 65 43462 1 "5555,5556"
// Creates a new droplet named "test_droplet" with 8BG and Ubuntu 11.04x32 Desktop in New York with 2 SSH keys.
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6895     |
+--------+----------+

$ php digitalocean droplets:create-interactively // see: http://shelr.tv/records/514c2ba796608075f800005a

$ php digitalocean droplets:reboot 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6896     |
+--------+----------+

$ php digitalocean droplets:power-cycle 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6897     |
+--------+----------+

$ php digitalocean droplets:shutdown 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6898     |
+--------+----------+

$ php digitalocean droplets:power-on 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6899     |
+--------+----------+

$ php digitalocean droplets:power-off 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6900     |
+--------+----------+

$ php digitalocean droplets:reset-root-password 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6901     |
+--------+----------+

$ php digitalocean droplets:resize 12345 62 // resizes to 2GB
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6902     |
+--------+----------+

$ php digitalocean droplets:snapshot 12345 my_new_snapshot // the name is optional
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6903     |
+--------+----------+

$ php digitalocean droplets:restore 12345 46964 // restores to "LAMP on Ubuntu 12.04" image
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6904     |
+--------+----------+

$ php digitalocean droplets:rebuild 12345 1601 // rebuilds to "CentOS 5.8 x64" image
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6905     |
+--------+----------+

$ php digitalocean droplets:enable-automatic-backups 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6906     |
+--------+----------+

$ php digitalocean droplets:disable-automatic-backups 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6907     |
+--------+----------+

$ php digitalocean droplets:rename 12345 new_name
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6908     |
+--------+----------+

$ php digitalocean droplets:destroy 12345
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 6909     |
+--------+----------+
```

Commands for `Images`:

```bash
$ php digitalocean list images

$ php digitalocean images:all
+--------+----------+--------------+
| ID     | Name     | Distribution |
+--------+----------+--------------+
| 13245  | my_image | Ubuntu       |
| 21345  | my_image | Ubuntu       |
+--------+----------+--------------+

$ php digitalocean images:mines
+--------+----------+--------------+
| ID     | Name     | Distribution |
+--------+----------+--------------+
| 13245  | my_image | Ubuntu       |
+--------+----------+--------------+

$ php digitalocean images:global
+--------+-------------------------+--------------+
| ID     | Name                    | Distribution |
+--------+-------------------------+--------------+
| 1601   | CentOS 5.8 x64          | CentOS       |
| 1602   | CentOS 5.8 x32          | CentOS       |
| 43462  | Ubuntu 11.04x32 Desktop | Ubuntu       |
+--------+-------------------------+--------------+

$ php digitalocean images:show 46964
+--------+----------------------+--------------+
| ID     | Name                 | Distribution |
+--------+----------------------+--------------+
| 46964  | LAMP on Ubuntu 12.04 | Ubuntu       |
+--------+----------------------+--------------+

$ php digitalocean images:destroy 12345
+--------+
| Status |
+--------+
| OK     |
+--------+

$ php digitalocean images:transfert 12345 67890
+--------+----------+
| Status | Event ID |
+--------+----------+
| OK     | 32654     |
+--------+----------+
```

Commands for `Regions`:

```bash
$ php digitalocean list regions

$ php digitalocean regions:all
+----+-------------+
| ID | Name        |
+----+-------------+
| 1  | New York 1  |
| 2  | Amsterdam 1 |
+----+-------------+
```

Commands for `Sizes`:

```bash
$ php digitalocean list sizes

$ php digitalocean sizes:all
+----+-------+
| ID | Name  |
+----+-------+
| 1  | 512MB |
| 2  | 1GB   |
| 3  | 2GB   |
| 4  | 4GB   |
| 5  | 8GB   |
| 6  | 16GB  |
| 7  | 21GB  |
| 8  | 48GB  |
| 9  | 64GB  |
| 10 | 96GB  |
+----+-------+
```

Commands for `SSH Keys`:

```bash
$ php digitalocean list ssh-keys

$ php digitalocean ssh-keys:all
+------+----------------+
| ID   | Name           |
+------+----------------+
| 5555 | my_pub_ssh_key |
+------+----------------+

$ php digitalocean ssh-keys:show 5555
+------+----------------+----------------------------------------------------+
| ID   | Name           | Pub Key                                            |
+------+----------------+----------------------------------------------------+
| 5555 | my_pub_ssh_key | ssh-dss                                            |
|      |                | AHJASDBVY6723bgBVhusadkih238723kjLKFnbkjGFklaslkhf |
|      |                | .... ZkjCTuZVy6dcH3ag6JlEfju67euWT5yMnT1I0Ow==     |
|      |                | dev@my_pub_ssh_key                                 |
+------+----------------+----------------------------------------------------+

$ php digitalocean ssh-keys:add my_new_ssh_key "ssh-dss DFDSFSDFC1.......FDSFewf987fdsf= dev@my_new_ssh_key"
+------+----------------+----------------------------------------------------+
| ID   | Name           | Pub Key                                            |
+------+----------------+----------------------------------------------------+
| 5556 | my_new_ssh_key | ssh-dss                                            |
|      |                | DFDSFSDFC1723bgBVhusadkih238723kjLKFnbkjGFklaslkhf |
|      |                | .... ZkjCTuZVy6dcH3ag6JlEfju67FDSFewf987fdsf==     |
|      |                | dev@my_new_ssh_key                                 |
+------+----------------+----------------------------------------------------+

$ php digitalocean ssh-keys:edit 5556 "ssh-dss fdSDlfDFdsfRF893...jidfs8Q== me@new_macbook_pro"
+------+----------------+----------------------------------------------------+
| ID   | Name           | Pub Key                                            |
+------+----------------+----------------------------------------------------+
| 5556 | my_new_ssh_key | ssh-dss                                            |
|      |                | fdSDlfDFdsfRF893Vhusadkih238723kjLKFnbkjGFklaslkhf |
|      |                | .... ZkjCTuZVy6dcH3ag6JlEfju67FDSFewfjidfs8Q==     |
|      |                | me@new_macbook_pro                                 |
+------+----------------+----------------------------------------------------+

$ php digitalocean ssh-keys:destroy 5556
+--------+
| Status |
+--------+
| OK     |
+--------+
```

Commands for `Domains`:

```bash
$ php digitalocean list domains

$ php digitalocean domains:all
+-----+---------+------+----------------+-------+----------------------+
| ID  | Name    | TTL  | Live Zone File | Error | Zone File With Error |
+-----+---------+------+----------------+-------+----------------------+
| 245 | foo.com | 6800 | ...            |       | ...                  |
| 678 | foo.org | 1800 | ...            |       | ...                  |
+-----+---------+------+----------------+-------+----------------------+

$ php digitalocean domains:show 678
+-----+---------+------+----------------+-------+----------------------+
| ID  | Name    | TTL  | Live Zone File | Error | Zone File With Error |
+-----+---------+------+----------------+-------+----------------------+
| 678 | foo.org | 1800 | ...            |       | ...                  |
+-----+---------+------+----------------+-------+----------------------+

$ php digitalocean domains:add bar.org 127.0.0.1
+--------+-----+---------+
| Status | ID  | Name    |
+--------+-----+---------+
| OK     | 679 | bar.org |
+--------+-----+---------+

$ php digitalocean domains:destroy 679
+--------+
| Status |
+--------+
| OK     |
+--------+

$ php digitalocean domains:records:all
+----+-----------+-------+-------------+---------+----------+------+--------+
| ID | Domain ID | Type  | Name        | Data    | Priority | Port | Weight |
+----+-----------+-------+-------------+---------+----------+------+--------+
| 49 | 678       | A     | example.com | 8.8.8.8 |          |      |        |
| 50 | 678       | CNAME | www         | @       |          |      |        |
+----+-----------+-------+-------------+---------+----------+------+--------+

$ php digitalocean domains:records:add 678 SRV @ foo 1 2 3
+--------+----+-----------+------+------+------+----------+------+--------+
| Status | ID | Domain ID | Type | Name | Data | Priority | Port | Weight |
+--------+----+-----------+------+------+------+----------+------+--------+
| OK     | 7  | 678       | SRV  | foo  | @    | 1        | 2    | 3      |
+--------+----+-----------+------+------+------+----------+------+--------+

$ php digitalocean domains:records:show 678 7
+--------+----+-----------+------+------+------+----------+------+--------+
| Status | ID | Domain ID | Type | Name | Data | Priority | Port | Weight |
+--------+----+-----------+------+------+------+----------+------+--------+
| OK     | 7  | 678       | SRV  | foo  | @    | 1        | 2    | 3      |
+--------+----+-----------+------+------+------+----------+------+--------+

$ php digitalocean domains:records:edit 678 7 SRV new_data new_name 5 8888 10
+--------+----+-----------+------+-----------+-------------+----------+------+--------+
| Status | ID | Domain ID | Type | Name      | Data        | Priority | Port | Weight |
+--------+----+-----------+------+-----------+-------------+----------+------+--------+
| OK     | 7  | 678       | SRV  | new_name  | new_data    | 5        | 8888 | 10     |
+--------+----+-----------+------+-----------+-------------+----------+------+--------+

$ php digitalocean domains:records:destroy 678 7
+--------+
| Status |
+--------+
| OK     |
+--------+
```

Commands for `Events`:

```bash
$ php digitalocean events:show 123
+----+--------+------------+---------------+------------+
| ID | Status | Droplet ID | Event Type ID | Percentage |
+----+--------+------------+---------------+------------+
| 1  | done   | 100824     | 1             | 100        |
+----+--------+------------+---------------+------------+
```

Integration with Frameworks
---------------------------
* [Silex](https://github.com/toin0u/DigitalOcean-silex)
* [Laravel](https://github.com/toin0u/DigitalOcean-laravel)
* [Symfony2](https://github.com/toin0u/Toin0uDigitalOceanBundle)
* ...

Unit Tests
----------

To run unit tests, you'll need the `cURL` extension and a set of dependencies, you can install them using Composer:

```bash
$ php composer.phar install --dev
```

Once installed, just launch the following command:

```bash
$ phpunit --coverage-text
```

You'll obtain some skipped unit tests due to the need of API keys.

Rename the `phpunit.xml.dist` file to `phpunit.xml`, then uncomment the following lines and add your own Client ID and
API key:

```xml
<php>
    <!-- <server name="CLIENT_ID" value="YOUR_CLIENT_ID" /> -->
    <!-- <server name="API_KEY" value="YOUR_API_KEY" /> -->
</php>
```


Contributing
------------

Please see [CONTRIBUTING](https://github.com/toin0u/DigitalOcean/blob/master/CONTRIBUTING.md) for details.


Credits
-------

* [Antoine Corcy](https://twitter.com/toin0u)
* [All contributors](https://github.com/toin0u/DigitalOcean/contributors)


Acknowledgments
---------------
* [Symfony Console Component](https://packagist.org/packages/symfony/console)
* [Symfony Yaml Component](https://packagist.org/packages/symfony/yaml)


Changelog
---------

[See the changelog file](https://github.com/toin0u/DigitalOcean/blob/master/CHANGELOG.md)


Support
-------

[Please open an issues in github](https://github.com/toin0u/DigitalOcean/issues)


License
-------

Geotools is released under the MIT License. See the bundled
[LICENSE](https://github.com/toin0u/DigitalOcean/blob/master/LICENSE) file for details.

[![Bitdeli Badge](https://d2weczhvl823v0.cloudfront.net/toin0u/DigitalOcean/trend.png)](https://bitdeli.com/free "Bitdeli Badge")
