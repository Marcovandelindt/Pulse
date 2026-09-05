<x-layouts.app title="AI Assistant">

    <div class="ai-layout" x-data="aiChat()" x-init="$nextTick(() => scrollToBottom())">

        {{-- Conversation sidebar --}}
        <aside class="ai-layout__sidebar">
            <div class="ai-conv__header">
                <span class="ai-conv__title">Conversations</span>
                <form method="POST" action="{{ route('ai.chat.store') }}">
                    @csrf
                    <button type="submit" class="ai-conv__new" title="New chat">
                        <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" style="width:1rem;height:1rem;">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" />
                        </svg>
                    </button>
                </form>
            </div>

            <nav class="ai-conv__list">
                @foreach($conversations as $conv)
                    <div class="ai-conv__item {{ $conv->id === $conversation->id ? 'ai-conv__item--active' : '' }}">
                        <a href="{{ route('ai.chat.show', $conv) }}" class="ai-conv__item-link">
                            <span class="ai-conv__item-title">{{ $conv->title ?? 'New conversation' }}</span>
                            <span class="ai-conv__item-date">{{ $conv->updated_at->diffForHumans() }}</span>
                        </a>
                        <form method="POST" action="{{ route('ai.chat.destroy', $conv) }}" onsubmit="return confirm('Delete this conversation?')">
                            @csrf @method('DELETE')
                            <button type="submit" class="ai-conv__item-delete" title="Delete">
                                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.75" stroke="currentColor" style="width:0.75rem;height:0.75rem;">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </form>
                    </div>
                @endforeach
            </nav>

            <div class="ai-conv__footer">
                <a href="{{ route('ai.settings') }}" class="ai-conv__settings-link">
                    <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:0.875rem;height:0.875rem;flex-shrink:0;">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.723 7.723 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                    </svg>
                    Settings
                </a>
            </div>
        </aside>

        {{-- Chat area --}}
        <div class="ai-layout__main">
            <div class="ai-chat">

                {{-- Messages --}}
                <div class="ai-chat__messages" x-ref="messages">

                    @if($messages->isEmpty())
                        <div class="ai-chat__empty">
                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />
                            </svg>
                            <p>Start a conversation. Ask about your steps, gaming sessions, listening habits, or anything else tracked in Pulse.</p>
                            <div class="ai-chat__suggestions">
                                <button class="ai-chat__suggestion" @click="sendSuggestion('How many steps did I walk this week?')">How many steps did I walk this week?</button>
                                <button class="ai-chat__suggestion" @click="sendSuggestion('Which games have I played most recently?')">Which games have I played most recently?</button>
                                <button class="ai-chat__suggestion" @click="sendSuggestion('Who are my most listened artists lately?')">Who are my most listened artists lately?</button>
                                <button class="ai-chat__suggestion" @click="sendSuggestion('What have I been watching on TV?')">What have I been watching on TV?</button>
                            </div>
                        </div>
                    @endif

                    {{-- Existing messages from DB --}}
                    @foreach($messages as $msg)
                        <div class="ai-chat__message ai-chat__message--{{ $msg->role }}">
                            <div class="ai-chat__bubble">{{ $msg->content }}</div>
                        </div>
                    @endforeach

                    {{-- New messages appended by Alpine --}}
                    <template x-for="(msg, index) in newMessages" :key="index">
                        <div class="ai-chat__message" :class="'ai-chat__message--' + msg.role">
                            <div class="ai-chat__bubble" x-text="msg.content"></div>
                        </div>
                    </template>

                    <template x-if="loading">
                        <div class="ai-chat__message ai-chat__message--assistant">
                            <div class="ai-chat__bubble ai-chat__bubble--loading">
                                <span></span><span></span><span></span>
                            </div>
                        </div>
                    </template>

                </div>

                {{-- Error --}}
                <div class="ai-chat__error" x-show="error" x-transition>
                    <span x-text="error"></span>
                    <button @click="error = null">✕</button>
                </div>

                {{-- Input --}}
                <form class="ai-chat__form" @submit.prevent="send()">
                    <textarea
                        class="ai-chat__input"
                        x-model="input"
                        placeholder="Ask something about your data…"
                        rows="1"
                        :disabled="loading"
                        @keydown.enter.prevent="!$event.shiftKey && send()"
                        @input="autoResize($el)"
                    ></textarea>
                    <button type="submit" class="ai-chat__send btn btn--primary" :disabled="loading || !input.trim()">
                        <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="currentColor" style="width:1rem;height:1rem;">
                            <path d="M3.478 2.405a.75.75 0 00-.926.94l2.432 7.905H13.5a.75.75 0 010 1.5H4.984l-2.432 7.905a.75.75 0 00.926.94 60.519 60.519 0 0018.445-8.986.75.75 0 000-1.218A60.517 60.517 0 003.478 2.405z" />
                        </svg>
                    </button>
                </form>

                <p class="ai-chat__hint">Enter to send · Shift+Enter for new line</p>

            </div>
        </div>

    </div>

    @push('scripts')
    <script>
        function aiChat() {
            return {
                newMessages: [],
                input: '',
                loading: false,
                error: null,

                async send() {
                    const text = this.input.trim();
                    if (!text || this.loading) return;

                    this.newMessages.push({ role: 'user', content: text });
                    this.input = '';
                    this.loading = true;
                    this.error = null;
                    await this.$nextTick();
                    this.scrollToBottom();

                    try {
                        const res = await fetch('{{ route('ai.chat.send', $conversation) }}', {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ message: text }),
                        });

                        const data = await res.json();

                        if (!res.ok) {
                            this.error = data.error ?? 'Something went wrong.';
                            this.newMessages.pop();
                        } else {
                            this.newMessages.push({ role: 'assistant', content: data.reply });
                        }
                    } catch (e) {
                        this.error = 'Could not reach the server. Is Ollama running?';
                        this.newMessages.pop();
                    } finally {
                        this.loading = false;
                        await this.$nextTick();
                        this.scrollToBottom();
                    }
                },

                sendSuggestion(text) {
                    this.input = text;
                    this.send();
                },

                scrollToBottom() {
                    const el = this.$refs.messages;
                    if (el) el.scrollTop = el.scrollHeight;
                },

                autoResize(el) {
                    el.style.height = 'auto';
                    el.style.height = Math.min(el.scrollHeight, 160) + 'px';
                },
            };
        }
    </script>
    @endpush

</x-layouts.app>
