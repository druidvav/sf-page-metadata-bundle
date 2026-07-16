<?php

namespace Druidvav\PageMetadataBundle\Tests;

use Druidvav\PageMetadataBundle\PageMetadata;
use Druidvav\PageMetadataBundle\Twig\Extension\PageMetadataExtension;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
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

    public function testItGeneratesALocalizedUrlWithAllQueryParameters(): void
    {
        $request = Request::create('/ru/blog/example?page=2&filter[level]=high&slug=query');
        $request->attributes->set('_route', 'blog-post');
        $request->attributes->set('_route_params', [
            '_locale' => 'ru',
            'slug' => 'example',
        ]);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator
            ->expects(self::once())
            ->method('generate')
            ->with('blog-post', [
                'page' => '2',
                'filter' => [ 'level' => 'high' ],
                'slug' => 'example',
                '_locale' => 'en',
            ], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/en/blog/example?page=2&filter%5Blevel%5D=high');

        $extension = $this->createExtension($this->createPageMetadata(), true, $urlGenerator);

        self::assertSame(
            'https://example.com/en/blog/example?page=2&filter%5Blevel%5D=high',
            $extension->pageLocaleUrl($request, 'en')
        );
    }

    public function testItRendersCanonicalAlternateLinks(): void
    {
        $request = Request::create('/ru/blog?page=2&utm_source=newsletter');
        $request->setLocale('ru');
        $request->attributes->set('_route', 'blog-index');
        $request->attributes->set('_route_params', [ '_locale' => 'ru' ]);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::exactly(3))
            ->method('generate')
            ->willReturnCallback(static fn (string $route, array $parameters): string => sprintf(
                'https://example.com/%s/blog?page=%s',
                $parameters['_locale'],
                $parameters['page']
            ));

        $page = new PageMetadata($router, $this->createMock(TranslatorInterface::class));
        $page
            ->setCanonicalFromRequest($request)
            ->addCanonicalParameter('page')
            ->setCanonicalAlternateLocales([ 'hy', 'en' ]);

        $html = $this->createExtension($page)->renderMeta();

        self::assertStringContainsString(
            '<link rel="canonical" hreflang="ru" href="https://example.com/ru/blog?page=2" />',
            $html
        );
        self::assertStringContainsString(
            '<link rel="alternate" hreflang="hy" href="https://example.com/hy/blog?page=2" />',
            $html
        );
        self::assertStringContainsString(
            '<link rel="alternate" hreflang="en" href="https://example.com/en/blog?page=2" />',
            $html
        );
        self::assertStringNotContainsString('utm_source', $html);
    }

    private function createExtension(
        PageMetadata $page,
        bool $enabled = true,
        ?UrlGeneratorInterface $urlGenerator = null
    ): PageMetadataExtension
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
        ], $urlGenerator);
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
