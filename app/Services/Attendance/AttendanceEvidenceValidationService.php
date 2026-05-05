<?php

namespace App\Services\Attendance;

use App\Models\AttendanceSite;
use App\Models\AttendanceSiteWifiRule;

class AttendanceEvidenceValidationService
{
    /**
     * @param  array<string, mixed>  $network
     * @param  array<string, mixed>  $location
     * @return array{
     *   valid: bool,
     *   reason_code: string|null,
     *   reason_detail: string|null,
     *   distance_m: float|null,
     *   wifi_rule_id: int|null,
     *   site_id: int|null
     * }
     */
    public function validate(int $attendanceSiteId, array $network, array $location): array
    {
        $site = AttendanceSite::query()
            ->where('id', $attendanceSiteId)
            ->where('is_active', true)
            ->first();

        if (! $site) {
            return $this->rejected('SITE_NOT_ACTIVE', 'Titik absensi tidak aktif atau tidak ditemukan.');
        }

        $ssid = (string) ($network['ssid'] ?? '');
        $bssid = (string) ($network['bssid'] ?? '');
        $localIp = (string) ($network['local_ip'] ?? '');

        if ($ssid === '' && $bssid === '') {
            return $this->rejected('WIFI_NOT_CONNECTED', 'Data Wi-Fi tidak tersedia.');
        }

        $wifiRules = AttendanceSiteWifiRule::query()
            ->where('attendance_site_id', $site->id)
            ->where('is_active', true)
            ->get();

        $matchedWifiRule = $wifiRules->first(function (AttendanceSiteWifiRule $rule) use ($ssid, $bssid, $localIp): bool {
            if ($rule->ssid !== $ssid) {
                return false;
            }

            if ($rule->bssid && strcasecmp($rule->bssid, $bssid) !== 0) {
                return false;
            }

            if ($rule->ip_subnet && (! filter_var($localIp, FILTER_VALIDATE_IP) || ! $this->isIpInSubnet($localIp, $rule->ip_subnet))) {
                return false;
            }

            return true;
        });

        if (! $matchedWifiRule) {
            return $this->rejected('WIFI_NOT_MATCHED', 'Jaringan Wi-Fi tidak sesuai aturan sekolah.');
        }

        if (($location['is_mock'] ?? false) === true) {
            return $this->rejected('MOCK_LOCATION_DETECTED', 'Lokasi terindikasi mock location.');
        }

        $distance = $this->haversineDistanceMeters(
            (float) $site->latitude,
            (float) $site->longitude,
            (float) ($location['latitude'] ?? 0),
            (float) ($location['longitude'] ?? 0),
        );

        if ($distance > $site->radius_m) {
            return [
                'valid' => false,
                'reason_code' => 'OUT_OF_RADIUS',
                'reason_detail' => 'Lokasi di luar radius absensi.',
                'distance_m' => $distance,
                'wifi_rule_id' => $matchedWifiRule->id,
                'site_id' => $site->id,
            ];
        }

        return [
            'valid' => true,
            'reason_code' => null,
            'reason_detail' => null,
            'distance_m' => $distance,
            'wifi_rule_id' => $matchedWifiRule->id,
            'site_id' => $site->id,
        ];
    }

    /**
     * @return array{
     *   valid: bool,
     *   reason_code: string,
     *   reason_detail: string,
     *   distance_m: float|null,
     *   wifi_rule_id: int|null,
     *   site_id: int|null
     * }
     */
    private function rejected(string $reasonCode, string $reasonDetail): array
    {
        return [
            'valid' => false,
            'reason_code' => $reasonCode,
            'reason_detail' => $reasonDetail,
            'distance_m' => null,
            'wifi_rule_id' => null,
            'site_id' => null,
        ];
    }

    private function haversineDistanceMeters(float $lat1, float $lon1, float $lat2, float $lon2): float
    {
        $earthRadius = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLon = deg2rad($lon2 - $lon1);

        $a = sin($dLat / 2) * sin($dLat / 2)
            + cos(deg2rad($lat1)) * cos(deg2rad($lat2))
            * sin($dLon / 2) * sin($dLon / 2);
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    private function isIpInSubnet(string $ip, string $cidr): bool
    {
        if (! str_contains($cidr, '/')) {
            return false;
        }

        [$subnet, $bits] = explode('/', $cidr, 2);
        $bits = (int) $bits;
        if ($bits < 0 || $bits > 32) {
            return false;
        }

        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        if ($ipLong === false || $subnetLong === false) {
            return false;
        }

        $mask = -1 << (32 - $bits);

        return ($ipLong & $mask) === ($subnetLong & $mask);
    }
}
