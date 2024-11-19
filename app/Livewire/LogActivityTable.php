<?php

namespace App\Livewire;

use Carbon\Carbon;
use App\Models\User;
use Livewire\Component;
use App\Models\LogActivity;
use Laravel\Jetstream\Agent;
use Livewire\WithPagination;
use App\Models\Session;

class LogActivityTable extends Component
{
    use WithPagination;

    public $search = '';
    public $perPage = 25;
    protected $paginationTheme = 'tailwind';

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function getActiveSessions()
    {
        $activeSessions = Session::where('last_activity', '>=', now()->subMinutes(5)->timestamp)
            ->where('user_id', '<>', null)
            ->get();

        $activeSessionsWithAgent = [];

        foreach ($activeSessions as $session) {
            $user = User::find($session->user_id);

            if ($user) {
                $agent = new Agent();
                $agent->setUserAgent($session->user_agent);

                $activeSessionsWithAgent[] = [
                    'user' => $user->name,
                    'ip_address' => $session->ip_address,
                    'last_active' => Carbon::createFromTimestamp($session->last_activity)->diffForHumans(),
                    'platform' => $agent->platform(),
                    'browser' => $agent->browser(),
                ];
            }
        }

        return $activeSessionsWithAgent;
    }

    public function render()
    {
        $activities = LogActivity::with('user')
            ->where(function ($query) {
                $query->where('method', 'like', '%' . $this->search . '%')
                    ->orWhere('ip_address', 'like', '%' . $this->search . '%')
                    ->orWhere('url', 'like', '%' . $this->search . '%')
                    ->orWhere('platform', 'like', '%' . $this->search . '%')
                    ->orWhere('browser', 'like', '%' . $this->search . '%')
                    ->orWhere('status', 'like', '%' . $this->search . '%')
                    ->orWhere('message', 'like', '%' . $this->search . '%')
                    ->orWhere('created_at', 'like', '%' . $this->search . '%')
                    ->orWhereHas('user', function ($query) {
                        $query->where('name', 'like', '%' . $this->search . '%');
                    });
            })
            ->orderBy('created_at', 'desc')
            ->paginate($this->perPage);

        return view('livewire.log-activity-table', [
            'activities' => $activities,
            'activeSessionsWithAgent' => $this->getActiveSessions(),
        ]);
    }
}
