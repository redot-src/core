<?php

namespace Redot\Datatables\Actions;

use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

class BulkAction extends Action
{
    /**
     * Determine if row should be pushed to parameters for SubstituteBindings middleware.
     */
    public bool $bounded = false;

    /**
     * Determine if the action is expanded.
     */
    public bool $expanded = true;

    /**
     * The request key holding the selected row keys.
     */
    public string $keys = 'keys';

    /**
     * The currently selected row keys, filled by the datatable before rendering.
     */
    public array $selected = [];

    /**
     * Create a new delete bulk action instance.
     */
    public static function delete(?string $route = null, array $parameters = []): static
    {
        $action = static::preset(__('datatables::datatable.actions.delete'), 'fas fa-trash-alt', $route, $parameters)
            ->method('delete')
            ->confirmable(message: __('datatables::datatable.bulk.confirm'));

        return $route ? $action : $action->action('delete', fn (Collection $records) => $records->each->delete());
    }

    /**
     * Create a new restore bulk action instance.
     */
    public static function restore(?string $route = null, array $parameters = []): static
    {
        $action = static::preset(__('datatables::datatable.actions.restore'), 'fas fa-trash-restore', $route, $parameters)
            ->method('post')
            ->confirmable(message: __('datatables::datatable.bulk.confirm'));

        return $route ? $action : $action->action('restore', fn (Collection $records) => $records->each->restore());
    }

    /**
     * Set the request key holding the selected row keys.
     */
    public function keys(string $keys): static
    {
        $this->keys = $keys;

        return $this;
    }

    /**
     * Set the currently selected row keys.
     */
    public function selected(array $selected): static
    {
        $this->selected = $selected;

        return $this;
    }

    /**
     * Prepare the attributes before building.
     */
    protected function prepareAttributes(?Model $row = null): void
    {
        // Route driven bulk actions have no row to bind to, they carry the
        // current selection in the request payload instead.
        if ($this->route) {
            $this->body[$this->keys] = $this->selected;
        }

        parent::prepareAttributes($row);

        if ($this->callback) {
            unset($this->attributes['action-key']);

            $this->attribute('action-scope', 'bulk');
        }
    }
}
