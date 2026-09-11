<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Nexus Arena') - Esports Tournament Management System</title>
    
    <!-- Custom Style -->
    <link rel="stylesheet" href="/css/app.css">
</head>
<body>

    <!-- Header Navigation -->
    <nav class="navbar">
        <div class="container nav-container">
            <div class="brand-logo">
                <a href="/" class="brand-name">Nexus Arena</a>
            </div>
            
            <ul class="nav-links">
                <li><a href="/tournaments" class="{{ Request::is('tournaments*') ? 'active' : '' }}">Tournaments</a></li>
                @auth
                    <li><a href="/dashboard" class="{{ Request::is('dashboard*') ? 'active' : '' }}">Dashboard</a></li>
                    @if(auth()->user()->isAdmin())
                        <li><a href="/admin/users" class="{{ Request::is('admin/users*') ? 'active' : '' }}">Users</a></li>
                    @endif
                    @if(auth()->user()->captainedTeam)
                        <li><a href="{{ route('teams.show', auth()->user()->captainedTeam->id) }}" class="{{ Request::is('teams*') ? 'active' : '' }}">My Team</a></li>
                    @endif
                    <li>
                        <form action="/logout" method="POST" style="display:inline;">
                            @csrf
                            <button type="submit" class="btn btn-secondary btn-small" style="margin-left:10px;">Logout</button>
                        </form>
                    </li>
                @else
                    <li><a href="/login" class="{{ Request::is('login') ? 'active' : '' }}">Login</a></li>
                    <li><a href="/register" class="{{ Request::is('register') ? 'active' : '' }}">Register</a></li>
                @endauth
            </ul>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="container" style="margin-top: 30px; margin-bottom: 50px;">
        
        <!-- Flash Alert Banners -->
        @if(session('success'))
            <div class="alert alert-success">
                {{ session('success') }}
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger">
                {{ session('error') }}
            </div>
        @endif

        @if($errors->any())
            <div class="alert alert-danger">
                <ul style="list-style: none;">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Page Specific Contents -->
        @yield('content')
    </main>

    <!-- Global Scripts -->
    <script>
        // Real-time notifications read marker
        function markNotificationsAsRead() {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
            
            fetch('/notifications/read', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    const badge = document.querySelector('.notification-count');
                    if (badge) {
                        badge.style.display = 'none';
                    }
                    const unreadItems = document.querySelectorAll('.notification-item.unread');
                    unreadItems.forEach(item => item.classList.remove('unread'));
                }
            })
            .catch(error => console.error('Error marking notifications read:', error));
        }
    </script>
    
    @yield('scripts')
</body>
</html>
