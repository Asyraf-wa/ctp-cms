@extends('errors.minimal')
@section('title', 'Page expired')
@section('code', '419')
@section('heading', 'This page expired')
@section('message', 'The form was open for a long time. Go back, refresh the page and try again.')
@section('action')<a href="{{ url()->previous() }}">Go back</a>@endsection
