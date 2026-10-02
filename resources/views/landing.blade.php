@extends('layouts.app')

@section('title', config('app.name'))

@section('container-class', '')

@section('content')
    <x-organisms.site-header />
    <x-organisms.hero />
    <x-organisms.features />
    <x-organisms.cta />
    <x-organisms.site-footer />
@endsection
