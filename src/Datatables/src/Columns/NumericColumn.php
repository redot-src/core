<?php

namespace Redot\Datatables\Columns;

use Illuminate\Database\Eloquent\Model;

class NumericColumn extends Column
{
    /**
     * The column's precision.
     */
    public ?int $precision = null;

    /**
     * Set the column's precision.
     */
    public function precision(int $precision): static
    {
        $this->precision = $precision;

        return $this;
    }

    /**
     * Default getter for the column.
     */
    protected function defaultGetter(mixed $value, Model $row): mixed
    {
        if (is_null($value)) {
            return null;
        }

        if ($this->precision !== null) {
            return number_format($value, $this->precision);
        }

        return $value;
    }
}
