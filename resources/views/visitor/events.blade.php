@extends('visitor.layout')
@section('title', 'Events - Fan Hub Plus')
@php
    $base = $events->first()?->event_date ?? now();
    $start = $base->copy()->startOfMonth(); $end = $base->copy()->endOfMonth();
    $byDay = $events->getCollection()->groupBy(fn($e) => $e->event_date?->day);
@endphp
@section('content')
<main class="shell">
    <nav class="crumbs"><a href="{{ url('/') }}">Home</a><span>›</span><span>Events</span></nav>
    <section class="hero-card"><p class="kicker">Fan calendar</p><h1>Upcoming fandom events</h1><p class="lead">City and type filters use the existing event fields. Ticket and map previews appear only when those fields exist.</p></section>
    <form class="filter-bar" method="get"><div class="field"><label>City</label><select name="city"><option value="">All cities</option>@foreach($cities as $city)<option value="{{ $city }}" @selected(request('city')===$city)>{{ $city }}</option>@endforeach</select></div><div class="field"><label>Type</label><select name="event_type"><option value="">All event types</option>@foreach($types as $type)<option value="{{ $type }}" @selected(request('event_type')===$type)>{{ $type }}</option>@endforeach</select></div><button class="btn" type="submit">Filter</button></form>
    <section class="calendar-layout">
        <div class="calendar-card"><div class="section-head"><div><p class="kicker">{{ $base->format('F Y') }}</p><h2>{{ $events->total() }} events</h2></div></div><div class="calendar-grid">@foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $d)<div class="cal-head">{{ $d }}</div>@endforeach @for($i=0;$i<$start->dayOfWeek;$i++)<div class="cal-day is-empty"></div>@endfor @for($day=1;$day<=$end->day;$day++)<div class="cal-day">{{ $day }}@foreach(($byDay[$day] ?? collect())->take(2) as $event)<span class="cal-event">{{ $event->title }}</span>@endforeach</div>@endfor</div></div>
        <aside class="side-panel"><p class="kicker">Upcoming list</p>@forelse($events as $event)<article class="mini-card" style="margin-bottom:12px"><h3>{{ $event->title }}</h3><p class="muted">{{ $event->event_type }} · {{ $event->city }} · {{ $event->event_date?->format('M d, Y h:i A') }}</p>@if($event->ticket_link)<a class="btn alt" href="{{ $event->ticket_link }}">Ticket link</a>@endif @if($event->latitude && $event->longitude)<div class="map-preview" style="margin-top:10px">Map preview<br>{{ $event->latitude }}, {{ $event->longitude }}</div>@endif</article>@empty<p class="muted">No events match the current filters.</p>@endforelse</aside>
    </section>
    <div class="pagination">{{ $events->links() }}</div>
</main>
@endsection
