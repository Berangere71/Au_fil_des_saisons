<?php

namespace App\Tests\Service;

use App\Service\WeatherService;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

final class WeatherServiceTest extends TestCase
{
    public function testItReturnsNormalizedWeatherData(): void
    {
        $client = new MockHttpClient(new MockResponse(json_encode([
            'name' => 'Lyon',
            'sys' => ['country' => 'FR'],
            'main' => ['temp' => 27.4, 'feels_like' => 28.1, 'humidity' => 61],
            'wind' => ['speed' => 3.5],
            'weather' => [['main' => 'Clear', 'description' => 'ciel dégagé', 'icon' => '01d']],
        ], JSON_THROW_ON_ERROR)));

        $weather = (new WeatherService($client, 'test-key'))->getCurrentWeather('Lyon');

        self::assertSame('Lyon', $weather['city']);
        self::assertSame(27, $weather['temperature']);
        self::assertSame(12.6, $weather['wind']);
        self::assertStringContainsString('salade fraîche', $weather['suggestion']);
    }

    public function testItExplainsAnInvalidApiKey(): void
    {
        $client = new MockHttpClient(new MockResponse(
            json_encode(['message' => 'Invalid API key'], JSON_THROW_ON_ERROR),
            ['http_code' => 401],
        ));

        $this->expectExceptionMessage('La clé OpenWeather n’est pas encore valide.');

        (new WeatherService($client, 'bad-key'))->getCurrentWeather('Paris');
    }
}
