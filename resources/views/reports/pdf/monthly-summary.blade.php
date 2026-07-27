<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Summary Report</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        h1 { font-size: 18px; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background: #f5f5f5; }
    </style>
</head>
<body>
    <h1>Monthly Summary Report</h1>
    <p>Team ID: {{ $teamId }}</p>

    <table>
        <thead>
            <tr>
                <th>Date</th>
                <th>Type</th>
                <th>Description</th>
                <th>Amount</th>
                <th>Currency</th>
                <th>Account</th>
                <th>Category</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($transactions as $transaction)
                <tr>
                    <td>{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                    <td>{{ $transaction->type->value ?? $transaction->type }}</td>
                    <td>{{ $transaction->description }}</td>
                    <td>{{ number_format((float) $transaction->amount, 2) }}</td>
                    <td>{{ $transaction->currency }}</td>
                    <td>{{ $transaction->account?->name }}</td>
                    <td>{{ $transaction->category?->name }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No transactions found for the selected filters.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
