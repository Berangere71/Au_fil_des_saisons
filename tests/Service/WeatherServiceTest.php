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
        $todayAtNoon = strtotime(gmdate('Y-m-d').' 12:00:00 UTC');
        $client = new MockHttpClient([
            new MockResponse(json_encode([
                'name' => 'Lyon',
                'timezone' => 0,
                'sys' => ['country' => 'FR'],
                'main' => ['temp' => 27.4, 'temp_min' => 25.2, 'temp_max' => 28.1, 'feels_like' => 28.1, 'humidity' => 61],
                'wind' => ['speed' => 3.5],
                'weather' => [['main' => 'Clear', 'description' => 'ciel dégagé', 'icon' => '01d']],
            ], JSON_THROW_ON_ERROR)),
            new MockResponse(json_encode([
                'city' => ['timezone' => 0],
                'list' => [
                    ['dt' => $todayAtNoon, 'main' => ['temp_min' => 18.4, 'temp_max' => 30.2]],
                ],
            ], JSON_THROW_ON_ERROR)),
        ]);

        $weather = (new WeatherService($client, 'test-key'))->getCurrentWeather('Lyon');

        self::assertSame('Lyon', $weather['city']);
        self::assertSame(27, $weather['temperature']);
        self::assertSame(18, $weather['temperatureMin']);
        self::assertSame(30, $weather['temperatureMax']);
        self::assertSame('Clear', $weather['condition']);
        self::assertStringContainsString('salade fraîche', $weather['suggestion']);
        self::assertSame(12.6, $weather['wind']);
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

    public function testItRejectsAnEmptyApiKeyWithoutCallingTheApi(): void
    {
        $client = new MockHttpClient(static function (): MockResponse {
            self::fail('Aucune requête HTTP ne doit être envoyée sans clé API.');
        });

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('La clé OpenWeather n’est pas configurée.');

        (new WeatherService($client, '  '))->getCurrentWeather('Paris');
    }

    public function testItExplainsAnUnknownCity(): void
    {
        $client = new MockHttpClient(new MockResponse(
            json_encode(['message' => 'city not found'], JSON_THROW_ON_ERROR),
            ['http_code' => 404],
        ));

        $this->expectExceptionMessage('Cette ville est introuvable.');

        (new WeatherService($client, 'test-key'))->getCurrentWeather('Ville inconnue');
    }

    public function testItHandlesANetworkFailure(): void
    {
        $client = new MockHttpClient(new MockResponse('', ['error' => 'Network unavailable']));

        $this->expectExceptionMessage('Le service météo est momentanément inaccessible.');

        (new WeatherService($client, 'test-key'))->getCurrentWeather('Paris');
    }

    public function testItRejectsAnIncompleteSuccessfulResponse(): void
    {
        $client = new MockHttpClient(new MockResponse(
            json_encode(['name' => 'Paris'], JSON_THROW_ON_ERROR),
            ['http_code' => 200],
        ));

        $this->expectExceptionMessage('Impossible de récupérer la météo pour le moment.');

        (new WeatherService($client, 'test-key'))->getCurrentWeather('Paris');
    }

    public function testItUsesCurrentTemperaturesWhenForecastIsUnavailable(): void
    {
        $client = new MockHttpClient([
            new MockResponse(json_encode([
                'name' => 'Toulouse',
                'timezone' => 7200,
                'sys' => ['country' => 'FR'],
                'main' => ['temp' => 14.2, 'temp_min' => 11.4, 'temp_max' => 16.6, 'humidity' => 70],
                'weather' => [['main' => 'Clouds', 'description' => 'nuageux', 'icon' => '03d']],
            ], JSON_THROW_ON_ERROR)),
            new MockResponse('', ['error' => 'Forecast timeout']),
        ]);

        $weather = (new WeatherService($client, 'test-key'))->getCurrentWeather(' Toulouse ');

        self::assertSame(11, $weather['temperatureMin']);
        self::assertSame(17, $weather['temperatureMax']);
        self::assertSame('Toulouse', $weather['city']);
    }

    public function testItSendsExpectedParametersToOpenWeather(): void
    {
        $requests = [];
        $client = new MockHttpClient(static function (string $method, string $url, array $options) use (&$requests): MockResponse {
            $requests[] = [$method, $url, $options];

            if (str_contains($url, '/forecast')) {
                return new MockResponse(json_encode(['city' => ['timezone' => 0], 'list' => []], JSON_THROW_ON_ERROR));
            }

            return new MockResponse(json_encode([
                'name' => 'Lille',
                'main' => ['temp' => 12, 'temp_min' => 10, 'temp_max' => 14],
                'weather' => [['main' => 'Clouds']],
            ], JSON_THROW_ON_ERROR));
        });

        (new WeatherService($client, 'secret-test'))->getCurrentWeather(' Lille ');

        self::assertCount(2, $requests);
        foreach ($requests as [$method, $url, $options]) {
            self::assertSame('GET', $method);
            self::assertStringStartsWith('https://api.openweathermap.org/data/2.5/', $url);
            self::assertSame('Lille', $options['query']['q']);
            self::assertSame('secret-test', $options['query']['appid']);
            self::assertSame('metric', $options['query']['units']);
            self::assertSame('fr', $options['query']['lang']);
            self::assertSame(8.0, $options['timeout']);
        }
    }
}
