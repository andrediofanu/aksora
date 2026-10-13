@props(['title' => null])

<x-layouts.guest :title="$title">{{ $slot }}</x-layouts.guest>
