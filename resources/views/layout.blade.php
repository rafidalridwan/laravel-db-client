<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>DB Client - @yield('title', 'Database Management')</title>
  <link rel="stylesheet" href="{{ asset('vendor/dbclient/css/toast.css') }}">
  <style>
    * {
      margin: 0;
      padding: 0;
      box-sizing: border-box;
    }

    body {
      font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
      background: #f5f7fa;
      color: #333;
    }

    .container {
      max-width: 1400px;
      margin: 0 auto;
      padding: 20px;
    }

    .header {
      background: white;
      padding: 20px;
      border-radius: 8px;
      margin-bottom: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    }

    .header h1 {
      color: #2563eb;
      margin-bottom: 10px;
    }

    .nav {
      display: flex;
      gap: 15px;
      margin-top: 15px;
    }

    .nav a {
      padding: 8px 16px;
      background: #f3f4f6;
      border-radius: 6px;
      text-decoration: none;
      color: #374151;
      transition: all 0.2s;
    }

    .nav a:hover,
    .nav a.active {
      background: #2563eb;
      color: white;
    }

    .card {
      background: white;
      border-radius: 8px;
      padding: 20px;
      box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
      margin-bottom: 20px;
    }

    .btn {
      padding: 8px 16px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 14px;
      transition: all 0.2s;
      text-decoration: none;
      display: inline-block;
    }

    .btn-primary {
      background: #2563eb;
      color: white;
    }

    .btn-primary:hover {
      background: #1d4ed8;
    }

    .btn-danger {
      background: #dc2626;
      color: white;
    }

    .btn-danger:hover {
      background: #b91c1c;
    }

    .btn-info {
      background: #0dcaf0;
      color: white;
    }

    .btn-info:hover {
      background: #0aa2c4;
    }

    .btn-success {
      background: #16a34a;
      color: white;
    }

    .btn-success:hover {
      background: #15803d;
    }

    .btn-secondary {
      background: #6b7280;
      color: white;
    }

    .btn-secondary:hover {
      background: #4b5563;
    }

    .btn-sm {
      padding: 6px 12px;
      font-size: 13px;
    }

    .table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 20px;
    }

    .table th,
    .table td {
      padding: 12px;
      text-align: left;
      border-bottom: 1px solid #e5e7eb;
    }

    .table th {
      background: #f9fafb;
      font-weight: 600;
      color: #374151;
    }

    .table tr:hover {
      background: #f9fafb;
    }

    .form-group {
      margin-bottom: 15px;
    }

    .form-group label {
      display: block;
      margin-bottom: 5px;
      font-weight: 500;
      color: #374151;
    }

    .form-control {
      width: 100%;
      padding: 8px 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      font-size: 14px;
    }

    .form-control:focus {
      outline: none;
      border-color: #2563eb;
      box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
    }

    .alert {
      padding: 12px 16px;
      border-radius: 6px;
      margin-bottom: 20px;
    }

    .alert-success {
      background: #d1fae5;
      color: #065f46;
      border: 1px solid #6ee7b7;
    }

    .alert-error {
      background: #fee2e2;
      color: #991b1b;
      border: 1px solid #fca5a5;
    }

    .pagination {
      display: flex;
      gap: 8px;
      margin-top: 20px;
      align-items: center;
    }

    .pagination a,
    .pagination span {
      padding: 8px 12px;
      border: 1px solid #d1d5db;
      border-radius: 6px;
      text-decoration: none;
      color: #374151;
    }

    .pagination a:hover {
      background: #f3f4f6;
    }

    .pagination .active {
      background: #2563eb;
      color: white;
      border-color: #2563eb;
    }

    .modal {
      display: none;
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: rgba(0, 0, 0, 0.5);
      z-index: 1000;
    }

    .modal.active {
      display: flex;
      align-items: center;
      justify-content: center;
    }

    .modal-content {
      background: white;
      padding: 30px;
      border-radius: 8px;
      max-width: 600px;
      width: 90%;
      max-height: 90vh;
      overflow-y: auto;
    }

    .modal-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
    }

    .close {
      font-size: 24px;
      cursor: pointer;
      color: #6b7280;
    }

    .close:hover {
      color: #374151;
    }

    .badge {
      display: inline-block;
      padding: 4px 8px;
      border-radius: 4px;
      font-size: 12px;
      font-weight: 500;
    }

    .badge-primary {
      background: #dbeafe;
      color: #1e40af;
    }

    .badge-success {
      background: #d1fae5;
      color: #065f46;
    }

    .badge-danger {
      background: #fee2e2;
      color: #991b1b;
    }
  </style>
  @stack('styles')
</head>

<body>
  <div class="container">
    <div class="header">
      <h1>🗄️ Database Client</h1>
      <nav class="nav">
        <a href="{{ route('dbclient.index') }}" class="{{ request()->routeIs('dbclient.index') ? 'active' : '' }}">Database Info</a>
        <a href="{{ route('dbclient.tables') }}" class="{{ request()->routeIs('dbclient.tables') || request()->routeIs('dbclient.table.*') ? 'active' : '' }}">Tables</a>
        <a href="{{ route('dbclient.query') }}" class="{{ request()->routeIs('dbclient.query*') ? 'active' : '' }}">SQL Query</a>
        <a href="{{ route('dbclient.artisan') }}" class="{{ request()->routeIs('dbclient.artisan*') ? 'active' : '' }}">Artisan</a>
      </nav>
    </div>

    @yield('content')
  </div>

  <!-- Toast Container -->
  <div class="hs-toast-wrapper hs-toast-fixed-top" id="dbclient-toaster"></div>

  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <script src="{{ asset('vendor/dbclient/js/toast.js') }}"></script>
  <script>
    const csrfToken = '{{ csrf_token() }}';
    $.ajaxSetup({
      headers: {
        'X-CSRF-TOKEN': csrfToken
      }
    });

    // Show session messages as toasts
    @if(session('success'))
    showToast({
      eleWrapper: '#dbclient-toaster',
      msg: {
        !!json_encode(session('success')) !!
      },
      theme: 'success',
      closeButton: true,
      autoClose: true
    });
    @endif

    @if(session('error'))
    showToast({
      eleWrapper: '#dbclient-toaster',
      msg: {
        !!json_encode(session('error')) !!
      },
      theme: 'error',
      closeButton: true,
      autoClose: true
    });
    @endif

    @if(session('warning'))
    showToast({
      eleWrapper: '#dbclient-toaster',
      msg: {
        !!json_encode(session('warning')) !!
      },
      theme: 'warning',
      closeButton: true,
      autoClose: true
    });
    @endif

    @if(session('info'))
    showToast({
      eleWrapper: '#dbclient-toaster',
      msg: {
        !!json_encode(session('info')) !!
      },
      theme: 'info',
      closeButton: true,
      autoClose: true
    });
    @endif
  </script>
  @stack('scripts')
</body>

</html>