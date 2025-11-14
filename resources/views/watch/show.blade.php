@extends('layouts.app')

@section('title', ucfirst($watchToCompare->brand) . ' ' . ucfirst($watchToCompare->model) . ' vergelijken | WatchMatch')
@section('meta_description', 'Bekijk de ' . ucfirst($watchToCompare->brand) . ' ' . ucfirst($watchToCompare->model) . '
inclusief prijzen, specificaties en vergelijkbare horloges.')
@section('meta_keywords', $watchToCompare->brand . ', horloge, ' . $watchToCompare->model)

@section('og_title', ucfirst($watchToCompare->brand) . ' ' . ucfirst($watchToCompare->model))
@section('og_description', 'Ontdek de ' . ucfirst($watchToCompare->brand) . ' ' . ucfirst($watchToCompare->model) . ' en
vergelijk prijzen.')
@section('og_image', $watchToCompare->image_url)

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

            <div class="d-flex justify-content-between mt-5">
                <h4>Price</h4>
                <h4>€ {{$watchToCompare->price}}</h4>
            </div>
            <div class="d-flex justify-content-between mt-2">
                <h4>Variant</h4>
                <h4>{{$watchToCompare->variant ? ucfirst($watchToCompare->variant) : "-"}}</h4>
            </div>
            @if($watchToCompare->description != null)
                <div class="d-flex flex-column mt-4">
                    <h4>Description</h4>
                    <span>{{$watchToCompare->description}}</span>
                </div>
            @endif
        </div>
    </div>
</div>
<div class="row-fluid text-center d-flex justify-content-center">
    <div class="col-md-12">
        <h5>Similar watches</h5>
        <similarities :original="{{$watchToCompare}}" :similarities="{{$similarWatches}}"></similarities>
    </div>
</div>
@endsection

<script type="application/ld+json">
    {
        "@context": "https://schema.org/",
        "@type": "Product",
        "name": "{{$watchToCompare->brand}} {{$watchToCompare->model}} {{$watchToCompare->variant}}",
        "image": "{{$watchToCompare->imageUrl}}",
        "brand": {
          "@type": "Brand",
          "name": "{{$watchToCompare->brand}}"
        },
        "offers": {
          "@type": "Offer",
          "priceCurrency": "EUR",
          "price": "{{$watchToCompare->price}}",
          "availability": "https://schema.org/InStock"
        }
      }
</script>