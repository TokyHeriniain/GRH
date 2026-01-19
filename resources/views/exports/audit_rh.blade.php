<h3>Journal Audit RH</h3>
<table width="100%" border="1" cellspacing="0" cellpadding="5">
    <thead>
        <tr>
            <th>Date</th>
            <th>Action</th>
            <th>Personnel</th>
            <th>RH</th>
        </tr>
    </thead>
    <tbody>
        @foreach($audits as $a)
            <tr>
                <td>{{ $a->created_at->timezone(config('app.timezone')) }}</td>
                <td>{{ $a->action }}</td>
                <td>{{ $a->personnel_id }}</td>
                <td>{{ $a->actor_id }}</td>
            </tr>
        @endforeach
    </tbody>
</table>
