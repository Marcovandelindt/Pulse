<x-layouts.app title="Ideas">

<div x-data="ideasPage()">

    <x-layout.page-header title="Ideas">
        <x-slot:actions>
            <button @click="openAdd()" class="btn btn--primary btn--sm">+ Add idea</button>
        </x-slot:actions>
    </x-layout.page-header>

    @if(session('success'))
        <div class="mb-4 px-4 py-3 rounded-lg text-sm font-medium"
             style="background: rgba(34,197,94,0.15); color: #4ade80; border: 1px solid rgba(34,197,94,0.25);">
            {{ session('success') }}
        </div>
    @endif

    {{-- Stats --}}
    <div class="grid grid-cols-3 gap-4 mb-6">
        <x-stats.stat-card label="Total" :value="$totalCount" />
        <x-stats.stat-card label="Pending" :value="$pendingCount" />
        <x-stats.stat-card label="Done" :value="$doneCount" />
    </div>

    {{-- Filters --}}
    <div class="ideas-filters">
        <div class="ideas-filters__tabs">
            <a href="{{ route('ideas.index', array_filter(['module' => $filterModule])) }}"
               class="ideas-filters__tab {{ !$filterStatus ? 'ideas-filters__tab--active' : '' }}">
                All
            </a>
            @foreach($statuses as $s)
                <a href="{{ route('ideas.index', array_filter(['status' => $s->value, 'module' => $filterModule])) }}"
                   class="ideas-filters__tab {{ $filterStatus === $s->value ? 'ideas-filters__tab--active' : '' }}">
                    {{ $s->label() }}
                </a>
            @endforeach
        </div>

        @if($modules->isNotEmpty())
            <form method="GET" action="{{ route('ideas.index') }}" class="ideas-filters__module">
                @if($filterStatus)
                    <input type="hidden" name="status" value="{{ $filterStatus }}">
                @endif
                <select name="module" onchange="this.form.submit()" class="ideas-filters__select">
                    <option value="">All modules</option>
                    @foreach($modules as $mod)
                        <option value="{{ $mod }}" {{ $filterModule === $mod ? 'selected' : '' }}>
                            {{ ucfirst($mod) }}
                        </option>
                    @endforeach
                </select>
            </form>
        @endif
    </div>

    {{-- Ideas list --}}
    @if($ideas->isEmpty())
        <div class="ideas-empty">
            <p class="ideas-empty__text">No ideas yet. Add the first one!</p>
        </div>
    @else
        <div class="ideas-list">
            @foreach($ideas as $idea)
                <div class="idea-card idea-card--{{ $idea->status->value }}">
                    <div class="idea-card__status-bar"></div>

                    <div class="idea-card__body">
                        <div class="idea-card__header">
                            <div class="idea-card__meta">
                                <span class="badge badge--{{ $idea->status->color() }}">{{ $idea->status->label() }}</span>
                                <span class="badge badge--{{ $idea->priority->color() }}">{{ $idea->priority->label() }}</span>
                                @if($idea->module)
                                    <span class="badge badge--muted">{{ ucfirst($idea->module) }}</span>
                                @endif
                            </div>
                            <div class="idea-card__actions">
                                @if($idea->status !== \App\Enums\IdeaStatus::Done)
                                    <form method="POST" action="{{ route('ideas.status.update', $idea) }}">
                                        @csrf
                                        @method('PATCH')
                                        <button type="submit" class="idea-card__advance" title="Advance status">
                                            → {{ $idea->status->next()->label() }}
                                        </button>
                                    </form>
                                @endif
                                <button @click="openEdit({{ $idea->id }}, {{ json_encode($idea->title) }}, {{ json_encode($idea->description ?? '') }}, '{{ $idea->module ?? '' }}', '{{ $idea->priority->value }}', '{{ $idea->status->value }}')"
                                        class="idea-card__btn" title="Edit">
                                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:0.875rem;height:0.875rem;">
                                        <path stroke-linecap="round" stroke-linejoin="round" d="m16.862 4.487 1.687-1.688a1.875 1.875 0 1 1 2.652 2.652L10.582 16.07a4.5 4.5 0 0 1-1.897 1.13L6 18l.8-2.685a4.5 4.5 0 0 1 1.13-1.897l8.932-8.931Zm0 0L19.5 7.125" />
                                    </svg>
                                </button>
                                <form method="POST" action="{{ route('ideas.destroy', $idea) }}" onsubmit="return confirm('Delete this idea?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="idea-card__btn idea-card__btn--danger" title="Delete">
                                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:0.875rem;height:0.875rem;">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" />
                                        </svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <h3 class="idea-card__title {{ $idea->status === \App\Enums\IdeaStatus::Done ? 'idea-card__title--done' : '' }}">
                            {{ $idea->title }}
                        </h3>

                        @if($idea->description)
                            <p class="idea-card__description">{{ $idea->description }}</p>
                        @endif

                        <span class="idea-card__date">{{ $idea->created_at->diffForHumans() }}</span>
                    </div>
                </div>
            @endforeach
        </div>
    @endif

    {{-- Add modal --}}
    <div x-show="showAdd" x-transition.opacity style="display:none;" class="modal">
        <div class="modal__backdrop" @click="showAdd = false"></div>
        <div class="modal__panel" @keydown.escape.window="showAdd = false">
            <div class="modal__header">
                <h2 class="modal__title">New idea</h2>
                <button @click="showAdd = false" class="modal__close">&times;</button>
            </div>
            <form method="POST" action="{{ route('ideas.store') }}">
                @csrf
                <div class="modal__body">
                    @include('pages.ideas._form', ['idea' => null, 'priorities' => $priorities, 'statuses' => $statuses])
                </div>
                <div class="modal__footer">
                    <button type="button" @click="showAdd = false" class="btn btn--secondary btn--sm">Cancel</button>
                    <button type="submit" class="btn btn--primary btn--sm">Save idea</button>
                </div>
            </form>
        </div>
    </div>

    {{-- Edit modal --}}
    <div x-show="showEdit" x-transition.opacity style="display:none;" class="modal">
        <div class="modal__backdrop" @click="showEdit = false"></div>
        <div class="modal__panel" @keydown.escape.window="showEdit = false">
            <div class="modal__header">
                <h2 class="modal__title">Edit idea</h2>
                <button @click="showEdit = false" class="modal__close">&times;</button>
            </div>
            <form method="POST" :action="`/ideas/${editId}`">
                @csrf
                @method('PATCH')
                <div class="modal__body">
                    <div class="form-group">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" x-model="editTitle" class="form-input" required>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Description</label>
                        <textarea name="description" x-model="editDescription" class="form-input" rows="3"></textarea>
                    </div>
                    <div class="form-group">
                        <label class="form-label">Module</label>
                        <input type="text" name="module" x-model="editModule" class="form-input" placeholder="e.g. playstation, health">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div class="form-group">
                            <label class="form-label">Priority</label>
                            <select name="priority" x-model="editPriority" class="form-input">
                                @foreach($priorities as $p)
                                    <option value="{{ $p->value }}">{{ $p->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group">
                            <label class="form-label">Status</label>
                            <select name="status" x-model="editStatus" class="form-input">
                                @foreach($statuses as $s)
                                    <option value="{{ $s->value }}">{{ $s->label() }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal__footer">
                    <button type="button" @click="showEdit = false" class="btn btn--secondary btn--sm">Cancel</button>
                    <button type="submit" class="btn btn--primary btn--sm">Save changes</button>
                </div>
            </form>
        </div>
    </div>

</div>

<script>
function ideasPage() {
    return {
        showAdd: false,
        showEdit: false,
        editId: null,
        editTitle: '',
        editDescription: '',
        editModule: '',
        editPriority: 'medium',
        editStatus: 'idea',

        openAdd() {
            this.showAdd = true;
        },

        openEdit(id, title, description, module, priority, status) {
            this.editId          = id;
            this.editTitle       = title;
            this.editDescription = description;
            this.editModule      = module;
            this.editPriority    = priority;
            this.editStatus      = status;
            this.showEdit        = true;
        },
    };
}
</script>

</x-layouts.app>
