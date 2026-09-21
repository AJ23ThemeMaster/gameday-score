{{--
  Flash messages (status / error) - WattVision dark theme.
  Borde lateral de 3px segun nivel + texto blanco sobre superficie dark.
--}}
@if (session('status'))
    <div class="mb-4 rounded-card bg-wv-surface border border-wv-border border-l-[3px] border-l-wv-success p-4 text-sm text-wv-text">
        {{ session('status') }}
    </div>
@endif
@if (session('error'))
    <div class="mb-4 rounded-card bg-wv-surface border border-wv-border border-l-[3px] border-l-wv-alert p-4 text-sm text-wv-text">
        {{ session('error') }}
    </div>
@endif