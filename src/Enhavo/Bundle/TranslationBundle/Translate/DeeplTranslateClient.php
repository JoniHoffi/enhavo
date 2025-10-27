<?php

namespace Enhavo\Bundle\TranslationBundle\Translate;

use Symfony\Component\HttpClient\Exception\TimeoutException;
use Symfony\Contracts\HttpClient\Exception\ClientExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\DecodingExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\RedirectionExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\ServerExceptionInterface;
use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class DeeplTranslateClient implements TranslateClientInterface
{
    private string $apiKey;
    private HttpClientInterface $client;

    public function __construct(
        string $apiKey,
        HttpClientInterface $client,
    )
    {
        $this->apiKey = $apiKey;
        $this->client = $client;
    }

    public function translate(string $text, string $targetLanguage, array $options = []): string
    {
        try {
            $body = [
                'text' => $text,
                'target_lang' => $targetLanguage,
                'tag_handling' => 'html'
            ];
            if (!empty($options['source_lang'])) {
                $body['source_lang'] = $options['source_lang'];
            }
            $response = $this->client->request('POST', 'https://api.deepl.com/v2/translate', [
                'headers' => [
                    'Authorization' => 'DeepL-Auth-Key ' . $this->apiKey,
                ],
                'body' => $body,
                'timeout' => 3.5
            ]);

            return $response->toArray()['translations'][0]['text'];
        } catch (TransportExceptionInterface | ClientExceptionInterface | DecodingExceptionInterface | RedirectionExceptionInterface | ServerExceptionInterface $e) {
            throw new TimeoutException($e->getMessage(), $e->getCode());
        }
    }
}
