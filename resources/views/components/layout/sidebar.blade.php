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
            <x-layout.nav-item route="recommendations.index" icon="sparkles" label="Recommendations" />
        </div>

        <div class="sidebar__group">
            <span class="sidebar__group-label">Gaming</span>
            <x-layout.nav-item route="playstation.index" icon="puzzle-piece" label="PlayStation" />
            <x-layout.nav-item route="steam.index" icon="computer-desktop" label="Steam" />
        </div>

        <div class="sidebar__group">
            <span class="sidebar__group-label">Planning</span>
            <x-layout.nav-item route="people.index" icon="users" label="People" />
            <x-layout.nav-item route="calendar.index" icon="calendar" label="Calendar" />
        </div>

        <div class="sidebar__group">
            <span class="sidebar__group-label">Mind</span>
            <x-layout.nav-item route="insights.index" icon="light-bulb" label="Insights" />
        </div>

        <div class="sidebar__group">
            <span class="sidebar__group-label">System</span>
            <x-layout.nav-item route="stats.index" icon="chart-bar" label="Stats" />
            <x-layout.nav-item route="stats.week" icon="calendar" label="Weekly report" />
            <x-layout.nav-item route="changelog.index" icon="clock" label="Changelog" />
        </div>
    </nav>

    <div class="sidebar__footer">
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="sidebar__logout">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" style="width:1rem;height:1rem;flex-shrink:0;">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                </svg>
                Sign out
            </button>
        </form>
    </div>
</aside>
