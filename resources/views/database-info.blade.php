@extends('dbclient::layout')

@section('title', 'Database Information')

@section('content')
<div class="card">
  <h2 style="margin-bottom: 20px;">Database Information</h2>
  
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 20px; margin-bottom: 20px;">
    <div style="padding: 15px; background: #f9fafb; border-radius: 8px;">
      <div style="color: #6b7280; font-size: 13px; margin-bottom: 5px;">Database Name</div>
      <div style="font-size: 18px; font-weight: 600; color: #2563eb;">{{ $dbInfo['database_name'] }}</div>
    </div>
    
    <div style="padding: 15px; background: #f9fafb; border-radius: 8px;">
      <div style="color: #6b7280; font-size: 13px; margin-bottom: 5px;">Host</div>
      <div style="font-size: 18px; font-weight: 600;">{{ $dbInfo['host'] }}:{{ $dbInfo['port'] }}</div>
    </div>
    
    <div style="padding: 15px; background: #f9fafb; border-radius: 8px;">
      <div style="color: #6b7280; font-size: 13px; margin-bottom: 5px;">MySQL Version</div>
      <div style="font-size: 18px; font-weight: 600;">{{ $dbInfo['version'] }}</div>
    </div>
    
    <div style="padding: 15px; background: #f9fafb; border-radius: 8px;">
      <div style="color: #6b7280; font-size: 13px; margin-bottom: 5px;">Charset / Collation</div>
      <div style="font-size: 18px; font-weight: 600;">{{ $dbInfo['charset'] }} / {{ $dbInfo['collation'] }}</div>
    </div>
  </div>
  
  <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px;">
    <div style="padding: 20px; background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border-radius: 8px; color: white;">
      <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Total Tables</div>
      <div style="font-size: 32px; font-weight: 700;">{{ number_format($dbInfo['tables_count']) }}</div>
    </div>
    
    <div style="padding: 20px; background: linear-gradient(135deg, #f093fb 0%, #f5576c 100%); border-radius: 8px; color: white;">
      <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Total Rows</div>
      <div style="font-size: 32px; font-weight: 700;">{{ number_format($dbInfo['total_rows']) }}</div>
    </div>
    
    <div style="padding: 20px; background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%); border-radius: 8px; color: white;">
      <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Total Data Size</div>
      <div style="font-size: 32px; font-weight: 700;">
        @if($dbInfo['size_gb'] >= 0.01)
          {{ number_format($dbInfo['size_gb'], 2) }} GB
        @else
          {{ number_format($dbInfo['size_mb'], 2) }} MB
        @endif
      </div>
      @if($dbInfo['size_gb'] >= 0.01)
      <div style="font-size: 12px; opacity: 0.8; margin-top: 5px;">({{ number_format($dbInfo['size_mb'], 2) }} MB)</div>
      @endif
    </div>
    
    <div style="padding: 20px; background: linear-gradient(135deg, #43e97b 0%, #38f9d7 100%); border-radius: 8px; color: white;">
      <div style="font-size: 13px; opacity: 0.9; margin-bottom: 8px;">Driver</div>
      <div style="font-size: 32px; font-weight: 700;">{{ strtoupper($dbInfo['driver']) }}</div>
    </div>
  </div>
  
  @if(count($dbInfo['largest_tables']) > 0)
  <div style="margin-top: 25px; padding-top: 20px; border-top: 1px solid #e5e7eb;">
    <h3 style="margin-bottom: 15px; font-size: 16px; color: #374151;">Top 10 Largest Tables</h3>
    <div style="overflow-x: auto;">
      <table class="table" style="margin-top: 0;">
        <thead>
          <tr>
            <th>Table Name</th>
            <th>Rows</th>
            <th>Size (MB)</th>
            <th>Size (GB)</th>
          </tr>
        </thead>
        <tbody>
          @foreach($dbInfo['largest_tables'] as $table)
          <tr>
            <td><strong>{{ $table->table_name ?? 'N/A' }}</strong></td>
            <td><span class="badge badge-primary">{{ number_format($table->table_rows ?? 0) }}</span></td>
            <td>
              @php
                $sizeMb = $table->size_mb ?? 0;
                $sizeGb = $sizeMb / 1024;
              @endphp
              <span class="badge badge-success">{{ number_format($sizeMb, 2) }} MB</span>
            </td>
            <td>
              @if($sizeGb >= 0.01)
                <span class="badge badge-primary">{{ number_format($sizeGb, 2) }} GB</span>
              @else
                <span style="color: #9ca3af; font-size: 12px;">&lt; 0.01 GB</span>
              @endif
            </td>
          </tr>
          @endforeach
        </tbody>
      </table>
    </div>
  </div>
  @endif
</div>
@endsection

