import './bootstrap';

import Alpine from 'alpinejs';
import Chart from 'chart.js/auto';
import { io } from 'socket.io-client';
import { Calendar as FullCalendar } from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import timeGridPlugin from '@fullcalendar/timegrid';
import interactionPlugin from '@fullcalendar/interaction';

window.Alpine = Alpine;
window.Chart = Chart;
window.FullCalendar = {
    Calendar: FullCalendar,
    dayGridPlugin,
    timeGridPlugin,
    interactionPlugin,
};
Alpine.start();

async function initRealtime() {
    const meta = document.querySelector('meta[name="realtime-gateway"]');
    if (!meta) return;
    const baseUrl = meta.getAttribute('content') || '';
    if (!baseUrl) return;

    // Login sayfasında /realtime/token yok (auth gerekir)
    if (!document.querySelector('meta[name="csrf-token"]')) return;

    let token = null;
    try {
        const r = await fetch('/realtime/token', { credentials: 'same-origin' });
        if (!r.ok) return;
        const j = await r.json();
        token = j.token;
    } catch (e) {
        return;
    }
    if (!token) return;

    const socket = io(baseUrl, { transports: ['websocket'], auth: { token } });
    window.__realtime = socket;

    socket.on('connect', () => {
        const url = new URL(window.location.href);
        if (url.pathname.startsWith('/chats')) {
            const threadId = Number(url.searchParams.get('thread') || 0);
            if (threadId > 0) socket.emit('join_thread', { thread_id: threadId });
        }
    });

    const softReload = (() => {
        let t = null;
        return () => {
            if (t) return;
            t = setTimeout(() => {
                t = null;
                window.location.reload();
            }, 400);
        };
    })();

    socket.on('chat.message_created', softReload);
    socket.on('lead.stage_changed', softReload);
    socket.on('lead.score_updated', softReload);
}

document.addEventListener('DOMContentLoaded', () => {
    initRealtime();
});
