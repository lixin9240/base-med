<?php

namespace Tests\Unit;

use App\Services\Traits\FiltersByMedicalStaff;
use Illuminate\Database\Eloquent\Builder;
use PHPUnit\Framework\TestCase;

/**
 * 医护筛选 Trait 单元测试（不依赖数据库）
 */
class FiltersByMedicalStaffTest extends TestCase
{
    private function subject(): object
    {
        return new class {
            use FiltersByMedicalStaff;

            public function normalize(array $params): ?int
            {
                return $this->normalizeMedicalStaffParam($params);
            }

            public function apply($query, mixed $value, string $column = 'is_medical_staff'): void
            {
                $this->applyMedicalStaffFilter($query, $value, $column);
            }
        };
    }

    public function test_normalize_returns_null_when_absent(): void
    {
        $this->assertNull($this->subject()->normalize([]));
        $this->assertNull($this->subject()->normalize(['is_medical_staff' => null]));
        $this->assertNull($this->subject()->normalize(['is_medical_staff' => '']));
    }

    public function test_normalize_keeps_zero_as_non_medical(): void
    {
        $this->assertSame(0, $this->subject()->normalize(['is_medical_staff' => 0]));
        $this->assertSame(0, $this->subject()->normalize(['is_medical_staff' => '0']));
    }

    public function test_normalize_maps_one_to_medical(): void
    {
        $this->assertSame(1, $this->subject()->normalize(['is_medical_staff' => 1]));
        $this->assertSame(1, $this->subject()->normalize(['is_medical_staff' => '1']));
    }

    public function test_apply_filters_medical_staff_equals_one(): void
    {
        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->getMock();

        $query->expects($this->once())
            ->method('where')
            ->with('is_medical_staff', 1)
            ->willReturnSelf();

        $this->subject()->apply($query, 1);
    }

    public function test_apply_non_medical_includes_zero_and_null(): void
    {
        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->getMock();

        $query->expects($this->once())
            ->method('where')
            ->with($this->callback(function ($arg) {
                return is_callable($arg);
            }))
            ->willReturnSelf();

        $this->subject()->apply($query, 0);
    }

    public function test_apply_skips_when_filter_absent(): void
    {
        $query = $this->getMockBuilder(Builder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['where'])
            ->getMock();

        $query->expects($this->never())->method('where');

        $this->subject()->apply($query, null);
    }
}
