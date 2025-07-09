@extends('layouts.app')

@section('content')
<div class="row justify-content-center text-white">
    <div class="col-auto text-center">
        <div class="p-4">
            <div class="h1">
                {{$message}}
            </div>
            <div>
                {{$brand}} {{$model}} 
            </div>
        </div>
    </div>
</div>

@endsection