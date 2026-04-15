<?php
namespace Druidvav\PageMetadataBundle\Twig\Extension;

use Druidvav\PageMetadataBundle\PageMetadata;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageMetadataExtension extends AbstractExtension
{
    protected PageMetadata $page;
    protected Environment $twig;
    protected array $options;

    public function __construct(PageMetadata $page, Environment $twig, array $options)
    {
        $this->page = $page;
        $this->twig = $twig;
        $this->options = $options;
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
            new TwigFunction("page_title", [ $this->page, "getPageTitleAsString" ]),
            new TwigFunction("page_description", [ $this, "metaDescription" ]),
            new TwigFunction("page_keywords", [ $this, "metaKeywords" ]),
            new TwigFunction("page_link_canonical", [ $this->page, "getLinkCanonical" ]),
            new TwigFunction("page_link_canonical_lang", [ $this->page, "getLinkCanonicalLang" ]),
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

    public function metaDescription(string $default = null): ?string
    {
        return $this->page->getMetaDescription() ?: $default;
    }

    public function metaKeywords(string $default = null): ?string
    {
        return $this->page->getMetaKeywords() ?: $default;
    }

    public function getName(): string
    {
        return "page_metadata";
    }
}
