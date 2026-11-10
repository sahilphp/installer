{{--
    Reusable responsive table. Every cell is escaped; pages pass the table
    and wrapper classes so the same markup works with each UI profile.
--}}
<div class="{{ wrapper_class ?? 'table-wrap' }}">
    <table class="{{ table_class ?? 'table' }}">
        <thead>
            <tr>
                @foreach (headers as header)
                    <th scope="col">{{ header }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @if (empty(rows))
                <tr>
                    <td colspan="{{ count(headers) }}">{{ empty_message ?? 'No records.' }}</td>
                </tr>
            @else
                @foreach (rows as row)
                    <tr>
                        @foreach (row as cell)
                            <td>{{ cell }}</td>
                        @endforeach
                    </tr>
                @endforeach
            @endif
        </tbody>
    </table>
</div>
