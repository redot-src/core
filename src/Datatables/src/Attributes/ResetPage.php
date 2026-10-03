<?php

namespace Redot\Datatables\Attributes;

use Attribute;
use Closure;
use Livewire\Attribute as LivewireAttribute;

#[Attribute(Attribute::TARGET_PROPERTY)]
class ResetPage extends LivewireAttribute
{
    /**
     * Reset pagination after the property or one of its nested values changes.
     */
    public function update(): Closure
    {
        return fn () => $this->getComponent()->resetPage();
    }
}
