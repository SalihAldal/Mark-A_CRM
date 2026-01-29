@extends('layouts.app')

@section('title', 'Lead - ' . $lead->name . ' - Mark-A CRM')
@section('page_title', 'Leads')

@section('content')
    @php($roleKey = (string)(auth()->user()->role?->key ?? ''))
    <div class="card">
        <div class="toolbar" style="justify-content:space-between; margin-bottom:6px;">
            <div>
                <div class="pageTitle">{{ $lead->name }}</div>
                <div class="muted">
                    {{ $lead->email ?? '-' }} • {{ $lead->phone ?? '-' }} • #{{ $lead->id }}
                </div>
            </div>
            <div class="toolbar" style="margin:0;">
                <a class="btn" href="/leads">← Geri</a>
                @if(!empty($threadId))
                    <a class="btn btnPrimary" href="/chats?thread={{ (int)$threadId }}">Chat</a>
                @endif
            </div>
        </div>

        <div class="filters" style="margin-top:12px;">
            <div>
                <div class="label">Kaynak</div>
                <div style="font-weight:900">{{ $lead->source }}</div>
            </div>
            <div>
                <div class="label">Stage</div>
                <div style="font-weight:900">{{ $stage?->name ?? '-' }}</div>
            </div>
            <div>
                <div class="label">Durum</div>
                <div style="font-weight:900">{{ $lead->status }}</div>
            </div>
            <div>
                <div class="label">Skor</div>
                <div style="font-weight:900">{{ (int)$lead->score }}</div>
            </div>
            <div>
                <div class="label">Devralan</div>
                @if($roleKey === 'tenant_admin')
                    <div style="display:flex; gap:8px; align-items:center; margin-top:6px;">
                        <select class="input js-assign-lead" data-lead-id="{{ (int)$lead->id }}" style="min-width:220px;">
                            <option value="">—</option>
                            @foreach(($assignableUsers ?? []) as $u)
                                <option value="{{ (int) data_get($u,'id',0) }}" {{ (int)($lead->assigned_user_id ?? 0) === (int) data_get($u,'id',0) ? 'selected' : '' }}>
                                    {{ data_get($u,'name','') }}
                                </option>
                            @endforeach
                        </select>
                        <div class="muted js-assign-status" style="display:none;">Kaydediliyor…</div>
                    </div>
                @else
                    <div style="font-weight:900">{{ $assigned?->name ?? '—' }}</div>
                @endif
            </div>
            <div>
                <div class="label">Son temas</div>
                <div style="font-weight:900">{{ $lead->last_contact_at ? \Illuminate\Support\Carbon::parse($lead->last_contact_at)->format('d.m.Y H:i') : '—' }}</div>
            </div>
        </div>
    </div>

    @if($roleKey !== 'customer')
        <div class="card" style="margin-top:14px;">
            <div class="toolbar" style="justify-content:space-between;">
                <div>
                    <div class="pageTitle">Notlar</div>
                </div>
            </div>

            <form method="POST" action="/leads/{{ $lead->id }}/notes" style="margin-top:12px;">
                @csrf
                <div class="label">Yeni Not</div>
                <textarea class="input" name="note_text" rows="3" placeholder="Not yaz..." required></textarea>
                <div class="filtersActions" style="margin-top:10px;">
                    <button class="btn btnPrimary" type="submit">Not Ekle</button>
                </div>
            </form>
        </div>

        <div class="card" style="margin-top:14px; padding:0;">
            <div class="tableWrap">
                <table class="table">
                    <thead>
                    <tr>
                        <th style="padding-left:16px;">Not</th>
                        <th>Yazan</th>
                        <th style="padding-right:16px; text-align:right;">Tarih</th>
                    </tr>
                    </thead>
                    <tbody>
                    @forelse($notes as $n)
                        <tr>
                            <td style="padding-left:16px;">
                                <div style="white-space:pre-wrap; font-weight:700">{{ $n->note_text }}</div>
                            </td>
                            <td>{{ $noteAuthors[(int)$n->user_id]->name ?? '—' }}</td>
                            <td style="padding-right:16px; text-align:right;" class="muted">{{ \Illuminate\Support\Carbon::parse($n->created_at)->format('d.m.Y H:i') }}</td>
                        </tr>
                    @empty
                        <tr><td colspan="3" class="muted" style="padding:16px;">Not yok.</td></tr>
                    @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <script>
        (function () {
            function csrf() {
                const el = document.querySelector('meta[name="csrf-token"]');
                return el ? el.getAttribute('content') : '';
            }

            async function assignLead(leadId, userId, selectEl, statusEl) {
                if (!leadId || !selectEl) return;
                const token = csrf();
                if (!token) return;

                const prev = selectEl.getAttribute('data-prev') ?? selectEl.value;
                selectEl.setAttribute('data-prev', prev);
                selectEl.disabled = true;
                if (statusEl) statusEl.style.display = 'block';

                try {
                    const r = await fetch(`/leads/${leadId}/assign`, {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'Accept': 'application/json',
                            'X-CSRF-TOKEN': token,
                        },
                        body: JSON.stringify({ assigned_user_id: userId || null }),
                        credentials: 'same-origin',
                    });
                    const j = await r.json().catch(() => ({}));
                    if (!r.ok || !j.ok) {
                        selectEl.value = prev;
                        return;
                    }
                    selectEl.setAttribute('data-prev', selectEl.value);
                } catch (e) {
                    selectEl.value = prev;
                } finally {
                    selectEl.disabled = false;
                    if (statusEl) statusEl.style.display = 'none';
                }
            }

            document.addEventListener('DOMContentLoaded', () => {
                const sel = document.querySelector('.js-assign-lead');
                if (!sel) return;
                const statusEl = document.querySelector('.js-assign-status');
                sel.setAttribute('data-prev', sel.value);
                sel.addEventListener('change', () => {
                    const leadId = Number(sel.getAttribute('data-lead-id') || 0);
                    const userId = sel.value ? Number(sel.value) : null;
                    assignLead(leadId, userId, sel, statusEl);
                });
            });
        })();
    </script>
@endsection

