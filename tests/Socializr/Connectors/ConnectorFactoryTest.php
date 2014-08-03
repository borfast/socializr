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
            'providers' => array(
                'Twitter' => array(
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb'
                ),
                'Facebook' => array(
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb',
                    'scopes' => 'email, publish_stream, manage_pages, publish_actions',
                ),
                'Linkedin' => array(
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb',
                    'scopes' => 'r_fullprofile, r_emailaddress, rw_nus, rw_company_admin, r_network, rw_groups',
                    'csrf_token_name' => 'state',
                ),
                'Google' => array(
                    'consumer_key' => 'aaa',
                    'consumer_secret' => 'bbb',
                    'public_api_key' => 'ccc', // Google-specific
                ),
            )
        ];
    }


    public function tearDown()
    {
        m::close();
    }


    public function testCreateFacebookConnectorReturnsCorrectClass()
    {
        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('Facebook', $mock_storage);

        $this->assertInstanceOf("\\Borfast\\Socializr\\Connectors\\Facebook", $connector);
    }


    public function testCreateInvalidConnectorThrowsException()
    {
        $this->setExpectedException("Borfast\\Socializr\\Exceptions\\InvalidProviderException");

        $mock_storage = m::mock("OAuth\\Common\\Storage\\TokenStorageInterface");

        $factory = new ConnectorFactory($this->config);
        $connector = $factory->createConnector('invalid', $mock_storage);
    }
}
