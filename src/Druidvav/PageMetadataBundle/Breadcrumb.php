<?php
namespace Druidvav\PageMetadataBundle;

use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RouterInterface;

class Breadcrumb
{
    private RouterInterface $router;

    protected ?string $transDomain = null;

    protected string $url;
    protected string $text;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function setTransDomain(string $domain): void
    {
        $this->transDomain = $domain;
    }

    public function setRoute(string $route, array $parameters = [ ], int $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH): self
    {
        return $this->setUrl($this->router->generate($route, $parameters, $referenceType));
    }

    public function setUrl(string $url): self
    {
        $this->url = $url;
        return $this;
    }

    public function setRawText(string $text): self
    {
        $this->text = $text;
        return $this;
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getText(): string
    {
        return $this->text;
    }
}