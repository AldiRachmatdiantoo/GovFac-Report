<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <title>{{ $report->code }}</title>
    <style>
        @page { margin: 28mm 18mm 22mm; }
        body { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; color: #1f2937; }
        header { position: fixed; top: -20mm; left: 0; right: 0; border-bottom: 1px solid #d1d5db; padding-bottom: 4px; }
        footer { position: fixed; bottom: -14mm; left: 0; right: 0; font-size: 8pt; color: #6b7280; border-top: 1px solid #d1d5db; padding-top: 4px; }
        h1 { font-size: 15pt; margin: 0 0 6px; }
        h2 { font-size: 11pt; margin: 18px 0 6px; }
        table { width: 100%; border-collapse: collapse; }
        .meta td { padding: 3px 0; vertical-align: top; }
        .meta td.label { width: 32%; color: #6b7280; }
        .history th, .history td { border: 1px solid #d1d5db; padding: 4px 6px; text-align: left; vertical-align: top; }
        .history th { background: #f3f4f6; }
        .photo { margin: 0 0 8px; text-align: center; }
        .photo img { max-width: 100%; max-height: 95mm; }
        .description { white-space: pre-line; }
    </style>
</head>
<body>
    <header>
        <strong>GovFac Report</strong> &middot; Laporan Kerusakan Fasilitas Umum
    </header>

    <footer>
        {{ $report->code }} &middot; Dicetak pada {{ $formatDate($printedAt) }}
    </footer>

    <h1>{{ $report->title }}</h1>

    <table class="meta">
        <tr><td class="label">Nomor laporan</td><td>{{ $report->code }}</td></tr>
        <tr><td class="label">Status</td><td>{{ $statusLabels[$report->status->value] }}</td></tr>
        <tr><td class="label">Tingkat kekritisan</td><td>{{ $report->criticality ? $criticalityLabels[$report->criticality->value] : '-' }}</td></tr>
        <tr><td class="label">Revisi</td><td>{{ $report->revision }}</td></tr>
        <tr><td class="label">Pelapor</td><td>{{ $report->reporter?->name ?? '-' }}</td></tr>
        <tr><td class="label">Dibuat</td><td>{{ $formatDate($report->created_at) }}</td></tr>
        <tr><td class="label">Tagar</td><td>{{ $report->tags->map(fn ($tag) => '#'.$tag->name)->implode(' ') ?: '-' }}</td></tr>
    </table>

    <h2>Deskripsi</h2>
    <div class="description">{{ $report->description }}</div>

    <h2>Foto bukti</h2>
    @forelse ($photos as $photo)
        <div class="photo"><img src="{{ $photo }}" alt=""></div>
    @empty
        <p>-</p>
    @endforelse

    <h2>Riwayat status</h2>
    <table class="history">
        <thead>
            <tr><th>Waktu</th><th>Status</th><th>Oleh</th><th>Catatan</th></tr>
        </thead>
        <tbody>
            @foreach ($timeline as $entry)
                <tr>
                    <td>{{ $formatDate($entry->created_at) }}</td>
                    <td>
                        @if ($entry->from_status)
                            {{ $statusLabels[$entry->from_status->value] }} &rarr;
                        @endif
                        {{ $statusLabels[$entry->to_status->value] }}
                    </td>
                    <td>{{ $entry->actor?->name ?? 'Sistem' }}</td>
                    <td>
                        @if ($entry->reason_category)
                            [{{ $reasonLabels[$entry->reason_category->value] }}]
                        @endif
                        {{ $entry->note ?? '' }}
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
