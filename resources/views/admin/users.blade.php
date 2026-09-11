@extends('layouts.app')

@section('title', 'Manage User Roles')

@section('content')
<div class="dashboard-header">
    <div>
        <h1 style="color: var(--text-primary);">User Accounts Registry</h1>
        <p style="color: var(--text-muted);">Manage platform roles, elevate credentials, and review user status.</p>
    </div>
</div>

<div class="card">
    <h3 class="card-title" style="color: var(--neon-cyan);">System Users</h3>
    
    <div class="table-container">
        <table>
            <thead>
                <tr>
                    <th>Full Name</th>
                    <th>Username</th>
                    <th>Email Address</th>
                    <th>Current Role</th>
                    <th>Change Role Actions</th>
                </tr>
            </thead>
            <tbody>
                @foreach($users as $user)
                    <tr>
                        <td>{{ $user->name }}</td>
                        <td>{{ $user->username }}</td>
                        <td>{{ $user->email }}</td>
                        <td>
                            @if($user->isAdmin())
                                <span class="badge badge-completed">Admin</span>
                            @elseif($user->isOrganizer())
                                <span class="badge badge-active">Organizer</span>
                            @else
                                <span class="badge badge-open">Player</span>
                            @endif
                        </td>
                        <td>
                            @if($user->id !== auth()->id())
                                <div style="display:inline-flex; gap:8px; align-items:center;">
                                    <form action="/admin/users/{{ $user->id }}/role" method="POST" style="display:inline-flex; gap:5px;">
                                        @csrf
                                        <select name="role" class="form-control" style="padding: 4px 8px; font-size: 0.8rem; height: auto; width: auto; background-color: var(--bg-dark); color: var(--text-primary); border-color: var(--border-color);" onchange="this.form.submit()">
                                            <option value="player" {{ $user->role === 'player' ? 'selected' : '' }}>Player</option>
                                            <option value="organizer" {{ $user->role === 'organizer' ? 'selected' : '' }}>Organizer</option>
                                            <option value="admin" {{ $user->role === 'admin' ? 'selected' : '' }}>Admin</option>
                                        </select>
                                    </form>

                                    <form action="{{ route('admin.users.destroy', $user->id) }}" method="POST" style="display:inline-flex;" onsubmit="return confirm('Are you sure you want to remove this user?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" style="background:#ff4d4f; color:#fff; border:none; padding:6px 10px; border-radius:4px; font-size:0.85rem; cursor:pointer;">Remove</button>
                                    </form>
                                </div>
                            @else
                                <span style="font-size:0.8rem; color:var(--text-muted);">Self (Locked)</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
