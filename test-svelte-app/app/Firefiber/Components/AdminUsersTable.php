<?php

declare(strict_types=1);

namespace App\Firefiber\Components;

use Sahil\Firefiber\Component;
use Sahil\Firefiber\Data\DataTable;
use Sahil\Firefiber\Exceptions\UnknownActionException;

/**
 * Example: a server-driven admin table with URL-synchronized state.
 *
 * Pagination, sorting, filtering, and search are validated server-side.
 * The query string stays in sync so views are bookmarkable and work with
 * browser back/forward navigation (the runtime uses the built-in query
 * action, never raw model writes).
 */
final class AdminUsersTable extends Component
{
    /** @var array<string, array<string, mixed>|string> */
    protected array $querySync = [
        'search'    => ['param' => 'q', 'type' => 'string'],
        'page'      => ['param' => 'page', 'type' => 'int'],
        'sort'      => ['param' => 'sort', 'type' => 'string'],
        'direction' => ['param' => 'dir', 'type' => 'string'],
        'status'    => ['param' => 'status', 'type' => 'string'],
    ];

    /** @var list<string> */
    protected array $modelable = ['search'];

    protected bool $rerenderOnAction = true;

    public function __construct()
    {
        parent::__construct(
            [
                'search' => '',
                'page' => 1,
                'sort' => 'name',
                'direction' => 'asc',
                'status' => '',
            ],
            ['sortBy', 'setStatus', 'goToPage'],
        );
    }

    public function render(): string
    {
        $result = $this->table()->paginate($this->state());

        $rows = '';
        foreach ($result->data as $user) {
            $rows .= '<tr>'
                .'<td>'.self::escape((string) $user['name']).'</td>'
                .'<td>'.self::escape((string) $user['email']).'</td>'
                .'<td>'.self::escape((string) $user['role']).'</td>'
                .'<td>'.self::escape((string) $user['status']).'</td>'
                .'</tr>';
        }

        if ($rows === '') {
            $rows = '<tr><td colspan="4">No users match the current filters.</td></tr>';
        }

        $summary = sprintf(
            'Showing %d–%d of %d users (page %d/%d)',
            $result->from,
            $result->to,
            $result->total,
            $result->currentPage,
            $result->lastPage,
        );

        $search = self::escape((string) $this->state()['search']);
        $status = (string) $this->state()['status'];

        $statusOptions = '';
        foreach (['' => 'All statuses', 'active' => 'Active', 'invited' => 'Invited', 'disabled' => 'Disabled'] as $value => $label) {
            $selected = $status === $value ? ' selected' : '';
            $statusOptions .= '<option value="'.self::escape($value).'"'.$selected.'>'.self::escape($label).'</option>';
        }

        return '<div class="admin-users-table">'
            .'<div class="admin-users-controls">'
            .'<label>Search <input type="search" fiber:model="search" data-firefiber-debounce="300" value="'.$search.'" placeholder="Name or email" /></label>'
            .'<label>Status <select name="status" fiber:change="setStatus">'.$statusOptions.'</select></label>'
            .'</div>'
            .'<table>'
            .'<thead><tr>'
            .$this->sortHeader('Name', 'name', $result)
            .$this->sortHeader('Email', 'email', $result)
            .'<th>Role</th>'
            .$this->sortHeader('Status', 'status', $result)
            .'</tr></thead>'
            .'<tbody>'.$rows.'</tbody>'
            .'</table>'
            .'<p class="admin-users-summary">'.self::escape($summary).'</p>'
            .'<div class="admin-users-pagination">'
            .'<button type="button" fiber:click="goToPage" data-firefiber-payload="'.self::escape((string) json_encode(['page' => max(1, $result->currentPage - 1)])).'"'.($result->hasPrevious() ? '' : ' disabled').'>Previous</button>'
            .'<button type="button" fiber:click="goToPage" data-firefiber-payload="'.self::escape((string) json_encode(['page' => min($result->lastPage, $result->currentPage + 1)])).'"'.($result->hasNext() ? '' : ' disabled').'>Next</button>'
            .'</div>'
            .'</div>';
    }

    public function handleAction(string $action, array $payload): array
    {
        if ($action === 'sortBy') {
            $field = (string) ($payload['field'] ?? '');
            $direction = $this->state()['sort'] === $field && $this->state()['direction'] === 'asc' ? 'desc' : 'asc';
            $this->updateState(['sort' => $field, 'direction' => $direction, 'page' => 1]);

            return ['sort' => $field, 'direction' => $direction];
        }

        if ($action === 'setStatus') {
            $this->updateState(['status' => (string) ($payload['status'] ?? $payload['value'] ?? ''), 'page' => 1]);

            return ['status' => $this->state()['status']];
        }

        if ($action === 'goToPage') {
            $page = max(1, (int) ($payload['page'] ?? 1));
            $this->updateState(['page' => $page]);

            return ['page' => $page];
        }

        throw new UnknownActionException($action, $this->id());
    }

    private function sortHeader(string $label, string $field, \Sahil\Firefiber\Data\PaginatedResult $result): string
    {
        $indicator = $result->sort === $field ? ($result->direction === 'asc' ? ' ↑' : ' ↓') : '';
        $payload = self::escape((string) json_encode(['field' => $field]));

        return '<th><button type="button" fiber:click="sortBy" data-firefiber-payload="'.$payload.'">'.self::escape($label.$indicator).'</button></th>';
    }

    private function table(): DataTable
    {
        $filters = [];
        $status = trim((string) ($this->state()['status'] ?? ''));

        if ($status !== '') {
            $filters['status'] = $status;
        }

        return new DataTable($this->rows(), [
            'perPage' => 5,
            'sortableFields' => ['name', 'email', 'status'],
            'searchableFields' => ['name', 'email', 'role'],
            'filters' => ['status' => ['operator' => 'eq']],
            'defaultFilters' => $filters,
            'defaultSort' => ['field' => 'name', 'direction' => 'asc'],
        ]);
    }

    /**
     * In-memory sample data. Replace with a database query in a real app.
     *
     * @return list<array<string, string>>
     */
    private function rows(): array
    {
        return [
            ['name' => 'Amina Khan', 'email' => 'amina@example.test', 'role' => 'Admin', 'status' => 'active'],
            ['name' => 'Bilal Shah', 'email' => 'bilal@example.test', 'role' => 'Editor', 'status' => 'active'],
            ['name' => 'Chen Wei', 'email' => 'chen@example.test', 'role' => 'Viewer', 'status' => 'invited'],
            ['name' => 'Dina Okafor', 'email' => 'dina@example.test', 'role' => 'Editor', 'status' => 'active'],
            ['name' => 'Elias Park', 'email' => 'elias@example.test', 'role' => 'Viewer', 'status' => 'disabled'],
            ['name' => 'Farah Iqbal', 'email' => 'farah@example.test', 'role' => 'Admin', 'status' => 'active'],
            ['name' => 'Gabe Romero', 'email' => 'gabe@example.test', 'role' => 'Viewer', 'status' => 'invited'],
            ['name' => 'Hana Yilmaz', 'email' => 'hana@example.test', 'role' => 'Editor', 'status' => 'active'],
            ['name' => 'Ivan Petrov', 'email' => 'ivan@example.test', 'role' => 'Viewer', 'status' => 'disabled'],
            ['name' => 'Jules Martin', 'email' => 'jules@example.test', 'role' => 'Admin', 'status' => 'active'],
            ['name' => 'Kira Novak', 'email' => 'kira@example.test', 'role' => 'Editor', 'status' => 'invited'],
            ['name' => 'Luca Bianchi', 'email' => 'luca@example.test', 'role' => 'Viewer', 'status' => 'active'],
        ];
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    }
}
