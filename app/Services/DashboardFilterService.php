<?php

namespace App\Services;

use App\Models\Crop;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class DashboardFilterService
{
    /**
     * Common validation rules for dashboard filter requests.
     */
    public function validationRules(): array
    {
        return [
            'farm_id' => [
                'nullable',
                'integer',
                'exists:farms,id',
            ],
            'plot_id' => [
                'nullable',
                'integer',
                'exists:plots,id',
            ],
            'crop_name' => [
                'nullable',
                'string',
                'max:255',
            ],
            'season' => [
                'nullable',
                'string',
                'max:255',
            ],
            'start_date' => [
                'nullable',
                'date',
            ],
            'end_date' => [
                'nullable',
                'date',
                'after_or_equal:start_date',
            ],
        ];
    }

    /**
     * Validate and normalize dashboard filter values.
     */
    public function getFilters(array $validated): array
    {
        return [
            'farm_id' => !empty($validated['farm_id'])
                ? (int) $validated['farm_id']
                : null,

            'plot_id' => !empty($validated['plot_id'])
                ? (int) $validated['plot_id']
                : null,

            'crop_name' => !empty($validated['crop_name'])
                ? (string) $validated['crop_name']
                : null,

            'season' => !empty($validated['season'])
                ? (string) $validated['season']
                : null,

            'start_date' => $validated['start_date'] ?? null,

            'end_date' => $validated['end_date'] ?? null,
        ];
    }

    /**
     * Apply common dashboard filters to a Crop query.
     */
    public function applyCropFilters(
        Builder $query,
        array $filters
    ): Builder {
        return $query
            ->when(
                !empty($filters['farm_id']),
                function (Builder $query) use ($filters) {
                    $query->whereHas(
                        'plot',
                        function (Builder $plotQuery) use ($filters) {
                            $plotQuery->where(
                                'farm_id',
                                $filters['farm_id']
                            );
                        }
                    );
                }
            )
            ->when(
                !empty($filters['plot_id']),
                function (Builder $query) use ($filters) {
                    $query->where(
                        'plot_id',
                        $filters['plot_id']
                    );
                }
            )
            ->when(
                !empty($filters['crop_name']),
                function (Builder $query) use ($filters) {
                    $query->where(
                        'crop_name',
                        $filters['crop_name']
                    );
                }
            )
            ->when(
                !empty($filters['season']),
                function (Builder $query) use ($filters) {
                    $query->where(
                        'season',
                        $filters['season']
                    );
                }
            );
    }

    /**
     * Return crop IDs after applying dashboard filters.
     *
     * @return \Illuminate\Support\Collection<int, int>
     */
    public function getFilteredCropIds(array $filters): Collection
    {
        $query = Crop::query();

        $this->applyCropFilters(
            $query,
            $filters
        );

        return $query->pluck('id');
    }

    /**
     * Apply common expense filters based on active dashboard filters.
     *
     * Rules:
     * - When filtering by plot_id, crop_name, or season: include only expenses linked to filtered crops.
     * - When filtering by farm_id only: include all expenses for that farm (farm-wide + crop-specific).
     * - When no filters are provided: include expenses linked to filtered crops.
     */
    public function applyExpenseFilters(
        Builder $query,
        array $filters,
        Collection|array $cropIds
    ): Builder {
        if (
            !empty($filters['plot_id']) ||
            !empty($filters['crop_name']) ||
            !empty($filters['season'])
        ) {
            $query->whereIn('crop_id', $cropIds);
        } elseif (!empty($filters['farm_id'])) {
            $query->where('farm_id', $filters['farm_id']);
        } else {
            $query->whereIn('crop_id', $cropIds);
        }

        return $query;
    }
}