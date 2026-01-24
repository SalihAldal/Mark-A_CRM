@extends('layouts.app')

@section('title', __('ui.nav_dashboard') . ' - Mark-A CRM')
@section('page_title', __('ui.nav_dashboard'))

@section('content')
    <div class="grid2">
        <div class="card">
            <div class="cardTitle">{{ __('ui.dashboard_leads') }}</div>
            <div class="metric">{{ number_format($leadCount) }}</div>
        </div>
        <div class="card">
            <div class="cardTitle">{{ __('ui.dashboard_open_chats') }}</div>
            <div class="metric">{{ number_format($openThreadCount) }}</div>
        </div>
    </div>

    <div class="card" style="margin-top:16px;">
        <div class="cardTitle">{{ __('ui.dashboard_pipeline_snapshot') }}</div>
        <div class="tableWrap">
            <table class="table">
                <thead>
                <tr>
                    <th>{{ __('ui.stage') }}</th>
                    <th style="text-align:right">{{ __('ui.count') }}</th>
                </tr>
                </thead>
                <tbody>
                @foreach($byStage as $row)
                    <tr>
                        <td>#{{ $row->stage_id ?? '-' }}</td>
                        <td style="text-align:right">{{ number_format($row->cnt ?? 0) }}</td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
    </div>
@endsection

