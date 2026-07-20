<?php

namespace App\Http\Controllers\Admin;

use App\Models\BillType;
use App\Models\Department;
use App\Support\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * What the town bills for. Staff-configurable, because every municipality has
 * one charge nobody else has.
 */
class BillTypeController extends AdminController
{
    protected string $model = BillType::class;
    protected string $views = 'bill-types';
    protected string $routes = 'bill-types';
    protected string $label = 'Bill Type';
    protected array $with = ['department'];
    protected array $searchable = ['label', 'key', 'description'];
    protected array $orderBy = ['sort_order', 'asc'];

    protected function rules(Request $request, ?Model $record = null): array
    {
        $id = $record?->getKey();

        return [
            'label' => ['required', 'string', 'max:120'],
            'key' => ['nullable', 'string', 'max:64', 'alpha_dash', 'unique:bill_types,key' . ($id ? ',' . $id : '')],
            'description' => ['nullable', 'string', 'max:1000'],
            'icon' => ['nullable', 'string', 'max:40'],
            'department_id' => ['nullable', 'integer', 'exists:departments,id'],
            'min_amount' => ['nullable', 'string', 'max:20'],
            'max_amount' => ['nullable', 'string', 'max:20'],
            'sort_order' => ['nullable', 'integer', 'min:0', 'max:9999'],
        ];
    }

    protected function formData(): array
    {
        return [
            'departments' => Department::ordered()->get(['id', 'name']),
            'iconOptions' => ['file-text', 'bolt', 'clipboard', 'scale', 'building', 'home', 'shield', 'archive', 'database', 'star'],
        ];
    }

    protected function transform(array $data, Request $request, ?Model $record = null): array
    {
        $data['key'] = $data['key'] ?: Str::slug($data['label']);

        // Toggle switches post 1/0 through a hidden input, never a bare checkbox.
        $data['requires_lookup'] = $request->boolean('requires_lookup');
        $data['allows_open_payment'] = $request->boolean('allows_open_payment');
        $data['is_active'] = $request->boolean('is_active', true);

        // Amounts arrive as "25.00" and are stored as cents.
        $data['min_amount_cents'] = Money::parse($request->input('min_amount'));
        $data['max_amount_cents'] = Money::parse($request->input('max_amount'));

        unset($data['min_amount'], $data['max_amount']);

        $data['sort_order'] = (int) ($data['sort_order'] ?? 0);

        return $data;
    }
}
