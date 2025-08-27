@extends('layouts.app')
@section('content')
<h2>Edit Reward/Punishment</h2>
<form action="{{ route('reward-punishments.update', $rewardPunishment) }}" method="POST">
    @csrf
    @method('PUT')
    <div class="mb-3">
        <label for="child_id" class="form-label">Child</label>
        <select name="child_id" id="child_id" class="form-select" required>
            @foreach($children as $child)
                <option value="{{ $child->id }}" @if($rewardPunishment->child_id == $child->id) selected @endif>{{ $child->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label for="type" class="form-label">Type</label>
        <select name="type" id="type" class="form-select" required>
            <option value="reward" @if($rewardPunishment->type == 'reward') selected @endif>Reward</option>
            <option value="punishment" @if($rewardPunishment->type == 'punishment') selected @endif>Punishment</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="points" class="form-label">Points</label>
        <input type="number" name="points" id="points" class="form-control" value="{{ $rewardPunishment->points }}" required>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Description</label>
        <input type="text" name="description" id="description" class="form-control" value="{{ $rewardPunishment->description }}">
    </div>
    <button type="submit" class="btn btn-success">Update</button>
    <a href="{{ route('reward-punishments.index') }}" class="btn btn-secondary">Back</a>
</form>
@endsection

