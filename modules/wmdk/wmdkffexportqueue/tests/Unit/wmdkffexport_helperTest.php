<?php

declare(strict_types=1);

use OxidEsales\Eshop\Core\Registry;
use OxidEsales\TestingLibrary\UnitTestCase;

require_once __DIR__ . '/../../core/wmdkffexport_helper.php';

class wmdkffexport_helperTest extends UnitTestCase
{
    public function testGetChannelListParsesConfiguredChannels(): void
    {
        $config = Registry::getConfig();
        $original = $config->getConfigParam('sWmdkFFGeneralChannelList');
        $config->setConfigParam('sWmdkFFGeneralChannelList', 'demo::1::0, de::2::1, onlycode');

        try {
            $channels = wmdkffexport_helper::getChannelList();
        } finally {
            $config->setConfigParam('sWmdkFFGeneralChannelList', $original);
        }

        $this->assertSame(
            array(
                array('code' => 'demo', 'shop_id' => 1, 'lang_id' => 0),
                array('code' => 'de', 'shop_id' => 2, 'lang_id' => 1),
                array('code' => 'onlycode', 'shop_id' => 0, 'lang_id' => 0),
            ),
            $channels
        );
    }

    public function testGetClientIpUsesForwardedHeader(): void
    {
        $originalServer = $_SERVER ?? array();
        $_SERVER = array(
            'HTTP_X_FORWARDED_FOR' => '203.0.113.5, 70.41.3.18',
        );

        try {
            $clientIp = wmdkffexport_helper::getClientIp();
        } finally {
            $_SERVER = $originalServer;
        }

        $this->assertSame('203.0.113.5', $clientIp);
        $this->assertSame('198.51.100.10', wmdkffexport_helper::getClientIp('198.51.100.10'));
    }
}
