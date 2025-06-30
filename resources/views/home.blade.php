@extends('layouts.app')

@section('content')
<watchwizard :randomwatches="{{$watches}}" :extrainfo="{{json_encode($info)}}"></watchwizard>
@endsection