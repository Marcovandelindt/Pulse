<div class="form-group">
    <label class="form-label">Title</label>
    <input type="text" name="title" value="{{ old('title', $idea?->title) }}" class="form-input" required autofocus>
</div>

<div class="form-group">
    <label class="form-label">Description</label>
    <textarea name="description" class="form-input" rows="3">{{ old('description', $idea?->description) }}</textarea>
</div>

<div class="form-group">
    <label class="form-label">Module</label>
    <input type="text" name="module" value="{{ old('module', $idea?->module) }}" class="form-input" placeholder="e.g. playstation, health, finance">
</div>

<div class="grid grid-cols-2 gap-4">
    <div class="form-group">
        <label class="form-label">Priority</label>
        <select name="priority" class="form-input">
            @foreach($priorities as $p)
                <option value="{{ $p->value }}" {{ old('priority', $idea?->priority->value ?? 'medium') === $p->value ? 'selected' : '' }}>
                    {{ $p->label() }}
                </option>
            @endforeach
        </select>
    </div>
    <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-input">
            @foreach($statuses as $s)
                <option value="{{ $s->value }}" {{ old('status', $idea?->status->value ?? 'idea') === $s->value ? 'selected' : '' }}>
                    {{ $s->label() }}
                </option>
            @endforeach
        </select>
    </div>
</div>
