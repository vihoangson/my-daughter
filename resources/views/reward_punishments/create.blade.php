@extends('layouts.app')
@section('content')
<h2>Add Reward/Punishment</h2>
<form action="{{ route('reward-punishments.store') }}" method="POST">
    @csrf
    <div class="mb-3">
        <label for="child_id" class="form-label">Child</label>
        <select name="child_id" id="child_id" class="form-select" required>
            <option value="">Select Child</option>
            @foreach($children as $child)
                <option value="{{ $child->id }}">{{ $child->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="type" class="form-label">Type</label>
        <select name="type" id="type" class="form-select" required>
            <option value="reward">Reward</option>
            <option value="punishment">Punishment</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="points" class="form-label">Points</label>
        <input type="number" name="points" id="points" class="form-control" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <input type="text" name="description" id="description" class="form-control">
    </div>
    <button type="submit" class="btn btn-success">Save</button>
    <a href="{{ route('reward-punishments.index') }}" class="btn btn-secondary">Back</a>
</form>
@endsection

