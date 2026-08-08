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
     * Determine if the action is grouped, bulk actions always render in a dropdown.
     */
    public bool $grouped = true;

    /**
     * The request key holding the selected row keys.
     */
    public string $keys = 'keys';

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
     * Prepare the attributes before building.
     */
    protected function prepareAttributes(?Model $row = null): void
    {
        parent::prepareAttributes($row);

        if ($this->callback) {
            unset($this->attributes['action-key']);
        }

        // The selection is browser state, so it is not known when the action is
        // rendered. Name the request key here and let the click handler fill it
        // in with whatever is ticked at that moment.
        $this->attributes([
            'action-scope' => 'bulk',
            'bulk-keys' => $this->keys,
        ]);
    }
}
