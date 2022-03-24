@extends('app')

@section('title', 'Your storage requests')

@push('scripts')
    <script src="{{ cachebust_asset('vendor/user-storage/scripts/main.js') }}"></script>
    <script type="text/javascript">
      // biigle.$declare('volumes.name', '{!! old('name') !!}');
   </script>
@endpush

@push('styles')
    <link href="{{ cachebust_asset('vendor/user-storage/styles/main.css') }}" rel="stylesheet">
@endpush

@section('content')
<div class="container">
   <div class="col-sm-8 col-sm-offset-2 col-lg-6 col-lg-offset-3">
      <h2>
        Your storage requests<br>
        <small>x of y used</small>
    </h2>
        <ul class="list-group">
            @forelse($requests as $request)
                <li class="list-group-item">
                    <span class="text-muted">#{{$request->id}}</span>
                    {{count($request->files)}} files.
                    <span class="pull-right">
                        @if (!$request->expires_at)
                            <span class="label label-default" title="Waiting for review">pending</span>
                        @elseif ($request->expires_at > $now)
                            @if ($request->expires_at < $expireDate)
                                <span class="label label-warning" title="Expires {{$request->expires_at->diffForHumans()}}">expires {{$request->expires_at->diffForHumans()}}</span>
                            @else
                                <span class="label label-success" title="Expires {{$request->expires_at->diffForHumans()}}">approved</span>
                            @endif
                        @else
                            <span class="label label-danger" title="The storage request is expired and will be deleted soon">expired</span>
                        @endif
                    </span>
                </li>
            @empty
            @endforelse
        </ul>
    </div>
</div>
@endsection
