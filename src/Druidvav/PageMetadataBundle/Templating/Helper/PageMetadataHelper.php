<?php
namespace Druidvav\PageMetadataBundle\Templating\Helper;

use Druidvav\PageMetadataBundle\PageMetadata;
use Symfony\Component\DependencyInjection\ContainerAwareTrait;
use Symfony\Component\Templating\Helper\Helper;

class PageMetadataHelper extends Helper
{
    use ContainerAwareTrait;

    /**
     * @var PageMetadata
     */
    protected $page;

    /**
     * @var array The default options load from config file
     */
    protected $options = array();

    /**
     * @param PageMetadata $page
     * @param array $options The default options load from config file
     */
    public function __construct(PageMetadata $page, array $options)
    {
        $this->page = $page;
        $this->options = $options;
    }

    /**
     * Returns the HTML for the namespace breadcrumbs
     *
     * @param array $options The user-supplied options from the view
     * @return string A HTML string
     */
    public function breadcrumbs(array $options = array())
    {
        $options = array_merge(array_merge($this->options['breadcrumbs'], [
            'namespace' => PageMetadata::DEFAULT_NAMESPACE,
        ]), $options);
        $options['breadcrumbs'] = $this->page->getNsBreadcrumbs($options['namespace']);
        return $this->container->get('templating')->render($options['viewTemplate'], $options);
    }

    /**
     * @param array $options The user-supplied options from the view
     * @return string A HTML string
     */
    public function title(array $options = array())
    {
        return $this->page->getTitleAsString();
    }

    public function meta(array $options = array())
    {
        return $this->container->get('templating')->render('DvPageMetadataBundle::meta.html.twig', $options);
    }

    /**
     * @param string $default
     *
     * @return string
     */
    public function metaDescription($default = null)
    {
        $metaDescription = $this->page->getMetaDescription();
        return $metaDescription ?: $default;
    }

    /**
     * @param string|null $default
     *
     * @return string
     */
    public function metaKeywords(string $default = null): ?string
    {
        $metaKeywords = $this->page->getMetaKeywords();
        return $metaKeywords ?: $default;
    }

    /**
     * {@inheritdoc}
     * @codeCoverageIgnore
     */
    public function getName(): string
    {
        return 'rage_page';
    }
}
