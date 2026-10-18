@extends('errors.minimal')
@section('title', 'Something went wrong')
@section('code', '500')
@section('heading', 'Something went wrong on our side')
@section('message', 'The error has been logged. Please try again in a moment.')
@section('action')<a href="{{ url('/') }}">Back to the home page</a>@endsection
