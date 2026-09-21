@props(['status'])

@if ($status)
    {{--
      WattVision: status de sesion con borde lateral verde (success) sobre
      superficie dark.
    --}}
    <div {{ $attributes->merge(['class' => 'font-medium text-sm text-wv-success bg-wv-surface border border-wv-border border-l-[3px] border-l-wv-success rounded-card px-4 py-3']) }}>
        {{ $status }}
    </div>
@endif