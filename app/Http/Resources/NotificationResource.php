<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class NotificationResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'type' => $this->type,
            'data' => $this->filterNotificationData($this->data),
            'read_at' => $this->read_at,
            'created_at' => $this->created_at,
        ];
    }

    private function filterNotificationData(?array $data): array
    {
        if (empty($data) || ! is_array($data)) {
            return [];
        }

        $allowed = ['title', 'description', 'type', 'click_action', 'mobile_screen', 'order_id', 'reference_id', 'transfer_request_id', 'model_type', 'model_id', 'status', 'screen'];
        $filtered = [];

        foreach ($allowed as $key) {
            if (array_key_exists($key, $data)) {
                $filtered[$key] = $data[$key];
            }
        }

        // Mask any remaining keys that look like PII
        $piiKeys = ['email', 'phone', 'name', 'first_name', 'last_name', 'address', 'national_id', 'iqama', 'passport', 'ssn'];
        foreach ($data as $key => $value) {
            if (in_array($key, $allowed, true)) {
                continue;
            }
            foreach ($piiKeys as $piiKey) {
                if (str_contains(strtolower($key), $piiKey)) {
                    $filtered[$key] = is_string($value) ? $this->maskValue($value) : $value;
                    break;
                }
            }
        }

        return $filtered;
    }

    private function maskValue(string $value): string
    {
        $value = trim($value);
        $len = mb_strlen($value);
        if ($len <= 4) {
            return str_repeat('*', max(1, $len));
        }

        return str_repeat('*', $len - 2).mb_substr($value, -2);
    }
}
