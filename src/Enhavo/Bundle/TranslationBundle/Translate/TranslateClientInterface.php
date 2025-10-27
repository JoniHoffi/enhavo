<?php

namespace Enhavo\Bundle\TranslationBundle\Translate;

interface TranslateClientInterface
{
    public function translate(string $text, string $targetLanguage, array $options = []): string;
}
