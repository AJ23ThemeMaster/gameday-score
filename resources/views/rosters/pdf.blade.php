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

        /* Membrete: LOGO | EQUIPO / CIUDAD | FECHA
           Las 3 cajas son celdas independientes; cada elemento se centra
           dentro de su propio recuadro. */
        .membrete {
            width: 100%;
            margin-bottom: 14px;
            border-bottom: 1px solid #000;
            padding-bottom: 8px;
        }
        .membrete table { width: 100%; border-collapse: collapse; }
        .membrete td {
            vertical-align: middle;
            padding: 4px 6px;
            text-align: center;
        }
        .membrete-logo {
            width: 20%;
        }
        .membrete-logo img {
            max-width: 110px;
            max-height: 90px;
            display: inline-block;
        }
        .membrete-logo .placeholder {
            width: 110px;
            height: 80px;
            border: 1px dashed #999;
            display: inline-block;
            line-height: 80px;
            text-align: center;
            color: #999;
            font-size: 9px;
        }
        .membrete-names {
            width: 55%;
            font-weight: bold;
            font-size: 13px;
            line-height: 1.4;
        }
        .membrete-names .team { font-size: 14px; }
        .membrete-names .city { font-size: 11px; font-weight: normal; }
        .membrete-date {
            width: 25%;
            font-size: 12px;
            font-weight: bold;
            line-height: 1.4;
        }

        /* Titulo */
        .title {
            text-align: center;
            margin: 6px 0 2px;
            font-size: 16px;
            font-weight: bold;
        }
        .subtitle {
            text-align: center;
            margin: 0 0 10px;
            font-size: 12px;
            font-weight: bold;
        }

        /* Tabla unica (atletas + manager + tecnicos + delegado) */
        table.roster {
            width: 100%;
            border-collapse: collapse;
            font-size: 11px;
        }
        table.roster th,
        table.roster td {
            border: 1px solid #000;
            padding: 5px 6px;
            vertical-align: middle;
        }
        table.roster th {
            text-align: center;
            font-weight: bold;
            background: #e8e8e8;
        }
        table.roster td.center { text-align: center; }
        table.roster td.left { text-align: left; }

        /* Filas de seccion (MANAGER, TECNICOS, DELEGADO): ocupan las 4
           columnas (colspan=4), fondo gris y texto centrado. */
        .section-label {
            font-weight: bold;
            text-align: center;
            background: #d0d0d0;
        }

        /* Sin bordes dobles entre secciones: el navegador/PDF une
           los bordes adyacentes al colapsarlos. Como usamos
           border-collapse: collapse, las celdas consecutivas se ven
           como una sola linea de 1px. */
    </style>
</head>
<body>
    {{-- Membrete: LOGO | EQUIPO / CIUDAD | FECHA --}}
    <div class="membrete">
        <table>
            <tr>
                <td class="membrete-logo">
                    @if ($logoBase64)
                        <img src="{{ $logoBase64 }}" alt="logo">
                    @else
                        <span class="placeholder">Sin logo</span>
                    @endif
                </td>
                <td class="membrete-names">
                    <div class="team">{{ mb_strtoupper($team->name) }}</div>
                    @if (! empty($team->city))
                        <div class="city">{{ $team->city }}</div>
                    @endif
                </td>
                <td class="membrete-date">
                    {{ \Illuminate\Support\Carbon::parse($today)->format('d-m-Y') }}
                </td>
            </tr>
        </table>
    </div>

    <div class="title">ROSTER</div>
    <div class="subtitle">CATEGORÍA: {{ mb_strtoupper($category->name ?? '—') }}</div>

    {{-- Tabla unificada --}}
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
                // 20 filas en la seccion de atletas; las vacias quedan
                // disponibles para futuros ingresos.
                $maxAthletes = max(20, $athletes->count());
            @endphp

            {{-- Atletas --}}
            @for ($i = 1; $i <= $maxAthletes; $i++)
                @php $a = $athletes->get($i - 1); @endphp
                <tr>
                    <td class="center">{{ $i }}</td>
                    <td class="left">{{ $a?->full_name }}</td>
                    <td class="center">
                        {{ $a && $a->document_id ? number_format((int) preg_replace('/\D+/', '', (string) $a->document_id), 0, ',', '.') : '' }}
                    </td>
                    <td class="center">
                        {{ $a && $a->birth_date ? \Illuminate\Support\Carbon::parse($a->birth_date)->format('d/m/Y') : '' }}
                    </td>
                </tr>
            @endfor

            {{-- MANAGER --}}
            <tr>
                <td colspan="4" class="section-label">MANAGER</td>
            </tr>
            <tr>
                <td class="center">1</td>
                <td class="left">{{ $manager?->full_name }}</td>
                <td class="center">
                    {{ $manager && $manager->document_id ? number_format((int) preg_replace('/\D+/', '', (string) $manager->document_id), 0, ',', '.') : '' }}
                </td>
                <td class="center">
                    {{ $manager && $manager->birth_date ? \Illuminate\Support\Carbon::parse($manager->birth_date)->format('d/m/Y') : '' }}
                </td>
            </tr>

            {{-- TECNICOS --}}
            <tr>
                <td colspan="4" class="section-label">TECNICOS</td>
            </tr>
            @php $maxCoaches = 4; @endphp
            @for ($i = 1; $i <= $maxCoaches; $i++)
                @php $c = $coaches->get($i - 1); @endphp
                <tr>
                    <td class="center">{{ $i }}</td>
                    <td class="left">{{ $c?->full_name }}</td>
                    <td class="center">
                        {{ $c && $c->document_id ? number_format((int) preg_replace('/\D+/', '', (string) $c->document_id), 0, ',', '.') : '' }}
                    </td>
                    <td class="center">
                        {{ $c && $c->birth_date ? \Illuminate\Support\Carbon::parse($c->birth_date)->format('d/m/Y') : '' }}
                    </td>
                </tr>
            @endfor

            {{-- DELEGADO --}}
            <tr>
                <td colspan="4" class="section-label">DELEGADO</td>
            </tr>
            <tr>
                <td class="center">1</td>
                <td class="left">
                    @if ($delegateCoachData)
                        {{ $delegateCoachData->full_name }}
                    @else
                        {{ $delegateUser?->name }}
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
