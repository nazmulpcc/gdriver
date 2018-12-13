@extends('layouts.main')

@section('title', 'Home')

@section('content')
<div id="particles">
    <div id="intro">
        {{-- <h1>Particleground</h1> --}}
        <h1>{{ config('app.name') }}</h1>
        <form>
            @if(!$auth)
                <input type="text" class="url" placeholder="Direct File URL"><br>
                <a href="#" class="btn" id="upload">Upload</a>
            @else
                <a href="{{ $auth }}" class="btn">Sign In with Google</a>
            @endif
        </form>
    </div>
</div>
@endsection

@section('footerjs')
<script>
    $(document).ready(function () {
        @if(!$auth)
        $('#upload').click(function name() {
            $.post("{{ route('upload') }}", {
                url: $('.url').val(),
                _token: "{{ csrf_token() }}"
            }).done(function () {
                alert("Your file is in queue and will be uploaded.")
            });
            $('.url').val('');
        });
        @endif
    });
</script>
@endsection