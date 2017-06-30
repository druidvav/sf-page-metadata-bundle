<?php
namespace Druidvav\PageMetadataBundle;

use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Routing\RouterInterface;

class Breadcrumb
{
    /**
     * @var RouterInterface
     */
    private $router;

    /**
     * @var TranslatorInterface
     */
    private $translator;

    private $transDomain = null;

    protected $url;
    protected $text;

    public function __construct(RouterInterface $router, TranslatorInterface $translator)
    {
        $this->router = $router;
        $this->translator = $translator;
    }

    public function setTransDomain($domain)
    {
        $this->transDomain = $domain;
    }

    public function setRoute($route, array $parameters = array(), $referenceType = RouterInterface::ABSOLUTE_PATH)
    {
        return $this->setUrl($this->router->generate($route, $parameters, $referenceType));
    }

    public function setUrl($url)
    {
        $this->url = $url;
        return $this;
    }

    public function setText($text, array $parameters = array(), $domain = null)
    {
        $this->text = $this->translator->trans($text, $parameters, $domain ?: $this->transDomain);
        return $this;
    }

    public function setRawText($text)
    {
        $this->text = $text;
        return $this;
    }

    public function getUrl()
    {
        return $this->url;
    }

    public function getText()
    {
        return $this->text;
    }
}