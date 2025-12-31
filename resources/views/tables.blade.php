@extends('dbclient::layout')

@section('title', 'Tables')

@section('content')
<div class="card">
  <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
    <div>
      <h2>Database Tables</h2>
      <p style="color: #6b7280; margin-top: 5px;">Select a table to view and manage its data</p>
    </div>
    <div style="display: flex; align-items: center; gap: 10px;">
      <label style="color: #6b7280; font-size: 14px;">Per Page:</label>
      <select id="perPageSelect" class="form-control" style="width: auto; min-width: 80px;" onchange="changePerPage(this.value)">
        <option value="10" {{ $per_page == 10 ? 'selected' : '' }}>10</option>
        <option value="20" {{ $per_page == 20 ? 'selected' : '' }}>20</option>
        <option value="50" {{ $per_page == 50 ? 'selected' : '' }}>50</option>
        <option value="100" {{ $per_page == 100 ? 'selected' : '' }}>100</option>
      </select>
    </div>
  </div>

  <div style="overflow-x: auto;">
    <table class="table">
      <thead>
        <tr>
          <th>Table Name</th>
          <th>Rows</th>
          <th>Columns</th>
          <th style="width: 150px;">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($tableInfo as $info)
        <tr>
          <td>
            <strong>{{ $info['name'] }}</strong>
          </td>
          <td>
            <span class="badge badge-primary">{{ number_format($info['rows']) }}</span>
          </td>
          <td>
            <span class="badge badge-success">{{ $info['columns'] }}</span>
          </td>
          <td>
            <a href="{{ route('dbclient.table.show', $info['name']) }}?page={{ $current_page }}&per_page={{ $per_page }}" class="btn btn-primary btn-sm" style="margin-right: 5px;">View</a>
            <button class="btn btn-danger btn-sm" onclick="deleteTable('{{ $info['name'] }}')">Delete</button>
          </td>
        </tr>
        @empty
        <tr>
          <td colspan="4" style="text-align: center; padding: 40px; color: #6b7280;">
            No tables found in the database
          </td>
        </tr>
        @endforelse
      </tbody>
    </table>
  </div>

  <!-- Pagination -->
  @if($last_page > 1)
  <div style="margin-top: 20px;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
      <div style="color: #6b7280; font-size: 14px;">
        Showing <strong>{{ $from }}</strong> to <strong>{{ $to }}</strong> of <strong>{{ $total }}</strong> tables
      </div>
      <div class="pagination">
        @if($current_page > 1)
        <a href="?page={{ $current_page - 1 }}&per_page={{ $per_page }}">&laquo; Prev</a>
        @else
        <span class="disabled">&laquo; Prev</span>
        @endif

        @php
        $start = max(1, $current_page - 2);
        $end = min($last_page, $current_page + 2);
        @endphp

        @if($start > 1)
        <a href="?page=1&per_page={{ $per_page }}">1</a>
        @if($start > 2)
        <span>...</span>
        @endif
        @endif

        @for($i = $start; $i <= $end; $i++)
          @if($i==$current_page)
          <span class="active">{{ $i }}</span>
          @else
          <a href="?page={{ $i }}&per_page={{ $per_page }}">{{ $i }}</a>
          @endif
          @endfor

          @if($end < $last_page)
            @if($end < $last_page - 1)
            <span>...</span>
            @endif
            <a href="?page={{ $last_page }}&per_page={{ $per_page }}">{{ $last_page }}</a>
            @endif

            @if($current_page < $last_page)
              <a href="?page={{ $current_page + 1 }}&per_page={{ $per_page }}">Next &raquo;</a>
              @else
              <span class="disabled">Next &raquo;</span>
              @endif
      </div>
    </div>
  </div>
  @endif
</div>
@endsection

<!-- Delete Table Confirmation Modal -->
<div id="deleteTableModal" class="modal">
  <div class="modal-content" style="max-width: 500px;">
    <div class="modal-header">
      <h3 id="deleteTableModalTitle">Drop Table</h3>
      <span class="close" onclick="closeDeleteTableModal()">&times;</span>
    </div>
    <div style="padding: 20px 0;">
      <p style="color: #6b7280; margin-bottom: 15px; font-size: 15px;" id="deleteTableModalMessage">
        This action cannot be undone. Please type <strong style="color: #dc2626;">DELETE</strong> to confirm.
      </p>
      <div class="form-group">
        <label for="deleteTableConfirmInput">Type <strong style="color: #dc2626;">DELETE</strong> to confirm:</label>
        <input
          type="text"
          id="deleteTableConfirmInput"
          class="form-control"
          placeholder="Type DELETE here"
          autocomplete="off"
          style="text-transform: uppercase; font-weight: 600; letter-spacing: 1px;">
        <small id="deleteTableConfirmHint" style="color: #6b7280; margin-top: 5px; display: block;">
          You must type exactly "DELETE" to proceed
        </small>
      </div>
      <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
        <button type="button" class="btn btn-secondary" onclick="closeDeleteTableModal()">Cancel</button>
        <button type="button" id="confirmDeleteTableBtn" class="btn btn-danger" onclick="executeDeleteTable()" disabled>
          Drop Table
        </button>
      </div>
    </div>
  </div>
</div>

@push('styles')
<style>
  .modal {
    display: none;
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    overflow: auto;
    background-color: rgba(0, 0, 0, 0.5);
  }

  .modal.active {
    display: flex;
    align-items: center;
    justify-content: center;
  }

  .modal-content {
    background-color: white;
    border-radius: 8px;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
    width: 90%;
    max-width: 600px;
    animation: modalSlideIn 0.3s ease;
  }

  @keyframes modalSlideIn {
    from {
      transform: translateY(-50px);
      opacity: 0;
    }

    to {
      transform: translateY(0);
      opacity: 1;
    }
  }

  .modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e5e7eb;
  }

  .modal-header h3 {
    margin: 0;
    font-size: 18px;
    color: #374151;
  }

  .close {
    color: #9ca3af;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
    line-height: 1;
  }

  .close:hover {
    color: #374151;
  }

  #deleteTableConfirmInput:focus {
    border-color: #dc2626;
    box-shadow: 0 0 0 3px rgba(220, 38, 38, 0.1);
  }

  #confirmDeleteTableBtn:disabled {
    opacity: 0.5;
    cursor: not-allowed;
  }

  #confirmDeleteTableBtn:not(:disabled) {
    opacity: 1;
  }
</style>
@endpush

@push('scripts')
<script>
  let pendingDeleteTableName = null;

  function changePerPage(value) {
    const url = new URL(window.location);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
  }

  function deleteTable(tableName) {
    pendingDeleteTableName = tableName;
    document.getElementById('deleteTableModalTitle').textContent = 'Drop Table: ' + tableName;
    document.getElementById('deleteTableModalMessage').innerHTML =
      'Are you sure you want to drop table <strong>"' + escapeHtml(tableName) + '"</strong>? This action cannot be undone.<br><br>' +
      'Please type <strong style="color: #dc2626;">DELETE</strong> to confirm.';
    document.getElementById('deleteTableConfirmInput').value = '';
    document.getElementById('confirmDeleteTableBtn').disabled = true;
    document.getElementById('deleteTableConfirmInput').style.borderColor = '#d1d5db';
    document.getElementById('deleteTableConfirmHint').innerHTML = 'You must type exactly "DELETE" to proceed';
    document.getElementById('deleteTableModal').classList.add('active');
    document.getElementById('deleteTableConfirmInput').focus();
  }

  function closeDeleteTableModal() {
    document.getElementById('deleteTableModal').classList.remove('active');
    document.getElementById('deleteTableConfirmInput').value = '';
    document.getElementById('confirmDeleteTableBtn').disabled = true;
    document.getElementById('deleteTableConfirmInput').style.borderColor = '#d1d5db';
    document.getElementById('deleteTableConfirmHint').innerHTML = 'You must type exactly "DELETE" to proceed';
    pendingDeleteTableName = null;
  }

  function executeDeleteTable() {
    if (!pendingDeleteTableName) return;

    $.ajax({
      url: '/dbclient/table/' + encodeURIComponent(pendingDeleteTableName) + '/drop',
      type: 'DELETE',
      data: {
        _token: '{{ csrf_token() }}'
      },
      success: function(response) {
        closeDeleteTableModal();
        if (response.success) {
          location.reload();
        }
      },
      error: function(xhr) {
        closeDeleteTableModal();
        const error = xhr.responseJSON?.error || 'Failed to drop table';
        alert(error);
      }
    });
  }

  // Handle DELETE input validation for table deletion
  $('#deleteTableConfirmInput').on('input', function() {
    const input = $(this);
    let value = input.val().toUpperCase();
    input.val(value);
    value = value.trim();
    const confirmBtn = $('#confirmDeleteTableBtn');
    const hint = $('#deleteTableConfirmHint');

    if (value === 'DELETE') {
      confirmBtn.prop('disabled', false);
      confirmBtn.css('opacity', '1');
      hint.html('<span style="color: #16a34a;">✓ Ready to drop table</span>');
      input.css('border-color', '#16a34a');
    } else if (value.length > 0) {
      confirmBtn.prop('disabled', true);
      confirmBtn.css('opacity', '0.5');
      hint.html('<span style="color: #dc2626;">Incorrect. Type "DELETE" to proceed</span>');
      input.css('border-color', '#dc2626');
    } else {
      confirmBtn.prop('disabled', true);
      confirmBtn.css('opacity', '0.5');
      hint.html('You must type exactly "DELETE" to proceed');
      input.css('border-color', '#d1d5db');
    }
  });

  // Allow Enter key to submit when DELETE is typed
  $('#deleteTableConfirmInput').on('keypress', function(e) {
    if (e.which === 13 && $(this).val().toUpperCase().trim() === 'DELETE') {
      executeDeleteTable();
    }
  });

  // Close modal on outside click
  document.getElementById('deleteTableModal').addEventListener('click', function(e) {
    if (e.target === this) {
      closeDeleteTableModal();
    }
  });

  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return text.replace(/[&<>"']/g, function(m) {
      return map[m];
    });
  }
</script>
@endpush