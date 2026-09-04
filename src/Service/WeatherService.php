<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherService
{
    private const ENDPOINT = 'https://api.openweathermap.org/data/2.5/weather';
    private const FORECAST_ENDPOINT = 'https://api.openweathermap.org/data/2.5/forecast';

    public function __construct(
        private readonly HttpClientInterface $httpClient,
        private readonly string $openWeatherApiKey,
    ) {
    }

    /**
     * @return array{
     *     city: string,
     *     country: string,
     *     temperature: int,
     *     temperatureMin: int,
     *     temperatureMax: int,
     *     feelsLike: int,
     *     humidity: int,
     *     wind: float,
     *     description: string,
     *     condition: string,
     *     icon: string,
     *     suggestion: string
     * }
     */
    public function getCurrentWeather(string $city): array
    {
        if ('' === trim($this->openWeatherApiKey)) {
            throw new \RuntimeException('La clé OpenWeather n’est pas configurée.');
        }

        try {
            $response = $this->httpClient->request('GET', self::ENDPOINT, [
                'query' => [
                    'q' => trim($city),
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
                'timeout' => 8,
            ]);
            $statusCode = $response->getStatusCode();
            $data = $response->toArray(false);
        } catch (TransportExceptionInterface) {
            throw new \RuntimeException('Le service météo est momentanément inaccessible.');
        }

        if (401 === $statusCode) {
            throw new \RuntimeException('La clé OpenWeather n’est pas encore valide.');
        }

        if (404 === $statusCode) {
            throw new \RuntimeException('Cette ville est introuvable.');
        }

        if (200 !== $statusCode || !isset($data['main'], $data['weather'][0])) {
            throw new \RuntimeException('Impossible de récupérer la météo pour le moment.');
        }

        $condition = (string) ($data['weather'][0]['main'] ?? '');
        $temperature = (int) round((float) $data['main']['temp']);
        [$temperatureMin, $temperatureMax] = $this->getTodayTemperatureRange(
            $city,
            (int) ($data['timezone'] ?? 0),
            (float) ($data['main']['temp_min'] ?? $temperature),
            (float) ($data['main']['temp_max'] ?? $temperature),
        );

        return [
            'city' => (string) ($data['name'] ?? $city),
            'country' => (string) ($data['sys']['country'] ?? ''),
            'temperature' => $temperature,
            'temperatureMin' => $temperatureMin,
            'temperatureMax' => $temperatureMax,
            'feelsLike' => (int) round((float) ($data['main']['feels_like'] ?? $temperature)),
            'humidity' => (int) ($data['main']['humidity'] ?? 0),
            'wind' => round((float) ($data['wind']['speed'] ?? 0) * 3.6, 1),
            'description' => ucfirst((string) ($data['weather'][0]['description'] ?? '')),
            'condition' => $condition,
            'icon' => (string) ($data['weather'][0]['icon'] ?? '01d'),
            'suggestion' => $this->getRecipeSuggestion($condition, $temperature, $temperatureMax),
        ];
    }

    /**
     * Calcule les températures minimale et maximale prévues pour la journée
     * locale de la ville. Les valeurs de l'observation courante servent de repli.
     *
     * @return array{0: int, 1: int}
     */
    private function getTodayTemperatureRange(
        string $city,
        int $timezoneOffset,
        float $fallbackMin,
        float $fallbackMax,
    ): array {
        $temperaturesMin = [$fallbackMin];
        $temperaturesMax = [$fallbackMax];

        try {
            $response = $this->httpClient->request('GET', self::FORECAST_ENDPOINT, [
                'query' => [
                    'q' => trim($city),
                    'appid' => $this->openWeatherApiKey,
                    'units' => 'metric',
                    'lang' => 'fr',
                ],
                'timeout' => 8,
            ]);

            if (200 !== $response->getStatusCode()) {
                return [(int) round(min($temperaturesMin)), (int) round(max($temperaturesMax))];
            }

            $forecast = $response->toArray(false);
            $timezoneOffset = (int) ($forecast['city']['timezone'] ?? $timezoneOffset);
            $localToday = gmdate('Y-m-d', time() + $timezoneOffset);

            foreach ($forecast['list'] ?? [] as $period) {
                $timestamp = (int) ($period['dt'] ?? 0);
                if (0 === $timestamp || gmdate('Y-m-d', $timestamp + $timezoneOffset) !== $localToday) {
                    continue;
                }

                if (isset($period['main']['temp_min'])) {
                    $temperaturesMin[] = (float) $period['main']['temp_min'];
                }
                if (isset($period['main']['temp_max'])) {
                    $temperaturesMax[] = (float) $period['main']['temp_max'];
                }
            }
        } catch (TransportExceptionInterface) {
            // L'observation courante reste affichée si les prévisions sont indisponibles.
        }

        return [
            (int) round(min($temperaturesMin)),
            (int) round(max($temperaturesMax)),
        ];
    }

    private function getRecipeSuggestion(string $condition, int $temperature, int $temperatureMax): string
    {
        if (in_array($condition, ['Rain', 'Drizzle', 'Thunderstorm', 'Snow'], true) || $temperature < 10) {
            return 'Un plat mijoté ou une soupe de saison sera parfait aujourd’hui.';
        }

        if ($temperatureMax >= 25) {
            return 'Privilégiez une salade fraîche, des fruits de saison ou une soupe froide.';
        }

        return 'Une tarte de saison, un poisson en papillote ou un plat rôti conviendra bien à cette journée douce.';
    }
}
