@extends('layouts.app')

@section('content')
<div class="row mb-4 pb-4" id="details-brands">
</div>
<div class="row d-flex justify-content-center">
    <div class="col-md-10 d-flex flex-column flex-md-row justify-content-center mb-4">
        <div class="d-flex justify-content-center">
            <div class="card col-10 col-md-8">
                <img class="watch-card-image" src="{{$watchToCompare->image_url}}" alt="">
            </div>
        </div>
        <div class="col-lg-4 col-md-6 p-2 m-4">
            <h1 class="logo">{{ucfirst($watchToCompare->brand)}}</h1>
            <p>{{ucfirst($watchToCompare->model)}}</p>

            <div class="d-flex justify-content-between mt-4">
                <h4>Prijs</h4>
                <h4>€ {{$watchToCompare->price}}</h4>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <h4>Variant</h4>
                <h4>{{$watchToCompare->variant ? ucfirst($watchToCompare->variant) : "-"}}</h4>
            </div>
        </div>
    </div>
</div>
<div class="row-fluid text-center d-flex justify-content-center">
    <div class="col-md-12">
        <h5>Vergelijkbare horloges</h5>
        <similarities :original="{{$watchToCompare}}"></similarities>
    </div>
</div>
@endsection