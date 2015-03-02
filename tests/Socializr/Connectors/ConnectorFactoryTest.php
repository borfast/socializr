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

        $this->mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");
        $this->factory = new ConnectorFactory($this->config);
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
        $connector = $this->factory->createConnector(
            'Twitter',
            $this->mock_storage,
            [],
            $this->id
        );

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\Twitter", $connector);
    }


    public function testCreateFacebookConnectorReturnsCorrectClass()
    {
        $connector = $this->factory->createConnector(
            'Facebook',
            $this->mock_storage,
            [],
            $this->id
        );

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\Facebook", $connector);
    }


    public function testCreateFacebookPageConnectorReturnsCorrectClass()
    {
        $connector = $this->factory->createConnector(
            'FacebookPage',
            $this->mock_storage,
            [],
            $this->id
        );

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\FacebookPage", $connector);
    }


    public function testCreateLinkedinConnectorReturnsCorrectClass()
    {
        $connector = $this->factory->createConnector(
            'Linkedin',
            $this->mock_storage,
            [],
            $this->id
        );

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\Linkedin", $connector);
    }


    public function testCreateLinkedinPageConnectorReturnsCorrectClass()
    {
        $connector = $this->factory->createConnector(
            'LinkedinPage',
            $this->mock_storage,
            [],
            $this->id
        );

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\LinkedinPage", $connector);
    }


    public function testCreateLinkedinGroupConnectorReturnsCorrectClass()
    {
        $connector = $this->factory->createConnector(
            'LinkedinGroup',
            $this->mock_storage,
            [],
            $this->id
        );

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\LinkedinGroup", $connector);
    }


    public function testCreateInvalidConnectorThrowsException()
    {
        $this->setExpectedException("Borfast\\Socializr\\Exceptions\\InvalidProviderException");

        $connector = $this->factory->createConnector(
            'invalid',
            $this->mock_storage,
            [],
            $this->id
        );
    }
}
