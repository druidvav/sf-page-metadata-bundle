<?php

namespace Druidvav\PageMetadataBundle\Tests;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Druidvav\PageMetadataBundle\PageMetadata;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageMetadataTest extends TestCase
{
    public function testItNormalizesDateTimesRecursively(): void
    {
        $page = $this->createPageMetadata();
        $publishedAt = new DateTimeImmutable('2026-07-15 10:20:30', new DateTimeZone('+04:00'));
        $modifiedAt = new DateTime('2026-07-16 11:30:40', new DateTimeZone('UTC'));

        $page->setStructuredData('article', [
            '@type' => 'BlogPosting',
            'datePublished' => $publishedAt,
            'nested' => [ 'dateModified' => $modifiedAt ],
        ]);

        self::assertSame([
            'article' => [
                '@type' => 'BlogPosting',
                'datePublished' => '2026-07-15T10:20:30+04:00',
                'nested' => [ 'dateModified' => '2026-07-16T11:30:40+00:00' ],
            ],
        ], $page->getStructuredData());
    }

    public function testItReplacesRemovesAndClearsNamedNodes(): void
    {
        $page = $this->createPageMetadata();
        $page
            ->setStructuredData('page', [ '@type' => 'WebPage', 'name' => 'Old' ])
            ->setStructuredData('page', [ '@type' => 'WebPage', 'name' => 'New' ])
            ->setStructuredData('organization', [ '@type' => 'Organization' ]);

        self::assertSame('New', $page->getStructuredData()['page']['name']);

        $page->removeStructuredData('page');
        self::assertArrayNotHasKey('page', $page->getStructuredData());

        $page->clearStructuredData();
        self::assertSame([ ], $page->getStructuredData());
    }

    public function testItRemovesNodeContextBecauseGraphOwnsIt(): void
    {
        $page = $this->createPageMetadata();
        $page->setStructuredData('website', [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
        ]);

        self::assertArrayNotHasKey('@context', $page->getStructuredData()['website']);
    }

    public function testItRejectsUnsupportedValuesWithTheirPath(): void
    {
        $page = $this->createPageMetadata();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('article.author');

        $page->setStructuredData('article', [
            '@type' => 'BlogPosting',
            'author' => new \stdClass(),
        ]);
    }

    public function testItBuildsBreadcrumbStructuredDataWithAbsoluteUrls(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('/', '/en/blog');

        $page = $this->createPageMetadata($router);
        $page
            ->setBaseUrl('https://airquality.am/')
            ->addRouteItem('Home', 'root')
            ->addRouteItem('Blog', 'blog');

        self::assertSame([
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => 'https://airquality.am/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Blog',
                    'item' => 'https://airquality.am/en/blog',
                ],
            ],
        ], $page->getStructuredDataGraph()[0]);
    }

    public function testItBuildsUrlsWhenBaseUrlHasNoTrailingSlash(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->expects(self::exactly(2))
            ->method('generate')
            ->willReturnOnConsecutiveCalls('/', '/en/blog');

        $page = $this->createPageMetadata($router);
        $page
            ->setBaseUrl('https://airquality.am')
            ->addRouteItem('Home', 'root')
            ->addRouteItem('Blog', 'blog');

        self::assertSame([
            '@type' => 'BreadcrumbList',
            'itemListElement' => [
                [
                    '@type' => 'ListItem',
                    'position' => 1,
                    'name' => 'Home',
                    'item' => 'https://airquality.am/',
                ],
                [
                    '@type' => 'ListItem',
                    'position' => 2,
                    'name' => 'Blog',
                    'item' => 'https://airquality.am/en/blog',
                ],
            ],
        ], $page->getStructuredDataGraph()[0]);
    }

    public function testItResolvesRelativeMetadataUrlsAgainstBaseUrl(): void
    {
        $page = $this->createPageMetadata();
        $page
            ->setBaseUrl('https://airquality.am/')
            ->setImage('/img/logo-og.jpg')
            ->setOgUrl('/en/')
            ->setLinkCanonical('/en/')
            ->setStructuredData('organization', [
                '@type' => 'Organization',
                '@id' => '/#organization',
                'name' => 'AirQuality.am',
                'url' => '/',
                'logo' => '/img/logo-og.jpg',
                'sameAs' => [ 'https://t.me/armeniaAirQuality' ],
            ]);

        self::assertSame('https://airquality.am/img/logo-og.jpg', $page->getOgImage());
        self::assertSame('https://airquality.am/img/logo-og.jpg', $page->getOgTwitterImage());
        self::assertSame('https://airquality.am/en/', $page->getOgUrl());
        self::assertSame('https://airquality.am/en/', $page->getLinkCanonical());
        self::assertSame([
            '@type' => 'Organization',
            '@id' => 'https://airquality.am/#organization',
            'name' => 'AirQuality.am',
            'url' => 'https://airquality.am/',
            'logo' => 'https://airquality.am/img/logo-og.jpg',
            'sameAs' => [ 'https://t.me/armeniaAirQuality' ],
        ], $page->getStructuredData()['organization']);
    }

    public function testItPreservesAbsoluteAndSchemeRelativeMetadataUris(): void
    {
        $page = $this->createPageMetadata();
        $page
            ->setBaseUrl('https://airquality.am')
            ->setOgImage('https://cdn.example.com/image.jpg')
            ->setOgTwitterImage('//cdn.example.com/twitter.jpg');

        self::assertSame('https://cdn.example.com/image.jpg', $page->getOgImage());
        self::assertSame('//cdn.example.com/twitter.jpg', $page->getOgTwitterImage());
    }

    public function testItGeneratesCanonicalAndAlternatesFromTheStoredRequest(): void
    {
        $request = Request::create('/ru/blog/example?page=2&utm_source=newsletter');
        $request->setLocale('ru');
        $request->attributes->set('_route', 'blog-post');
        $request->attributes->set('_route_params', [
            '_locale' => 'ru',
            'slug' => 'example',
        ]);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::exactly(4))
            ->method('generate')
            ->with(
                'blog-post',
                self::callback(static fn (array $parameters): bool => $parameters === [
                    '_locale' => $parameters['_locale'],
                    'slug' => 'example',
                    'page' => '2',
                ] && in_array($parameters['_locale'], [ 'ru', 'hy', 'en' ], true)),
                UrlGeneratorInterface::ABSOLUTE_URL
            )
            ->willReturnCallback(static fn (string $route, array $parameters): string => sprintf(
                'https://example.com/%s/blog/example?page=2',
                $parameters['_locale']
            ));

        $page = $this->createPageMetadata($router);
        $page
            ->setCanonicalFromRequest($request)
            ->addCanonicalParameter('page')
            ->setCanonicalAlternateLocales([ 'hy', 'ru', 'en' ]);

        self::assertSame('ru', $page->getLinkCanonicalLang());
        self::assertSame('https://example.com/ru/blog/example?page=2', $page->getLinkCanonical());
        self::assertSame([
            'hy' => 'https://example.com/hy/blog/example?page=2',
            'ru' => 'https://example.com/ru/blog/example?page=2',
            'en' => 'https://example.com/en/blog/example?page=2',
        ], $page->getCanonicalAlternates());
    }

    public function testCanonicalParameterCanBeAddedAfterTheRequest(): void
    {
        $request = Request::create('/ru/blog?page=3');
        $request->setLocale('ru');
        $request->attributes->set('_route', 'blog-index');
        $request->attributes->set('_route_params', [ '_locale' => 'ru' ]);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('blog-index', [ '_locale' => 'ru', 'page' => '3' ], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/ru/blog?page=3');

        $page = $this->createPageMetadata($router);
        $page->setCanonicalFromRequest($request);
        $page->addCanonicalParameter('page');

        self::assertSame('https://example.com/ru/blog?page=3', $page->getLinkCanonical());
    }

    public function testItDoesNotLocalizeARouteWithoutALocaleParameter(): void
    {
        $request = Request::create('/sitemap.xml');
        $request->setLocale('en');
        $request->attributes->set('_route', 'sitemap');
        $request->attributes->set('_route_params', [ ]);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('sitemap', [ ], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/sitemap.xml');

        $page = $this->createPageMetadata($router);
        $page
            ->setCanonicalFromRequest($request)
            ->setCanonicalAlternateLocales([ 'hy', 'ru', 'en' ]);

        self::assertSame('https://example.com/sitemap.xml', $page->getLinkCanonical());
        self::assertSame([ ], $page->getCanonicalAlternates());
    }

    public function testOgUrlDefaultsToTheCanonicalUrl(): void
    {
        $request = Request::create('/ru/blog?page=2');
        $request->setLocale('ru');
        $request->attributes->set('_route', 'blog-index');
        $request->attributes->set('_route_params', [ '_locale' => 'ru' ]);

        $router = $this->createMock(RouterInterface::class);
        $router
            ->expects(self::once())
            ->method('generate')
            ->with('blog-index', [ '_locale' => 'ru', 'page' => '2' ], UrlGeneratorInterface::ABSOLUTE_URL)
            ->willReturn('https://example.com/ru/blog?page=2');

        $page = $this->createPageMetadata($router);
        $page
            ->setCanonicalFromRequest($request)
            ->addCanonicalParameter('page');

        self::assertSame('https://example.com/ru/blog?page=2', $page->getOgUrl());
    }

    public function testOgUrlCanOverrideTheCanonicalUrl(): void
    {
        $page = $this->createPageMetadata();
        $page
            ->setBaseUrl('https://example.com')
            ->setLinkCanonical('/en/canonical')
            ->setOgUrl('/en/shared-object');

        self::assertSame('https://example.com/en/shared-object', $page->getOgUrl());
    }

    public function testItOmitsBreadcrumbDataForFewerThanTwoItems(): void
    {
        $page = $this->createPageMetadata();
        self::assertSame([ ], $page->getStructuredDataGraph());
    }

    public function testItRequiresABaseUrlToMakeBreadcrumbUrlsAbsolute(): void
    {
        $router = $this->createMock(RouterInterface::class);
        $router->method('generate')->willReturnOnConsecutiveCalls('/', '/en/blog');

        $page = $this->createPageMetadata($router);
        $page
            ->addRouteItem('Home', 'root')
            ->addRouteItem('Blog', 'blog');

        $this->expectException(LogicException::class);
        $this->expectExceptionMessage('base URL must be configured');
        $page->getStructuredDataGraph();
    }

    public function testItRejectsAnEmptyBaseUrl(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('base URL must not be empty');

        $this->createPageMetadata()->setBaseUrl('');
    }

    public function testItRejectsABaseUrlContainingOnlySlashes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('must contain more than slashes');

        $this->createPageMetadata()->setBaseUrl('/');
    }

    public function testAutotextMethodsExplainHowToInstallTheOptionalDependency(): void
    {
        if (class_exists(\TextGenerator\TextGenerator::class) && class_exists(\TextGenerator\Part::class)) {
            self::markTestSkipped('The optional meniam/autotext package is installed.');
        }

        $calls = [
            static fn (PageMetadata $page) => $page->setTitleAutotext('Title'),
            static fn (PageMetadata $page) => $page->setMetaDescriptionAutotext('Description'),
            static fn (PageMetadata $page) => $page->setMetaKeywordsAutotext('keyword'),
        ];

        foreach ($calls as $call) {
            try {
                $call($this->createPageMetadata());
                self::fail('An unavailable Autotext dependency must result in a LogicException.');
            } catch (LogicException $exception) {
                self::assertStringContainsString('composer require meniam/autotext', $exception->getMessage());
            }
        }
    }

    private function createPageMetadata(?RouterInterface $router = null): PageMetadata
    {
        $router = $router ?: $this->createMock(RouterInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $translator->method('trans')->willReturnCallback(static fn ($id) => $id);

        return new PageMetadata($router, $translator);
    }
}
