<div {{ $attributes->class(['table-wrap']) }}>
    <table class="table">
        @if (isset($head))
            <thead>{{ $head }}</thead>
        @endif
        @if (isset($body))
            <tbody>{{ $body }}</tbody>
        @endif
    </table>
</div>
