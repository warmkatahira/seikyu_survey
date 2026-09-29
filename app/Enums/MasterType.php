<?php

namespace App\Enums;

use App\Models\Customer;
use App\Models\Employee;
use App\Models\Office;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * The three masters an administrator maintains, and the CSV layout each one round-trips.
 *
 * Exported headings are also the accepted import headings, so a file can be exported,
 * edited in Excel and imported back without renaming anything.
 */
enum MasterType: string
{
    case Offices = 'offices';
    case Employees = 'employees';
    case Customers = 'customers';

    public function label(): string
    {
        return match ($this) {
            self::Offices => '営業所マスタ',
            self::Employees => '従業員マスタ',
            self::Customers => '顧客マスタ',
        };
    }

    public function codeHeading(): string
    {
        return match ($this) {
            self::Offices => '営業所コード',
            self::Employees => '従業員コード',
            self::Customers => '顧客コード',
        };
    }

    public function nameHeading(): string
    {
        return match ($this) {
            self::Offices => '営業所名',
            self::Employees => '氏名',
            self::Customers => '顧客名',
        };
    }

    /**
     * Whether rows of this master point at an office. Customers do not: one customer can be
     * served by several offices, so the office is recorded on each answer instead.
     */
    public function hasOffice(): bool
    {
        return $this === self::Employees;
    }

    /** @return list<string> */
    public function headings(): array
    {
        return array_values(array_filter([
            $this->codeHeading(),
            $this->nameHeading(),
            $this->hasOffice() ? '所属営業所コード' : null,
            '表示順',
            '有効',
        ]));
    }

    /** @return class-string<Model> */
    public function modelClass(): string
    {
        return match ($this) {
            self::Offices => Office::class,
            self::Employees => Employee::class,
            self::Customers => Customer::class,
        };
    }

    public function query(): Builder
    {
        $query = $this->modelClass()::query()->ordered();

        return $this->hasOffice() ? $query->with('office') : $query;
    }

    /**
     * @return list<string|int>
     */
    public function toRow(Model $model): array
    {
        $row = [$model->code, $model->name];

        if ($this->hasOffice()) {
            $row[] = $model->office?->code ?? '';
        }

        $row[] = $model->sort_order;
        $row[] = $model->is_active ? '有効' : '無効';

        return $row;
    }

    public function csvFilename(): string
    {
        return sprintf('%s_%s.csv', $this->value, now()->format('Ymd_His'));
    }

    public function routeKey(): string
    {
        return "admin.{$this->value}";
    }
}
