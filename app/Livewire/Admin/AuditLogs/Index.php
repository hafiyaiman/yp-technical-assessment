<?php

namespace App\Livewire\Admin\AuditLogs;

use App\Models\AuditLog;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithPagination;

#[Layout('layouts.app')]
class Index extends Component
{
    use WithPagination;

    public string $search = '';
    public $actionFilters = [];

    public function mount(): void
    {
        abort_unless(auth()->user()->hasRole('system-admin'), 403);
    }

    public function updated($property): void
    {
        if ($property === 'search' || str_starts_with($property, 'actionFilters')) {
            $this->actionFilters = $this->filterValues('actionFilters');
            $this->resetPage();
        }
    }

    public function logs()
    {
        $actionFilters = $this->filterValues('actionFilters');

        return AuditLog::query()
            ->with('actor')
            ->when($this->search !== '', function ($query): void {
                $query->where(function ($query): void {
                    $query
                        ->where('description', 'like', "%{$this->search}%")
                        ->orWhere('action', 'like', "%{$this->search}%")
                        ->orWhereHas('actor', function ($query): void {
                            $query
                                ->where('name', 'like', "%{$this->search}%")
                                ->orWhere('email', 'like', "%{$this->search}%");
                        });
                });
            })
            ->when($actionFilters !== [], fn ($query) => $query->whereIn('action', $actionFilters))
            ->latest()
            ->paginate(15);
    }

    public function actionOptions(): array
    {
        return AuditLog::query()
            ->select('action')
            ->distinct()
            ->orderBy('action')
            ->pluck('action')
            ->map(fn (string $action) => [
                'label' => str($action)->headline()->toString(),
                'value' => $action,
            ])
            ->all();
    }

    private function filterValues(string $property): array
    {
        $value = property_exists($this, $property) ? $this->{$property} : [];

        return collect(is_array($value) ? $value : [])
            ->filter(fn ($value) => filled($value))
            ->values()
            ->all();
    }

    public function render(): View
    {
        return view('livewire.admin.audit-logs.index');
    }
}
