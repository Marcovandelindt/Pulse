# Plan: Lokale AI-assistent met spraak voor Pulse

## Doel
Een lokale, gratis AI-assistent toevoegen aan Pulse (Laravel/Blade dashboard) die:
- Context heeft over mijn eigen data (Strava, gaming, muziek, gezondheid) uit de Pulse-database
- Vragen kan beantwoorden en samenvattingen/inzichten kan geven op basis van die data
- Via spraak te bedienen is (praten tegen de bot, antwoord hardop terug)
- 100% lokaal draait, geen API-kosten, geen data die de deur uit gaat

## Hardware / omgeving
- Ryzen 9 5900X, 32GB RAM, RTX 3070 (8GB VRAM)
- Laravel/Blade stack, MySQL database (bestaande Pulse-app)
- Windows 11

## Tech stack
- **LLM runtime**: Ollama (lokaal, gratis, REST API op `localhost:11434`)
- **Model**: `llama3.1:8b` of `qwen2.5:7b` (Q4-quantized, past ruim in 8GB VRAM)
- **Speech-to-text**: whisper.cpp of faster-whisper (lokaal, goede NL-ondersteuning)
- **Text-to-speech**: Piper (lokaal, NL-stemmen beschikbaar)
- **Backend**: bestaande Laravel-app (Pulse)
- **Frontend**: Blade + JS (browser voor microfoon-opname en audio-afspelen)

## Fasering

### Fase 1 — Ollama basis werkend krijgen
1. Ollama installeren op Windows (winget of installer van ollama.com)
2. Model pullen: `ollama pull llama3.1:8b`
3. Testen via `ollama run llama3.1:8b` in terminal of via curl naar `localhost:11434/api/generate`
4. In Laravel: een `OllamaService` class bouwen die een HTTP-call doet naar de lokale Ollama API (via Laravel's `Http` facade) en een tekstantwoord teruggeeft

### Fase 2 — Context uit Pulse-database meegeven
1. Bepalen welke data relevant is (bijv. laatste 7 dagen Strava, laatste gelogde games/muziek, mood-logs)
2. Een `ContextBuilderService` bouwen die deze data ophaalt en samenvat tot een compacte prompt-context (let op tokenlimiet — niet de hele database dumpen)
3. `OllamaService` uitbreiden zodat de system prompt deze context + instructies bevat (bijv. "Je bent Marco's persoonlijke assistent, hier is zijn recente data: ...")
4. Een chat-UI in Blade bouwen: simpel invoerveld + gespreksgeschiedenis, POST naar een `ChatController`

### Fase 3 — Spraakinvoer toevoegen (speech-to-text)
1. whisper.cpp of faster-whisper lokaal installeren
2. Kiezen: losse achtergrondservice (bijv. een klein Python/FastAPI wrapper-servertje) die audio ontvangt en tekst teruggeeft, of whisper.cpp als CLI aanroepen vanuit Laravel
3. Frontend: microfoon-opname via `MediaRecorder` API in de browser, audio (WAV/webm) opsturen naar Laravel-endpoint
4. Laravel endpoint stuurt audio door naar Whisper-service, krijgt transcript terug
5. Transcript wordt de prompt die naar `OllamaService` gaat (zelfde flow als Fase 2)

### Fase 4 — Spraakuitvoer toevoegen (text-to-speech)
1. Piper lokaal installeren, Nederlandse stem downloaden
2. Laravel roept Piper aan (CLI of lichte wrapper-service) met het LLM-antwoord als input, krijgt audiobestand terug
3. Audiobestand teruggeven aan frontend, afspelen via `<audio>` element

### Fase 5 — Polish
1. Gesprekshistorie opslaan in database (nieuwe tabel `ai_conversations` / `ai_messages`)
2. Loading states en foutafhandeling (Ollama niet gestart, Whisper-service niet bereikbaar, etc.)
3. Eventueel: streaming responses van Ollama (token-voor-token) voor een snellere "live" feel
4. Eventueel: hotword/push-to-talk knop i.p.v. losse opname-knop

## Concrete eerste opdracht voor Claude Code
> "Ik heb een Laravel-app genaamd Pulse. Ik wil een `OllamaService` class toevoegen in `app/Services/` die een HTTP POST doet naar `http://localhost:11434/api/generate` met een prompt, en het antwoord teruggeeft als string. Voeg ook een simpele `ChatController` en Blade-view toe met een chatvenster (invoerveld + berichtenlijst) die deze service aanroept via een POST-route `/ai/chat`. Gebruik de bestaande stijl/structuur van het project."

## Openstaande keuzes om te maken tijdens het bouwen
- Welk model uiteindelijk (start met llama3.1:8b, kan later wisselen)
- Losse Python-microservice voor Whisper/Piper, of alles via CLI-calls vanuit PHP
- Hoeveel/welke Pulse-data precies als context meegaat (privacy/relevantie afweging)
- Streaming vs. volledig antwoord afwachten
