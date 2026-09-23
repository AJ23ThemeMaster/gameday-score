<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Roster - {{ $team->name }}</title>
    <style>
        @page { margin: 1cm 1cm 1cm 1cm; }
        body {
            font-family: DejaVu Sans, Arial, sans-serif;
            font-size: 11px;
            color: #000;
            margin: 0;
            padding: 0;
        }

        /* Encabezado */
        .header {
            width: 100%;
            margin-bottom: 8px;
        }
        .header table {
            width: 100%;
            border-collapse: collapse;
        }
        .header td {
            vertical-align: top;
        }
        .header-logo {
            width: 110px;
            text-align: left;
        }
        .header-logo img {
            max-width: 110px;
            max-height: 110px;
        }
        .header-logo .placeholder {
            width: 110px;
            height: 110px;
            border: 1px dashed #999;
            display: inline-block;
            line-height: 110px;
            text-align: center;
            color: #999;
            font-size: 9px;
        }
        .header-school {
            text-align: center;
            font-weight: bold;
            font-size: 11.5px;
            line-height: 1.45;
        }
        .header-date {
            text-align: right;
            font-size: 11px;
            padding-top: 6px;
        }

        /* Titulos */
        .title {
            text-align: center;
            margin: 14px 0 4px;
            font-size: 16px;
            font-weight: bold;
            text-decoration: underline;
        }
        .subtitle {
            text-align: center;
            margin: 0 0 8px;
            font-size: 12px;
            font-weight: bold;
        }

        /* Tabla principal */
        table.roster {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table.roster th,
        table.roster td {
            border: 1px solid #000;
            padding: 4px 6px;
        }
        table.roster th {
            text-align: center;
            font-weight: bold;
            background: #f2f2f2;
        }
        table.roster td.center { text-align: center; }
        table.roster td.left { text-align: left; }
        table.roster td.right { text-align: right; }

        .section-label {
            text-align: center;
            font-weight: bold;
        }
    </style>
</head>
<body>
    {{-- Encabezado: logo | datos escuela | fecha --}}
    <div class="header">
        <table>
            <tr>
                <td class="header-logo" style="width: 30%;">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="logo">
                    @else
                        <span class="placeholder">Sin logo</span>
                    @endif
                </td>
                <td class="header-school" style="width: 45%;">
                    ESCUELA DE BEISBOL MENOR<br>
                    LOS PELUITOS DE CAIGUIRE<br>
                    FUNDADA 27-08-97 EN EL<br>
                    ESTADIO JOSÉ AGUSTÍN PELUO ASTUDILLO<br>
                    CUMANÁ EDO SUCRE
                </td>
                <td class="header-date" style="width: 25%;">
                    Cumaná, {{ \Illuminate\Support\Carbon::parse($today)->locale('es')->translatedFormat('j \\de F \\de Y') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="title">ROSTER.</div>
    <div class="subtitle">CATEGORÍA: {{ mb_strtoupper($category->name ?? '—') }}</div>

    {{-- Tabla de atletas --}}
    <table class="roster">
        <thead>
            <tr>
                <th style="width: 6%;">N°</th>
                <th style="width: 50%;">ATLETAS</th>
                <th style="width: 22%;">CEDULAS</th>
                <th style="width: 22%;">FECHA DE NACIMIENTO</th>
            </tr>
        </thead>
        <tbody>
            @php
                // El formato del PDF original muestra hasta 20 filas en la
                // seccion de atletas; las vacias se reservan para futuros
                // ingresos.
                $maxAthletes = max(20, $athletes->count());
            @endphp
            @for ($i = 1; $i <= $maxAthletes; $i++)
                @php
                    $a = $athletes->get($i - 1);
                @endphp
                <tr>
                    <td class="center">{{ $a ? $i : $i }}</td>
                    <td class="left">{{ $a ? $a->full_name : '' }}</td>
                    <td class="center">{{ $a && $a->document_id ? number_format((int) preg_replace('/\D+/', '', (string) $a->document_id), 0, ',', '.') : '' }}</td>
                    <td class="center">
                        {{ $a && $a->birth_date ? \Illuminate\Support\Carbon::parse($a->birth_date)->format('d/m/Y') : '' }}
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- Manager --}}
    <table class="roster" style="margin-top: -1px;">
        <tbody>
            <tr>
                <td colspan="1" class="section-label" style="width: 6%; font-weight: bold;">&nbsp;</td>
                <td colspan="1" class="section-label" style="width: 50%;">MANAGER:</td>
                <td colspan="1" class="center" style="width: 22%;">&nbsp;</td>
                <td colspan="1" class="center" style="width: 22%;">&nbsp;</td>
            </tr>
            <tr>
                <td class="center">1</td>
                <td class="left">{{ $manager?->full_name ?? '' }}</td>
                <td class="center">
                    {{ $manager && $manager->document_id ? number_format((int) preg_replace('/\D+/', '', (string) $manager->document_id), 0, ',', '.') : '' }}
                </td>
                <td class="center">
                    {{ $manager && $manager->birth_date ? \Illuminate\Support\Carbon::parse($manager->birth_date)->format('d/m/Y') : '' }}
                </td>
            </tr>
        </tbody>
    </table>

    {{-- Tecnicos (coaches adicionales del roster) --}}
    <table class="roster" style="margin-top: -1px;">
        <tbody>
            <tr>
                <td colspan="1" class="center">&nbsp;</td>
                <td colspan="1" class="section-label">TECNICOS:</td>
                <td colspan="1" class="center">&nbsp;</td>
                <td colspan="1" class="center">&nbsp;</td>
            </tr>
            @php $maxCoaches = max(4, $coaches->count()); @endphp
            @for ($i = 1; $i <= $maxCoaches; $i++)
                @php $c = $coaches->get($i - 1); @endphp
                <tr>
                    <td class="center">{{ $i }}</td>
                    <td class="left">{{ $c?->full_name ?? '' }}</td>
                    <td class="center">
                        {{ $c && $c->document_id ? number_format((int) preg_replace('/\D+/', '', (string) $c->document_id), 0, ',', '.') : '' }}
                    </td>
                    <td class="center">
                        {{ $c && $c->birth_date ? \Illuminate\Support\Carbon::parse($c->birth_date)->format('d/m/Y') : '' }}
                    </td>
                </tr>
            @endfor
        </tbody>
    </table>

    {{-- Delegado --}}
    <table class="roster" style="margin-top: -1px;">
        <tbody>
            <tr>
                <td colspan="1" class="center">&nbsp;</td>
                <td colspan="1" class="section-label">DELEGADO:</td>
                <td colspan="1" class="center">&nbsp;</td>
                <td colspan="1" class="center">&nbsp;</td>
            </tr>
            <tr>
                <td class="center">1</td>
                <td class="left">
                    @if ($delegateCoachData)
                        {{ $delegateCoachData->full_name }}
                    @else
                        {{ $delegateUser?->name ?? '' }}
                    @endif
                </td>
                <td class="center">
                    {{ $delegateCoachData && $delegateCoachData->document_id
                        ? number_format((int) preg_replace('/\D+/', '', (string) $delegateCoachData->document_id), 0, ',', '.')
                        : '' }}
                </td>
                <td class="center">
                    {{ $delegateCoachData && $delegateCoachData->birth_date
                        ? \Illuminate\Support\Carbon::parse($delegateCoachData->birth_date)->format('d/m/Y')
                        : '' }}
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
