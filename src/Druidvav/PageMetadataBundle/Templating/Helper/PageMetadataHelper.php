<?php
namespace Druidvav\PageMetadataBundle\Templating\Helper;

use Druidvav\PageMetadataBundle\PageMetadata;
use Symfony\Component\Templating\EngineInterface;
use Symfony\Component\Templating\Helper\Helper;

class PageMetadataHelper extends Helper
{
    protected PageMetadata $page;
    protected EngineInterface $templating;

    protected array $options = [ ];

    public function __construct(PageMetadata $page, EngineInterface $templating, array $options)
    {
        $this->page = $page;
        $this->options = $options;
        $this->templating = $templating;
    }

    public function getPage(): PageMetadata
    {
        return $this->page;
    }

    public function breadcrumbs(array $options = [ ]): string
    {
        $options = array_merge(array_merge($this->options['breadcrumbs'], [
            'namespace' => PageMetadata::DEFAULT_NAMESPACE,
        ]), $options);
        $options['breadcrumbs'] = $this->page->getNsBreadcrumbs($options['namespace']);
        return $this->templating->render($options['viewTemplate'], $options);
    }

    public function meta(array $options = [ ]): string
    {
        return $this->templating->render('@DvPageMetadata/meta.html.twig', $options);
    }

    public function metaDescription(string $default = null): ?string
    {
        $metaDescription = $this->page->getMetaDescription();
        return $metaDescription ?: $default;
    }

    public function metaKeywords(string $default = null): ?string
    {
        $metaKeywords = $this->page->getMetaKeywords();
        return $metaKeywords ?: $default;
    }

    public function getName(): string
    {
        return 'page_metadata';
    }
}
