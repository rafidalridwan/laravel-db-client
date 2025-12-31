@extends('dbclient::layout')

@section('title', 'SQL Query')

@section('content')
<!-- Predefined Queries Card -->
<div class="card" style="margin-bottom: 20px;">
  <h2 style="margin-bottom: 15px;">Predefined Queries</h2>
  <p style="color: #6b7280; margin-bottom: 15px; font-size: 14px;">Select a query template and fill in the details</p>

  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 10px; margin-bottom: 15px;">
    <button type="button" class="btn btn-secondary" onclick="loadQueryTemplate('select')">SELECT Query</button>
    <button type="button" class="btn btn-secondary" onclick="loadQueryTemplate('select_where')">SELECT with WHERE</button>
    <button type="button" class="btn btn-secondary" onclick="loadQueryTemplate('count')">COUNT Query</button>
    <button type="button" class="btn btn-secondary" onclick="loadQueryTemplate('insert')">INSERT Query</button>
    <button type="button" class="btn btn-secondary" onclick="loadQueryTemplate('update')">UPDATE Query</button>
    <button type="button" class="btn btn-secondary" onclick="loadQueryTemplate('delete')">DELETE Query</button>
  </div>

  <div id="queryTemplateForm" style="display: none; padding: 15px; background: #f9fafb; border-radius: 8px; margin-top: 15px;">
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 15px;">
      <div class="form-group" style="margin-bottom: 0;">
        <label for="templateTable" style="font-size: 13px;">Table Name</label>
        <select id="templateTable" class="form-control" onchange="updateTableColumns()">
          <option value="">Select table...</option>
          @foreach($tablesWithColumns as $table => $columns)
          <option value="{{ $table }}">{{ $table }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" id="templateColumnsGroup" style="margin-bottom: 0; display: none;">
        <label for="templateColumns" style="font-size: 13px;">Columns (comma-separated or * for all)</label>
        <input type="text" id="templateColumns" class="form-control" placeholder="id, name, email or *">
        <div id="columnsSuggestions" style="margin-top: 5px; font-size: 12px; color: #6b7280;"></div>
      </div>
    </div>

    <div id="templateWhereGroup" style="display: none; margin-bottom: 15px;">
      <div class="form-group" style="margin-bottom: 10px;">
        <label for="templateWhere" style="font-size: 13px;">WHERE Condition</label>
        <input type="text" id="templateWhere" class="form-control" placeholder="e.g., id = 1 AND status = 'active'">
      </div>
    </div>

    <div id="templateValuesGroup" style="display: none; margin-bottom: 15px;">
      <div class="form-group" style="margin-bottom: 10px;">
        <label for="templateValues" style="font-size: 13px;">Values (comma-separated)</label>
        <input type="text" id="templateValues" class="form-control" placeholder="e.g., 'John', 'john@example.com', '2024-01-01'">
      </div>
    </div>

    <div id="templateSetGroup" style="display: none; margin-bottom: 15px;">
      <div class="form-group" style="margin-bottom: 10px;">
        <label for="templateSet" style="font-size: 13px;">SET Clause</label>
        <input type="text" id="templateSet" class="form-control" placeholder="e.g., name = 'John', email = 'john@example.com'">
      </div>
    </div>

    <div style="display: flex; gap: 10px;">
      <button type="button" class="btn btn-primary" onclick="generateQuery()">Generate Query</button>
      <button type="button" class="btn btn-secondary" onclick="clearTemplate()">Clear Template</button>
    </div>
  </div>
</div>

<div class="card">
  <h2>SQL Query Runner</h2>
  <p style="color: #6b7280; margin-top: 10px; margin-bottom: 20px;">
    Execute SQL queries against your database.
    @if(config('dbclient.read_only'))
    <strong style="color: #dc2626;">Read-only mode is enabled.</strong>
    @endif
  </p>

  <form id="queryForm">
    <div class="form-group">
      <label for="sqlQuery">SQL Query</label>
      <textarea
        id="sqlQuery"
        name="query"
        class="form-control"
        rows="8"
        placeholder="SELECT * FROM users LIMIT 10;"
        style="font-family: 'Courier New', monospace;"></textarea>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
      <button type="submit" class="btn btn-primary">Execute Query</button>
      <button type="button" class="btn btn-secondary" onclick="clearQuery()">Clear</button>
      <div style="margin-left: auto; display: flex; align-items: center; gap: 10px;">
        <label style="color: #6b7280; font-size: 14px;">Per Page:</label>
        <select id="perPageSelect" class="form-control" style="width: auto; min-width: 80px;">
          <option value="25">25</option>
          <option value="50" selected>50</option>
          <option value="100">100</option>
          <option value="200">200</option>
        </select>
      </div>
    </div>
  </form>
</div>

<div id="results" style="display: none;">
  <div class="card">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
      <h3>Query Results</h3>
      <div>
        <span id="resultCount" class="badge badge-primary"></span>
        <span id="executionTime" class="badge badge-success" style="margin-left: 10px;"></span>
      </div>
    </div>
    <div id="errorMessage" class="alert alert-error" style="display: none;"></div>

    <!-- Loading Indicator -->
    <div id="loadingIndicator" style="text-align: center; padding: 40px; display: none;">
      <div style="display: inline-block; width: 40px; height: 40px; border: 4px solid #e5e7eb; border-top-color: #2563eb; border-radius: 50%; animation: spin 1s linear infinite;"></div>
      <p style="margin-top: 15px; color: #6b7280;">Loading data...</p>
    </div>

    <div id="tableContainer" style="overflow-x: auto;">
      <table id="resultsTable" class="table">
        <thead id="resultsTableHead"></thead>
        <tbody id="resultsTableBody"></tbody>
      </table>
    </div>

    <!-- Pagination -->
    <div id="paginationContainer" style="display: none; margin-top: 20px;">
      <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div style="color: #6b7280; font-size: 14px;">
          Showing <strong id="showingFrom">0</strong> to <strong id="showingTo">0</strong> of <strong id="showingTotal">0</strong> entries
        </div>
        <div class="pagination" id="pagination">
          <!-- Pagination will be generated by JavaScript -->
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('styles')
<style>
  @keyframes spin {
    to {
      transform: rotate(360deg);
    }
  }

  #resultsTable th {
    background: #f9fafb;
    position: sticky;
    top: 0;
    z-index: 10;
    font-weight: 600;
  }
</style>
@endpush

@push('scripts')
<script>
  let currentQuery = '';
  let currentPage = 1;
  let perPage = 50;

  $('#queryForm').on('submit', function(e) {
    e.preventDefault();

    const query = $('#sqlQuery').val().trim();
    if (!query) {
      showToast({
        eleWrapper: '#dbclient-toaster',
        msg: 'Please enter a SQL query',
        theme: 'warning',
        closeButton: true,
        autoClose: true
      });
      return;
    }

    currentQuery = query;
    currentPage = 1;
    executeQuery(currentPage);
  });

  function executeQuery(page = 1) {
    currentPage = page;

    $('#loadingIndicator').show();
    $('#tableContainer').hide();
    $('#paginationContainer').hide();
    $('#errorMessage').hide();

    const submitBtn = $('#queryForm').find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.text('Executing...').prop('disabled', true);

    $.ajax({
      url: '{{ route("dbclient.query.run") }}',
      type: 'POST',
      data: {
        query: currentQuery,
        page: currentPage,
        per_page: perPage
      },
      success: function(response) {
        submitBtn.text(originalText).prop('disabled', false);

        if (response.success) {
          displayResults(response.data, response);
          $('#errorMessage').hide();
        } else {
          showError(response.error);
        }
      },
      error: function(xhr) {
        submitBtn.text(originalText).prop('disabled', false);
        const error = xhr.responseJSON?.error || 'Failed to execute query';
        showError(error);
      }
    });
  }

  function displayResults(data, response) {
    if (!data || data.length === 0) {
      $('#resultsTableBody').html('<tr><td colspan="100%" style="text-align: center; padding: 40px; color: #6b7280;">No results found</td></tr>');
      $('#resultsTableHead').html('');
      $('#resultCount').text('0 rows');
      $('#executionTime').text(response.execution_time || '0ms');
      $('#results').show();
      $('#loadingIndicator').hide();
      $('#tableContainer').show();
      $('#paginationContainer').hide();
      return;
    }

    // Get column names from first row
    const columns = Object.keys(data[0]);

    // Build table header
    let headerHtml = '<tr>';
    columns.forEach(col => {
      headerHtml += `<th>${escapeHtml(col)}</th>`;
    });
    headerHtml += '</tr>';
    $('#resultsTableHead').html(headerHtml);

    // Build table body
    let bodyHtml = '';
    data.forEach(row => {
      bodyHtml += '<tr>';
      columns.forEach(col => {
        let value = row[col];
        if (value === null || value === undefined) {
          value = '<em style="color: #9ca3af;">NULL</em>';
        } else if (typeof value === 'string' && value.length > 100) {
          value = `<span title="${escapeHtml(value)}">${escapeHtml(value.substring(0, 100))}...</span>`;
        } else {
          value = escapeHtml(String(value));
        }
        bodyHtml += `<td>${value}</td>`;
      });
      bodyHtml += '</tr>';
    });
    $('#resultsTableBody').html(bodyHtml);

    $('#resultCount').text(`${response.total || data.length} row${(response.total || data.length) !== 1 ? 's' : ''}`);
    $('#executionTime').text(response.execution_time || '0ms');

    // Update pagination
    updatePagination(response);
    updateStats(response);

    $('#loadingIndicator').hide();
    $('#tableContainer').show();
    $('#results').show();
  }

  function updatePagination(response) {
    if (response.last_page <= 1) {
      $('#paginationContainer').hide();
      return;
    }

    $('#paginationContainer').show();

    let html = '';
    const current = response.current_page;
    const last = response.last_page;

    // Previous button
    if (current > 1) {
      html += '<a href="#" onclick="executeQuery(' + (current - 1) + '); return false;">&laquo; Prev</a>';
    } else {
      html += '<span class="disabled">&laquo; Prev</span>';
    }

    // Page numbers
    let start = Math.max(1, current - 2);
    let end = Math.min(last, current + 2);

    if (start > 1) {
      html += '<a href="#" onclick="executeQuery(1); return false;">1</a>';
      if (start > 2) {
        html += '<span>...</span>';
      }
    }

    for (let i = start; i <= end; i++) {
      if (i === current) {
        html += '<span class="active">' + i + '</span>';
      } else {
        html += '<a href="#" onclick="executeQuery(' + i + '); return false;">' + i + '</a>';
      }
    }

    if (end < last) {
      if (end < last - 1) {
        html += '<span>...</span>';
      }
      html += '<a href="#" onclick="executeQuery(' + last + '); return false;">' + last + '</a>';
    }

    // Next button
    if (current < last) {
      html += '<a href="#" onclick="executeQuery(' + (current + 1) + '); return false;">Next &raquo;</a>';
    } else {
      html += '<span class="disabled">Next &raquo;</span>';
    }

    $('#pagination').html(html);
  }

  function updateStats(response) {
    $('#showingFrom').text(response.from || 0);
    $('#showingTo').text(response.to || 0);
    $('#showingTotal').text(response.total || 0);
  }

  function showError(error) {
    showToast({
      eleWrapper: '#dbclient-toaster',
      msg: error,
      theme: 'error',
      closeButton: true,
      autoClose: true
    });
    $('#errorMessage').text(error).show();
    $('#resultsTableHead').html('');
    $('#resultsTableBody').html('');
    $('#loadingIndicator').hide();
    $('#tableContainer').show();
    $('#paginationContainer').hide();
    $('#results').show();
  }

  function clearQuery() {
    $('#sqlQuery').val('');
    $('#results').hide();
    $('#errorMessage').hide();
    currentQuery = '';
  }

  function escapeHtml(text) {
    const map = {
      '&': '&amp;',
      '<': '&lt;',
      '>': '&gt;',
      '"': '&quot;',
      "'": '&#039;'
    };
    return String(text).replace(/[&<>"']/g, function(m) {
      return map[m];
    });
  }

  // Per page change handler
  $('#perPageSelect').on('change', function() {
    perPage = $(this).val();
    if (currentQuery) {
      executeQuery(1);
    }
  });

  // Keyboard shortcut: Ctrl+Enter to execute
  $('#sqlQuery').on('keydown', function(e) {
    if (e.ctrlKey && e.key === 'Enter') {
      $('#queryForm').submit();
    }
  });

  // Predefined Query Templates
  let currentTemplate = '';
  const tablesWithColumns = @json($tablesWithColumns);

  function loadQueryTemplate(template) {
    currentTemplate = template;
    document.getElementById('queryTemplateForm').style.display = 'block';
    document.getElementById('templateTable').value = '';
    document.getElementById('templateColumns').value = '';
    document.getElementById('templateWhere').value = '';
    document.getElementById('templateValues').value = '';
    document.getElementById('templateSet').value = '';
    document.getElementById('templateColumnsGroup').style.display = 'none';
    document.getElementById('templateWhereGroup').style.display = 'none';
    document.getElementById('templateValuesGroup').style.display = 'none';
    document.getElementById('templateSetGroup').style.display = 'none';
    document.getElementById('columnsSuggestions').innerHTML = '';

    // Show relevant fields based on template
    if (['select', 'select_where', 'count'].includes(template)) {
      document.getElementById('templateColumnsGroup').style.display = 'block';
    }

    if (['select_where', 'update', 'delete'].includes(template)) {
      document.getElementById('templateWhereGroup').style.display = 'block';
    }

    if (template === 'insert') {
      document.getElementById('templateValuesGroup').style.display = 'block';
    }

    if (template === 'update') {
      document.getElementById('templateSetGroup').style.display = 'block';
    }
  }

  function updateTableColumns() {
    const tableName = document.getElementById('templateTable').value;
    const columnsGroup = document.getElementById('templateColumnsGroup');
    const suggestions = document.getElementById('columnsSuggestions');

    if (tableName && tablesWithColumns[tableName]) {
      columnsGroup.style.display = 'block';
      const columns = tablesWithColumns[tableName];
      suggestions.innerHTML = 'Available columns: <strong>' + columns.join(', ') + '</strong> | Use <strong>*</strong> for all columns';
    } else {
      columnsGroup.style.display = 'none';
      suggestions.innerHTML = '';
    }
  }

  function generateQuery() {
    const table = document.getElementById('templateTable').value;
    if (!table) {
      showToast({
        eleWrapper: '#dbclient-toaster',
        msg: 'Please select a table',
        theme: 'warning',
        closeButton: true,
        autoClose: true
      });
      return;
    }

    let query = '';
    const columns = document.getElementById('templateColumns').value.trim() || '*';
    const where = document.getElementById('templateWhere').value.trim();
    const values = document.getElementById('templateValues').value.trim();
    const set = document.getElementById('templateSet').value.trim();

    switch (currentTemplate) {
      case 'select':
        query = `SELECT ${columns} FROM \`${table}\` LIMIT 100;`;
        break;

      case 'select_where':
        if (!where) {
          showToast({
            eleWrapper: '#dbclient-toaster',
            msg: 'Please enter WHERE condition',
            theme: 'warning',
            closeButton: true,
            autoClose: true
          });
          return;
        }
        query = `SELECT ${columns} FROM \`${table}\` WHERE ${where} LIMIT 100;`;
        break;

      case 'count':
        query = `SELECT COUNT(*) as total FROM \`${table}\`;`;
        if (where) {
          query = `SELECT COUNT(*) as total FROM \`${table}\` WHERE ${where};`;
        }
        break;

      case 'insert':
        if (!values) {
          showToast({
            eleWrapper: '#dbclient-toaster',
            msg: 'Please enter values',
            theme: 'warning',
            closeButton: true,
            autoClose: true
          });
          return;
        }
        const insertColumns = columns === '*' ? '' : `(${columns})`;
        query = `INSERT INTO \`${table}\` ${insertColumns} VALUES (${values});`;
        break;

      case 'update':
        if (!set) {
          showToast({
            eleWrapper: '#dbclient-toaster',
            msg: 'Please enter SET clause',
            theme: 'warning',
            closeButton: true,
            autoClose: true
          });
          return;
        }
        if (!where) {
          showToast({
            eleWrapper: '#dbclient-toaster',
            msg: 'Please enter WHERE condition (required for UPDATE)',
            theme: 'warning',
            closeButton: true,
            autoClose: true
          });
          return;
        }
        query = `UPDATE \`${table}\` SET ${set} WHERE ${where};`;
        break;

      case 'delete':
        if (!where) {
          showToast({
            eleWrapper: '#dbclient-toaster',
            msg: 'Please enter WHERE condition (required for DELETE)',
            theme: 'warning',
            closeButton: true,
            autoClose: true
          });
          return;
        }
        query = `DELETE FROM \`${table}\` WHERE ${where};`;
        break;
    }

    if (query) {
      document.getElementById('sqlQuery').value = query;
      // Scroll to query textarea
      document.getElementById('sqlQuery').scrollIntoView({
        behavior: 'smooth',
        block: 'center'
      });
    }
  }

  function clearTemplate() {
    currentTemplate = '';
    document.getElementById('queryTemplateForm').style.display = 'none';
    document.getElementById('templateTable').value = '';
    document.getElementById('templateColumns').value = '';
    document.getElementById('templateWhere').value = '';
    document.getElementById('templateValues').value = '';
    document.getElementById('templateSet').value = '';
  }
</script>
@endpush