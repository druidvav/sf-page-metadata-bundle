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
     */
    public function getFunctions(): array
    {
        return array(
            new TwigFunction("page_breadcrumbs", array($this, "renderBreadcrumbs"), array("is_safe" => array("html"))),
            new TwigFunction("page_render_title", array($this, "renderTitle"), array("is_safe" => array("html"))),
            new TwigFunction("page_render_meta", array($this, "renderMeta"), array("is_safe" => array("html"))),
            new TwigFunction("page_render_meta_description", array($this, "renderMetaDescription"), array("is_safe" => array("html"))),
            new TwigFunction("page_render_meta_keywords", array($this, "renderMetaKeywords"), array("is_safe" => array("html"))),
        );
    }

    public function renderBreadcrumbs(array $options = array()): string
    {
        return $this->helper->breadcrumbs($options);
    }

    public function renderTitle(array $options = array()): string
    {
        return $this->helper->title($options);
    }

    public function renderMeta()
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
