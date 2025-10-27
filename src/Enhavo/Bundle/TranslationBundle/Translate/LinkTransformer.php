<?php

namespace Enhavo\Bundle\TranslationBundle\Translate;

use Enhavo\Bundle\RoutingBundle\Repository\RouteRepository;
use Enhavo\Bundle\TranslationBundle\Translation\TranslationManager;

class LinkTransformer implements TranslateTransformInterface
{
    private string $defaultLocale;
    private RouteRepository $routeRepository;
    private TranslationManager $translationManager;
    private TranslateClientInterface $translateClient;
    private string $domains;

    public function __construct(
        string $defaultLocale,
        RouteRepository $routeRepository,
        TranslationManager $translationManager,
        TranslateClientInterface $translateClient,
        string $domains
    )
    {
        $this->defaultLocale = $defaultLocale;
        $this->routeRepository = $routeRepository;
        $this->translationManager = $translationManager;
        $this->translateClient = $translateClient;
        $this->domains = $domains;
    }

    public function transform(string $text, string $targetLang): string
    {
        $pattern = '~<a\s+[^>]*href=["\']([^"\']+)["\'][^>]*>.*?</a>~i';

        $text = preg_replace_callback($pattern, function ($matches) use ($targetLang) {
            $link = $matches[1];

            if ($this->containsDomain($link)) {
                $pattern = '~https?://[^/]+(/[^"]*)~i';

                $result = preg_replace_callback($pattern, function($matches) use ($targetLang) {
                    $path = $matches[1];

                    $pathWithLang = str_starts_with($path, "/" . $this->defaultLocale) ? $path : '/' . $this->defaultLocale . $path;
                    $route = $this->routeRepository->findBy(['staticPrefix' => $pathWithLang], null, 1);
                    if (count($route) > 0) {
                        $route = $route[0];
                        $content = $route->getContent();
                        $newPath = $this->translationManager->getProperty($content, 'route', $targetLang);
                        return str_replace($path, $newPath->getStaticPrefix(), $matches[1]);
                    }
                    return $matches[0];
                }, $link);

                return str_replace($link, $result, $matches[0]);
            }
            return $matches[0];
        }, $text);

        return $this->translateClient->translate($text, $targetLang);
    }

    private function containsDomain($inputString): bool
    {
        $domains = explode(", ", $this->domains);
        foreach ($domains as $domain) {
            if (str_contains($inputString, $domain)) {
                return true;
            }
        }
        return false;
    }
}
