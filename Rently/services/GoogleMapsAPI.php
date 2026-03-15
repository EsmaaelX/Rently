<?php
/**
 * GoogleMapsAPI Mock
 * Placeholder for Google Maps geocoding and map embed.
 * Replace mock logic with real Google Maps API calls when ready.
 *
 * Usage:
 *   $maps = new GoogleMapsAPI();
 *   $coords = $maps->geocode('123 Main St, Tel Aviv');
 */
class GoogleMapsAPI
{
    // ──────────────────────────────────────────
    // DROP YOUR REAL GOOGLE MAPS API KEY HERE:
    private string $apiKey = 'YOUR_GOOGLE_MAPS_API_KEY';
    // ──────────────────────────────────────────

    /**
     * Geocode an address → lat/lng (mock).
     * In production, call:
     *   https://maps.googleapis.com/maps/api/geocode/json?address=...&key=...
     *
     * @param string $address
     * @return array ['lat' => float, 'lng' => float]
     */
    public function geocode(string $address): array
    {
        // ── MOCK IMPLEMENTATION ──
        // Returns a default position (Tel Aviv city center) with slight randomness
        return [
            'lat' => 32.0853 + (mt_rand(-100, 100) / 10000),
            'lng' => 34.7818 + (mt_rand(-100, 100) / 10000),
            'formatted_address' => $address ?: 'Mock Address, Tel Aviv',
        ];
    }

    /**
     * Get a static map image URL (mock).
     * In production use the real Static Maps API endpoint.
     */
    public function getStaticMapUrl(float $lat, float $lng, int $zoom = 14, string $size = '600x300'): string
    {
        // ── MOCK — returns an OpenStreetMap tile preview ──
        // When you have a real key, use:
        // return "https://maps.googleapis.com/maps/api/staticmap?center={$lat},{$lng}&zoom={$zoom}&size={$size}&key={$this->apiKey}";
        return "https://staticmap.openstreetmap.de/staticmap.php?center={$lat},{$lng}&zoom={$zoom}&size={$size}&maptype=mapnik";
    }

    /**
     * Get an embeddable map iframe URL (mock).
     */
    public function getEmbedUrl(float $lat, float $lng): string
    {
        // When you add a real key, switch to the Google Maps Embed API:
        // return "https://www.google.com/maps/embed/v1/view?key={$this->apiKey}&center={$lat},{$lng}&zoom=15";
        return "https://www.openstreetmap.org/export/embed.html?bbox=" .
               ($lng - 0.01) . "," . ($lat - 0.01) . "," .
               ($lng + 0.01) . "," . ($lat + 0.01) .
               "&layer=mapnik&marker={$lat},{$lng}";
    }
}
