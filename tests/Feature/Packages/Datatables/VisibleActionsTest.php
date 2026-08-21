<?php

use Redot\Datatables\Actions\Action;
use Redot\Datatables\Actions\ActionGroup;
use Redot\Datatables\Actions\BulkAction;
use Tests\Fixtures\Datatables\RunActionDatatable;

it('filters hidden actions out of groups without mutating the group', function () {
    $group = ActionGroup::make('More')->actions([
        Action::make('Approve'),
        Action::make('Hidden')->hidden(),
    ]);

    $datatable = new class($group) extends RunActionDatatable
    {
        public function __construct(protected ActionGroup $group)
        {
            parent::__construct();
        }

        public function actions(): array
        {
            return [$this->group];
        }

        public function visibleActions(): array
        {
            return $this->getVisibleActions();
        }
    };

    [$visible] = $datatable->visibleActions();

    expect($visible->actions)->toHaveCount(1)
        ->and($visible)->not->toBe($group)
        ->and($group->actions)->toHaveCount(2);
});

it('drops groups whose actions are all hidden', function () {
    $group = ActionGroup::make('More')->actions([Action::make('Hidden')->hidden()]);

    $datatable = new class($group) extends RunActionDatatable
    {
        public function __construct(protected ActionGroup $group)
        {
            parent::__construct();
        }

        public function actions(): array
        {
            return [$this->group];
        }

        public function visibleActions(): array
        {
            return $this->getVisibleActions();
        }
    };

    expect($datatable->visibleActions())->toBe([]);
});

it('keeps bulk actions grouped by default without mutating them', function () {
    $action = BulkAction::make('Delete');

    $datatable = new class($action) extends RunActionDatatable
    {
        public function __construct(protected BulkAction $action)
        {
            parent::__construct();
        }

        public function bulkActions(): array
        {
            return [$this->action];
        }

        public function visibleBulkActions(): array
        {
            return $this->getVisibleBulkActions();
        }
    };

    expect($datatable->visibleBulkActions())->toBe([$action])
        ->and($action->grouped)->toBeTrue();
});
