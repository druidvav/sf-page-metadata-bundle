<?php

namespace Druidvav\PageMetadataBundle\Tests;

use Druidvav\PageMetadataBundle\PageMetadata;
use Druidvav\PageMetadataBundle\Twig\Extension\PageMetadataExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;

class PageMetadataExtensionTest extends TestCase
{
    public function testItRendersOneSafeJsonLdGraph(): void
    {
        $page = $this->createPageMetadata();
        $page
            ->setStructuredData('organization', [ '@type' => 'Organization' ])
            ->setStructuredData('article', [
                '@type' => 'BlogPosting',
                'headline' => '</script><script>alert("xss")</script>',
            ]);

        $extension = $this->createExtension($page);
        $html = $extension->renderMeta();

        self::assertSame(1, substr_count($html, '<script type="application/ld+json">'));
        self::assertStringNotContainsString('</script><script>', $html);
        self::assertStringContainsString('\\u003C/script\\u003E', $html);

        preg_match('#<script type="application/ld\\+json">(.*)</script>#s', $html, $matches);
        $json = json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
        self::assertSame('https://schema.org', $json['@context']);
        self::assertCount(2, $json['@graph']);
    }

    public function testItRendersNothingForAnEmptyGraph(): void
    {
        $extension = $this->createExtension($this->createPageMetadata());
        self::assertSame('', $extension->renderStructuredData());
    }

    public function testItCanBeDisabled(): void
    {
        $page = $this->createPageMetadata();
        $page->setStructuredData('website', [ '@type' => 'WebSite' ]);

        $extension = $this->createExtension($page, false);
        self::assertSame('', $extension->renderStructuredData());
    }

    private function createExtension(PageMetadata $page, bool $enabled = true): PageMetadataExtension
    {
        $loader = new FilesystemLoader();
        $loader->addPath(dirname(__DIR__) . '/src/Druidvav/PageMetadataBundle/Resources/views', 'DvPageMetadata');
        $twig = new Environment($loader);

        $extension = new PageMetadataExtension($page, $twig, [
            'structured_data' => [
                'enabled' => $enabled,
                'breadcrumbs' => true,
                'nodes' => [ ],
            ],
        ]);
        $twig->addExtension($extension);

        return $extension;
    }

    private function createPageMetadata(): PageMetadata
    {
        return new PageMetadata(
            $this->createMock(RouterInterface::class),
            $this->createMock(TranslatorInterface::class)
        );
    }
}
