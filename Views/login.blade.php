@extends('layouts')

@section('title')
    Login
@endsection

@section('content')

    <div class="list-group">
        <form action="/login" method="POST">
            {{ csrf_field() }}
            <input type="email" name="email" required>
            <input type="password" name="password" required>
            <button type="submit">Submit</button>
        </form>
    </div>

@endsection