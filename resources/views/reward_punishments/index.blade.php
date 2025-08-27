@extends('layouts.app')
@section('content')
<div class="d-flex justify-content-between align-items-center mb-3">
    <h2>Reward & Punishment List</h2>
    <a href="{{ route('reward-punishments.create') }}" class="btn btn-primary">Add New</a>
</div>
@if(session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
@endif
<table class="table table-bordered">
    <thead>
        <tr>
            <th>ID</th>
            <th>Child</th>
            <th>Type</th>
            <th>Points</th>
            <th>Description</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        @foreach($rewards as $reward)
        <tr>
            <td>{{ $reward->id }}</td>
            <td>{{ $reward->child->name ?? '' }}</td>
            <td>{{ ucfirst($reward->type) }}</td>
            <td>{{ $reward->points }}</td>
            <td>{{ $reward->description }}</td>
            <td>
                <a href="{{ route('reward-punishments.edit', $reward) }}" class="btn btn-sm btn-warning">Edit</a>
                <form action="{{ route('reward-punishments.destroy', $reward) }}" method="POST" style="display:inline-block">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-sm btn-danger" onclick="return confirm('Are you sure?')">Delete</button>
                </form>
            </td>
        </tr>
        @endforeach
    </tbody>
</table>
{{ $rewards->links() }}
@endsection

