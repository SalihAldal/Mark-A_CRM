<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;

class LeadScoringService
{
    public function recalcForLead(int $tenantId, int $leadId): int
    {
        // Mesaj sayısı + cevap hızı + satış sinyali (keyword) temelli skor.
        // NOTLAR bölümündeki "… …" kısımları tahmin edilmeden, sadece ölçülebilir alanlar kullanılır.

        $threads = DB::table('threads')
            ->where('tenant_id', $tenantId)
            ->where('lead_id', $leadId)
            ->pluck('id')
            ->all();

        if (empty($threads)) {
            $this->updateLead($tenantId, $leadId, 0);
            return 0;
        }

        $msgCount = (int) DB::table('messages')
            ->where('tenant_id', $tenantId)
            ->whereIn('thread_id', $threads)
            ->count();

        $contactMsgs = DB::table('messages')
            ->select('created_at', 'body_text')
            ->where('tenant_id', $tenantId)
            ->whereIn('thread_id', $threads)
            ->where('sender_type', 'contact')
            ->orderBy('created_at')
            ->get();

        $userMsgs = DB::table('messages')
            ->select('created_at')
            ->where('tenant_id', $tenantId)
            ->whereIn('thread_id', $threads)
            ->where('sender_type', 'user')
            ->orderBy('created_at')
            ->get();

        $contactCount = $contactMsgs->count();

        // Basit cevap hızı ölçümü: her müşteri mesajından sonraki ilk temsilci mesajına bak.
        $userTimes = $userMsgs->map(fn ($m) => strtotime((string) $m->created_at))->all();
        $userIdx = 0;
        $deltas = [];

        foreach ($contactMsgs as $cm) {
            $t = strtotime((string) $cm->created_at);
            while ($userIdx < count($userTimes) && $userTimes[$userIdx] <= $t) {
                $userIdx++;
            }
            if ($userIdx < count($userTimes)) {
                $delta = $userTimes[$userIdx] - $t;
                if ($delta >= 0 && $delta <= 86400) {
                    $deltas[] = $delta;
                }
            }
        }

        $avgResponse = empty($deltas) ? null : array_sum($deltas) / count($deltas);

        $responseScore = 0;
        if ($avgResponse !== null) {
            if ($avgResponse <= 300) $responseScore = 30;        // <= 5dk
            elseif ($avgResponse <= 900) $responseScore = 20;    // <= 15dk
            elseif ($avgResponse <= 3600) $responseScore = 10;   // <= 1saat
            else $responseScore = 0;
        }

        $salesSignalScore = 0;
        $kw = ['fiyat', 'teklif', 'ne kadar', 'sipariş', 'satın', 'almak', 'kaç', 'teslim', 'stok'];
        $joined = mb_strtolower($contactMsgs->pluck('body_text')->implode("\n"));
        foreach ($kw as $k) {
            if (str_contains($joined, $k)) {
                $salesSignalScore += 2;
            }
        }
        $salesSignalScore = min(20, $salesSignalScore);

        $score =
            min(40, $msgCount * 2) +
            min(30, $contactCount * 3) +
            $responseScore +
            $salesSignalScore;

        $score = max(0, min(100, (int) $score));
        $this->updateLead($tenantId, $leadId, $score);
        return $score;
    }

    private function updateLead(int $tenantId, int $leadId, int $score): void
    {
        DB::table('leads')
            ->where('tenant_id', $tenantId)
            ->where('id', $leadId)
            ->update([
                'score' => $score,
                'updated_at' => now(),
            ]);
    }
}

