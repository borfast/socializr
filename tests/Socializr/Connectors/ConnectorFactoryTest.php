<?php

namespace Borfast\Socializr\Tests;

use OAuth\Common\Storage\TokenStorageInterface;
use \Mockery as m;
use Borfast\Socializr\Connectors\ConnectorFactory;

class ConnectorFactoryTest extends \PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->config = [
            'callback' => 'blah',
            'providers' => [
                'Twitter' => [
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb',
                    'service' => 'Twitter',
                ],
                'Facebook' => [
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb',
                    'scopes' => 'email, publish_stream, manage_pages, publish_actions',
                    'service' => 'Facebook',
                ],
                'Linkedin' => [
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb',
                    'scopes' => 'r_fullprofile, r_emailaddress, rw_nus, rw_company_admin, r_network, rw_groups',
                    'csrf_token_name' => 'state',
                    'service' => 'Linkedin',
                ],
                'Google' => [
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb',
                    'public_api_key' => 'ccc', // Google-specific
                    'service' => 'Google',
                ],
            ]
        ];

        $this->config['providers']['FacebookPage'] = $this->config['providers']['Facebook'];
        $this->config['providers']['LinkedinPage'] = $this->config['providers']['Linkedin'];
        $this->config['providers']['LinkedinGroup'] = $this->config['providers']['Linkedin'];

        $this->id = 'foo';
    }


    public function tearDown()
    {
        m::close();
    }


    public function testEmptyArrayConfigThrowsException()
    {
        $this->setExpectedException('Borfast\\Socializr\\Exceptions\\InvalidConfigurationException');

        $factory = new ConnectorFactory([]);
    }


    public function testCreateTwitterConnectorReturnsCorrectClass()
    {
        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('Twitter', $this->id, $mock_storage);

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\Twitter", $connector);
    }


    public function testCreateFacebookConnectorReturnsCorrectClass()
    {
        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('Facebook', $this->id, $mock_storage);

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\Facebook", $connector);
    }


    public function testCreateFacebookPageConnectorReturnsCorrectClass()
    {
        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('FacebookPage', $this->id, $mock_storage);

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\FacebookPage", $connector);
    }


    public function testCreateLinkedinConnectorReturnsCorrectClass()
    {
        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('Linkedin', $this->id, $mock_storage);

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\Linkedin", $connector);
    }


    public function testCreateLinkedinPageConnectorReturnsCorrectClass()
    {
        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('LinkedinPage', $this->id, $mock_storage);

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\LinkedinPage", $connector);
    }


    public function testCreateLinkedinGroupConnectorReturnsCorrectClass()
    {
        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('LinkedinGroup', $this->id, $mock_storage);

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\LinkedinGroup", $connector);
    }


    public function testCreateInvalidConnectorThrowsException()
    {
        $this->setExpectedException("Borfast\\Socializr\\Exceptions\\InvalidProviderException");

        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('invalid', $this->id, $mock_storage);
    }
}
