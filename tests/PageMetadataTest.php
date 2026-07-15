<?php

namespace Druidvav\PageMetadataBundle\Tests;

use DateTime;
use DateTimeImmutable;
use DateTimeZone;
use Druidvav\PageMetadataBundle\PageMetadata;
use InvalidArgumentException;
use LogicException;
use PHPUnit\Framework\TestCase;
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
