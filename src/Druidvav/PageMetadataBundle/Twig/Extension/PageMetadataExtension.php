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
            new TwigFunction("page_title", [ $this->helper->getPage(), "getPageTitleAsString" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_description", [ $this->helper, "metaDescription" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_keywords", [ $this->helper, "metaKeywords" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_type", [ $this->helper->getPage(), "getOgType" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_site_name", [ $this->helper->getPage(), "getOgSiteName" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_image", [ $this->helper->getPage(), "getOgImage" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_title", [ $this->helper->getPage(), "getOgTitleAsString" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_og_description", [ $this->helper->getPage(), "getOgDescription" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_twitter_image", [ $this->helper->getPage(), "getOgTwitterImage" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_twitter_site", [ $this->helper->getPage(), "getOgTwitterSite" ], [ "is_safe" => [ "html" ] ]),
            new TwigFunction("page_twitter_card", [ $this->helper->getPage(), "getOgTwitterCard" ], [ "is_safe" => [ "html" ] ]),
        ];
    }

    public function renderBreadcrumbs(array $options = [ ]): string
    {
        return $this->helper->breadcrumbs($options);
    }

    public function renderMeta(): string
    {
        return $this->helper->meta();
    }

    public function getName(): string
    {
        return "page_metadata";
    }
}
