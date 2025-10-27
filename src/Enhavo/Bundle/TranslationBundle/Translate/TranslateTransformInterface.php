<?php

namespace Enhavo\Bundle\TranslationBundle\Translate;

interface TranslateTransformInterface
{
    public function transform(string $text, string $targetLang): string;
}
