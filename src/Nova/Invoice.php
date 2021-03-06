<?php

declare(strict_types=1);

namespace Tipoff\Invoices\Nova;

use Illuminate\Http\Request;
use Laravel\Nova\Fields\BelongsTo;
use Laravel\Nova\Fields\Currency;
use Laravel\Nova\Fields\Date;
use Laravel\Nova\Fields\DateTime;
use Laravel\Nova\Fields\HasMany;
use Laravel\Nova\Fields\ID;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;
use Laravel\Nova\Panel;
use Tipoff\Support\Nova\BaseResource;

class Invoice extends BaseResource
{
    public static $model = \Tipoff\Invoices\Models\Invoice::class;

    public static $title = 'invoice_number';

    public static $search = [
        'invoice_number',
    ];
    
    public static $group = 'Operations Units';

    public static function indexQuery(NovaRequest $request, $query)
    {
        if ($request->user()->hasRole([
            'Admin',
            'Owner',
            'Accountant',
            'Executive',
            'Reservation Manager',
            'Reservationist',
        ])) {
            return $query;
        }

        return $query->whereHas('order', function ($orderlocation) use ($request) {
            return $orderlocation->whereIn('location_id', $request->user()->locations->pluck('id'));
        });
    }

    public function fieldsForIndex(NovaRequest $request)
    {
        return array_filter([
            ID::make()->sortable(),
            Text::make('Invoice Number')->sortable(),
            nova('order') ? BelongsTo::make('Order', 'order', nova('order'))->sortable() : null,
            nova('user') ? BelongsTo::make('Customer', 'user', nova('user'))->sortable() : null,
            Currency::make('Amount')->asMinorUnits()->sortable(),
            Date::make('Invoiced At', 'invoiced_at')->sortable(),
        ]);
    }

    public function fields(Request $request)
    {
        return array_filter([
            Text::make('Invoice Number')->exceptOnForms(),
            nova('order') ? BelongsTo::make('Order', 'order', nova('order'))->hideWhenUpdating() : null,
            nova('user') ? BelongsTo::make('Customer', 'user', nova('user'))->exceptOnForms() : null,
            Currency::make('Amount')->asMinorUnits()
                ->step('0.01')
                ->resolveUsing(function ($value) {
                    return $value / 100;
                })
                ->fillUsing(function ($request, $model, $attribute) {
                    $model->$attribute = $request->$attribute * 100;
                }),
            Text::make('Note'),
            DateTime::make('Invoiced At', 'invoiced_at')->exceptOnForms(),
            Date::make('Due Date', 'due_at'),

            // Will need a 'Send Invoice' button

            nova('payment') ? HasMany::make('Payments', 'payments', nova('payment')) : null,

            new Panel('Data Fields', $this->dataFields()),
        ]);
    }

    protected function dataFields(): array
    {
        return array_merge(
            parent::dataFields(),
            $this->creatorDataFields(),
            $this->updaterDataFields(),
        );
    }
    
    public function filters(Request $request)
    {
        return [
            // new Tipoff\LocationsFilters\OrderLocation,
        ];
    }
}
