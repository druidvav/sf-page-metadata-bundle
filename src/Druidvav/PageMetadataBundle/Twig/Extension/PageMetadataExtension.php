<?php
namespace Druidvav\PageMetadataBundle\Twig\Extension;

use Druidvav\PageMetadataBundle\Templating\Helper\PageMetadataHelper;

class PageMetadataExtension extends \Twig_Extension
{
    protected $helper;

    public function __construct(PageMetadataHelper $helper)
    {
        $this->helper = $helper;
    }

    /**
     * {@inheritdoc}
     */
    public function getFunctions()
    {
        return array(
            new \Twig_SimpleFunction("page_breadcrumbs", array($this, "renderBreadcrumbs"), array("is_safe" => array("html"))),
            new \Twig_SimpleFunction("page_render_title", array($this, "renderTitle"), array("is_safe" => array("html"))),
            new \Twig_SimpleFunction("page_render_meta", array($this, "renderMeta"), array("is_safe" => array("html"))),
            new \Twig_SimpleFunction("page_render_meta_description", array($this, "renderMetaDescription"), array("is_safe" => array("html"))),
            new \Twig_SimpleFunction("page_render_meta_keywords", array($this, "renderMetaKeywords"), array("is_safe" => array("html"))),
        );
    }

    public function renderBreadcrumbs(array $options = array())
    {
        return $this->helper->breadcrumbs($options);
    }

    public function renderTitle(array $options = array())
    {
        return $this->helper->title($options);
    }

    public function renderMeta()
    {
        return $this->helper->meta();
    }

    public function renderMetaDescription()
    {
        return $this->helper->metaDescription();
    }

    public function renderMetaKeywords()
    {
        return $this->helper->metaKeywords();
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return "page_metadata";
    }
}
