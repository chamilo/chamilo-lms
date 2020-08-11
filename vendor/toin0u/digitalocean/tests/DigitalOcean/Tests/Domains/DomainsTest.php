<?php

/**
 * This file is part of the DigitalOcean library.
 *
 * (c) Antoine Corcy <contact@sbin.dk>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace DigitalOcean\Tests\Domains;

use DigitalOcean\Tests\TestCase;
use DigitalOcean\Domains\Domains;
use DigitalOcean\Domains\DomainsActions;
use DigitalOcean\Domains\RecordsActions;

/**
 * @author Antoine Corcy <contact@sbin.dk>
 */
class DomainsTest extends TestCase
{
    protected $domainId;
    protected $domainName;
    protected $recordId;
    protected $domains;
    protected $domainsBuildQueryMethod;

    protected function setUp()
    {
        $this->domainId   = 123;
        $this->domainName = 'foo.org';
        $this->recordId   = 456;

        $this->domains = new Domains($this->getMockCredentials(), $this->getMockAdapter($this->never()));
        $this->domainsBuildQueryMethod = new \ReflectionMethod(
            $this->domains, 'buildQuery'
        );
        $this->domainsBuildQueryMethod->setAccessible(true);

        $this->recordsBuildQueryMethod = new \ReflectionMethod(
            $this->domains, 'buildRecordsQuery'
        );
        $this->recordsBuildQueryMethod->setAccessible(true);
    }

    /**
     * @expectedException \RuntimeException
     * @expectedExceptionMEssage Impossible to process this query: https://api.digitalocean.com/domains/?client_id=foo&api_key=bar
     */
    public function testProcessQuery()
    {
        $sshKeys = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns(null));
        $sshKeys->getAll();
    }

    public function testGetAllUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/?client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains)
        );
    }

    public function testGetAllReturnsNoDomains()
    {
        $response = <<<JSON
{"status":"OK","domains":[]}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $domains = $domains->getAll();

        $this->assertTrue(is_object($domains));
        $this->assertEquals('OK', $domains->status);
        $this->assertEmpty($domains->domains);
    }

    public function testGetAll()
    {
        $response = <<<'JSON'
{
  "status": "OK",
  "domains": [
    {
      "id": 100,
      "name": "example.com",
      "ttl": 1800,
      "live_zone_file": "$TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM.\\thostmaster.example.com. (\\n\\t\\t\\t1369261882 ; last update: 2013-05-22 22:31:22 UTC\\n\\t\\t\\t3600 ; refresh\\n\\t\\t\\t900 ; retry\\n\\t\\t\\t1209600 ; expire\\n\\t\\t\\t10800 ; 3 hours ttl\\n\\t\\t\\t)\\n             IN      NS      NS1.DIGITALOCEAN.COM.\\n @\\tIN A\\t8.8.8.8\\n",
      "error": null,
      "zone_file_with_error": null
    }
  ]
}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $domains = $domains->getAll();

        $this->assertTrue(is_object($domains));
        $this->assertEquals('OK', $domains->status);
        $this->assertTrue(is_array($domains->domains));

        $domains = $domains->domains[0];
        $this->assertTrue(is_object($domains));
        $this->assertSame(100, $domains->id);
        $this->assertSame('example.com', $domains->name);
        $this->assertSame(1800, $domains->ttl);
        $this->assertSame('$TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM.\\thostmaster.example.com. (\\n\\t\\t\\t1369261882 ; last update: 2013-05-22 22:31:22 UTC\\n\\t\\t\\t3600 ; refresh\\n\\t\\t\\t900 ; retry\\n\\t\\t\\t1209600 ; expire\\n\\t\\t\\t10800 ; 3 hours ttl\\n\\t\\t\\t)\\n             IN      NS      NS1.DIGITALOCEAN.COM.\\n @\\tIN A\\t8.8.8.8\\n', $domains->live_zone_file);
        $this->assertNull($domains->error);
        $this->assertNull($domains->zone_file_with_error);
    }

    public function testShowUrlWithDomainId()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/123/?client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains, $this->domainId)
        );
    }

    public function testShowUrlWithDomainName()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/foo.org/?client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains, $this->domainName)
        );
    }

    public function testShow()
    {
        $response = <<<'JSON'
{
  "status": "OK",
  "domain": {
    "id": 100,
    "name": "example.com",
    "ttl": 1800,
    "live_zone_file": "$TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM.\\thostmaster.example.com. (\\n\\t\\t\\t1369261882 ; last update: 2013-05-22 22:31:22 UTC\\n\\t\\t\\t3600 ; refresh\\n\\t\\t\\t900 ; retry\\n\\t\\t\\t1209600 ; expire\\n\\t\\t\\t10800 ; 3 hours ttl\\n\\t\\t\\t)\\n             IN      NS      NS1.DIGITALOCEAN.COM.\\n @\\tIN A\\t8.8.8.8\\n",
    "error": null,
    "zone_file_with_error": null
  }
}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $domain  = $domains->show($this->domainId);

        $this->assertTrue(is_object($domain));
        $this->assertEquals('OK', $domain->status);

        $domain = $domain->domain;
        $this->assertTrue(is_object($domain));
        $this->assertSame(100, $domain->id);
        $this->assertSame('example.com', $domain->name);
        $this->assertSame(1800, $domain->ttl);
        $this->assertSame('$TTL\\t600\\n@\\t\\tIN\\tSOA\\tNS1.DIGITALOCEAN.COM.\\thostmaster.example.com. (\\n\\t\\t\\t1369261882 ; last update: 2013-05-22 22:31:22 UTC\\n\\t\\t\\t3600 ; refresh\\n\\t\\t\\t900 ; retry\\n\\t\\t\\t1209600 ; expire\\n\\t\\t\\t10800 ; 3 hours ttl\\n\\t\\t\\t)\\n             IN      NS      NS1.DIGITALOCEAN.COM.\\n @\\tIN A\\t8.8.8.8\\n', $domain->live_zone_file);
        $this->assertNull($domain->error);
        $this->assertNull($domain->zone_file_with_error);
    }

    public function testAddUrl()
    {
        $newDomain = array(
            'name'       => 'bar.org',
            'ip_address' => '127.0.0.1',
        );

        $this->assertEquals(
            'https://api.digitalocean.com/domains/new/?name=bar.org&ip_address=127.0.0.1&client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains, null, DomainsActions::ACTION_ADD, $newDomain)
        );
    }

    public function testAdd()
    {
        $response = <<<JSON
{
  "status": "OK",
  "domain": {
    "id": 101,
    "name": "newdomain.com"
  }
}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $domains = $domains->add(array('name' => 'newdomain.com', 'ip_address' => '127.0.0.1'));

        $this->assertTrue(is_object($domains));
        $this->assertEquals('OK', $domains->status);

        $this->assertTrue(is_object($domains->domain));
        $this->assertSame(101, $domains->domain->id);
        $this->assertSame('newdomain.com', $domains->domain->name);
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the name of the domain.
     */
    public function testAddThrowsNameInvalidArgumentException()
    {
        $this->domains->add(array());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the IP address for the domain's initial A record.
     */
    public function testAddThrowsIpAddressInvalidArgumentException()
    {
        $this->domains->add(array('name' => 'bar.org'));
    }

    public function testDestroyUrlWithDomainId()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/123/destroy/?client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains, $this->domainId, DomainsActions::ACTION_DESTROY)
        );
    }

    public function testDestroyUrlWithDomainName()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/foo.org/destroy/?client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains, $this->domainName, DomainsActions::ACTION_DESTROY)
        );
    }

    public function testDestroy()
    {
        $response = <<<JSON
{"status":"OK"}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $destroy = $domains->destroy($this->domainId);

        $this->assertTrue(is_object($destroy));
        $this->assertEquals('OK', $destroy->status);
    }

    public function testGetRecordsUrlWithDomaineId()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/123/records/?client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains, $this->domainId, DomainsActions::ACTION_RECORDS)
        );
    }

    public function testGetRecordsUrlWithDomaineName()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/foo.org/records/?client_id=foo&api_key=bar',
            $this->domainsBuildQueryMethod->invoke($this->domains, $this->domainName, DomainsActions::ACTION_RECORDS)
        );
    }

    public function testGetRecords()
    {
        $response = <<<JSON
{
  "status": "OK",
  "records": [
    {
      "id": 49,
      "domain_id": "100",
      "record_type": "A",
      "name": "example.com",
      "data": "8.8.8.8",
      "priority": null,
      "port": null,
      "weight": null
    },
    {
      "id": 50,
      "domain_id": "100",
      "record_type": "CNAME",
      "name": "www",
      "data": "@",
      "priority": null,
      "port": null,
      "weight": null
    }
  ]
}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $records = $domains->getRecords($this->domainId);

        $this->assertTrue(is_object($records));
        $this->assertEquals('OK', $records->status);

        $this->assertTrue(is_array($records->records));

        $record1 = $records->records[0];
        $this->assertTrue(is_object($record1));
        $this->assertSame(49, $record1->id);
        $this->assertSame('100', $record1->domain_id);
        $this->assertSame('A', $record1->record_type);
        $this->assertSame('example.com', $record1->name);
        $this->assertSame('8.8.8.8', $record1->data);
        $this->assertNull($record1->priority);
        $this->assertNull($record1->port);
        $this->assertNull($record1->weight);

        $record2 = $records->records[1];
        $this->assertSame(50, $record2->id);
        $this->assertSame('100', $record2->domain_id);
        $this->assertSame('CNAME', $record2->record_type);
        $this->assertSame('www', $record2->name);
        $this->assertSame('@', $record2->data);
        $this->assertNull($record2->priority);
        $this->assertNull($record2->port);
        $this->assertNull($record2->weight);
    }

    public function testNewRecordUrlWithDomaineId()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/123/records/new/?client_id=foo&api_key=bar',
            $this->recordsBuildQueryMethod->invoke($this->domains, $this->domainId, RecordsActions::ACTION_ADD)
        );
    }

    public function testNewRecordUrlWithDomaineName()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/foo.org/records/new/?client_id=foo&api_key=bar',
            $this->recordsBuildQueryMethod->invoke($this->domains, $this->domainName, RecordsActions::ACTION_ADD)
        );
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the record_type.
     */
    public function testNewRecordWithEmptyArray()
    {
        $this->domains->newRecord($this->domainId, array());
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage The record_type can only be A, CNAME, NS, TXT, MX or SRV
     */
    public function testNewRecordWithWrongRecordTypeValueType()
    {
        $this->domains->newRecord($this->domainId, array(
            'record_type' => null,
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the data value of the record.
     */
    public function testNewRecordWithCorrectRecordTypeValue()
    {
        $this->domains->newRecord($this->domainId, array(
            'record_type' => 'A',
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the data value of the record.
     */
    public function testNewRecordWithWrongDataAndWrongDataType()
    {
        $this->domains->newRecord($this->domainId, array(
            'record_type' => 'A',
            'data'        => null
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the name string if the record_type is A, CNAME, TXT or SRV.
     */
    public function testNewRecordTypeAWithoutNameString()
    {
        $this->domains->newRecord($this->domainId, array(
            'record_type' => 'A',
            'data'        => 'data',
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the priority integer if the record_type is SRV or MX.
     */
    public function testNewRecordTypeSRVWithoutPriorityNumber()
    {
        $this->domains->newRecord($this->domainId, array(
            'record_type' => 'SRV',
            'data'        => 'data',
            'name'        => 'foo',
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the port integer if the record_type is SRV.
     */
    public function testNewRecordTypeSRVWithoutPortNumber()
    {
        $this->domains->newRecord($this->domainId, array(
            'record_type' => 'SRV',
            'data'        => 'data',
            'name'        => 'foo',
            'priority'    => 1,
        ));
    }

    /**
     * @expectedException \InvalidArgumentException
     * @expectedExceptionMessage You need to provide the weight integer if the record_type is SRV.
     */
    public function testNewRecordTypeSRVWithoutWeightNumber()
    {
        $this->domains->newRecord($this->domainId, array(
            'record_type' => 'SRV',
            'data'        => 'data',
            'name'        => 'foo',
            'priority'    => 1,
            'port'        => 2,
        ));
    }

    public function testNewRecordTypeA()
    {
        $response = <<<JSON
{"status":"OK","domain_record":{"id": 51,"domain_id":"100","record_type":"A","name":"foo","data":"data","priority":null,"port":null,"weight":null}}
JSON
        ;

        $parameters = array(
            'record_type' => 'A',
            'data'        => 'data',
            'name'        => 'foo',
        );

        $domains   = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $newRecord = $domains->newRecord($this->domainId, $parameters);

        $this->assertTrue(is_object($newRecord));
        $this->assertEquals('OK', $newRecord->status);

        $record = $newRecord->domain_record;
        $this->assertTrue(is_object($record));
        $this->assertSame(51, $record->id);
        $this->assertSame('100', $record->domain_id);
        $this->assertSame('A', $record->record_type);
        $this->assertSame('foo', $record->name);
        $this->assertSame('data', $record->data);
        $this->assertNull($record->priority);
        $this->assertNull($record->port);
        $this->assertNull($record->weight);
    }

    public function testNewRecordTypeCNAME()
    {
        $response = <<<JSON
{"status":"OK","domain_record":{"id": 52,"domain_id":"100","record_type":"CNAME","name":"foo","data":"data","priority":null,"port":null,"weight":null}}
JSON
        ;

        $parameters = array(
            'record_type' => 'CNAME',
            'data'        => 'data',
            'name'        => 'foo',
        );

        $domains   = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $newRecord = $domains->newRecord($this->domainId, $parameters);

        $this->assertTrue(is_object($newRecord));
        $this->assertEquals('OK', $newRecord->status);

        $record = $newRecord->domain_record;
        $this->assertTrue(is_object($record));
        $this->assertSame(52, $record->id);
        $this->assertSame('100', $record->domain_id);
        $this->assertSame('CNAME', $record->record_type);
        $this->assertSame('foo', $record->name);
        $this->assertSame('data', $record->data);
        $this->assertNull($record->priority);
        $this->assertNull($record->port);
        $this->assertNull($record->weight);
    }

    public function testNewRecordTypeNS()
    {
        $response = <<<JSON
{"status":"OK","domain_record":{"id": 53,"domain_id":"100","record_type":"NS","name":"bar","data":"data","priority":null,"port":null,"weight":null}}
JSON
        ;

        $parameters = array(
            'record_type' => 'NS',
            'data'        => 'data',
        );

        $domains   = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $newRecord = $domains->newRecord($this->domainId, $parameters);

        $this->assertTrue(is_object($newRecord));
        $this->assertEquals('OK', $newRecord->status);

        $record = $newRecord->domain_record;
        $this->assertTrue(is_object($record));
        $this->assertSame(53, $record->id);
        $this->assertSame('100', $record->domain_id);
        $this->assertSame('NS', $record->record_type);
        $this->assertSame('bar', $record->name);
        $this->assertSame('data', $record->data);
        $this->assertNull($record->priority);
        $this->assertNull($record->port);
        $this->assertNull($record->weight);
    }

    public function testNewRecordTypeTXT()
    {
        $response = <<<JSON
{"status":"OK","domain_record":{"id": 54,"domain_id":"100","record_type":"TXT","name":"foo","data":"data","priority":null,"port":null,"weight":null}}
JSON
        ;

        $parameters = array(
            'record_type' => 'TXT',
            'data'        => 'data',
            'name'        => 'foo'
        );

        $domains   = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $newRecord = $domains->newRecord($this->domainId, $parameters);

        $this->assertTrue(is_object($newRecord));
        $this->assertEquals('OK', $newRecord->status);

        $record = $newRecord->domain_record;
        $this->assertTrue(is_object($record));
        $this->assertSame(54, $record->id);
        $this->assertSame('100', $record->domain_id);
        $this->assertSame('TXT', $record->record_type);
        $this->assertSame('foo', $record->name);
        $this->assertSame('data', $record->data);
        $this->assertNull($record->priority);
        $this->assertNull($record->port);
        $this->assertNull($record->weight);
    }

    public function testNewRecordTypeMX()
    {
        $response = <<<JSON
{"status":"OK","domain_record":{"id": 55,"domain_id":"100","record_type":"MX","name":"baz","data":"data","priority":1,"port":null,"weight":null}}
JSON
        ;

        $parameters = array(
            'record_type' => 'MX',
            'data'        => 'data',
            'priority'    => 1,
        );

        $domains   = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $newRecord = $domains->newRecord($this->domainId, $parameters);

        $this->assertTrue(is_object($newRecord));
        $this->assertEquals('OK', $newRecord->status);

        $record = $newRecord->domain_record;
        $this->assertTrue(is_object($record));
        $this->assertSame(55, $record->id);
        $this->assertSame('100', $record->domain_id);
        $this->assertSame('MX', $record->record_type);
        $this->assertSame('baz', $record->name);
        $this->assertSame('data', $record->data);
        $this->assertSame(1, $record->priority);
        $this->assertNull($record->port);
        $this->assertNull($record->weight);
    }

    public function testNewRecordTypeSRV()
    {
        $response = <<<JSON
{"status":"OK","domain_record":{"id": 56,"domain_id":"100","record_type":"SRV","name":"foo","data":"data","priority":1,"port":88,"weight":2}}
JSON
        ;

        $parameters = array(
            'record_type' => 'SRV',
            'data'        => 'data',
            'name'        => 'foo',
            'priority'    => 1,
            'port'        => 88,
            'weight'      => 2,
        );

        $domains   = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $newRecord = $domains->newRecord($this->domainId, $parameters);

        $this->assertTrue(is_object($newRecord));
        $this->assertEquals('OK', $newRecord->status);

        $record = $newRecord->domain_record;
        $this->assertTrue(is_object($record));
        $this->assertSame(56, $record->id);
        $this->assertSame('100', $record->domain_id);
        $this->assertSame('SRV', $record->record_type);
        $this->assertSame('foo', $record->name);
        $this->assertSame('data', $record->data);
        $this->assertSame(1, $record->priority);
        $this->assertSame(88, $record->port);
        $this->assertSame(2, $record->weight);
    }

    public function testGetRecordUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/123/records/456/?client_id=foo&api_key=bar',
            $this->recordsBuildQueryMethod->invoke($this->domains, $this->domainId, $this->recordId)
        );
    }

    public function testGetRecord()
    {
        $response = <<<JSON
{"status":"OK","record":{"id":456,"domain_id":"123","record_type":"CNAME","name":"www","data":"@","priority":null,"port":null,"weight":null}}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $record  = $domains->getRecord($this->domainId, $this->recordId);

        $this->assertTrue(is_object($record));
        $this->assertEquals('OK', $record->status);

        $record = $record->record;
        $this->assertTrue(is_object($record));
        $this->assertSame(456, $record->id);
        $this->assertSame('123', $record->domain_id);
        $this->assertSame('CNAME', $record->record_type);
        $this->assertSame('www', $record->name);
        $this->assertSame('@', $record->data);
        $this->assertNull($record->priority);
        $this->assertNull($record->port);
        $this->assertNull($record->weight);
    }

    public function testEditRecordUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/123/records/456/edit/?client_id=foo&api_key=bar',
            $this->recordsBuildQueryMethod->invoke($this->domains, $this->domainId, $this->recordId, RecordsActions::ACTION_EDIT)
        );
    }

    public function testEditRecord()
    {
        $response = <<<JSON
{"status":"OK","record":{"id":50,"domain_id":"100","record_type":"CNAME","name":"www2","data":"@","priority":null,"port":null,"weight":null}}
JSON
        ;

        $parameters = array(
            'record_type' => 'CNAME',
            'data'        => '@',
            'name'        => 'www2'
        );

        $domains      = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $editedRecord = $domains->editRecord($this->domainId, $this->recordId, $parameters);

        $this->assertTrue(is_object($editedRecord));
        $this->assertEquals('OK', $editedRecord->status);

        $record = $editedRecord->record;
        $this->assertTrue(is_object($record));
        $this->assertSame(50, $record->id);
        $this->assertSame('100', $record->domain_id);
        $this->assertSame('CNAME', $record->record_type);
        $this->assertSame('www2', $record->name);
        $this->assertSame('@', $record->data);
        $this->assertNull($record->priority);
        $this->assertNull($record->port);
        $this->assertNull($record->weight);
    }

    // testEditRecord* are close to testNewRecord* because we use the protected method
    // Domains::checkParameters in both Domains::newRecord and Domains::editRecord.

    public function testDestroyRecordUrl()
    {
        $this->assertEquals(
            'https://api.digitalocean.com/domains/123/records/456/destroy/?client_id=foo&api_key=bar',
            $this->recordsBuildQueryMethod->invoke($this->domains, $this->domainId, $this->recordId, RecordsActions::ACTION_DESTROY)
        );
    }

    public function testDestroyRecord()
    {
        $response = <<<JSON
{"status":"OK"}
JSON
        ;

        $domains = new Domains($this->getMockCredentials(), $this->getMockAdapterReturns($response));
        $destroy = $domains->destroyRecord($this->domainId, $this->recordId);

        $this->assertTrue(is_object($destroy));
        $this->assertEquals('OK', $destroy->status);
    }
}
