<?php

namespace Tests\Wallabag\CoreBundle\SiteConfig;

use PHPUnit\Framework\TestCase;
use Wallabag\CoreBundle\SiteConfig\SiteConfig;

class SiteConfigTest extends TestCase
{
    public function testInitSiteConfig()
    {
        $config = new SiteConfig([]);

        $this->assertInstanceOf(SiteConfig::class, $config);
    }

    public function testUnknownProperty()
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown property: "bad"');

        new SiteConfig(['bad' => true]);
    }

    public function testInitSiteConfigWillFullOptions()
    {
        $config = new SiteConfig([
            'host' => 'example.com',
            'requiresLogin' => true,
            'notLoggedInXpath' => '//all',
            'loginUri' => 'https://example.com/login',
            'usernameField' => 'username',
            'passwordField' => 'password',
            'extraFields' => [
                'action' => 'login',
                'foo' => 'bar',
            ],
            'username' => 'johndoe',
            'password' => 'unkn0wn',
        ]);

        $this->assertInstanceOf(SiteConfig::class, $config);
    }
}
