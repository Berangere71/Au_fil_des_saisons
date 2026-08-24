<?php

namespace App\Service;

use Symfony\Contracts\HttpClient\Exception\TransportExceptionInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

final class WeatherService
{
    private const ENDPOINT = 'https://api.openweathermap.org/data/2.5/weather';

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

        return [
            'city' => (string) ($data['name'] ?? $city),
            'country' => (string) ($data['sys']['country'] ?? ''),
            'temperature' => $temperature,
            'temperatureMin' => (int) round((float) ($data['main']['temp_min'] ?? $temperature)),
            'temperatureMax' => (int) round((float) ($data['main']['temp_max'] ?? $temperature)),
            'feelsLike' => (int) round((float) ($data['main']['feels_like'] ?? $temperature)),
            'humidity' => (int) ($data['main']['humidity'] ?? 0),
            'wind' => round((float) ($data['wind']['speed'] ?? 0) * 3.6, 1),
            'description' => ucfirst((string) ($data['weather'][0]['description'] ?? '')),
            'icon' => (string) ($data['weather'][0]['icon'] ?? '01d'),
            'suggestion' => $this->getRecipeSuggestion($condition, $temperature),
        ];
    }

    private function getRecipeSuggestion(string $condition, int $temperature): string
    {
        if (in_array($condition, ['Rain', 'Drizzle', 'Thunderstorm', 'Snow'], true) || $temperature < 10) {
            return 'Un plat mijoté ou une soupe de saison sera parfait aujourd’hui.';
        }

        if ($temperature >= 25) {
            return 'Privilégiez une salade fraîche, des fruits de saison ou une soupe froide.';
        }

        return 'Une recette légère avec les produits du moment accompagnera bien cette météo.';
    }
}
