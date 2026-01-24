@extends('layouts.app')

@section('title', 'Super Panel - Mark-A CRM')
@section('page_title', 'Super Panel')

@section('content')
    <div class="grid2">
        <div class="card">
            <div class="cardTitle">Tenant</div>
            <div class="metric">{{ number_format($tenantCount) }}</div>
        </div>
        <div class="card">
            <div class="cardTitle">Toplam Lead</div>
            <div class="metric">{{ number_format($leadCount) }}</div>
        </div>
    </div>
@endsection

