<?php

namespace App\Http\Requests;

use App\Models\Order;
use Illuminate\Foundation\Http\FormRequest;

class UpdateOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        if (! $user || ! hasSensitivePermission('order_management')) {
            return false;
        }

        if ($user->user_type === 'admin') {
            return true;
        }

        $order = Order::find($this->route('id'));

        return $order && $order->assigned_to === $user->id;
    }

    public function rules(): array
    {
        return [
            'delivery_status' => 'nullable|in:pending,shipped,delivered,returned',
            'general_status' => 'nullable|in:accepted,processing,cancelled,failed',
        ];
    }
}
