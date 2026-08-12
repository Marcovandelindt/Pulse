<aside class="sidebar">
    <div class="sidebar__logo">Pulse</div>

    <nav class="sidebar__nav">
        <div class="sidebar__group">
            <span class="sidebar__group-label">Overview</span>
            <x-layout.nav-item route="dashboard" icon="home" label="Dashboard" />
        </div>

        <div class="sidebar__group">
            <span class="sidebar__group-label">Lifestyle</span>
            <x-layout.nav-item route="health.index" icon="heart" label="Health" />
            <x-layout.nav-item route="music.index" icon="musical-note" label="Music" />
        </div>

        <div class="sidebar__group">
            <span class="sidebar__group-label">Finance</span>
            <x-layout.nav-item route="finance.index" icon="credit-card" label="Expenses" />
        </div>

        <div class="sidebar__group">
            <span class="sidebar__group-label">Media</span>
            <x-layout.nav-item route="movies.index" icon="film" label="Movies" />
            <x-layout.nav-item route="tv.index" icon="tv" label="TV" />
        </div>
    </nav>
</aside>
