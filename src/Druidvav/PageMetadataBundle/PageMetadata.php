<?php
namespace Druidvav\PageMetadataBundle;

use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Translation\TranslatorInterface;
use TextGenerator\Part;
use TextGenerator\TextGenerator;

class PageMetadata
{
    private $transDomain = null;

    private $title = [ ];
    private $titleDelimeter;

    private $metaDescription;
    private $metaKeywords;

    const DEFAULT_NAMESPACE = "default";
    private $breadcrumbs = [
        self::DEFAULT_NAMESPACE => [ ]
    ];

    const MODE_SET = 'set';
    const MODE_PREPEND = 'prepend';
    const MODE_APPEND = 'append';

    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    public function __construct(RouterInterface $router, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->translator = $translator;
    }

    public function setTransDomain($domain)
    {
        $this->transDomain = $domain;
    }

    public function setTitleDelimeter($titleDelimeter)
    {
        $this->titleDelimeter = $titleDelimeter;
    }

    protected function addBreadcrumb(Breadcrumb $bc)
    {
        return $this->addBreadcrumbToNs(self::DEFAULT_NAMESPACE, $bc);
    }

    protected function addBreadcrumbToNs($namespace, Breadcrumb $bc)
    {
        $this->breadcrumbs[$namespace][] = $bc;
        return $this;
    }

    public function getNsBreadcrumbs($namespace = self::DEFAULT_NAMESPACE)
    {
        // Check whether requested namespace breadcrumbs is exists
        if (!isset($this->breadcrumbs[$namespace])) {
            throw new \InvalidArgumentException(sprintf(
                'The breadcrumb namespace "%s" does not exist', $namespace
            ));
        }
        return $this->breadcrumbs[$namespace];
    }

    /**
     * @param $id
     * @param $route
     * @param array $parameters
     * @param int $referenceType
     * @param array $translationParameters
     * @return PageMetadata
     */
    public function addRouteItem($id, $route, array $parameters = array(), $referenceType = RouterInterface::ABSOLUTE_PATH, array $translationParameters = array())
    {
        $bc = new Breadcrumb($this->router, $this->translator);
        $bc
            ->setRawText($this->transIfId($id, $translationParameters, $this->transDomain))
            ->setUrl($this->router->generate($route, $parameters, $referenceType));
        $this->addBreadcrumb($bc);
        return $this;
    }

    public function setTitle($text, $parameters = [ ])
    {
        return $this->addTitle($text, $parameters, self::MODE_SET);
    }

    public function setTitleAutotext($title, $parameters = [ ], $autotextId = null)
    {
        $title = $this->transIfId($title, $parameters, $this->transDomain);
        $textGeneratorOptions = array(Part::OPTION_GENERATE_HASH => $autotextId);
        $title = TextGenerator::factory(' ' . $title, $textGeneratorOptions)->generate();
        $title = trim(preg_replace('#[\s]+#si', ' ', $title));
        $this->title = [$title];
        return $this;
    }

    /**
     * @param $text
     * @param array $parameters
     * @param string $mode
     * @return PageMetadata
     */
    public function addTitle($text, $parameters = [ ], $mode = self::MODE_PREPEND)
    {
        $translated = $this->transIfId($text, $parameters, $this->transDomain);
        switch ($mode) {
            case self::MODE_SET:
                $this->title = [ $translated ];
                break;
            case self::MODE_APPEND:
                $this->title[] = $translated;
                break;
            case self::MODE_PREPEND:
                array_unshift($this->title, $translated);
                break;
        }
        return $this;
    }

    public function getTitleAsString()
    {
        return implode($this->titleDelimeter, $this->title);
    }

    public function setMetaDescription($metaDescription, $parameters = [ ])
    {
        $metaDescription = $this->transIfId($metaDescription, $parameters, $this->transDomain);
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function setMetaDescriptionAutotext($metaDescription, $parameters = [ ], $autotextId = null)
    {
        $metaDescription = $this->transIfId($metaDescription, $parameters, $this->transDomain);
        $textGeneratorOptions = array(Part::OPTION_GENERATE_HASH => $autotextId);
        $metaDescription = TextGenerator::factory(' ' . $metaDescription, $textGeneratorOptions)->generate();
        $metaDescription = trim(preg_replace('#[\s]+#si', ' ', $metaDescription));

        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getMetaDescription()
    {
        return $this->metaDescription;
    }

    public function setMetaKeywords($metaKeywords, $parameters = [ ])
    {
        $metaKeywords = $this->transIfId($metaKeywords, $parameters, $this->transDomain);
        $metaKeywordsArray = array_map('trim', explode(',', $metaKeywords));
        $metaKeywordsArray = array_unique($metaKeywordsArray);

        $this->metaKeywords = implode(', ', $metaKeywordsArray);
        return $this;
    }

    public function setMetaKeywordsAutotext($metaKeywords, $parameters = [ ], $autotextId = null)
    {
        $metaKeywords = $this->transIfId($metaKeywords, $parameters, $this->transDomain);
        $textGeneratorOptions = array(Part::OPTION_GENERATE_HASH => $autotextId);
        $this->metaKeywords = trim(TextGenerator::factory(' ' . $metaKeywords, $textGeneratorOptions)->generate());
        return $this;
    }

    public function getMetaKeywords()
    {
        return $this->metaKeywords;
    }

    protected function transIfId($text, array $parameters = [ ], $domain = null)
    {
        if (preg_match('/^[a-z_\-\\.]+$/', $text) || strpos($text, '%') !== false) {
            return $this->translator->trans($text, $parameters, $domain ?: $this->transDomain);
        } else {
            return $text;
        }
    }
}