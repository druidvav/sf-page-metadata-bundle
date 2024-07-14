<?php
namespace Druidvav\PageMetadataBundle\Twig\Extension;

use Druidvav\PageMetadataBundle\Templating\Helper\PageMetadataHelper;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class PageMetadataExtension extends AbstractExtension
{
    protected $helper;

    public function __construct(PageMetadataHelper $helper)
    {
        $this->helper = $helper;
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
            new TwigFunction("page_render_title", [ $this, "renderTitle" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_render_meta_description", [ $this, "renderMetaDescription" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_enabled", [ $this->helper->getPage(), "isOgEnabled" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_type", [ $this->helper->getPage(), "getOgType" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_site_name", [ $this->helper->getPage(), "getOgSiteName" ], [ "is_safe" => [ "html" ] ]),
        ];
    }

    public function renderBreadcrumbs(array $options = [ ]): string
    {
        return $this->helper->breadcrumbs($options);
    }

    public function renderTitle(array $options = [ ]): string
    {
        return $this->helper->title($options);
    }

    public function renderMeta(): string
    {
        return $this->helper->meta();
    }

    public function renderMetaDescription(): ?string
    {
        return $this->helper->metaDescription();
    }

    public function renderMetaKeywords(): ?string
    {
        return $this->helper->metaKeywords();
    }

    public function getName(): string
    {
        return "page_metadata";
    }
}
