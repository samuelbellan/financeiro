<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Fatura {{ $cartao->nome }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 30px; }
        .header h1 { margin: 0; color: #6366f1; }
        .card-info { margin-bottom: 20px; padding: 15px; background: #f9fafb; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th { background: #6366f1; color: white; padding: 10px; text-align: left; }
        td { padding: 10px; border-bottom: 1px solid #eee; }
        .total { text-align: right; margin-top: 20px; font-size: 16px; font-weight: bold; }
    </style>
</head>
<body>
    <div class="header">
        <h1>Fatura de Cartão de Crédito</h1>
        <p>{{ Carbon\Carbon::create(null, $mes)->translatedFormat('F') }} / {{ $ano }}</p>
    </div>

    <div class="card-info">
        <strong>Cartão:</strong> {{ $cartao->nome }}<br>
        <strong>Bandeira:</strong> {{ $cartao->bandeira ?? 'VISA' }}<br>
        <strong>Limite:</strong> R$ {{ number_format($cartao->limite, 2, ',', '.') }}
    </div>

    <table>
        <thead>
            <tr>
                <th>Vencimento</th>
                <th>Descrição</th>
                <th>Parcela</th>
                <th style="text-align: right;">Valor</th>
            </tr>
        </thead>
        <tbody>
            @php $total = 0; @endphp
            @foreach($faturas as $f)
                <tr>
                    <td>{{ Carbon\Carbon::parse($f->data_vencimento)->format('d/m/Y') }}</td>
                    <td>{{ $f->compra->descricao }}</td>
                    <td>
                        @if($f->compra->tipo == 'parcelada') {{ $f->numero_parcela }}/{{ $f->compra->numero_parcelas }}
                        @elseif($f->compra->tipo == 'recorrente') Recorrente
                        @else À vista @endif
                    </td>
                    <td style="text-align: right;">R$ {{ number_format($f->valor_parcela, 2, ',', '.') }}</td>
                </tr>
                @php $total += $f->valor_parcela; @endphp
            @endforeach
        </tbody>
    </table>

    <div class="total">
        Total da Fatura: R$ {{ number_format($total, 2, ',', '.') }}
    </div>
</body>
</html>
