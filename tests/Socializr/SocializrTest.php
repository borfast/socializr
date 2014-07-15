<?php

namespace Borfast\Socializr\Tests;

use OAuth\Common\Storage\TokenStorageInterface;
use \Mockery;
use Borfast\Socializr\Socializr;

class SocializrTest extends \PHPUnit_Framework_TestCase
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
        Mockery::close();
    }


    public function testGettingFacebookConnectorReturnsCorrectClass()
    {
        $socializr = new Socializr($this->config);

        $mock_storage = Mockery::mock('OAuth\Common\Storage\TokenStorageInterface');
        $connector = $socializr->getConnector('Facebook', $mock_storage);

        $this->assertTrue($connector instanceof \Borfast\Socializr\Connectors\Facebook);
    }
}
