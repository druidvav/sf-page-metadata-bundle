<?php

namespace Druidvav\PageMetadataBundle\Tests;

use Druidvav\PageMetadataBundle\DependencyInjection\DvPageMetadataConfiguration;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\Definition\Processor;

class DvPageMetadataConfigurationTest extends TestCase
{
    public function testStructuredDataDefaults(): void
    {
        $config = (new Processor())->processConfiguration(new DvPageMetadataConfiguration(), [
            [ 'base_url' => 'https://example.com' ],
        ]);

        self::assertSame('https://example.com', $config['base_url']);
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
            [
                'base_url' => 'https://example.com',
                'structured_data' => [ 'nodes' => $nodes ],
            ],
        ]);

        self::assertSame($nodes, $config['structured_data']['nodes']);
    }

    public function testItRejectsAnEmptyBaseUrl(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new DvPageMetadataConfiguration(), [
            [ 'base_url' => '' ],
        ]);
    }

    public function testItRequiresABaseUrl(): void
    {
        $this->expectException(\Symfony\Component\Config\Definition\Exception\InvalidConfigurationException::class);

        (new Processor())->processConfiguration(new DvPageMetadataConfiguration(), [ [ ] ]);
    }
}
