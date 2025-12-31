@extends('dbclient::layout')

@section('title', 'Artisan Commands')

@section('content')
<div class="card">
  <h2>Artisan Commands</h2>
  <p style="color: #6b7280; margin-top: 10px; margin-bottom: 20px;">
    Run Laravel Artisan commands. Only allowed commands can be executed.
  </p>

  <form id="artisanForm">
    <div class="form-group">
      <label for="artisanCommand">Command</label>
      <select id="artisanCommand" name="command" class="form-control">
        <option value="">Select a command...</option>
        @foreach($allowedCommands as $cmd)
        <option value="{{ $cmd }}">{{ $cmd }}</option>
        @endforeach
      </select>
    </div>
    <div class="form-group">
      <label for="artisanOptions">Options (optional)</label>
      <input
        type="text"
        id="artisanOptions"
        name="options"
        class="form-control"
        placeholder="e.g., --force, --seed=DatabaseSeeder">
    </div>
    <button type="submit" class="btn btn-primary">Run Command</button>
  </form>
</div>

<div id="output" style="display: none;">
  <div class="card">
    <h3>Command Output</h3>
    <div id="outputContent" style="background: #1f2937; color: #f9fafb; padding: 20px; border-radius: 6px; font-family: 'Courier New', monospace; white-space: pre-wrap; overflow-x: auto; margin-top: 15px;"></div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  $('#artisanForm').on('submit', function(e) {
    e.preventDefault();

    const command = $('#artisanCommand').val();
    if (!command) {
      showToast({
        eleWrapper: '#dbclient-toaster',
        msg: 'Please select a command',
        theme: 'warning',
        closeButton: true,
        autoClose: true
      });
      return;
    }

    const submitBtn = $(this).find('button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.text('Running...').prop('disabled', true);

    $.ajax({
      url: '{{ route("dbclient.artisan.run") }}',
      type: 'POST',
      data: {
        command: command,
        options: $('#artisanOptions').val()
      },
      success: function(response) {
        submitBtn.text(originalText).prop('disabled', false);

        if (response.success) {
          showToast({
            eleWrapper: '#dbclient-toaster',
            msg: 'Command executed successfully',
            theme: 'success',
            closeButton: true,
            autoClose: true
          });
          $('#outputContent').text(response.output || 'Command executed successfully');
          $('#output').show();
        } else {
          showToast({
            eleWrapper: '#dbclient-toaster',
            msg: response.error || 'Unknown error',
            theme: 'error',
            closeButton: true,
            autoClose: true
          });
          $('#outputContent').text('Error: ' + (response.error || 'Unknown error'));
          $('#output').show();
        }
      },
      error: function(xhr) {
        submitBtn.text(originalText).prop('disabled', false);
        const error = xhr.responseJSON?.error || 'Failed to run command';
        showToast({
          eleWrapper: '#dbclient-toaster',
          msg: error,
          theme: 'error',
          closeButton: true,
          autoClose: true
        });
        $('#outputContent').text('Error: ' + error);
        $('#output').show();
      }
    });
  });
</script>
@endpush