<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="utf-8">
    <style>
        * {
            font-family: "DejaVu Sans", sans-serif;
        }

        body {
            color: #1c1b1b;
            font-size: 11px;
        }

        .kop {
            border-bottom: 3px solid #0c5547;
            padding-bottom: 10px;
            margin-bottom: 16px;
        }

        .brand {
            color: #0c5547;
            font-size: 18px;
            font-weight: bold;
        }

        .sub {
            color: #555;
            font-size: 11px;
        }

        h1 {
            font-size: 15px;
            margin: 0 0 2px;
            color: #0c5547;
        }

        .meta {
            color: #666;
            font-size: 10px;
            margin-bottom: 12px;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
        }

        table.data th {
            background: #0c5547;
            color: #fff;
            text-align: left;
            padding: 6px 8px;
            font-size: 10px;
            text-transform: uppercase;
        }

        table.data td {
            padding: 5px 8px;
            border-bottom: 1px solid #e5e7eb;
            font-size: 10px;
        }

        table.data tr:nth-child(even) td {
            background: #f6f3f2;
        }

        .summary {
            margin-top: 14px;
            width: 45%;
            float: right;
            border-collapse: collapse;
        }

        .summary td {
            padding: 4px 8px;
            font-size: 11px;
        }

        .summary .label {
            color: #555;
        }

        .summary .val {
            text-align: right;
            font-weight: bold;
        }

        .footer {
            position: fixed;
            bottom: -12px;
            left: 0;
            right: 0;
            color: #999;
            font-size: 9px;
            text-align: center;
        }
    </style>
</head>

<body>
    <div class="kop">
        <table style="width:100%;">
            <tr>
                <td style="padding:0;">
                    <div class="brand">BWKR — Wakaf Digital</div>
                    <div class="sub">Pondok Pesantren Khulafaur Rasyidin</div>
                </td>
                <td style="padding:0; text-align:right; color:#666; font-size:10px;">
                    Dicetak: {{ $generatedAt }}
                </td>
            </tr>
        </table>
    </div>

    <h1>{{ $title }}</h1>
    @if (!empty($subtitle))
        <div class="meta">{{ $subtitle }}</div>
    @endif

    <table class="data">
        <thead>
            <tr>
                @foreach ($columns as $col)
                    <th>{{ $col }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    @foreach ($row as $cell)
                        <td>{{ $cell }}</td>
                    @endforeach
                </tr>
            @empty
                <tr>
                    <td colspan="{{ count($columns) }}" style="text-align:center; color:#999; padding:20px;">Tidak ada
                        data.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if (!empty($summary))
        <table class="summary">
            @foreach ($summary as $label => $value)
                <tr>
                    <td class="label">{{ $label }}</td>
                    <td class="val">{{ $value }}</td>
                </tr>
            @endforeach
        </table>
    @endif

    <div class="footer">Dokumen ini dihasilkan otomatis oleh sistem BWKR.</div>
</body>

</html>
