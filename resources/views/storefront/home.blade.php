@extends('layouts.app')

@section('content')
    <main id="top" dir="{{ $dir }}">
        @include('storefront.partials.hero')
        @include('storefront.partials.language-bar')
        @include('storefront.partials.catalog')
    </main>
@endsection
