@extends('layouts.app')

@section('content')
<watchwizard :randomwatches="{{ $watches }}" :filter='@json($filter)'></watchwizard>
@endsection