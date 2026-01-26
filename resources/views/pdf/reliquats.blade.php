<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Rapport des reliquats de congés {{ $annee }}</title>

    <style>
        body {
            font-family: DejaVu Sans, sans-serif;
            font-size: 11px;
            color: #000;
        }

        .page { page-break-after: always; }
        .page:last-child { page-break-after: auto; }

        .header {
            text-align: center;
            margin-bottom: 15px;
        }

        .header h2 {
            margin: 0;
            font-size: 18px;
        }

        .meta {
            margin-bottom: 12px;
            font-size: 11px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 8px;
        }

        th, td {
            border: 1px solid #333;
            padding: 5px 6px;
        }

        th {
            background-color: #f0f0f0;
        }

        .right { text-align: right; }

        .total-row {
            font-weight: bold;
            background-color: #f9f9f9;
        }

        .note {
            margin-top: 6px;
            font-size: 10px;
            font-style: italic;
        }

        .signature {
            margin-top: 40px;
            width: 100%;
        }

        .signature td {
            border: none;
            padding-top: 40px;
            text-align: center;
        }

        .signature .box {
            border-top: 1px solid #000;
            width: 60%;
            margin: auto;
            padding-top: 6px;
        }

        .footer {
            position: fixed;
            bottom: 10px;
            left: 0;
            right: 0;
            text-align: center;
            font-size: 10px;
            color: #666;
        }
    </style>
</head>

<body>

@php
    $grouped = collect($rows)->groupBy('direction');
@endphp

@foreach($grouped as $direction => $agents)

<div class="page">

    <div class="header">
        <h2>Liste des reliquats de congés</h2>
        <p>Situation au 01/01/{{ $annee }}</p>
    </div>

    <div class="meta">
        <p><strong>Direction :</strong> {{ $direction ?? 'Non renseignée' }}</p>
        <p><strong>Année de référence :</strong> {{ $annee }}</p>
        <p><strong>Date d’édition :</strong> {{ now()->format('d/m/Y H:i') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th>Matricule</th>
                <th>Nom & Prénom</th>
                <th>Service</th>
                <th class="right">Reliquat (jours)</th>
            </tr>
        </thead>
        <tbody>
            @foreach($agents as $row)
                <tr>
                    <td>{{ $row->matricule }}</td>
                    <td>{{ $row->personnel }}</td>
                    <td>{{ $row->service ?? '-' }}</td>
                    <td class="right">{{ number_format($row->reliquat, 2) }}</td>
                </tr>
            @endforeach

            <tr class="total-row">
                <td colspan="3">Total personnels</td>
                <td class="right">{{ collect($agents)->count() }}</td>
            </tr>
        </tbody>
    </table>

    <div class="note">
        * Reliquats exprimés en jours ouvrés, calculés conformément à la politique RH en vigueur.
    </div>

    <table class="signature">
        <tr>
            <td>
                <div class="box">Responsable RH<br>Nom & Signature</div>
            </td>
            <td>
                <div class="box">Cachet de l’entreprise</div>
            </td>
        </tr>
    </table>

</div>

@endforeach

<div class="footer">
    Rapport RH – Reliquats de congés | Document généré automatiquement
</div>

</body>
</html>
