<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="UTF-8">
  <title>Liste des personnels</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #333;
      margin: 30px;
    }

    header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 20px;
      border-bottom: 1px solid #ccc;
      padding-bottom: 10px;
    }

    header img {
      height: 50px;
    }

    h2 {
      text-align: center;
      margin: 10px 0;
      color: #2c3e50;
    }

    table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 10px;
    }

    th, td {
      border: 1px solid #ccc;
      padding: 6px 8px;
      text-align: left;
      vertical-align: middle;
    }

    th {
      background-color: #f5f5f5;
    }

    tr:nth-child(even) {
      background-color: #f9f9f9;
    }

    .footer {
      margin-top: 20px;
      font-size: 11px;
      text-align: center;
      color: #777;
      position: fixed;
      bottom: 20px;
      left: 0;
      right: 0;
    }

    .photo {
      width: 50px;
      height: 50px;
      object-fit: cover;
      border-radius: 4px;
    }

    @page {
      margin: 30px;
    }

    .pagenum:before {
      content: counter(page);
    }
  </style>
</head>
<body>
  <header>
    <img src="{{ public_path('images/logo1-02.png') }}" class="logo">
    <div><strong>Date :</strong> {{ now()->format('d/m/Y') }}</div>
  </header>

  <h2>Liste des personnels</h2>

  <table>
    <thead>
      <tr>
        <th>Photo</th>
        <th>Nom</th>
        <th>Prénom</th>
        <th>Matricule</th>
        <th>Fonction</th>
        <th>Entrée</th>
        <th>Naissance</th>
      </tr>
    </thead>
    <tbody>
      @foreach ($personnels as $p)
      <tr>
        <td>
          @if($p->photo)
            <img src="{{ public_path('storage/photos/' . $p->photo) }}" class="photo" alt="photo">
          @else
            <span>-</span>
          @endif
        </td>
        <td>{{ $p->nom }}</td>
        <td>{{ $p->prenom }}</td>
        <td>{{ $p->matricule }}</td>
        <td>{{ $p->fonction }}</td>
        <td>{{ \Carbon\Carbon::parse($p->date_entree)->format('d/m/Y') }}</td>
        <td>{{ \Carbon\Carbon::parse($p->date_naissance)->format('d/m/Y') }}</td>
      </tr>
      @endforeach
    </tbody>
  </table>

  <div class="footer">
    Page <span class="pagenum"></span>
  </div>
</body>
</html>
