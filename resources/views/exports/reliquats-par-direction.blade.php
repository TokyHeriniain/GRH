<table>
    <thead>
        <tr>
            <th>Direction</th>
            <th>Matricule</th>
            <th>Nom</th>
            <th>Prénom</th>
            <th>Reliquat (jours)</th>
        </tr>
    </thead>
    <tbody>
        @foreach($directions as $direction)
            @foreach($direction->personnels as $p)
                @if($p->leaveBalance)
                    <tr>
                        <td>{{ $direction->nom }}</td>
                        <td>{{ $p->matricule }}</td>
                        <td>{{ $p->nom }}</td>
                        <td>{{ $p->prenom }}</td>
                        <td>{{ number_format($p->leaveBalance->solde_global_jours, 2) }}</td>
                    </tr>
                @endif
            @endforeach
        @endforeach
    </tbody>
</table>
