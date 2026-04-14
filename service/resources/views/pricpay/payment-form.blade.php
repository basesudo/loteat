<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? translate('Payment Processing') }} - PricPay</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body { 
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; 
            line-height: 1.6; 
            color: #2d3748; 
            background: linear-gradient(135deg, #f7fafc 0%, #edf2f7 100%);
            min-height: 100vh;
            padding: 20px;
        }
        
        .container { 
            max-width: 600px;
            margin: 0 auto;
            background-color: #ffffff; 
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
        }
        
        .header {
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        
        .header h1 {
            font-size: 24px;
            font-weight: 600;
            margin-bottom: 8px;
        }
        
        .header p {
            opacity: 0.9;
            font-size: 14px;
        }
        
        .content {
            padding: 30px;
        }
        
        .message { 
            padding: 16px 20px; 
            border-radius: 8px; 
            margin: 20px 0; 
            border-width: 1px;
            border-style: solid;
            font-weight: 500;
        }
        
        .success { 
            color: #2f855a; 
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            border-color: #68d391; 
        }
        
        .error { 
            color: #c53030; 
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border-color: #fc8181; 
        }
        
        .processing { 
            color: #2c5282; 
            background: linear-gradient(135deg, #bee3f8 0%, #90cdf4 100%);
            border-color: #63b3ed; 
        }
        
        .retry-btn { 
            background: linear-gradient(135deg, #718096 0%, #4a5568 100%);
            color: white; 
            border: none; 
            padding: 12px 24px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 14px; 
            font-weight: 500;
            margin-top: 15px;
            transition: all 0.2s ease;
        }
        
        .retry-btn:hover { 
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
            transform: translateY(-1px);
        }
        
        .loading { 
            text-align: center; 
            padding: 20px;
            color: #4a5568;
        }
        
        .spinner { 
            display: inline-block; 
            width: 32px; 
            height: 32px; 
            border: 4px solid #e2e8f0; 
            border-top: 4px solid #4299e1; 
            border-radius: 50%; 
            animation: spin 1s linear infinite;
            margin-bottom: 15px;
        }
        
        @keyframes spin { 
            0% { transform: rotate(0deg); } 
            100% { transform: rotate(360deg); } 
        }
        
        @media (max-width: 768px) {
            .container {
                margin: 10px;
                border-radius: 8px;
            }
            
            .content {
                padding: 20px;
            }
            
            .header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        @if($type === 'error')
            <div class="header">
                <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
                <h1>{{ translate('Payment Error') }}</h1>
                <p>{{ translate('Something went wrong with your payment') }}</p>
            </div>
            
            <div class="content">
                <div class="message error">
                    {!! $message !!}
                </div>
                @if($showRetry ?? true)
                    <button onclick="history.back()" class="retry-btn">{{ translate('Go Back') }}</button>
                @endif
            </div>
            
        @elseif($type === 'processing')
            <div class="header">
                <div style="font-size: 48px; margin-bottom: 10px;">⏳</div>
                <h1>{{ translate('Processing Payment') }}</h1>
                <p>{{ translate('Please wait while we process your payment') }}</p>
            </div>
            
            <div class="content">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>{{ translate('Redirecting to payment gateway...') }}</p>
                </div>
            </div>
            
        @elseif($type === 'success')
            <div class="header">
                <div style="font-size: 48px; margin-bottom: 10px;">✅</div>
                <h1>{{ translate('Payment Successful') }}</h1>
                <p>{{ translate('Your payment has been processed successfully') }}</p>
            </div>
            
            <div class="content">
                <div class="message success">
                    {!! $message !!}
                </div>
            </div>
            
        @else
            <div class="header">
                <div style="font-size: 48px; margin-bottom: 10px;">💳</div>
                <h1>{{ translate('Payment Processing') }}</h1>
                <p>{{ translate('Secure payment processing powered by PricPay') }}</p>
            </div>
            
            <div class="content">
                <div class="loading">
                    <div class="spinner"></div>
                    <p>{{ translate('Processing your payment request...') }}</p>
                </div>
            </div>
        @endif
    </div>
</body>
</html>