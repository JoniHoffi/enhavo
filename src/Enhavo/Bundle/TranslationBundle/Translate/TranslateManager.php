<?php

namespace Enhavo\Bundle\TranslationBundle\Translate;

use Doctrine\ORM\EntityManagerInterface;
use Enhavo\Bundle\AppBundle\Resource\ResourceManager;
use Enhavo\Bundle\RoutingBundle\Repository\RouteRepository;
use Enhavo\Bundle\TranslationBundle\Translation\TranslationManager;
use Enhavo\Component\Metadata\MetadataRepository;

class TranslateManager
{

    public function __construct(
        private TranslationManager $translationManager,
        private string $defaultLocale,
        private RouteRepository $routeRepository,
        private array $transformers
    )
    {}

    public function translateMetadataProperties($resource, $metadata): void
    {
        foreach ($metadata->getProperties() as $property) {
            $textToTranslate = $this->translationManager->getProperty($resource, $property->getProperty(), $this->defaultLocale);

            if ($property->getProperty() == 'ctaLink') {
                if ($textToTranslate != null) {
                    $this->translateCtaProperty($resource, $property, $textToTranslate);
                }
            } elseif (is_array($textToTranslate)) {
                $this->translateArrayProperty($resource, $property, $textToTranslate);
            } elseif (is_string($textToTranslate)) {
                $this->translateSingleProperty($resource, $property, $textToTranslate);
            }
        }
    }

    private function translateArrayProperty($resource, $property, array $textArray): void
    {
        foreach ($textArray as $key => $value) {
            if ($value !== null) {
                foreach ($this->translationManager->getLocales() as $locale) {
                    if ($locale !== $this->defaultLocale) {
                        if ($this->translationManager->getProperty($resource, $property->getProperty(), $locale) === null) {
                            $translatedText = $this->translate($value, $locale);
                            $this->translationManager->setTranslation($resource, $property->getProperty(), $locale, $translatedText);
                        }
                    }
                }
            }
        }
    }

    private function translateSingleProperty($resource, $property, $textToTranslate): void
    {
        foreach ($this->translationManager->getLocales() as $locale) {
            if ($locale !== $this->defaultLocale) {
                if ($this->translationManager->getProperty($resource, $property->getProperty(), $locale) === null) {
                    $translatedText = $this->translate($textToTranslate, $locale);
                    $this->translationManager->setTranslation($resource, $property->getProperty(), $locale, $translatedText);
                }
            }
        }
    }

    private function translateCtaProperty($resource, $property, $textToTranslate): void
    {
        foreach ($this->translationManager->getLocales() as $locale) {
            if ($locale !== $this->defaultLocale) {
                if ($this->translationManager->getProperty($resource, $property->getProperty(), $locale) === null) {
                    $translatedText = $this->translateCtas($textToTranslate, $locale);
                    $this->translationManager->setTranslation($resource, $property->getProperty(), $locale, $translatedText);
                }
            }
        }
    }

    private function translateCtas(string $path, string $targetLang): string
    {
        $pathWithLang = str_starts_with($path, "/" . $this->defaultLocale) ? $path : '/' . $this->defaultLocale . $path;
        $route = $this->routeRepository->findBy(['staticPrefix' => $pathWithLang], null, 1);
        if (count($route) > 0) {
            $route = $route[0];
            $content = $route->getContent();
            $newPath = $this->translationManager->getProperty($content, 'route', $targetLang);
            return str_replace($path, $newPath->getStaticPrefix(), $path);
        }
        return $path;
    }

    private function translate(string $text, string $targetLang)
    {
        foreach ($this->transformers as $transformer) {
            $transformedText = $transformer->transform($text, $targetLang);
        }
        //@TODO Rename function and execute all TranslateTransformInterfaces
    }
}
