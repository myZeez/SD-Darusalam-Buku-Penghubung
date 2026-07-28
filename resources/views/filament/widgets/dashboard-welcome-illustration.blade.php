<x-filament-widgets::widget class="fi-wi-dashboard-welcome">
    <section class="dashboard-welcome" aria-labelledby="dashboard-welcome-heading">
        <div class="dashboard-welcome__copy">
            <p>{{ $eyebrow }}</p>
            <h2 id="dashboard-welcome-heading">{{ $heading }}</h2>
            <span>{{ $description }}</span>
        </div>

        <img class="dashboard-welcome__image" src="{{ $image }}" alt="{{ $alt }}">
    </section>
</x-filament-widgets::widget>
