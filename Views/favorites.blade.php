@extends('layouts.app')

@section('title')
    Favorites
@endsection

@section('content')

    <div class="container">
        @if(session()->has('message'))
            <div>
                {{ session()->get('message') }}
            </div>
        @endif
        @foreach($favorite as $favorit)
            <div>
                <form method="delete" action="{{ route('delete', $id->id) }}">
                    {{ csrf_field() }}
                    <a href="/finder?repo={{ $favorit['name'] }}">
                        <p>{{ $favorit['name'] }}</p>
                        <p>{{ $favorit['html_url'] }}</p>
                        <p>{{ $favorit['description'] }}</p>
                        <p>{{ $favorit['owner.login'] }}</p>
                        <p>{{ $favorit['stargazers_count'] }}</p>
                    </a>
                    <button type="submit" class="btn">Delete</button>
                </form>
            </div>
        @endforeach
    </div>

@endsection