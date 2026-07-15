<?php

namespace Druidvav\PageMetadataBundle\Tests;

use Druidvav\PageMetadataBundle\DependencyInjection\DvPageMetadataConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class DvPageMetadataConfigurationTest extends TestCase
{
    public function testStructuredDataDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new DvPageMetadataConfiguration(), [ [ ] ]);

        self::assertSame([
            'enabled' => true,
            'breadcrumbs' => true,
            'nodes' => [ ],
        ], $config['structured_data']);
    }

    public function testItPreservesConfiguredNodes(): void
    {
        $nodes = [
            'organization' => [
                '@type' => 'Organization',
                'name' => 'AirQuality.am',
                'sameAs' => [ 'https://t.me/armeniaAirQuality' ],
            ],
        ];

        $config = (new Processor())->processConfiguration(new DvPageMetadataConfiguration(), [
            [ 'structured_data' => [ 'nodes' => $nodes ] ],
        ]);

        self::assertSame($nodes, $config['structured_data']['nodes']);
    }
}
