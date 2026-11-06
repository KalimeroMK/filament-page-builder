@extends('filament-page-builder::layouts.default')

@section('fpb-content')
    {!! $rendered->toHtml() !!}
@endsection
