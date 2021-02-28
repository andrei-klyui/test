@extends('layouts.app')

@section('title')
    Search
@endsection

@section('content')

    <div class="list-group">
        <form action="/search" method="get" role="search">
            {{ csrf_field() }}
            <input type="text" name="repo">
            <button type="submit">Submit</button>
        </form>
    </div>
    <hr style="border: 1px solid #1d1b1b; width: 92%; margin-left: 4%;">
    <div class="container">
        @if(isset($result))
            @if(App\Models\Favorite::where('name', '=', $repo->name)->exists())
                @foreach($repos as $repo)
                    <div>
                        <form method="post" action="/edit">
                            {{ csrf_field() }}
                            <a href="/finder?repo={{ $repo['name'] }}">
                                <p id="the-span" data-name="{{ $repo->name }}">{{ $repo['name'] }}</p>
                                <p id="the-span" data-html_url="{{ $repo->html_url }}">{{ $repo['html_url'] }}</p>
                                <p id="the-span" data-description="{{ $repo->description }}">{{ $repo['description'] }}</p>
                                <p id="the-span" data-owner_login="{{ $repo->owner_login }}">{{ $repo['owner.login'] }}</p>
                                <p id="the-span" data-stargazers_count="topic">{{ $repo['stargazers_count'] }}</p>
                            </a>
                            <button type="submit" class="btn">Delete</button>
                        </form>
                    </div>
                @endforeach
            @else
                @foreach($repos as $repo)
                    <div>
                        <form method="post" action="/edit">
                            {{ csrf_field() }}
                            <a href="/finder?repo={{ $repo['name'] }}">
                                <p id="the-span" data-name="{{ $repo->name }}">{{ $repo['name'] }}</p>
                                <p id="the-span" data-html_url="{{ $repo->html_url }}">{{ $repo['html_url'] }}</p>
                                <p id="the-span" data-description="{{ $repo->description }}">{{ $repo['description'] }}</p>
                                <p id="the-span" data-owner_login="{{ $repo->owner_login }}">{{ $repo['owner.login'] }}</p>
                                <p id="the-span" data-stargazers_count="topic">{{ $repo['stargazers_count'] }}</p>
                            </a>
                            <button type="submit" class="btn">Add</button>
                        </form>
                    </div>
                @endforeach
            @endif
        @endif
    </div>

    @include('js1')

@endsection