<?php
namespace Druidvav\PageMetadataBundle\Twig\Extension;

use Druidvav\PageMetadataBundle\PageMetadata;
use LogicException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageMetadataExtension extends AbstractExtension
{
    protected PageMetadata $page;
    protected Environment $twig;
    protected ?UrlGeneratorInterface $urlGenerator;
    /** @var array<string, mixed> */
    protected array $options;

    /** @param array<string, mixed> $options */
    public function __construct(
        PageMetadata $page,
        Environment $twig,
        array $options,
        ?UrlGeneratorInterface $urlGenerator = null
    )
    {
        $this->page = $page;
        $this->twig = $twig;
        $this->options = $options;
        $this->urlGenerator = $urlGenerator;
    }

    /**
     * {@inheritdoc}
     * @noinspection FirstClassCallable
     */
    public function getFunctions(): array
    {
        return [
            new TwigFunction("page_breadcrumbs", [ $this, "renderBreadcrumbs" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_meta", [ $this, "renderMeta" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_structured_data", [ $this, "renderStructuredData" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_locale_url", [ $this, "pageLocaleUrl" ]),
            new TwigFunction("page_title", [ $this->page, "getPageTitleAsString" ]),
            new TwigFunction("page_description", [ $this, "metaDescription" ]),
            new TwigFunction("page_keywords", [ $this, "metaKeywords" ]),
            new TwigFunction("page_link_canonical", [ $this->page, "getLinkCanonical" ]),
            new TwigFunction("page_link_canonical_lang", [ $this->page, "getLinkCanonicalLang" ]),
            new TwigFunction("page_link_canonical_alternates", [ $this->page, "getCanonicalAlternates" ]),
            new TwigFunction("page_og_type", [ $this->page, "getOgType" ]),
            new TwigFunction("page_og_url", [ $this->page, "getOgUrl" ]),
            new TwigFunction("page_og_site_name", [ $this->page, "getOgSiteName" ]),
            new TwigFunction("page_og_image", [ $this->page, "getOgImage" ]),
            new TwigFunction("page_og_title", [ $this->page, "getOgTitleAsString" ]),
            new TwigFunction("page_og_description", [ $this->page, "getOgDescription" ]),
            new TwigFunction("page_twitter_image", [ $this->page, "getOgTwitterImage" ]),
            new TwigFunction("page_twitter_site", [ $this->page, "getOgTwitterSite" ]),
            new TwigFunction("page_twitter_card", [ $this->page, "getOgTwitterCard" ]),
        ];
    }

    public function pageLocaleUrl(Request $request, string $locale, int $referenceType = UrlGeneratorInterface::ABSOLUTE_URL): string
    {
        if ($this->urlGenerator === null) {
            throw new LogicException('The URL generator is required to generate a localized page URL.');
        }

        $route = $request->attributes->get('_route');
        if (!is_string($route) || $route === '') {
            throw new LogicException('A current route is required to generate a localized page URL.');
        }

        $routeParameters = $request->attributes->get('_route_params', [ ]);
        if (!is_array($routeParameters)) {
            $routeParameters = [ ];
        }

        return $this->urlGenerator->generate($route, array_merge(
            $request->query->all(),
            $routeParameters,
            [ '_locale' => $locale ]
        ), $referenceType);
    }

    public function renderBreadcrumbs(array $options = [ ]): string
    {
        $options = array_merge(array_merge($this->options['breadcrumbs'], [
            'namespace' => PageMetadata::DEFAULT_NAMESPACE,
        ]), $options);
        $options['breadcrumbs'] = $this->page->getNsBreadcrumbs($options['namespace']);
        return $this->twig->render($options['viewTemplate'], $options);
    }

    public function renderMeta(): string
    {
        return $this->twig->render('@DvPageMetadata/meta.html.twig');
    }

    public function renderStructuredData(): string
    {
        $options = $this->options['structured_data'] ?? [
            'enabled' => true,
            'breadcrumbs' => true,
        ];
        if (!($options['enabled'] ?? true)) {
            return '';
        }

        $graph = $this->page->getStructuredDataGraph($options['breadcrumbs'] ?? true);
        if ($graph === [ ]) {
            return '';
        }

        $json = json_encode([
            '@context' => 'https://schema.org',
            '@graph' => $graph,
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR);

        return $this->twig->render('@DvPageMetadata/structured_data.html.twig', [ 'json' => $json ]);
    }

    public function metaDescription(?string $default = null): ?string
    {
        return $this->page->getMetaDescription() ?: $default;
    }

    public function metaKeywords(?string $default = null): ?string
    {
        return $this->page->getMetaKeywords() ?: $default;
    }

    public function getName(): string
    {
        return "page_metadata";
    }
}
