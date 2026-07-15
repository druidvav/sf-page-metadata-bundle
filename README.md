# Page Metadata Bundle

Symfony bundle for managing page titles, meta tags, canonical URLs, Open Graph and Twitter metadata, breadcrumbs, and Schema.org JSON-LD from one request-scoped metadata object.

## Requirements

- PHP 7.4 or later
- Symfony 5, 6, or 7
- Twig 2.7 or later

## Installation

```bash
composer require druidvav/page-metadata-bundle
```

When Symfony Flex is not available, register the bundle manually:

```php
// config/bundles.php

return [
    // ...
    Druidvav\PageMetadataBundle\DvPageMetadataBundle::class => ['all' => true],
];
```

Render the metadata in the document head and breadcrumbs where they belong in the page body:

```twig
<head>
    {{ page_meta() }}
</head>
<body>
    {{ page_breadcrumbs() }}
</body>
```

`page_meta()` renders the title, standard meta tags, canonical link, Open Graph and Twitter tags, and structured data.

## Configuration

`base_url` is required. All other sections are optional:

```yaml
# config/packages/dv_page_metadata.yaml
dv_page_metadata:
    base_url: '%env(DEFAULT_URI)%'

    title:
        default: 'Example'
        delimiter: ' - '
        locale: null
        translation_domain: null

    meta:
        description: null
        keywords: null

    opengraph:
        site_name: 'Example'
        type: website
        image: 'https://example.com/default-cover.jpg'
        twitter_image: 'https://example.com/default-cover.jpg'
        twitter_site: '@example'

    breadcrumbs:
        listId: ''
        listClass: ''
        itemClass: ''
        linkRel: ''
        locale: null
        translation_domain: null
        viewTemplate: '@DvPageMetadata/breadcrumbs/bootstrap.html.twig'

    structured_data:
        enabled: true
        breadcrumbs: true
        nodes: { }
```

`base_url` is the canonical site root shared by metadata features that need to turn a path into an absolute URL. It is expected without a trailing slash, for example `https://example.com`. A missing or empty value is rejected. The same value can be set at runtime with `PageMetadata::setBaseUrl()`.

The `locale` and `translation_domain` configuration keys are retained by the bundle configuration. Set the active translation domain at runtime with `PageMetadata::setTransDomain()` when translated titles, descriptions, or breadcrumb labels are required.

Canonical URL, canonical language, Open Graph URL, and Twitter card type have no configuration defaults and are normally set per request through the PHP API.

## Using `PageMetadata`

Inject `PageMetadata` into a controller, event listener, or another service:

```php
use Druidvav\PageMetadataBundle\PageMetadata;

final class ArticleController
{
    private PageMetadata $pageMetadata;

    public function __construct(PageMetadata $pageMetadata)
    {
        $this->pageMetadata = $pageMetadata;
    }

    public function __invoke(Article $article): Response
    {
        $this->pageMetadata
            ->setTitle($article->getTitle())
            ->setDescription($article->getDescription())
            ->setImage($article->getCoverUrl())
            ->setLinkCanonical($article->getCanonicalUrl())
            ->setOgUrl($article->getCanonicalUrl())
            ->setOgTwitterCard(PageMetadata::TWITTER_CARD_SUMMARY_LARGE_IMAGE);

        // Render the response.
    }
}
```

Setter methods return the same `PageMetadata` instance and can be chained, except for `setTransDomain()` and `setTitleDelimiter()`.

### Setter injection trait

Autoconfigured Symfony services can use `PageMetadataAwareTrait` instead of constructor injection:

```php
use Druidvav\PageMetadataBundle\PageMetadataAwareTrait;

final class ArticleController
{
    use PageMetadataAwareTrait;
}
```

Constructor injection is preferable when page metadata is a required dependency. The trait is useful for optional integration or existing classes where changing the constructor is inconvenient.

## Titles

`setTitle()` updates both the HTML page title and the Open Graph/Twitter title:

```php
$pageMetadata->setTitle('Article title');
```

Titles can be composed using a configured delimiter:

```php
use Druidvav\PageMetadataBundle\PageMetadata;

$pageMetadata
    ->setTitle('Example')
    ->addTitle('Air quality', [], PageMetadata::MODE_PREPEND);

// Air quality - Example
```

Available modes are:

- `PageMetadata::MODE_SET` — replace all title parts;
- `PageMetadata::MODE_PREPEND` — add a part before the existing title;
- `PageMetadata::MODE_APPEND` — add a part after the existing title.

Use `setPageTitle()` and `addPageTitle()` when only the HTML `<title>` should change. Use `setOgTitle()` and `addOgTitle()` when only Open Graph and Twitter title tags should change.

## Descriptions and keywords

`setDescription()` updates both the standard meta description and the Open Graph/Twitter description:

```php
$pageMetadata->setDescription('Current air quality and pollution measurements.');
```

They can also be controlled independently:

```php
$pageMetadata->setMetaDescription('Search result description.');
$pageMetadata->setOgDescription('Social sharing description.');
```

`setMetaKeywords()` accepts a comma-separated string, trims individual values, and removes duplicates:

```php
$pageMetadata->setMetaKeywords('air quality, Armenia, air quality');
// air quality, Armenia
```

## Translation

Set a translation domain before passing translation IDs:

```php
$pageMetadata->setTransDomain('messages');

$pageMetadata->setTitle('article.page_title', [
    '%title%' => $article->getTitle(),
]);

$pageMetadata->addRouteItem('navigation.home', 'homepage');
```

The bundle treats lowercase identifiers containing letters, numbers, `_`, `-`, or `.` as translation IDs. Strings containing `%` are also passed through the translator. Other strings are used as provided.

An explicit translation domain can be passed to description setters:

```php
$pageMetadata->setDescription('article.description', [], 'articles');
$pageMetadata->setMetaDescription('article.search_description', [], 'articles');
$pageMetadata->setOgDescription('article.share_description', [], 'articles');
```

## Canonical metadata

```php
$pageMetadata
    ->setLinkCanonical('https://example.com/en/articles/air-quality')
    ->setLinkCanonicalLang('en')
    ->setOgUrl('https://example.com/en/articles/air-quality');
```

The values are available in Twig through `page_link_canonical()`, `page_link_canonical_lang()`, and `page_og_url()`.

## Open Graph and Twitter

Convenience setters keep common values synchronized:

```php
$pageMetadata->setTitle('Page title');       // HTML title and OG/Twitter title
$pageMetadata->setDescription('Summary');   // meta and OG/Twitter description
$pageMetadata->setImage($absoluteImageUrl); // OG and Twitter image
```

Individual fields can be changed separately:

```php
$pageMetadata
    ->setOgType('article')
    ->setOgSiteName('Example')
    ->setOgTitle('Social title')
    ->setOgDescription('Social description')
    ->setOgImage('https://example.com/og-cover.jpg')
    ->setOgTwitterImage('https://example.com/twitter-cover.jpg')
    ->setOgTwitterSite('@example')
    ->setOgTwitterCard(PageMetadata::TWITTER_CARD_SUMMARY_LARGE_IMAGE);
```

Supported Twitter card constants are:

- `PageMetadata::TWITTER_CARD_SUMMARY`;
- `PageMetadata::TWITTER_CARD_SUMMARY_LARGE_IMAGE`.

Configure or set `og:site_name` when Open Graph output is used. The default metadata template renders `og:type` together with `og:site_name`.

## Breadcrumbs

Add breadcrumb labels and routes in display order:

```php
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

$pageMetadata
    ->addRouteItem('navigation.home', 'homepage')
    ->addRouteItem(
        'navigation.article',
        'article_show',
        ['slug' => $article->getSlug()],
        UrlGeneratorInterface::ABSOLUTE_PATH,
        ['%title%' => $article->getTitle()]
    );
```

Arguments of `addRouteItem()` are:

1. label or translation ID;
2. Symfony route name;
3. route parameters;
4. URL reference type, defaulting to `ABSOLUTE_PATH`;
5. translation parameters for the label.

Render the default breadcrumb namespace with:

```twig
{{ page_breadcrumbs() }}
```

Template options can be overridden for one rendering:

```twig
{{ page_breadcrumbs({
    listId: 'page-breadcrumbs',
    listClass: 'mb-4',
    itemClass: 'text-secondary',
    linkRel: 'nofollow'
}) }}
```

The default template contains Bootstrap 4/5-compatible markup. A Bootstrap 3 variant is available at `@DvPageMetadata/breadcrumbs/bootstrap3.html.twig`. A custom template receives the configured options and a `breadcrumbs` array containing `Breadcrumb` objects with `text` and `url` properties.

Breadcrumb namespaces are supported by `getNsBreadcrumbs()` and by the rendering function's `namespace` option. Adding items to a custom namespace requires extending `PageMetadata` and using its protected `addBreadcrumbToNs()` method. The public `addRouteItem()` method adds to the `default` namespace.

## Structured data

The bundle renders Schema.org data as one JSON-LD object containing a single `@context` and an `@graph` with all configured and dynamic nodes.

### Static nodes

Site-wide nodes can be configured once:

```yaml
dv_page_metadata:
    base_url: '%env(DEFAULT_URI)%'

    structured_data:
        enabled: true
        breadcrumbs: true
        nodes:
            organization:
                '@type': Organization
                '@id': 'https://example.com/#organization'
                name: Example
                url: 'https://example.com/'
                logo: 'https://example.com/logo.jpg'

            website:
                '@type': WebSite
                '@id': 'https://example.com/#website'
                name: Example
                url: 'https://example.com/'
                publisher:
                    '@id': 'https://example.com/#organization'
                inLanguage: [en, fr]
```

The YAML keys `organization` and `website` are internal node names. They are not included in the generated JSON-LD.

### Dynamic nodes

Add page-specific data through `PageMetadata`:

```php
$pageMetadata->setStructuredData('article', [
    '@type' => 'BlogPosting',
    '@id' => $article->getCanonicalUrl() . '#article',
    'headline' => $article->getTitle(),
    'description' => $article->getDescription(),
    'datePublished' => $article->getPublishedAt(),
    'dateModified' => $article->getModifiedAt(),
    'publisher' => [
        '@id' => 'https://example.com/#organization',
    ],
]);
```

Calling `setStructuredData()` with the same internal name replaces the previous node without changing its graph position. This can be used to override a node loaded from configuration.

The node-level `@context` key is removed because the bundle owns the graph context. Other Schema.org properties remain generic, so the API can represent any current or future Schema.org type.

### Date and value normalization

Any `DateTimeInterface` value is converted recursively to `DATE_ATOM` ISO 8601 format:

```php
$pageMetadata->setStructuredData('event', [
    '@type' => 'Event',
    'startDate' => new DateTimeImmutable('2026-08-01 10:00:00+04:00'),
    'subEvent' => [
        'startDate' => new DateTime('2026-08-01 12:00:00+04:00'),
    ],
]);
```

Structured values may contain strings, integers, floats, booleans, `null`, nested arrays, and `DateTimeInterface` instances. Unsupported objects or resources cause an `InvalidArgumentException` whose message includes the property path.

### Removing and reading nodes

```php
$pageMetadata->removeStructuredData('article');
$pageMetadata->clearStructuredData();

$namedNodes = $pageMetadata->getStructuredData();
$graph = $pageMetadata->getStructuredDataGraph();
$graphWithoutBreadcrumbs = $pageMetadata->getStructuredDataGraph(false);
```

`getStructuredData()` preserves internal names. `getStructuredDataGraph()` returns the sequential array used as `@graph` and optionally appends generated breadcrumb data.

### BreadcrumbList

When `structured_data.breadcrumbs` is enabled and the default namespace contains at least two breadcrumbs, the bundle appends a `BreadcrumbList` node automatically. Relative breadcrumb paths use the global `base_url`; the current request is not used to determine the host.

### Rendering and escaping

Structured data is included automatically by `page_meta()`:

```twig
{{ page_meta() }}
```

`page_structured_data()` is available for custom head templates that render individual metadata functions instead of calling `page_meta()`:

```twig
<title>{{ page_title() }}</title>
{{ page_structured_data() }}
```

Do not call both `page_meta()` and `page_structured_data()` in the same document, because `page_meta()` already includes the JSON-LD block.

JSON is encoded with Unicode and URLs left readable, while HTML-sensitive characters are escaped to prevent values such as `</script>` from leaving the JSON-LD script element.

## Autotext integration

The optional `meniam/autotext` package can generate title, description, and keyword variants:

```bash
composer require meniam/autotext
```

Available methods are:

```php
$pageMetadata->setTitleAutotext($title, [], $stableId);
$pageMetadata->setMetaDescriptionAutotext($description, [], $stableId);
$pageMetadata->setMetaKeywordsAutotext($keywords, [], $stableId);
```

The optional stable ID controls deterministic variant selection according to the Autotext package behavior.
When the optional package is not installed, calling any Autotext method throws a `LogicException` containing the installation command instead of causing a missing-class error.

## Twig functions

| Function | Result |
| --- | --- |
| `page_meta()` | Complete metadata HTML, including structured data |
| `page_breadcrumbs(options = {})` | Rendered breadcrumb HTML |
| `page_structured_data()` | JSON-LD script for custom metadata templates |
| `page_title()` | Composed HTML page title |
| `page_description(default = null)` | Meta description or supplied default |
| `page_keywords(default = null)` | Meta keywords or supplied default |
| `page_link_canonical()` | Canonical URL |
| `page_link_canonical_lang()` | Canonical language value |
| `page_og_type()` | Open Graph type |
| `page_og_url()` | Open Graph URL |
| `page_og_site_name()` | Open Graph site name |
| `page_og_image()` | Open Graph image |
| `page_og_title()` | Composed Open Graph/Twitter title |
| `page_og_description()` | Open Graph/Twitter description |
| `page_twitter_image()` | Twitter image |
| `page_twitter_site()` | Twitter account |
| `page_twitter_card()` | Twitter card type |

## Custom templates

The built-in templates are:

- `@DvPageMetadata/meta.html.twig` — complete metadata output;
- `@DvPageMetadata/structured_data.html.twig` — JSON-LD script wrapper;
- `@DvPageMetadata/breadcrumbs/bootstrap.html.twig` — Bootstrap 4/5 breadcrumbs;
- `@DvPageMetadata/breadcrumbs/bootstrap3.html.twig` — Bootstrap 3 breadcrumbs.

Configure `breadcrumbs.viewTemplate` to use an application template. Symfony's standard bundle template overriding mechanism can also be used to customize metadata output globally.

## Testing

Install development dependencies and run:

```bash
composer install
composer test
```
