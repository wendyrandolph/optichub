<?php

namespace App\Http\Controllers\ClientPortal;

use App\Http\Controllers\Controller;
use App\Models\ChatChannel;
use App\Models\ChatMessage;
use App\Models\ChatRead;
use App\Models\Client;
use App\Models\Project;
use App\Models\Task;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class ProjectController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::guard('client')->user();
        if (! $user || ! $user->contact_id) {
            abort(403);
        }

        $client = Client::where('tenant_id', $user->tenant_id)
            ->where('id', $user->contact_id)
            ->firstOrFail();

        $companyId = $client->client_company_id;

        $projects = Project::query()
            ->where('tenant_id', $user->tenant_id)
            ->where(function ($q) use ($client, $companyId) {
                $q->where('contact_id', $client->id);
                if ($companyId) {
                    $q->orWhere('client_company_id', $companyId)
                        ->orWhereIn('contact_id', function ($sub) use ($companyId) {
                            $sub->select('id')
                                ->from('contacts')
                                ->where('client_company_id', $companyId);
                        });
                }
            })
            ->withCount(['tasks'])
            ->latest('updated_at')
            ->paginate(12);

        $projectIds = $projects->pluck('id')->filter()->values();
        if ($projectIds->isNotEmpty()) {
            $clientId = (int) $client->id;
            $actionTaskCounts = Task::query()
                ->where('tenant_id', $user->tenant_id)
                ->whereIn('project_id', $projectIds)
                ->whereNotIn('status', ['completed', 'closed', 'archived'])
                ->where(function ($q) use ($clientId) {
                    $q->where('contact_id', $clientId)
                        ->orWhere(function ($sub) use ($clientId) {
                            $sub->where('assign_type', 'client')
                                ->where('assign_id', $clientId);
                        })
                        ->orWhere('client_visible', true)
                        ->orWhere('requires_approval', true)
                        ->orWhereIn('approval_status', ['needs_approval', 'awaiting_approval', 'approval'])
                        ->orWhere('title', 'like', 'client:%');
                })
                ->selectRaw('project_id, COUNT(*) as action_count')
                ->groupBy('project_id')
                ->pluck('action_count', 'project_id');

            $projects->getCollection()->transform(function ($project) use ($actionTaskCounts) {
                $project->action_tasks_count = (int) ($actionTaskCounts[$project->id] ?? 0);
                return $project;
            });
        }

        $projectCollection = $projects->getCollection();
        $activeProjects = $projectCollection->filter(function ($project) {
            $status = strtolower((string) ($project->status ?? ''));
            return ! in_array($status, ['closed', 'completed', 'complete', 'archived', 'canceled', 'cancelled'], true);
        })->values();

        $chatProjects = collect();
        $requestedChatProjectId = (int) $request->query('chat_project', 0);
        if ($requestedChatProjectId) {
            $requestedProject = $projectCollection->firstWhere('id', $requestedChatProjectId);
            if ($requestedProject) {
                $chatProjects->push($requestedProject);
            }
        }

        foreach ($activeProjects as $project) {
            if ($chatProjects->count() >= 2) {
                break;
            }
            if (! $chatProjects->contains('id', $project->id)) {
                $chatProjects->push($project);
            }
        }

        $chatProjectIds = $chatProjects->pluck('id')->filter()->values();
        $chatChannelsByProject = collect();
        $chatMessagesByProject = [];

        if ($chatProjectIds->isNotEmpty()) {
            $channels = ChatChannel::query()
                ->where('tenant_id', $user->tenant_id)
                ->where('type', 'project')
                ->whereIn('project_id', $chatProjectIds)
                ->get();

            $chatChannelsByProject = $channels->keyBy('project_id');
            $channelIds = $channels->pluck('id')->filter()->values();

            if ($channelIds->isNotEmpty()) {
                $unreadCounts = DB::table('chat_messages as m')
                    ->leftJoin('chat_reads as r', function ($join) use ($user) {
                        $join->on('r.channel_id', '=', 'm.channel_id')
                            ->where('r.user_id', '=', $user->id);
                    })
                    ->whereIn('m.channel_id', $channelIds)
                    ->where('m.user_id', '!=', $user->id)
                    ->whereRaw('m.created_at > COALESCE(r.last_read_at, ?)', ['1970-01-01 00:00:00'])
                    ->groupBy('m.channel_id')
                    ->selectRaw('m.channel_id, COUNT(*) as unread_count')
                    ->pluck('unread_count', 'm.channel_id');

                $channelMeta = ChatChannel::query()
                    ->whereIn('id', $channelIds)
                    ->withMax('messages as last_message_at', 'created_at')
                    ->addSelect([
                        'last_read_at' => ChatRead::query()
                            ->select('last_read_at')
                            ->whereColumn('chat_reads.channel_id', 'chat_channels.id')
                            ->where('chat_reads.user_id', $user->id)
                            ->limit(1),
                        'last_message_user_id' => ChatMessage::query()
                            ->select('user_id')
                            ->whereColumn('chat_messages.channel_id', 'chat_channels.id')
                            ->latest('created_at')
                            ->limit(1),
                    ])
                    ->get()
                    ->keyBy('id');

                $messages = ChatMessage::query()
                    ->whereIn('channel_id', $channelIds)
                    ->with('user:id,first_name,last_name,role,email')
                    ->orderBy('created_at')
                    ->get()
                    ->groupBy('channel_id');

                foreach ($chatProjects as $project) {
                    $channel = $chatChannelsByProject->get($project->id);
                    $chatMessagesByProject[$project->id] = $channel
                        ? ($messages->get($channel->id) ?? collect())
                        : collect();
                    $meta = $channel ? $channelMeta->get($channel->id) : null;
                    $project->chat_is_unread = $meta && $meta->last_message_at
                        && (! $meta->last_read_at || $meta->last_message_at > $meta->last_read_at)
                        && (int) ($meta->last_message_user_id ?? 0) !== (int) $user->id;
                    $project->chat_unread_count = $channel ? (int) ($unreadCounts[$channel->id] ?? 0) : 0;
                }

                foreach ($channelIds as $channelId) {
                    ChatRead::updateOrCreate(
                        ['channel_id' => $channelId, 'user_id' => $user->id],
                        ['last_read_at' => now()]
                    );
                }
            }
        }

        return view('portal.projects.index', [
            'projects' => $projects,
            'client' => $client,
            'chatProjects' => $chatProjects,
            'chatChannelsByProject' => $chatChannelsByProject,
            'chatMessagesByProject' => $chatMessagesByProject,
            'tenantName' => $client->tenant?->name ?? 'your provider',
        ]);
    }
}
