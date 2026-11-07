@extends('filament-page-builder::layouts.default')

@section('fpb-content')
    <article style="max-width: 860px; margin: 0 auto;">
        @if($post->category)
            <p style="margin: 0 0 12px; color: #dc2626; font-size: 12px; font-weight: 700; letter-spacing: .08em; text-transform: uppercase;">
                {{ $post->category->name }}
            </p>
        @endif

        <h1 style="margin: 0 0 12px; color: #111827; font-size: clamp(32px, 5vw, 46px); line-height: 1.15;">
            {{ $post->title }}
        </h1>

        <p style="margin: 0 0 24px; color: #64748b; font-size: 14px;">
            @if($post->author) By {{ $post->author }} · @endif
            {{ $post->published_at?->format('F j, Y') }}
        </p>

        @if($post->image_url)
            <img src="{{ $post->image_url }}" alt="{{ $post->title }}" style="display: block; width: 100%; height: auto; margin-bottom: 28px; border-radius: 10px;" />
        @endif

        @if($post->excerpt)
            <p style="margin: 0 0 24px; color: #334155; font-size: 19px; line-height: 1.7;">
                {{ $post->excerpt }}
            </p>
        @endif

        <div style="color: #1f2937; font-size: 17px; line-height: 1.8;">
            {!! \KalimeroMK\FilamentPageBuilder\Support\HtmlSanitizer::clean((string) $post->body) !!}
        </div>
    </article>
@endsection
