<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            line-height: 1.6;
            color: #333;
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 20px;
            border-radius: 8px 8px 0 0;
        }
        .content {
            background: #f9fafb;
            padding: 20px;
            border: 1px solid #e5e7eb;
            border-radius: 0 0 8px 8px;
        }
        .details {
            background: white;
            padding: 15px;
            border-radius: 6px;
            margin-top: 15px;
        }
        .detail-row {
            padding: 8px 0;
            border-bottom: 1px solid #e5e7eb;
        }
        .detail-row:last-child {
            border-bottom: none;
        }
        .detail-label {
            font-weight: bold;
            color: #6366f1;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            color: #6b7280;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1 style="margin: 0;">{{ $title }}</h1>
    </div>
    <div class="content">
        <p>Uma notificação foi gerada pelo sistema Health Check.</p>
        
        <div class="details">
            @foreach($details as $label => $value)
                <div class="detail-row">
                    <span class="detail-label">{{ $label }}:</span>
                    <span>{{ $value }}</span>
                </div>
            @endforeach
        </div>
        
        @if(isset($link))
            <p style="margin-top: 20px;">
                <a href="{{ $link->url }}" style="color: #6366f1; text-decoration: none;">
                    Acessar link →
                </a>
            </p>
        @endif
    </div>
    <div class="footer">
        <p>Health Check - Sistema de Monitoramento de Links</p>
        <p>Você recebeu este e-mail porque configurou uma integração em sua conta.</p>
    </div>
</body>
</html>
