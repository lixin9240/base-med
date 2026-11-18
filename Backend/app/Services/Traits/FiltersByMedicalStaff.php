<?php

namespace App\Services\Traits;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;

/**
 * 按「是否医护人员」筛选答题相关查询。
 *
 * 约定：1=医护；0=非医护（含未采集 null）；未传则不筛。
 */
trait FiltersByMedicalStaff
{
    /**
     * @param  Builder|QueryBuilder  $query
     */
    protected function applyMedicalStaffFilter($query, mixed $isMedicalStaff, string $column = 'is_medical_staff'): void
    {
        if ($isMedicalStaff === null || $isMedicalStaff === '') {
            return;
        }

        if ((int) $isMedicalStaff === 1) {
            $query->where($column, 1);

            return;
        }

        $query->where(function ($inner) use ($column) {
            $inner->where($column, 0)->orWhereNull($column);
        });
    }

    /**
     * 从请求/查询参数中解析筛选值；未传返回 null。
     * 注意：值为 0 时不能用 filled()，否则会被当成未传。
     */
    protected function normalizeMedicalStaffParam(array $params, string $key = 'is_medical_staff'): ?int
    {
        if (! array_key_exists($key, $params) || $params[$key] === null || $params[$key] === '') {
            return null;
        }

        return (int) $params[$key];
    }
}
