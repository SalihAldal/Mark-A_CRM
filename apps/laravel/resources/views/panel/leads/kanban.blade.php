@extends('layouts.app')

@section('title', __('ui.leads_kanban') . ' - Mark-A CRM')
@section('page_title', __('ui.leads_kanban'))

@section('content')
    <div class="toolbar">
        <a class="btn" href="/leads">← {{ __('ui.leads_list') }}</a>
        <button class="btn" type="button" onclick="window.location.reload()">{{ __('ui.refresh') }}</button>
    </div>

    <div class="card" x-data="kanban()">
        <div class="cardTitle">{{ __('ui.leads_kanban') }}</div>
        <div class="muted" style="margin-bottom:10px;">Sürükle bırak ile stage değiştir.</div>

        <div style="display:flex; gap:12px; overflow:auto; padding-bottom:6px;">
            @foreach($stages as $stage)
                <div
                    class="card"
                    data-stage="{{ (int)$stage->id }}"
                    style="min-width:320px; flex:0 0 320px; box-shadow:none; background: #f8fafc;"
                    @dragover.prevent
                    @drop.prevent="onDrop({{ (int)$stage->id }}, $event)"
                >
                    <div style="display:flex; align-items:center; justify-content:space-between; margin-bottom:10px;">
                        <div style="font-weight:900">{{ $stage->name }}</div>
                        <span class="badge badgeNeutral" data-count-for="{{ (int)$stage->id }}">
                            {{ ($grouped[(string)$stage->id] ?? collect())->count() }}
                        </span>
                    </div>

                    <div data-list-for="{{ (int)$stage->id }}" style="display:flex; flex-direction:column; gap:10px;">
                        @foreach(($grouped[(string)$stage->id] ?? collect()) as $lead)
                            <div
                                class="card"
                                data-lead="{{ (int)$lead->id }}"
                                style="padding:12px; box-shadow:none; cursor:grab; background:#fff;"
                                draggable="true"
                                @dragstart="onDragStart({{ (int)$lead->id }}, {{ (int)$stage->id }}, $event)"
                                @dragend="onDragEnd()"
                            >
                                <div style="font-weight:900">{{ $lead->name }}</div>
                                <div class="muted">{{ $lead->company }}</div>
                                <div class="muted">{{ $lead->phone }}</div>
                                <div style="margin-top:8px; display:flex; gap:8px; align-items:center;">
                                    <span class="badge badgeNeutral">Skor: {{ (int)$lead->score }}</span>
                                    <span class="badge badgeNeutral">{{ $lead->source }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    </div>

    <script>
        function kanban() {
            return {
                draggingLeadId: null,
                draggingFromStageId: null,
                draggingEl: null,
                draggingParent: null,
                draggingNext: null,
                saving: false,

                updateCounts() {
                    const root = this.$root;
                    if (!root) return;
                    root.querySelectorAll('[data-stage]').forEach((col) => {
                        const sid = col.getAttribute('data-stage');
                        const list = root.querySelector(`[data-list-for="${sid}"]`);
                        const countEl = root.querySelector(`[data-count-for="${sid}"]`);
                        if (list && countEl) {
                            countEl.textContent = String(list.querySelectorAll('[data-lead]').length);
                        }
                    });
                },

                onDragStart(leadId, fromStageId, ev) {
                    this.draggingLeadId = leadId;
                    this.draggingFromStageId = fromStageId;
                    this.draggingEl = ev?.currentTarget || null;
                    this.draggingParent = this.draggingEl?.parentElement || null;
                    this.draggingNext = this.draggingEl?.nextElementSibling || null;
                },
                onDragEnd() {
                    this.draggingLeadId = null;
                    this.draggingFromStageId = null;
                    this.draggingEl = null;
                    this.draggingParent = null;
                    this.draggingNext = null;
                },

                async onDrop(toStageId, ev) {
                    if (!this.draggingLeadId) return;
                    const leadId = this.draggingLeadId;
                    const fromStageId = this.draggingFromStageId;

                    const root = this.$root;
                    const col = ev?.currentTarget || null;
                    const targetList = col ? col.querySelector(`[data-list-for="${toStageId}"]`) : (root ? root.querySelector(`[data-list-for="${toStageId}"]`) : null);

                    // Optimistic UI: move card immediately
                    let moved = false;
                    if (targetList) {
                        const el = this.draggingEl || (root ? root.querySelector(`[data-lead="${leadId}"]`) : null);
                        if (el) {
                            targetList.prepend(el);
                            moved = true;
                            this.updateCounts();
                        }
                    }

                    const csrf = document.querySelector('meta[name="csrf-token"]').getAttribute('content');
                    try {
                        this.saving = true;
                        const r = await fetch(`/leads/${leadId}/move-stage`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf },
                            body: JSON.stringify({ to_stage_id: toStageId })
                        });
                        if (!r.ok) {
                            const j = await r.json().catch(() => null);
                            const msg = (j && (j.error || j.message)) ? (j.error || j.message) : `Stage güncellenemedi. (${r.status})`;

                            // Revert optimistic move
                            if (moved && this.draggingEl && this.draggingParent) {
                                if (this.draggingNext) {
                                    this.draggingParent.insertBefore(this.draggingEl, this.draggingNext);
                                } else {
                                    this.draggingParent.appendChild(this.draggingEl);
                                }
                                this.updateCounts();
                            }
                            alert(msg);
                            return;
                        }
                    } finally {
                        this.saving = false;
                        this.onDragEnd();
                    }
                }
            }
        }
    </script>
@endsection

