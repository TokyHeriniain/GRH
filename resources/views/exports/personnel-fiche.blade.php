<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Fiche du personnel</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      line-height: 1.5;
      margin: 40px;
    }

    .header {
      text-align: center;
      margin-bottom: 30px;
    }

    .header img {
      width: 100px;
      margin-bottom: 10px;
    }

    .section-title {
      font-weight: bold;
      font-size: 14px;
      margin-top: 20px;
      border-bottom: 1px solid #333;
      padding-bottom: 5px;
    }

    .info {
      margin-top: 10px;
    }

    .info p {
      margin: 3px 0;
    }

    .badge {
      display: inline-block;
      padding: 2px 6px;
      background-color: #007bff;
      color: #fff;
      border-radius: 4px;
      font-size: 11px;
    }

    .photo {
      margin-top: 10px;
      border: 1px solid #ccc;
      padding: 4px;
      width: 120px;
      height: auto;
    }

    .doc {
      font-size: 11px;
      margin-top: 8px;
      padding-left: 10px;
    }

    .doc-title {
      font-weight: bold;
    }

    .doc-item {
      margin-bottom: 4px;
    }
  </style>
</head>
<body>
  <div class="header">
    <img src="{{ public_path('images/logo1-02.png') }}" class="logo1">
    <h2>Fiche individuelle - {{ $personnel->nom }} {{ $personnel->prenom }}</h2>
  </div>

  <div class="section-title">Informations personnelles</div>
  <div class="info">
    <p><strong>Matricule:</strong> {{ $personnel->matricule }}</p>
    <p><strong>Date de naissance:</strong> {{ $personnel->date_naissance }}</p>
    <p><strong>Adresse:</strong> {{ $personnel->adresse }}</p>
    <p><strong>Email:</strong> {{ $personnel->email }}</p>
    <p><strong>CIN:</strong> {{ $personnel->cin }}</p>
    <p><strong>Diplôme:</strong> {{ $personnel->diplome }}</p>
    <p><strong>Date d'entrée:</strong> {{ $personnel->date_entree }}</p>
  </div>

  <div class="section-title">Affectation</div>
  <div class="info">
    <p><strong>Direction:</strong> <span class="badge">{{ $personnel->direction->nom ?? '-' }}</span></p>
    <p><strong>Service:</strong> <span class="badge">{{ $personnel->service->nom ?? '-' }}</span></p>
    <p><strong>Fonction:</strong> <span class="badge">{{ $personnel->fonction->nom ?? '-' }}</span></p>
  </div>

  @if ($personnel->photo_path)
    <div class="section-title">Photo</div>
    <img src="{{ public_path('storage/' . $personnel->photo_path) }}" class="photo" alt="Photo du personnel">
  @endif

  @if ($personnel->documents->count())
    <div class="section-title">Documents joints</div>
    <div class="doc">
      @foreach($personnel->documents as $doc)
        <div class="doc-item">
          📎 <span class="doc-title">{{ $doc->type }}</span> – {{ $doc->nom }}
        </div>
      @endforeach
    </div>
  @endif

  <hr style="margin-top: 40px;">

    <div style="margin-top: 30px; font-size: 12px;">
    <div style="float: left; width: 45%; text-align: center;">
        <p>Fait à Antananarivo, le {{ now()->format('d/m/Y') }}</p>
        <p><strong>Signature du responsable RH</strong></p>
        <br><br>
        <p>__________________________</p>
    </div>

    <div style="float: right; width: 45%; text-align: center;">
        <p><strong>Signature du personnel</strong></p>
        <br><br>
        <p>__________________________</p>
    </div>
    </div>

    <div style="clear: both;"></div>

    <footer style="position: fixed; bottom: 20px; left: 0; right: 0; text-align: center; font-size: 10px; color: #666;">
    © {{ date('Y') }} - Département RH - Confidential
    </footer>

</body>
</html>
