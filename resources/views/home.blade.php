@extends('layouts.app')

@section('content')
<watchwizard :randomwatches="{{$watches}}"></watchwizard>
@endsection