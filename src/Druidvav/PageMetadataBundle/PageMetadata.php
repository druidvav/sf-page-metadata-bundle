<?php /** @noinspection PhpUnused */

namespace Druidvav\PageMetadataBundle;

use DateTimeInterface;
use InvalidArgumentException;
use LogicException;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

class PageMetadata
{
    private ?string $transDomain = null;

    private RouterInterface $router;
    private TranslatorInterface $translator;

    private ?string $titleDelimiter = null;

    public const DEFAULT_NAMESPACE = "default";
    private array $breadcrumbs = [
        self::DEFAULT_NAMESPACE => [ ]
    ];

    public const MODE_SET = 'set';
    public const MODE_PREPEND = 'prepend';
    public const MODE_APPEND = 'append';

    private array $pageTitle = [ ];
    private ?string $metaDescription = null;
    private ?string $metaKeywords = null;

    private ?string $linkCanonical = null;
    private ?string $linkCanonicalLang = null;

    private ?string $ogType = null;
    private ?string $ogSiteName = null;
    private array $ogTitle = [ ];
    private ?string $ogDescription = null;
    private ?string $ogImage = null;
    private ?string $ogUrl = null;
    private ?string $ogTwitterImage = null;
    private ?string $ogTwitterSite = null;
    private ?string $ogTwitterCard = null;

    /** @var array<string, array<int|string, mixed>> */
    private array $structuredData = [ ];
    private ?string $baseUrl = null;

    public function __construct(RouterInterface $router, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->translator = $translator;
    }

    public function setTransDomain(?string $domain): void
    {
        $this->transDomain = $domain;
    }

    public function setTitleDelimiter(?string $titleDelimiter): void
    {
        $this->titleDelimiter = $titleDelimiter;
    }

    protected function addBreadcrumb(Breadcrumb $bc): self
    {
        return $this->addBreadcrumbToNs(self::DEFAULT_NAMESPACE, $bc);
    }

    protected function addBreadcrumbToNs(string $namespace, Breadcrumb $bc): self
    {
        $this->breadcrumbs[$namespace][] = $bc;
        return $this;
    }

    /**
     * @param string $namespace
     * @return Breadcrumb[]
     */
    public function getNsBreadcrumbs(string $namespace = self::DEFAULT_NAMESPACE): array
    {
        if (!isset($this->breadcrumbs[$namespace])) {
            throw new InvalidArgumentException(sprintf(
                'The breadcrumb namespace "%s" does not exist', $namespace
            ));
        }
        return $this->breadcrumbs[$namespace];
    }

    public function addRouteItem($id, string $route, array $parameters = [ ], ?int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH, array $translationParameters = [ ]): self
    {
        if ($referenceType === null) {
            $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        }

        $bc = new Breadcrumb($this->router);
        $bc
            ->setRawText($this->transIfId($id, $translationParameters, $this->transDomain))
            ->setUrl($this->router->generate($route, $parameters, $referenceType));
        $this->addBreadcrumb($bc);
        return $this;
    }

    public function setTitle($text, array $parameters = [ ]): self
    {
        return $this->addPageTitle($text, $parameters, self::MODE_SET)->addOgTitle($text, $parameters, self::MODE_SET);
    }

    public function setTitleAutotext($title, array $parameters = [ ], ?string $autotextId = null): self
    {
        $this->ensureAutotextAvailable();
        $title = $this->transIfId($title, $parameters, $this->transDomain);
        $textGeneratorOptions = [ \TextGenerator\Part::OPTION_GENERATE_HASH => $autotextId ];
        $title = \TextGenerator\TextGenerator::factory(' ' . $title, $textGeneratorOptions)->generate();
        $title = trim(preg_replace('#\s+#si', ' ', $title));
        return $this->addPageTitle($title, $parameters, self::MODE_SET)->addOgTitle($title, $parameters, self::MODE_SET);
    }

    public function addTitle($text, array $parameters = [ ], string $mode = self::MODE_PREPEND): self
    {
        return $this->addPageTitle($text, $parameters, $mode)->addOgTitle($text, $parameters, $mode);
    }

    public function setImage(?string $ogImage): self
    {
        return $this->setOgImage($ogImage)->setOgTwitterImage($ogImage);
    }

    public function setDescription(?string $description, array $parameters = [ ], ?string $transDomain = null): self
    {
        return $this->setMetaDescription($description, $parameters, $transDomain)->setOgDescription($description, $parameters, $transDomain);
    }

    public function setPageTitle($text, array $parameters = [ ]): self
    {
        return $this->addPageTitle($text, $parameters, self::MODE_SET);
    }

    public function addPageTitle($text, array $parameters = [ ], string $mode = self::MODE_PREPEND): self
    {
        $translated = $this->transIfId($text, $parameters, $this->transDomain);
        switch ($mode) {
            case self::MODE_SET:
                $this->pageTitle = [ $translated ];
                break;
            case self::MODE_APPEND:
                $this->pageTitle[] = $translated;
                break;
            case self::MODE_PREPEND:
                array_unshift($this->pageTitle, $translated);
                break;
        }
        return $this;
    }

    public function getPageTitleAsString(): string
    {
        return implode($this->titleDelimiter ?? '', $this->pageTitle);
    }

    public function setMetaDescription($metaDescription, array $parameters = [ ], ?string $transDomain = null): self
    {
        $metaDescription = $this->transIfId($metaDescription, $parameters, $transDomain ?: $this->transDomain);
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function setMetaDescriptionAutotext($metaDescription, array $parameters = [ ], ?string $autotextId = null): self
    {
        $this->ensureAutotextAvailable();
        $metaDescription = $this->transIfId($metaDescription, $parameters, $this->transDomain);
        $textGeneratorOptions = [ \TextGenerator\Part::OPTION_GENERATE_HASH => $autotextId ];
        $metaDescription = \TextGenerator\TextGenerator::factory(' ' . $metaDescription, $textGeneratorOptions)->generate();
        $metaDescription = trim(preg_replace('#\s+#si', ' ', $metaDescription));
        $this->metaDescription = $metaDescription;
        return $this;
    }

    public function getMetaDescription(): ?string
    {
        return $this->metaDescription;
    }

    public function setMetaKeywords($metaKeywords, array $parameters = [ ]): self
    {
        $metaKeywords = $this->transIfId($metaKeywords, $parameters, $this->transDomain);
        $metaKeywordsArray = array_map('trim', explode(',', $metaKeywords));
        $metaKeywordsArray = array_unique($metaKeywordsArray);
        $this->metaKeywords = implode(', ', $metaKeywordsArray);
        return $this;
    }

    public function setMetaKeywordsAutotext($metaKeywords, array $parameters = [ ], ?string $autotextId = null): self
    {
        $this->ensureAutotextAvailable();
        $metaKeywords = $this->transIfId($metaKeywords, $parameters, $this->transDomain);
        $textGeneratorOptions = [ \TextGenerator\Part::OPTION_GENERATE_HASH => $autotextId ];
        $this->metaKeywords = trim(\TextGenerator\TextGenerator::factory(' ' . $metaKeywords, $textGeneratorOptions)->generate());
        return $this;
    }

    public function getMetaKeywords(): ?string
    {
        return $this->metaKeywords;
    }

    protected function transIfId($text, array $parameters = [ ], ?string $domain = null)
    {
        if (preg_match('/^[a-z0-9_\-.]+$/', $text) || strpos($text, '%') !== false) {
            return $this->translator->trans($text, $parameters, $domain ?: $this->transDomain);
        }
        return $text;
    }

    private function ensureAutotextAvailable(): void
    {
        if (!class_exists(\TextGenerator\TextGenerator::class) || !class_exists(\TextGenerator\Part::class)) {
            throw new LogicException(
                'Autotext metadata methods require the optional "meniam/autotext" package. '
                . 'Install it with "composer require meniam/autotext".'
            );
        }
    }

    public function getOgType(): ?string
    {
        return $this->ogType;
    }

    public function setOgType(?string $ogType): self
    {
        $this->ogType = $ogType;
        return $this;
    }

    public function getOgSiteName(): ?string
    {
        return $this->ogSiteName;
    }

    public function setOgSiteName(?string $ogSiteName, array $parameters = [ ]): self
    {
        $this->ogSiteName = $this->transIfId($ogSiteName, $parameters, $this->transDomain);
        return $this;
    }

    public function getOgImage(): ?string
    {
        return $this->ogImage;
    }

    public function setOgImage(?string $ogImage): self
    {
        $this->ogImage = $ogImage;
        return $this;
    }

    public function getOgTwitterImage(): ?string
    {
        return $this->ogTwitterImage;
    }

    public function setOgTwitterImage(?string $ogTwitterImage): self
    {
        $this->ogTwitterImage = $ogTwitterImage;
        return $this;
    }

    public function getOgTwitterSite(): ?string
    {
        return $this->ogTwitterSite;
    }

    public function setOgTwitterSite(?string $ogTwitterSite): self
    {
        $this->ogTwitterSite = $ogTwitterSite;
        return $this;
    }

    public function getOgTitle(): array
    {
        return $this->ogTitle;
    }

    public function getOgTitleAsString(): string
    {
        return implode($this->titleDelimiter ?? '', $this->ogTitle);
    }

    public function setOgTitle($text, array $parameters = [ ]): self
    {
        return $this->addOgTitle($text, $parameters, self::MODE_SET);
    }

    public function addOgTitle($text, array $parameters = [ ], string $mode = self::MODE_PREPEND): self
    {
        $translated = $this->transIfId($text, $parameters, $this->transDomain);
        switch ($mode) {
            case self::MODE_SET:
                $this->ogTitle = [ $translated ];
                break;
            case self::MODE_APPEND:
                $this->ogTitle[] = $translated;
                break;
            case self::MODE_PREPEND:
                array_unshift($this->ogTitle, $translated);
                break;
        }
        return $this;
    }

    public function getOgDescription(): ?string
    {
        return $this->ogDescription;
    }

    public function setOgDescription(?string $ogDescription, array $parameters = [ ], ?string $transDomain = null): self
    {
        $this->ogDescription = $this->transIfId($ogDescription, $parameters, $transDomain ?: $this->transDomain);
        return $this;
    }

    const TWITTER_CARD_SUMMARY = 'summary';
    const TWITTER_CARD_SUMMARY_LARGE_IMAGE = 'summary_large_image';

    public function getOgTwitterCard(): ?string
    {
        return $this->ogTwitterCard;
    }

    public function setOgTwitterCard(?string $ogTwitterCard): self
    {
        $this->ogTwitterCard = $ogTwitterCard;
        return $this;
    }

    public function getOgUrl(): ?string
    {
        return $this->ogUrl;
    }

    public function setOgUrl(?string $ogUrl): self
    {
        $this->ogUrl = $ogUrl;
        return $this;
    }

    public function getLinkCanonical(): ?string
    {
        return $this->linkCanonical;
    }

    public function setLinkCanonical(?string $linkCanonical): self
    {
        $this->linkCanonical = $linkCanonical;
        return $this;
    }

    public function getLinkCanonicalLang(): ?string
    {
        return $this->linkCanonicalLang;
    }

    public function setLinkCanonicalLang(?string $linkCanonicalLang): self
    {
        $this->linkCanonicalLang = $linkCanonicalLang;
        return $this;
    }

    /** @param array<int|string, mixed> $data */
    public function setStructuredData(string $name, array $data): self
    {
        $name = trim($name);
        if ($name === '') {
            throw new InvalidArgumentException('The structured data name must not be empty.');
        }

        unset($data['@context']);
        if ($data === [ ]) {
            throw new InvalidArgumentException(sprintf('The structured data node "%s" must not be empty.', $name));
        }

        $this->structuredData[$name] = $this->normalizeStructuredDataValue($data, $name);
        return $this;
    }

    public function setBaseUrl(string $baseUrl): self
    {
        if ($baseUrl === '') {
            throw new InvalidArgumentException('The page metadata base URL must not be empty.');
        }

        $this->baseUrl = $baseUrl;
        return $this;
    }

    public function removeStructuredData(string $name): self
    {
        unset($this->structuredData[$name]);
        return $this;
    }

    public function clearStructuredData(): self
    {
        $this->structuredData = [ ];
        return $this;
    }

    /** @return array<string, array<int|string, mixed>> */
    public function getStructuredData(): array
    {
        return $this->structuredData;
    }

    /** @return array<int, array<int|string, mixed>> */
    public function getStructuredDataGraph(bool $includeBreadcrumbs = true): array
    {
        $graph = array_values($this->structuredData);
        if ($includeBreadcrumbs) {
            $breadcrumbs = $this->getBreadcrumbStructuredData();
            if ($breadcrumbs !== null) {
                $graph[] = $breadcrumbs;
            }
        }

        return $graph;
    }

    /** @return array<string, mixed>|null */
    private function getBreadcrumbStructuredData(): ?array
    {
        $breadcrumbs = $this->getNsBreadcrumbs();
        if (count($breadcrumbs) < 2) {
            return null;
        }

        $items = [ ];
        foreach ($breadcrumbs as $position => $breadcrumb) {
            $items[] = [
                '@type' => 'ListItem',
                'position' => $position + 1,
                'name' => $breadcrumb->getText(),
                'item' => $this->getAbsoluteUrl($breadcrumb->getUrl()),
            ];
        }

        return [
            '@type' => 'BreadcrumbList',
            'itemListElement' => $items,
        ];
    }

    private function getAbsoluteUrl(string $url): string
    {
        if (preg_match('#^[a-z][a-z0-9+.-]*://#i', $url)) {
            return $url;
        }

        if ($this->baseUrl === null) {
            throw new LogicException('The page metadata base URL must be configured to make relative URLs absolute.');
        }

        return $this->baseUrl . $url;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    private function normalizeStructuredDataValue($value, string $path)
    {
        if ($value instanceof DateTimeInterface) {
            return $value->format(DATE_ATOM);
        }

        if (is_array($value)) {
            $normalized = [ ];
            foreach ($value as $key => $item) {
                $normalized[$key] = $this->normalizeStructuredDataValue($item, $path . '.' . $key);
            }
            return $normalized;
        }

        if ($value === null || is_scalar($value)) {
            return $value;
        }

        throw new InvalidArgumentException(sprintf(
            'The structured data value at "%s" must be scalar, null, an array, or an instance of DateTimeInterface; %s given.',
            $path,
            is_object($value) ? get_class($value) : gettype($value)
        ));
    }
}
