<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $title ?? translate('Complete Payment') ?> - UPrimer</title>
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
            max-width: 800px;
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
        
        .payment-info { 
            background: linear-gradient(135deg, #ebf8ff 0%, #bee3f8 100%);
            border: 1px solid #90cdf4;
            border-radius: 8px;
            padding: 20px; 
            margin-bottom: 30px;
            text-align: center;
        }
        
        .payment-info p {
            margin: 5px 0;
            font-size: 16px;
        }
        
        .payment-info strong {
            color: #3182ce;
            font-weight: 600;
        }
        
        .section-title { 
            color: #2d3748; 
            font-size: 18px; 
            font-weight: 600; 
            margin: 30px 0 20px 0; 
            padding-bottom: 10px; 
            border-bottom: 2px solid #4299e1;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: #4299e1;
        }
        
        .form-group { 
            margin-bottom: 20px; 
        }
        
        .form-row { 
            display: grid; 
            grid-template-columns: 1fr 1fr; 
            gap: 20px; 
            margin-bottom: 20px; 
        }
        
        .form-row-3 {
            display: grid; 
            grid-template-columns: 1fr 1fr 1fr; 
            gap: 20px; 
            margin-bottom: 20px; 
        }
        
        label { 
            display: block; 
            margin-bottom: 8px; 
            font-weight: 500; 
            color: #4a5568;
            font-size: 14px;
        }
        
        .required { 
            color: #e53e3e; 
            margin-left: 2px;
        }
        
        input, select { 
            width: 100%; 
            padding: 12px 16px; 
            border: 2px solid #e2e8f0; 
            border-radius: 8px; 
            font-size: 14px; 
            background-color: #ffffff;
            transition: all 0.2s ease;
            outline: none;
        }
        
        input:focus, select:focus { 
            border-color: #4299e1; 
            background-color: #ebf8ff;
        }
        
        input.error, select.error {
            border-color: #e53e3e;
            background-color: #fed7d7;
        }
        
        .submit-btn { 
            background: linear-gradient(135deg, #4299e1 0%, #3182ce 100%);
            color: white; 
            border: none; 
            padding: 16px 32px; 
            border-radius: 8px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: 600; 
            width: 100%; 
            margin-top: 30px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
        }
        
        .submit-btn:hover:not(:disabled) { 
            transform: translateY(-2px);
            background: linear-gradient(135deg, #3182ce 0%, #2c5282 100%);
        }
        
        .submit-btn:disabled { 
            background: #a0aec0; 
            cursor: not-allowed;
            transform: none;
        }
        
        .submit-btn.processing {
            background: #ffa500;
            cursor: not-allowed;
            position: relative;
        }
        
        .submit-btn.processing::after {
            content: '';
            position: absolute;
            left: 50%;
            top: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid transparent;
            border-top: 2px solid white;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }
        
        .error-message { 
            color: #e53e3e; 
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border: 1px solid #fc8181; 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            display: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .success-message { 
            color: #38a169; 
            background: linear-gradient(135deg, #c6f6d5 0%, #9ae6b4 100%);
            border: 1px solid #68d391; 
            padding: 12px 16px; 
            border-radius: 8px; 
            margin-bottom: 20px; 
            display: none;
            font-size: 14px;
            font-weight: 500;
        }
        
        .loading { 
            display: none; 
            text-align: center; 
            padding: 20px;
            color: #4a5568;
        }
        
        .spinner { 
            display: inline-block; 
            width: 24px; 
            height: 24px; 
            border: 3px solid #e2e8f0; 
            border-top: 3px solid #4299e1; 
            border-radius: 50%; 
            animation: spin 1s linear infinite;
            margin-right: 10px;
        }
        
        .field-error {
            color: #e53e3e;
            font-size: 12px;
            margin-top: 4px;
            display: none;
        }
        
        .progress-bar {
            width: 100%;
            height: 4px;
            background-color: #e2e8f0;
            border-radius: 2px;
            margin: 20px 0;
            overflow: hidden;
        }
        
        .progress-bar-fill {
            height: 100%;
            background: linear-gradient(90deg, #4299e1 0%, #63b3ed 100%);
            border-radius: 2px;
            transition: width 0.3s ease;
            width: 0%;
        }
        
        .validation-errors {
            background: linear-gradient(135deg, #fed7d7 0%, #feb2b2 100%);
            border: 1px solid #fc8181;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
            color: #e53e3e;
        }
        
        .validation-errors h4 {
            margin: 0 0 10px 0;
            font-size: 16px;
            font-weight: 600;
        }
        
        .validation-errors ul {
            margin: 0;
            padding-left: 20px;
        }
        
        .validation-errors li {
            margin-bottom: 5px;
            font-size: 14px;
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
            
            .form-row, .form-row-3 {
                grid-template-columns: 1fr;
                gap: 15px;
            }
            
            .header {
                padding: 20px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>{{translate('messages.Complete Your Payment')}}</h1>
            <p>{{translate('messages.Secure payment processing powered by UPrimer')}}</p>
        </div>
        
        <div class="content">
            <div class="payment-info">
                <p><strong>{{translate('messages.Payment Amount')}}:</strong> $<?php echo $payment_amount; ?></p>
                <p><strong>{{translate('messages.Order ID')}}:</strong> <?php echo $payment_data->id; ?></p>
                <?php if ($is_wallet_payment): ?>
                <p><strong>{{translate('messages.Payment Type')}}:</strong> {{translate('messages.Wallet Payment')}}</p>
                <?php endif; ?>
            </div>

            <!-- Laravel 验证错误显示 -->
            <?php if(session('errors') && session('errors')->any()): ?>
            <div class="validation-errors">
                <h4>{{translate('messages.Please correct the following errors:')}}</h4>
                <ul>
                    <?php foreach(session('errors')->all() as $error): ?>
                    <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Laravel 错误消息显示 -->
            <?php if(session('error')): ?>
            <div class="error-message" style="display: block;">
                <?php echo htmlspecialchars(session('error')); ?>
            </div>
            <?php endif; ?>

            <!-- Laravel 成功消息显示 -->
            <?php if(session('success')): ?>
            <div class="success-message" style="display: block;">
                <?php echo htmlspecialchars(session('success')); ?>
            </div>
            <?php endif; ?>

            <div id="error-message" class="error-message"></div>
            
            <form id="payment-form" action="<?php echo $submit_url; ?>" method="POST">
                <?php echo csrf_field(); ?>
                <input type="hidden" name="payment_id" value="<?php echo $payment_data->id; ?>">
                
                <!-- 设备信息隐藏字段 -->
                <input type="hidden" name="accept_header" id="accept_header" value="">
                <input type="hidden" name="browser_java_enabled" id="browser_java_enabled" value="0">
                <input type="hidden" name="browser_javascript_enabled" id="browser_javascript_enabled" value="1">
                <input type="hidden" name="browser_user_agent" id="browser_user_agent" value="">
                <input type="hidden" name="challenge_window" id="challenge_window" value="5">
                <input type="hidden" name="language" id="language" value="">
                <input type="hidden" name="screen_color_depth" id="screen_color_depth" value="">
                <input type="hidden" name="screen_height" id="screen_height" value="">
                <input type="hidden" name="screen_width" id="screen_width" value="">
                <input type="hidden" name="timezone" id="timezone" value="">
                
                <!-- 支付卡信息 -->
                <div class="section-title">{{translate('messages.Payment Card Information')}}</div>
                
                <div class="form-group">
                    <label for="card_number">{{translate('messages.Card Number')}} <span class="required">*</span></label>
                    <input type="text" id="card_number" name="card_number" placeholder="1234567890123456" maxlength="19" required value="<?php echo old('card_number'); ?>">
                    <div class="field-error" id="card_number-error"></div>
                </div>
                
                <div class="form-row-3">
                    <div class="form-group">
                        <label for="expiry_month">{{translate('messages.Expiry Month')}} <span class="required">*</span></label>
                        <select id="expiry_month" name="expiry_month" required>
                            <option value="">{{translate('messages.Month')}}</option>
                            <?php for ($i = 1; $i <= 12; $i++): ?>
                            <option value="<?php echo sprintf('%02d', $i); ?>" <?php echo old('expiry_month') == sprintf('%02d', $i) ? 'selected' : ''; ?>><?php echo sprintf('%02d', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="field-error" id="expiry_month-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="expiry_year">{{translate('messages.Expiry Year')}} <span class="required">*</span></label>
                        <select id="expiry_year" name="expiry_year" required>
                            <option value="">{{translate('messages.Year')}}</option>
                            <?php for ($i = date('y'); $i <= date('y') + 15; $i++): ?>
                            <option value="<?php echo sprintf('%02d', $i); ?>" <?php echo old('expiry_year') == sprintf('%02d', $i) ? 'selected' : ''; ?>><?php echo sprintf('%02d', $i); ?></option>
                            <?php endfor; ?>
                        </select>
                        <div class="field-error" id="expiry_year-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="cvv">{{translate('messages.CVV')}} <span class="required">*</span></label>
                        <input type="text" id="cvv" name="cvv" placeholder="123" maxlength="4" required value="<?php echo old('cvv'); ?>">
                        <div class="field-error" id="cvv-error"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="card_holder_first_name">{{translate('messages.Cardholder First Name')}} <span class="required">*</span></label>
                        <input type="text" id="card_holder_first_name" name="card_holder_first_name" placeholder="{{translate('messages.John')}}" required value="<?php echo old('card_holder_first_name'); ?>">
                        <div class="field-error" id="card_holder_first_name-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="card_holder_last_name">{{translate('messages.Cardholder Last Name')}} <span class="required">*</span></label>
                        <input type="text" id="card_holder_last_name" name="card_holder_last_name" placeholder="{{translate('messages.Doe')}}" required value="<?php echo old('card_holder_last_name'); ?>">
                        <div class="field-error" id="card_holder_last_name-error"></div>
                    </div>
                </div>
                
                <!-- 账单地址 -->
                <div class="section-title">{{translate('messages.Billing Address')}}</div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="billing_email">{{translate('messages.Email')}} <span class="required">*</span></label>
                        <input type="email" id="billing_email" name="billing_email" placeholder="example@email.com" required value="<?php echo old('billing_email'); ?>">
                        <div class="field-error" id="billing_email-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="billing_phone">{{translate('messages.Phone Number')}} <span class="required">*</span></label>
                        <input type="text" id="billing_phone" name="billing_phone" placeholder="+1234567890" required value="<?php echo old('billing_phone'); ?>">
                        <div class="field-error" id="billing_phone-error"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="billing_country_code">{{translate('messages.Country')}} <span class="required">*</span></label>
                        <select id="billing_country_code" name="billing_country_code" required>
                            <option value="">{{translate('messages.Select Country')}}</option>
                        </select>
                        <div class="field-error" id="billing_country_code-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="billing_state">{{translate('messages.State/Province')}} <span class="required">*</span></label>
                        <input type="text" id="billing_state" name="billing_state" placeholder="{{translate('messages.California')}}" required value="<?php echo old('billing_state'); ?>">
                        <div class="field-error" id="billing_state-error"></div>
                    </div>
                </div>
                
                <div class="form-row">
                    <div class="form-group">
                        <label for="billing_city">{{translate('messages.City')}} <span class="required">*</span></label>
                        <input type="text" id="billing_city" name="billing_city" placeholder="{{translate('messages.Los Angeles')}}" required value="<?php echo old('billing_city'); ?>">
                        <div class="field-error" id="billing_city-error"></div>
                    </div>
                    <div class="form-group">
                        <label for="billing_post_code">{{translate('messages.Zip Code')}} <span class="required">*</span></label>
                        <input type="text" id="billing_post_code" name="billing_post_code" placeholder="90210" required value="<?php echo old('billing_post_code'); ?>">
                        <div class="field-error" id="billing_post_code-error"></div>
                    </div>
                </div>
                
                <div class="form-group">
                    <label for="billing_street">{{translate('messages.Street Address')}} <span class="required">*</span></label>
                    <input type="text" id="billing_street" name="billing_street" placeholder="123 Main Street" required value="<?php echo old('billing_street'); ?>">
                    <div class="field-error" id="billing_street-error"></div>
                </div>
                
                <div class="loading" id="loading">
                    <div class="spinner"></div>
                    <span>{{translate('messages.Processing payment...')}}</span>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="progress-fill"></div>
                    </div>
                </div>
                
                <button type="submit" class="submit-btn" id="submit-btn">
                    {{translate('messages.Complete Payment')}} ($<?php echo $payment_amount; ?>)
                </button>
            </form>

            <script>
                // 防止重复提交的标志
                let isSubmitting = false;
                let progressInterval = null;

                // 国家列表
                const countries = [
                    { "code": "AD", "name": "Andorra" },
                    { "code": "AE", "name": "United Arab Emirates (the)" },
                    { "code": "AF", "name": "Afghanistan" },
                    { "code": "AG", "name": "Antigua and Barbuda" },
                    { "code": "AI", "name": "Anguilla" },
                    { "code": "AL", "name": "Albania" },
                    { "code": "AM", "name": "Armenia" },
                    { "code": "AO", "name": "Angola" },
                    { "code": "AQ", "name": "Antarctica" },
                    { "code": "AR", "name": "Argentina" },
                    { "code": "AS", "name": "American Samoa" },
                    { "code": "AT", "name": "Austria" },
                    { "code": "AU", "name": "Australia" },
                    { "code": "AW", "name": "Aruba" },
                    { "code": "AX", "name": "Åland Islands" },
                    { "code": "AZ", "name": "Azerbaijan" },
                    { "code": "BA", "name": "Bosnia and Herzegovina" },
                    { "code": "BB", "name": "Barbados" },
                    { "code": "BD", "name": "Bangladesh" },
                    { "code": "BE", "name": "Belgium" },
                    { "code": "BF", "name": "Burkina Faso" },
                    { "code": "BG", "name": "Bulgaria" },
                    { "code": "BH", "name": "Bahrain" },
                    { "code": "BI", "name": "Burundi" },
                    { "code": "BJ", "name": "Benin" },
                    { "code": "BL", "name": "Saint Barthélemy" },
                    { "code": "BM", "name": "Bermuda" },
                    { "code": "BN", "name": "Brunei Darussalam" },
                    { "code": "BO", "name": "Bolivia (Plurinational State of)" },
                    { "code": "BQ", "name": "Bonaire, Sint Eustatius and Saba" },
                    { "code": "BR", "name": "Brazil" },
                    { "code": "BS", "name": "Bahamas (The)" },
                    { "code": "BT", "name": "Bhutan" },
                    { "code": "BV", "name": "Bouvet Island" },
                    { "code": "BW", "name": "Botswana" },
                    { "code": "BY", "name": "Belarus" },
                    { "code": "BZ", "name": "Belize" },
                    { "code": "CA", "name": "Canada" },
                    { "code": "CC", "name": "Cocos (Keeling) Islands (the)" },
                    { "code": "CD", "name": "Congo (the Democratic Republic of the)" },
                    { "code": "CF", "name": "Central African Republic (the)" },
                    { "code": "CG", "name": "Congo (the)" },
                    { "code": "CH", "name": "Switzerland" },
                    { "code": "CI", "name": "Côte d'Ivoire" },
                    { "code": "CK", "name": "Cook Islands (the)" },
                    { "code": "CL", "name": "Chile" },
                    { "code": "CM", "name": "Cameroon" },
                    { "code": "CN", "name": "China" },
                    { "code": "CO", "name": "Colombia" },
                    { "code": "CR", "name": "Costa Rica" },
                    { "code": "CU", "name": "Cuba" },
                    { "code": "CV", "name": "Cabo Verde" },
                    { "code": "CW", "name": "Curaçao" },
                    { "code": "CX", "name": "Christmas Island" },
                    { "code": "CY", "name": "Cyprus" },
                    { "code": "CZ", "name": "Czechia" },
                    { "code": "DE", "name": "Germany" },
                    { "code": "DJ", "name": "Djibouti" },
                    { "code": "DK", "name": "Denmark" },
                    { "code": "DM", "name": "Dominica" },
                    { "code": "DO", "name": "Dominican Republic (the)" },
                    { "code": "DZ", "name": "Algeria" },
                    { "code": "EC", "name": "Ecuador" },
                    { "code": "EE", "name": "Estonia" },
                    { "code": "EG", "name": "Egypt" },
                    { "code": "EH", "name": "Western Sahara*" },
                    { "code": "ER", "name": "Eritrea" },
                    { "code": "ES", "name": "Spain" },
                    { "code": "ET", "name": "Ethiopia" },
                    { "code": "FI", "name": "Finland" },
                    { "code": "FJ", "name": "Fiji" },
                    { "code": "FK", "name": "Falkland Islands (the) [Malvinas]" },
                    { "code": "FM", "name": "Micronesia (Federated States of)" },
                    { "code": "FO", "name": "Faroe Islands (the)" },
                    { "code": "FR", "name": "France" },
                    { "code": "GA", "name": "Gabon" },
                    { "code": "GB", "name": "United Kingdom of Great Britain and Northern Ireland (the)" },
                    { "code": "GD", "name": "Grenada" },
                    { "code": "GE", "name": "Georgia" },
                    { "code": "GF", "name": "French Guiana" },
                    { "code": "GG", "name": "Guernsey" },
                    { "code": "GH", "name": "Ghana" },
                    { "code": "GI", "name": "Gibraltar" },
                    { "code": "GL", "name": "Greenland" },
                    { "code": "GM", "name": "Gambia (the)" },
                    { "code": "GN", "name": "Guinea" },
                    { "code": "GP", "name": "Guadeloupe" },
                    { "code": "GQ", "name": "Equatorial Guinea" },
                    { "code": "GR", "name": "Greece" },
                    { "code": "GS", "name": "South Georgia and the South Sandwich Islands" },
                    { "code": "GT", "name": "Guatemala" },
                    { "code": "GU", "name": "Guam" },
                    { "code": "GW", "name": "Guinea-Bissau" },
                    { "code": "GY", "name": "Guyana" },
                    { "code": "HK", "name": "Hong Kong" },
                    { "code": "HM", "name": "Heard Island and McDonald Islands" },
                    { "code": "HN", "name": "Honduras" },
                    { "code": "HR", "name": "Croatia" },
                    { "code": "HT", "name": "Haiti" },
                    { "code": "HU", "name": "Hungary" },
                    { "code": "ID", "name": "Indonesia" },
                    { "code": "IE", "name": "Ireland" },
                    { "code": "IL", "name": "Israel" },
                    { "code": "IM", "name": "Isle of Man" },
                    { "code": "IN", "name": "India" },
                    { "code": "IO", "name": "British Indian Ocean Territory (the)" },
                    { "code": "IQ", "name": "Iraq" },
                    { "code": "IR", "name": "Iran (Islamic Republic of)" },
                    { "code": "IS", "name": "Iceland" },
                    { "code": "IT", "name": "Italy" },
                    { "code": "JE", "name": "Jersey" },
                    { "code": "JM", "name": "Jamaica" },
                    { "code": "JO", "name": "Jordan" },
                    { "code": "JP", "name": "Japan" },
                    { "code": "KE", "name": "Kenya" },
                    { "code": "KG", "name": "Kyrgyzstan" },
                    { "code": "KH", "name": "Cambodia" },
                    { "code": "KI", "name": "Kiribati" },
                    { "code": "KM", "name": "Comoros (the)" },
                    { "code": "KN", "name": "Saint Kitts and Nevis" },
                    { "code": "KP", "name": "Korea (the Democratic People's Republic of)" },
                    { "code": "KR", "name": "Korea (the Republic of)" },
                    { "code": "KW", "name": "Kuwait" },
                    { "code": "KY", "name": "Cayman Islands (the)" },
                    { "code": "KZ", "name": "Kazakhstan" },
                    { "code": "LA", "name": "Lao People's Democratic Republic (the)" },
                    { "code": "LB", "name": "Lebanon" },
                    { "code": "LC", "name": "Saint Lucia" },
                    { "code": "LI", "name": "Liechtenstein" },
                    { "code": "LK", "name": "Sri Lanka" },
                    { "code": "LR", "name": "Liberia" },
                    { "code": "LS", "name": "Lesotho" },
                    { "code": "LT", "name": "Lithuania" },
                    { "code": "LU", "name": "Luxembourg" },
                    { "code": "LV", "name": "Latvia" },
                    { "code": "LY", "name": "Libya" },
                    { "code": "MA", "name": "Morocco" },
                    { "code": "MC", "name": "Monaco" },
                    { "code": "MD", "name": "Moldova (the Republic of)" },
                    { "code": "ME", "name": "Montenegro" },
                    { "code": "MF", "name": "Saint Martin (French part)" },
                    { "code": "MG", "name": "Madagascar" },
                    { "code": "MH", "name": "Marshall Islands (the)" },
                    { "code": "MK", "name": "North Macedonia" },
                    { "code": "ML", "name": "Mali" },
                    { "code": "MM", "name": "Myanmar" },
                    { "code": "MN", "name": "Mongolia" },
                    { "code": "MO", "name": "Macao" },
                    { "code": "MP", "name": "Northern Mariana Islands (the)" },
                    { "code": "MQ", "name": "Martinique" },
                    { "code": "MR", "name": "Mauritania" },
                    { "code": "MS", "name": "Montserrat" },
                    { "code": "MT", "name": "Malta" },
                    { "code": "MU", "name": "Mauritius" },
                    { "code": "MV", "name": "Maldives" },
                    { "code": "MW", "name": "Malawi" },
                    { "code": "MX", "name": "Mexico" },
                    { "code": "MY", "name": "Malaysia" },
                    { "code": "MZ", "name": "Mozambique" },
                    { "code": "NA", "name": "Namibia" },
                    { "code": "NC", "name": "New Caledonia" },
                    { "code": "NE", "name": "Niger (the)" },
                    { "code": "NF", "name": "Norfolk Island" },
                    { "code": "NG", "name": "Nigeria" },
                    { "code": "NI", "name": "Nicaragua" },
                    { "code": "NL", "name": "Netherlands (Kingdom of the)" },
                    { "code": "NO", "name": "Norway" },
                    { "code": "NP", "name": "Nepal" },
                    { "code": "NR", "name": "Nauru" },
                    { "code": "NU", "name": "Niue" },
                    { "code": "NZ", "name": "New Zealand" },
                    { "code": "OM", "name": "Oman" },
                    { "code": "PA", "name": "Panama" },
                    { "code": "PE", "name": "Peru" },
                    { "code": "PF", "name": "French Polynesia" },
                    { "code": "PG", "name": "Papua New Guinea" },
                    { "code": "PH", "name": "Philippines (the)" },
                    { "code": "PK", "name": "Pakistan" },
                    { "code": "PL", "name": "Poland" },
                    { "code": "PM", "name": "Saint Pierre and Miquelon" },
                    { "code": "PN", "name": "Pitcairn" },
                    { "code": "PR", "name": "Puerto Rico" },
                    { "code": "PS", "name": "Palestine, State of" },
                    { "code": "PT", "name": "Portugal" },
                    { "code": "PW", "name": "Palau" },
                    { "code": "PY", "name": "Paraguay" },
                    { "code": "QA", "name": "Qatar" },
                    { "code": "RE", "name": "Réunion" },
                    { "code": "RO", "name": "Romania" },
                    { "code": "RS", "name": "Serbia" },
                    { "code": "RU", "name": "Russian Federation (the)" },
                    { "code": "RW", "name": "Rwanda" },
                    { "code": "SA", "name": "Saudi Arabia" },
                    { "code": "SB", "name": "Solomon Islands" },
                    { "code": "SC", "name": "Seychelles" },
                    { "code": "SD", "name": "Sudan (the)" },
                    { "code": "SE", "name": "Sweden" },
                    { "code": "SG", "name": "Singapore" },
                    { "code": "SH", "name": "Saint Helena, Ascension and Tristan da Cunha" },
                    { "code": "SI", "name": "Slovenia" },
                    { "code": "SJ", "name": "Svalbard and Jan Mayen" },
                    { "code": "SK", "name": "Slovakia" },
                    { "code": "SL", "name": "Sierra Leone" },
                    { "code": "SM", "name": "San Marino" },
                    { "code": "SN", "name": "Senegal" },
                    { "code": "SO", "name": "Somalia" },
                    { "code": "SR", "name": "Suriname" },
                    { "code": "SS", "name": "South Sudan" },
                    { "code": "ST", "name": "Sao Tome and Principe" },
                    { "code": "SV", "name": "El Salvador" },
                    { "code": "SX", "name": "Sint Maarten (Dutch part)" },
                    { "code": "SY", "name": "Syrian Arab Republic (the)" },
                    { "code": "SZ", "name": "Eswatini" },
                    { "code": "TC", "name": "Turks and Caicos Islands (the)" },
                    { "code": "TD", "name": "Chad" },
                    { "code": "TF", "name": "French Southern Territories (the)" },
                    { "code": "TG", "name": "Togo" },
                    { "code": "TH", "name": "Thailand" },
                    { "code": "TJ", "name": "Tajikistan" },
                    { "code": "TK", "name": "Tokelau" },
                    { "code": "TL", "name": "Timor-Leste" },
                    { "code": "TM", "name": "Turkmenistan" },
                    { "code": "TN", "name": "Tunisia" },
                    { "code": "TO", "name": "Tonga" },
                    { "code": "TR", "name": "Türkiye" },
                    { "code": "TT", "name": "Trinidad and Tobago" },
                    { "code": "TV", "name": "Tuvalu" },
                    { "code": "TW", "name": "Taiwan (Province of China)" },
                    { "code": "TZ", "name": "Tanzania, the United Republic of" },
                    { "code": "UA", "name": "Ukraine" },
                    { "code": "UG", "name": "Uganda" },
                    { "code": "UM", "name": "United States Minor Outlying Islands (the)" },
                    { "code": "US", "name": "United States of America (the)" },
                    { "code": "UY", "name": "Uruguay" },
                    { "code": "UZ", "name": "Uzbekistan" },
                    { "code": "VA", "name": "Holy See (the)" },
                    { "code": "VC", "name": "Saint Vincent and the Grenadines" },
                    { "code": "VE", "name": "Venezuela (Bolivarian Republic of)" },
                    { "code": "VG", "name": "Virgin Islands (British)" },
                    { "code": "VI", "name": "Virgin Islands (U.S.)" },
                    { "code": "VN", "name": "Viet Nam" },
                    { "code": "VU", "name": "Vanuatu" },
                    { "code": "WF", "name": "Wallis and Futuna" },
                    { "code": "WS", "name": "Samoa" },
                    { "code": "YE", "name": "Yemen" },
                    { "code": "YT", "name": "Mayotte" },
                    { "code": "ZA", "name": "South Africa" },
                    { "code": "ZM", "name": "Zambia" },
                    { "code": "ZW", "name": "Zimbabwe" }
                ];

                // 翻译函数
                const translations = {
                    'Please fill in all required fields.': '{{translate('messages.Please fill in all required fields.')}}',
                    'Please enter a valid email address.': '{{translate('messages.Please enter a valid email address.')}}',
                    'Card number is required.': '{{translate('messages.Card number is required.')}}',
                    'Card number must be 16 digits.': '{{translate('messages.Card number must be 16 digits.')}}',
                    'Expiry month is required.': '{{translate('messages.Expiry month is required.')}}',
                    'Expiry year is required.': '{{translate('messages.Expiry year is required.')}}',
                    'CVV is required.': '{{translate('messages.CVV is required.')}}',
                    'CVV must be 3-4 digits.': '{{translate('messages.CVV must be 3-4 digits.')}}',
                    'Cardholder first name is required.': '{{translate('messages.Cardholder first name is required.')}}',
                    'Cardholder last name is required.': '{{translate('messages.Cardholder last name is required.')}}',
                    'Email is required.': '{{translate('messages.Email is required.')}}',
                    'Phone number is required.': '{{translate('messages.Phone number is required.')}}',
                    'Country is required.': '{{translate('messages.Country is required.')}}',
                    'State/Province is required.': '{{translate('messages.State/Province is required.')}}',
                    'City is required.': '{{translate('messages.City is required.')}}',
                    'Zip code is required.': '{{translate('messages.Zip code is required.')}}',
                    'Street address is required.': '{{translate('messages.Street address is required.')}}',
                    'An error occurred. Please try again.': '{{translate('messages.An error occurred. Please try again.')}}',
                    'Please wait for payment processing...': '{{translate('messages.Please wait for payment processing...')}}',
                    'Invalid card number format.': '{{translate('messages.Invalid card number format.')}}',
                    'Invalid CVV format.': '{{translate('messages.Invalid CVV format.')}}',
                    'Phone number should only contain digits, spaces, and +()-': '{{translate('messages.Phone number should only contain digits, spaces, and +()-')}}'
                };

                function translate(key) {
                    return translations[key] || key;
                }

                // 页面加载时初始化
                document.addEventListener('DOMContentLoaded', function() {
                    loadCountries();
                    initializeFormBehavior();
                    populateDeviceData();
                    restoreFormValues();
                    highlightFieldsWithErrors();
                });

                // 加载国家列表
                function loadCountries() {
                    const billingCountrySelect = document.getElementById('billing_country_code');
                    const oldCountryValue = '<?php echo old('billing_country_code'); ?>';
                    
                    // 清空现有选项
                    billingCountrySelect.innerHTML = '<option value="">{{translate('messages.Select Country')}}</option>';
                    
                    // 添加国家选项
                    countries.forEach(country => {
                        const option = new Option(country.name, country.code);
                        if (oldCountryValue && oldCountryValue === country.code) {
                            option.selected = true;
                        }
                        billingCountrySelect.add(option);
                    });
                    
                    // 如果没有旧值，默认选择美国
                    if (!oldCountryValue) {
                        billingCountrySelect.value = 'US';
                    }
                }

                // 填充设备数据
                function populateDeviceData() {
                    document.getElementById('accept_header').value = 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8';
                    document.getElementById('browser_java_enabled').value = navigator.javaEnabled() ? 1 : 0;
                    document.getElementById('browser_javascript_enabled').value = 1;
                    document.getElementById('browser_user_agent').value = navigator.userAgent;
                    document.getElementById('language').value = navigator.language || 'en-US';
                    document.getElementById('screen_color_depth').value = screen.colorDepth;
                    document.getElementById('screen_height').value = screen.height;
                    document.getElementById('screen_width').value = screen.width;
                    document.getElementById('timezone').value = new Date().getTimezoneOffset();
                }

                // 恢复表单值
                function restoreFormValues() {
                    const cardNumberInput = document.getElementById('card_number');
                    if (cardNumberInput.value) {
                        let value = cardNumberInput.value.replace(/\D/g, '');
                        value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                        cardNumberInput.value = value;
                    }
                }

                // 高亮有错误的字段
                function highlightFieldsWithErrors() {
                    <?php if(session('errors') && session('errors')->any()): ?>
                    const errors = <?php echo json_encode(session('errors')->keys()); ?>;
                    errors.forEach(fieldName => {
                        const field = document.getElementById(fieldName);
                        if (field) {
                            field.classList.add('error');
                        }
                    });
                    <?php endif; ?>
                }

                // 初始化表单行为
                function initializeFormBehavior() {
                    // 卡号格式化
                    const cardNumberInput = document.getElementById('card_number');
                    cardNumberInput.addEventListener('input', function(e) {
                        let value = e.target.value.replace(/\D/g, '');
                        value = value.replace(/(\d{4})(?=\d)/g, '$1 ');
                        e.target.value = value;
                        clearFieldError('card_number');
                    });

                    // CVV 只允许数字
                    document.getElementById('cvv').addEventListener('input', function(e) {
                        e.target.value = e.target.value.replace(/\D/g, '');
                        clearFieldError('cvv');
                    });

                    // 电话号码格式验证
                    document.getElementById('billing_phone').addEventListener('input', function(e) {
                        const value = e.target.value;
                        e.target.value = value.replace(/[^\d\s\+\-\(\)]/g, '');
                        clearFieldError('billing_phone');
                    });

                    // 清除字段错误
                    const allInputs = document.querySelectorAll('input, select');
                    allInputs.forEach(input => {
                        input.addEventListener('input', function() {
                            clearFieldError(this.id);
                        });
                        
                        input.addEventListener('change', function() {
                            clearFieldError(this.id);
                        });
                    });
                }

                // 显示字段错误
                function showFieldError(fieldId, message) {
                    const field = document.getElementById(fieldId);
                    const errorDiv = document.getElementById(fieldId + '-error');
                    
                    if (field && errorDiv) {
                        field.classList.add('error');
                        errorDiv.textContent = translate(message);
                        errorDiv.style.display = 'block';
                    }
                }

                // 清除字段错误
                function clearFieldError(fieldId) {
                    const field = document.getElementById(fieldId);
                    const errorDiv = document.getElementById(fieldId + '-error');
                    
                    if (field && errorDiv) {
                        field.classList.remove('error');
                        errorDiv.style.display = 'none';
                    }
                }

                // 清除所有错误
                function clearAllErrors() {
                    const errorElements = document.querySelectorAll('.field-error');
                    errorElements.forEach(element => {
                        element.style.display = 'none';
                    });
                    
                    const errorInputs = document.querySelectorAll('.error');
                    errorInputs.forEach(input => {
                        input.classList.remove('error');
                    });
                    
                    document.getElementById('error-message').style.display = 'none';
                    
                    const validationErrors = document.querySelector('.validation-errors');
                    if (validationErrors) {
                        validationErrors.style.display = 'none';
                    }
                    
                    const errorMessage = document.querySelector('.error-message[style*="block"]');
                    if (errorMessage) {
                        errorMessage.style.display = 'none';
                    }
                }

                // 显示错误消息
                function showError(message) {
                    const errorMessage = document.getElementById('error-message');
                    errorMessage.textContent = translate(message);
                    errorMessage.style.display = 'block';
                    errorMessage.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }

                // 表单验证
                function validateForm() {
                    clearAllErrors();
                    let isValid = true;

                    // 卡片信息验证
                    const cardNumber = document.getElementById('card_number').value.replace(/\s/g, '');
                    if (!cardNumber) {
                        showFieldError('card_number', 'Card number is required.');
                        isValid = false;
                    } else if (!/^\d{13,19}$/.test(cardNumber)) {
                        showFieldError('card_number', 'Invalid card number format.');
                        isValid = false;
                    }

                    const expiryMonth = document.getElementById('expiry_month').value;
                    if (!expiryMonth) {
                        showFieldError('expiry_month', 'Expiry month is required.');
                        isValid = false;
                    }

                    const expiryYear = document.getElementById('expiry_year').value;
                    if (!expiryYear) {
                        showFieldError('expiry_year', 'Expiry year is required.');
                        isValid = false;
                    }

                    const cvv = document.getElementById('cvv').value;
                    if (!cvv) {
                        showFieldError('cvv', 'CVV is required.');
                        isValid = false;
                    } else if (!/^\d{3,4}$/.test(cvv)) {
                        showFieldError('cvv', 'CVV must be 3-4 digits.');
                        isValid = false;
                    }

                    // 持卡人姓名验证
                    if (!document.getElementById('card_holder_first_name').value.trim()) {
                        showFieldError('card_holder_first_name', 'Cardholder first name is required.');
                        isValid = false;
                    }

                    if (!document.getElementById('card_holder_last_name').value.trim()) {
                        showFieldError('card_holder_last_name', 'Cardholder last name is required.');
                        isValid = false;
                    }

                    // 账单地址验证
                    const billingRequiredFields = [
                        'billing_email', 'billing_phone', 'billing_country_code', 
                        'billing_state', 'billing_city', 'billing_post_code', 'billing_street'
                    ];

                    billingRequiredFields.forEach(fieldId => {
                        const field = document.getElementById(fieldId);
                        if (!field.value.trim()) {
                            let fieldName = fieldId.replace('billing_', '').replace('_', ' ');
                            fieldName = fieldName.charAt(0).toUpperCase() + fieldName.slice(1);
                            showFieldError(fieldId, fieldName + ' is required.');
                            isValid = false;
                        }
                    });

                    // 邮箱格式验证
                    const email = document.getElementById('billing_email').value.trim();
                    if (email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        showFieldError('billing_email', 'Please enter a valid email address.');
                        isValid = false;
                    }

                    // 电话号码格式验证
                    const phone = document.getElementById('billing_phone').value.trim();
                    if (phone && !/^[\d\s\+\-\(\)]+$/.test(phone)) {
                        showFieldError('billing_phone', 'Phone number should only contain digits, spaces, and +()-');
                        isValid = false;
                    }

                    return isValid;
                }

                // 重置表单状态
                function resetFormState() {
                    const submitBtn = document.getElementById('submit-btn');
                    const loading = document.getElementById('loading');
                    
                    submitBtn.disabled = false;
                    submitBtn.classList.remove('processing');
                    submitBtn.textContent = '{{translate('messages.Complete Payment')}} ($<?php echo $payment_amount; ?>)';
                    submitBtn.style.display = 'block';
                    loading.style.display = 'none';
                    
                    // 重置提交标志
                    isSubmitting = false;
                }

                // 表单提交处理
                document.getElementById('payment-form').addEventListener('submit', function(e) {
                    // 防止重复提交
                    if (isSubmitting) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // 验证表单
                    if (!validateForm()) {
                        e.preventDefault();
                        return false;
                    }
                    
                    // 设置提交标志
                    isSubmitting = true;
                    
                    const submitBtn = document.getElementById('submit-btn');
                    
                    // 显示等待状态
                    submitBtn.disabled = true;
                    submitBtn.classList.add('processing');
                    submitBtn.textContent = translate('Please wait for payment processing...');
                    
                    // 清除之前的错误
                    clearAllErrors();
                    
                    // 在提交前清理卡号的空格
                    const cardNumberInput = document.getElementById('card_number');
                    const cleanCardNumber = cardNumberInput.value.replace(/\s/g, '');
                    cardNumberInput.value = cleanCardNumber;
                    
                    // 表单将正常提交到服务器
                    return true;
                });

                // 处理浏览器返回按钮
                window.addEventListener('popstate', function(event) {
                    resetFormState();
                });

                // 页面卸载前的清理
                window.addEventListener('beforeunload', function() {
                    resetFormState();
                });
            </script>
        </div>
    </div>
</body>
</html>