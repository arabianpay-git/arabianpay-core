<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $fillable = [
        'key',
        'value',
        'group',
        'type',
        'options',
        'description',
        'is_required',
        'is_encrypted',
        'order',
    ];

    protected $casts = [
        'options' => 'array',
        'is_required' => 'boolean',
        'is_encrypted' => 'boolean',
        'order' => 'integer',
    ];

    public static function getGroupedSettings()
    {
        return self::orderBy('order')->get()->groupBy('group');
    }

    public static function getByKey($key, $default = null)
    {
        $setting = self::where('key', $key)->first();
        if (! $setting) {
            return $default;
        }

        $value = $setting->is_encrypted ? decrypt($setting->value) : $setting->value;

        // decode JSON strings into array/object if possible
        if (is_string($value)) {
            $decoded = @json_decode($value, true);

            return $decoded === null ? $value : $decoded;
        }

        return $value;
    }

    /**
     * Set by key. Update or create a setting. Accept arrays (will be json-encoded).
     *
     * @param  string  $key
     * @param  mixed  $value
     * @param  string|null  $group
     * @param  string|null  $type
     * @param  bool  $is_encrypted
     * @return \App\Models\Setting
     */
    public static function setByKey($key, $value, $group = null, $type = null, $is_encrypted = false)
    {
        $toSave = json_encode($value, JSON_UNESCAPED_UNICODE);

        $attributes = [
            'value' => $is_encrypted ? encrypt($toSave) : $toSave,
            'is_encrypted' => $is_encrypted,
        ];

        if ($group !== null) {
            $attributes['group'] = $group;
        }
        if ($type !== null) {
            $attributes['type'] = $type;
        }

        // update or create ensures no duplicate rows
        return self::updateOrCreate(['key' => $key], $attributes);
    }
}
