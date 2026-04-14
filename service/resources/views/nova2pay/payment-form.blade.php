<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title ?? translate('Complete Payment') }} - Nova2Pay</title>
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
            background: linear-gradient(135deg, #039D55 0%, #028a4a 100%);
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
            background: linear-gradient(135deg, #f0fff4 0%, #e6fffa 100%);
            border: 1px solid #9ae6b4;
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
            color: #039D55;
            font-weight: 600;
        }
        
        .section-title { 
            color: #2d3748; 
            font-size: 18px; 
            font-weight: 600; 
            margin: 30px 0 20px 0; 
            padding-bottom: 10px; 
            border-bottom: 2px solid #039D55;
            position: relative;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -2px;
            left: 0;
            width: 50px;
            height: 2px;
            background-color: #039D55;
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
            border-color: #039D55; 
            background-color: #f0fff4;
        }
        
        input.error, select.error {
            border-color: #e53e3e;
            background-color: #fed7d7;
        }
        
        .submit-btn { 
            background: linear-gradient(135deg, #039D55 0%, #028a4a 100%);
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
            background: linear-gradient(135deg, #028a4a 0%, #026d3a 100%);
        }
        
        .submit-btn:disabled { 
            background: #a0aec0; 
            cursor: not-allowed;
            transform: none;
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
            border-top: 3px solid #039D55; 
            border-radius: 50%; 
            animation: spin 1s linear infinite;
            margin-right: 10px;
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
            padding: 10px 20px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 14px; 
            margin-top: 15px;
            transition: all 0.2s ease;
        }
        
        .retry-btn:hover { 
            background: linear-gradient(135deg, #4a5568 0%, #2d3748 100%);
            transform: translateY(-1px);
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
            background: linear-gradient(90deg, #039D55 0%, #48bb78 100%);
            border-radius: 2px;
            transition: width 0.3s ease;
            width: 0%;
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
            
            .form-row {
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
        @if($type === 'payment-form')
            <div class="header">
                <h1>{{ translate('Complete Your Payment') }}</h1>
                <p>{{ translate('Secure payment processing powered by Nova2Pay') }}</p>
            </div>
            
            <div class="content">
                <div class="payment-info">
                    <p><strong>{{ translate('Payment Amount') }}:</strong> ${{ $paymentData->payment_amount }}</p>
                    <p><strong>{{ translate('Order ID') }}:</strong> {{ $orderId ?? $paymentData->id }}</p>
                </div>

                <div id="error-message" class="error-message"></div>
                
                <form id="payment-form" method="POST">
                    @csrf
                    
                    <div class="section-title">{{ translate('Payment Details') }}</div>
                    
                    <div class="form-group">
                        <label for="cardNo">{{ translate('Card Number') }} <span class="required">*</span></label>
                        <input type="text" id="cardNo" name="cardNo" placeholder="{{ translate('1234567890123456') }}" maxlength="16" required>
                        <div class="field-error" id="cardNo-error"></div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="expDate">{{ translate('Expiry Date (MMYY)') }} <span class="required">*</span></label>
                            <input type="text" id="expDate" name="expDate" placeholder="{{ translate('1224') }}" maxlength="4" required>
                            <div class="field-error" id="expDate-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="cvv">{{ translate('CVV') }} <span class="required">*</span></label>
                            <input type="text" id="cvv" name="cvv" placeholder="{{ translate('123') }}" maxlength="3" required>
                            <div class="field-error" id="cvv-error"></div>
                        </div>
                    </div>
                    
                    <div class="section-title">{{ translate('Customer Information') }}</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="firstName">{{ translate('First Name') }} <span class="required">*</span></label>
                            <input type="text" id="firstName" name="firstName" placeholder="{{ translate('John') }}" required>
                            <div class="field-error" id="firstName-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="lastName">{{ translate('Last Name') }} <span class="required">*</span></label>
                            <input type="text" id="lastName" name="lastName" placeholder="{{ translate('Doe') }}" required>
                            <div class="field-error" id="lastName-error"></div>
                        </div>
                    </div>
                    
                    <div class="form-group">
                        <label for="email">{{ translate('Email') }} <span class="required">*</span></label>
                        <input type="email" id="email" name="email" placeholder="{{ translate('example@email.com') }}" required>
                        <div class="field-error" id="email-error"></div>
                    </div>
                    

                        <div class="form-group">
                            <label for="fullPhone">{{ translate('Phone Number') }} <span class="required">*</span></label>
                            <div style="display: flex; gap: 8px;">
                                <select id="countryCode" name="countryCode" style="max-width: 120px;" required>
                                    <option value="+1">+1 ({{ translate('United States / Canada') }})</option>
                                    <option value="+7">+7 ({{ translate('Russia / Kazakhstan') }})</option>
                                    <option value="+20">+20 ({{ translate('Egypt') }})</option>
                                    <option value="+27">+27 ({{ translate('South Africa') }})</option>
                                    <option value="+30">+30 ({{ translate('Greece') }})</option>
                                    <option value="+31">+31 ({{ translate('Netherlands') }})</option>
                                    <option value="+32">+32 ({{ translate('Belgium') }})</option>
                                    <option value="+33">+33 ({{ translate('France') }})</option>
                                    <option value="+34">+34 ({{ translate('Spain') }})</option>
                                    <option value="+36">+36 ({{ translate('Hungary') }})</option>
                                    <option value="+39">+39 ({{ translate('Italy') }})</option>
                                    <option value="+40">+40 ({{ translate('Romania') }})</option>
                                    <option value="+41">+41 ({{ translate('Switzerland') }})</option>
                                    <option value="+43">+43 ({{ translate('Austria') }})</option>
                                    <option value="+44">+44 ({{ translate('United Kingdom') }})</option>
                                    <option value="+45">+45 ({{ translate('Denmark') }})</option>
                                    <option value="+46">+46 ({{ translate('Sweden') }})</option>
                                    <option value="+47">+47 ({{ translate('Norway') }})</option>
                                    <option value="+48">+48 ({{ translate('Poland') }})</option>
                                    <option value="+49">+49 ({{ translate('Germany') }})</option>
                                    <option value="+51">+51 ({{ translate('Peru') }})</option>
                                    <option value="+52">+52 ({{ translate('Mexico') }})</option>
                                    <option value="+53">+53 ({{ translate('Cuba') }})</option>
                                    <option value="+54">+54 ({{ translate('Argentina') }})</option>
                                    <option value="+55">+55 ({{ translate('Brazil') }})</option>
                                    <option value="+56">+56 ({{ translate('Chile') }})</option>
                                    <option value="+57">+57 ({{ translate('Colombia') }})</option>
                                    <option value="+58">+58 ({{ translate('Venezuela') }})</option>
                                    <option value="+60">+60 ({{ translate('Malaysia') }})</option>
                                    <option value="+61">+61 ({{ translate('Australia') }})</option>
                                    <option value="+62">+62 ({{ translate('Indonesia') }})</option>
                                    <option value="+63">+63 ({{ translate('Philippines') }})</option>
                                    <option value="+64">+64 ({{ translate('New Zealand') }})</option>
                                    <option value="+65">+65 ({{ translate('Singapore') }})</option>
                                    <option value="+66">+66 ({{ translate('Thailand') }})</option>
                                    <option value="+81">+81 ({{ translate('Japan') }})</option>
                                    <option value="+82">+82 ({{ translate('South Korea') }})</option>
                                    <option value="+84">+84 ({{ translate('Vietnam') }})</option>
                                    <option value="+86">+86 ({{ translate('China') }})</option>
                                    <option value="+90">+90 ({{ translate('Turkey') }})</option>
                                    <option value="+91">+91 ({{ translate('India') }})</option>
                                    <option value="+92">+92 ({{ translate('Pakistan') }})</option>
                                    <option value="+93">+93 ({{ translate('Afghanistan') }})</option>
                                    <option value="+94">+94 ({{ translate('Sri Lanka') }})</option>
                                    <option value="+95">+95 ({{ translate('Myanmar') }})</option>
                                    <option value="+98">+98 ({{ translate('Iran') }})</option>
                                    <option value="+212">+212 ({{ translate('Morocco') }})</option>
                                    <option value="+213">+213 ({{ translate('Algeria') }})</option>
                                    <option value="+216">+216 ({{ translate('Tunisia') }})</option>
                                    <option value="+218">+218 ({{ translate('Libya') }})</option>
                                    <option value="+220">+220 ({{ translate('Gambia') }})</option>
                                    <option value="+221">+221 ({{ translate('Senegal') }})</option>
                                    <option value="+222">+222 ({{ translate('Mauritania') }})</option>
                                    <option value="+223">+223 ({{ translate('Mali') }})</option>
                                    <option value="+224">+224 ({{ translate('Guinea') }})</option>
                                    <option value="+225">+225 ({{ translate('Ivory Coast') }})</option>
                                    <option value="+226">+226 ({{ translate('Burkina Faso') }})</option>
                                    <option value="+227">+227 ({{ translate('Niger') }})</option>
                                    <option value="+228">+228 ({{ translate('Togo') }})</option>
                                    <option value="+229">+229 ({{ translate('Benin') }})</option>
                                    <option value="+230">+230 ({{ translate('Mauritius') }})</option>
                                    <option value="+231">+231 ({{ translate('Liberia') }})</option>
                                    <option value="+232">+232 ({{ translate('Sierra Leone') }})</option>
                                    <option value="+233">+233 ({{ translate('Ghana') }})</option>
                                    <option value="+234">+234 ({{ translate('Nigeria') }})</option>
                                    <option value="+235">+235 ({{ translate('Chad') }})</option>
                                    <option value="+236">+236 ({{ translate('Central African Republic') }})</option>
                                    <option value="+237">+237 ({{ translate('Cameroon') }})</option>
                                    <option value="+238">+238 ({{ translate('Cape Verde') }})</option>
                                    <option value="+239">+239 ({{ translate('Sao Tome and Principe') }})</option>
                                    <option value="+240">+240 ({{ translate('Equatorial Guinea') }})</option>
                                    <option value="+241">+241 ({{ translate('Gabon') }})</option>
                                    <option value="+242">+242 ({{ translate('Congo') }})</option>
                                    <option value="+243">+243 ({{ translate('Congo (Democratic Republic)') }})</option>
                                    <option value="+244">+244 ({{ translate('Angola') }})</option>
                                    <option value="+245">+245 ({{ translate('Guinea-Bissau') }})</option>
                                    <option value="+246">+246 ({{ translate('British Indian Ocean Territory') }})</option>
                                    <option value="+248">+248 ({{ translate('Seychelles') }})</option>
                                    <option value="+249">+249 ({{ translate('Sudan') }})</option>
                                    <option value="+250">+250 ({{ translate('Rwanda') }})</option>
                                    <option value="+251">+251 ({{ translate('Ethiopia') }})</option>
                                    <option value="+252">+252 ({{ translate('Somalia') }})</option>
                                    <option value="+253">+253 ({{ translate('Djibouti') }})</option>
                                    <option value="+254">+254 ({{ translate('Kenya') }})</option>
                                    <option value="+255">+255 ({{ translate('Tanzania') }})</option>
                                    <option value="+256">+256 ({{ translate('Uganda') }})</option>
                                    <option value="+257">+257 ({{ translate('Burundi') }})</option>
                                    <option value="+258">+258 ({{ translate('Mozambique') }})</option>
                                    <option value="+260">+260 ({{ translate('Zambia') }})</option>
                                    <option value="+261">+261 ({{ translate('Madagascar') }})</option>
                                    <option value="+262">+262 ({{ translate('Reunion') }})</option>
                                    <option value="+263">+263 ({{ translate('Zimbabwe') }})</option>
                                    <option value="+264">+264 ({{ translate('Namibia') }})</option>
                                    <option value="+265">+265 ({{ translate('Malawi') }})</option>
                                    <option value="+266">+266 ({{ translate('Lesotho') }})</option>
                                    <option value="+267">+267 ({{ translate('Botswana') }})</option>
                                    <option value="+268">+268 ({{ translate('Eswatini') }})</option>
                                    <option value="+269">+269 ({{ translate('Comoros') }})</option>
                                    <option value="+290">+290 ({{ translate('Saint Helena') }})</option>
                                    <option value="+291">+291 ({{ translate('Eritrea') }})</option>
                                    <option value="+297">+297 ({{ translate('Aruba') }})</option>
                                    <option value="+298">+298 ({{ translate('Faroe Islands') }})</option>
                                    <option value="+299">+299 ({{ translate('Greenland') }})</option>
                                    <option value="+350">+350 ({{ translate('Gibraltar') }})</option>
                                    <option value="+351">+351 ({{ translate('Portugal') }})</option>
                                    <option value="+352">+352 ({{ translate('Luxembourg') }})</option>
                                    <option value="+353">+353 ({{ translate('Ireland') }})</option>
                                    <option value="+354">+354 ({{ translate('Iceland') }})</option>
                                    <option value="+355">+355 ({{ translate('Albania') }})</option>
                                    <option value="+356">+356 ({{ translate('Malta') }})</option>
                                    <option value="+357">+357 ({{ translate('Cyprus') }})</option>
                                    <option value="+358">+358 ({{ translate('Finland') }})</option>
                                    <option value="+359">+359 ({{ translate('Bulgaria') }})</option>
                                    <option value="+370">+370 ({{ translate('Lithuania') }})</option>
                                    <option value="+371">+371 ({{ translate('Latvia') }})</option>
                                    <option value="+372">+372 ({{ translate('Estonia') }})</option>
                                    <option value="+373">+373 ({{ translate('Moldova') }})</option>
                                    <option value="+374">+374 ({{ translate('Armenia') }})</option>
                                    <option value="+375">+375 ({{ translate('Belarus') }})</option>
                                    <option value="+376">+376 ({{ translate('Andorra') }})</option>
                                    <option value="+377">+377 ({{ translate('Monaco') }})</option>
                                    <option value="+378">+378 ({{ translate('San Marino') }})</option>
                                    <option value="+380">+380 ({{ translate('Ukraine') }})</option>
                                    <option value="+381">+381 ({{ translate('Serbia') }})</option>
                                    <option value="+382">+382 ({{ translate('Montenegro') }})</option>
                                    <option value="+383">+383 ({{ translate('Kosovo') }})</option>
                                    <option value="+385">+385 ({{ translate('Croatia') }})</option>
                                    <option value="+386">+386 ({{ translate('Slovenia') }})</option>
                                    <option value="+387">+387 ({{ translate('Bosnia and Herzegovina') }})</option>
                                    <option value="+389">+389 ({{ translate('North Macedonia') }})</option>
                                    <option value="+420">+420 ({{ translate('Czech Republic') }})</option>
                                    <option value="+421">+421 ({{ translate('Slovakia') }})</option>
                                    <option value="+423">+423 ({{ translate('Liechtenstein') }})</option>
                                    <option value="+500">+500 ({{ translate('Falkland Islands') }})</option>
                                    <option value="+501">+501 ({{ translate('Belize') }})</option>
                                    <option value="+502">+502 ({{ translate('Guatemala') }})</option>
                                    <option value="+503">+503 ({{ translate('El Salvador') }})</option>
                                    <option value="+504">+504 ({{ translate('Honduras') }})</option>
                                    <option value="+505">+505 ({{ translate('Nicaragua') }})</option>
                                    <option value="+506">+506 ({{ translate('Costa Rica') }})</option>
                                    <option value="+507">+507 ({{ translate('Panama') }})</option>
                                    <option value="+508">+508 ({{ translate('Saint Pierre and Miquelon') }})</option>
                                    <option value="+509">+509 ({{ translate('Haiti') }})</option>
                                    <option value="+590">+590 ({{ translate('Guadeloupe') }})</option>
                                    <option value="+591">+591 ({{ translate('Bolivia') }})</option>
                                    <option value="+592">+592 ({{ translate('Guyana') }})</option>
                                    <option value="+593">+593 ({{ translate('Ecuador') }})</option>
                                    <option value="+594">+594 ({{ translate('French Guiana') }})</option>
                                    <option value="+595">+595 ({{ translate('Paraguay') }})</option>
                                    <option value="+596">+596 ({{ translate('Martinique') }})</option>
                                    <option value="+597">+597 ({{ translate('Suriname') }})</option>
                                    <option value="+598">+598 ({{ translate('Uruguay') }})</option>
                                    <option value="+599">+599 ({{ translate('Curacao / Caribbean Netherlands') }})</option>
                                    <option value="+670">+670 ({{ translate('Timor-Leste') }})</option>
                                    <option value="+672">+672 ({{ translate('Antarctica / Australian Territories') }})</option>
                                    <option value="+673">+673 ({{ translate('Brunei') }})</option>
                                    <option value="+674">+674 ({{ translate('Nauru') }})</option>
                                    <option value="+675">+675 ({{ translate('Papua New Guinea') }})</option>
                                    <option value="+676">+676 ({{ translate('Tonga') }})</option>
                                    <option value="+677">+677 ({{ translate('Solomon Islands') }})</option>
                                    <option value="+678">+678 ({{ translate('Vanuatu') }})</option>
                                    <option value="+679">+679 ({{ translate('Fiji') }})</option>
                                    <option value="+680">+680 ({{ translate('Palau') }})</option>
                                    <option value="+681">+681 ({{ translate('Wallis and Futuna') }})</option>
                                    <option value="+682">+682 ({{ translate('Cook Islands') }})</option>
                                    <option value="+683">+683 ({{ translate('Niue') }})</option>
                                    <option value="+684">+684 ({{ translate('American Samoa') }})</option>
                                    <option value="+685">+685 ({{ translate('Samoa') }})</option>
                                    <option value="+686">+686 ({{ translate('Kiribati') }})</option>
                                    <option value="+687">+687 ({{ translate('New Caledonia') }})</option>
                                    <option value="+688">+688 ({{ translate('Tuvalu') }})</option>
                                    <option value="+689">+689 ({{ translate('French Polynesia') }})</option>
                                    <option value="+690">+690 ({{ translate('Tokelau') }})</option>
                                    <option value="+691">+691 ({{ translate('Micronesia') }})</option>
                                    <option value="+692">+692 ({{ translate('Marshall Islands') }})</option>
                                    <option value="+850">+850 ({{ translate('North Korea') }})</option>
                                    <option value="+852">+852 ({{ translate('Hong Kong') }})</option>
                                    <option value="+853">+853 ({{ translate('Macau') }})</option>
                                    <option value="+855">+855 ({{ translate('Cambodia') }})</option>
                                    <option value="+856">+856 ({{ translate('Laos') }})</option>
                                    <option value="+880">+880 ({{ translate('Bangladesh') }})</option>
                                    <option value="+886">+886 ({{ translate('Taiwan') }})</option>
                                    <option value="+960">+960 ({{ translate('Maldives') }})</option>
                                    <option value="+961">+961 ({{ translate('Lebanon') }})</option>
                                    <option value="+962">+962 ({{ translate('Jordan') }})</option>
                                    <option value="+963">+963 ({{ translate('Syria') }})</option>
                                    <option value="+964">+964 ({{ translate('Iraq') }})</option>
                                    <option value="+965">+965 ({{ translate('Kuwait') }})</option>
                                    <option value="+966">+966 ({{ translate('Saudi Arabia') }})</option>
                                    <option value="+967">+967 ({{ translate('Yemen') }})</option>
                                    <option value="+968">+968 ({{ translate('Oman') }})</option>
                                    <option value="+970">+970 ({{ translate('Palestine') }})</option>
                                    <option value="+971">+971 ({{ translate('United Arab Emirates') }})</option>
                                    <option value="+972">+972 ({{ translate('Israel') }})</option>
                                    <option value="+973">+973 ({{ translate('Bahrain') }})</option>
                                    <option value="+974">+974 ({{ translate('Qatar') }})</option>
                                    <option value="+975">+975 ({{ translate('Bhutan') }})</option>
                                    <option value="+976">+976 ({{ translate('Mongolia') }})</option>
                                    <option value="+977">+977 ({{ translate('Nepal') }})</option>
                                    <option value="+992">+992 ({{ translate('Tajikistan') }})</option>
                                    <option value="+993">+993 ({{ translate('Turkmenistan') }})</option>
                                    <option value="+994">+994 ({{ translate('Azerbaijan') }})</option>
                                    <option value="+995">+995 ({{ translate('Georgia') }})</option>
                                    <option value="+996">+996 ({{ translate('Kyrgyzstan') }})</option>
                                    <option value="+998">+998 ({{ translate('Uzbekistan') }})</option>
                                </select>
                                <input type="text" id="phoneNo" name="phoneNo" placeholder="{{ translate('1234567890') }}" required style="flex:1;">
                            </div>
                            <div class="field-error" id="fullPhone-error"></div>
                        </div>
                    
                    <div class="section-title">{{ translate('Billing Address') }}</div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="country">{{ translate('Country') }} <span class="required">*</span></label>
                            <select id="country" name="country" required>
                                <option value="">{{ translate('Select country') }}</option>
                                
                                <!-- 亚洲 -->
                                <option value="AF">{{ translate('Afghanistan') }}</option>
                                <option value="AM">{{ translate('Armenia') }}</option>
                                <option value="AZ">{{ translate('Azerbaijan') }}</option>
                                <option value="BH">{{ translate('Bahrain') }}</option>
                                <option value="BD">{{ translate('Bangladesh') }}</option>
                                <option value="BT">{{ translate('Bhutan') }}</option>
                                <option value="BN">{{ translate('Brunei') }}</option>
                                <option value="KH">{{ translate('Cambodia') }}</option>
                                <option value="CN">{{ translate('China') }}</option>
                                <option value="GE">{{ translate('Georgia') }}</option>
                                <option value="HK">{{ translate('Hong Kong') }}</option>
                                <option value="IN">{{ translate('India') }}</option>
                                <option value="ID">{{ translate('Indonesia') }}</option>
                                <option value="IR">{{ translate('Iran') }}</option>
                                <option value="IQ">{{ translate('Iraq') }}</option>
                                <option value="IL">{{ translate('Israel') }}</option>
                                <option value="JP">{{ translate('Japan') }}</option>
                                <option value="JO">{{ translate('Jordan') }}</option>
                                <option value="KZ">{{ translate('Kazakhstan') }}</option>
                                <option value="KW">{{ translate('Kuwait') }}</option>
                                <option value="KG">{{ translate('Kyrgyzstan') }}</option>
                                <option value="LA">{{ translate('Laos') }}</option>
                                <option value="LB">{{ translate('Lebanon') }}</option>
                                <option value="MO">{{ translate('Macau') }}</option>
                                <option value="MY">{{ translate('Malaysia') }}</option>
                                <option value="MV">{{ translate('Maldives') }}</option>
                                <option value="MN">{{ translate('Mongolia') }}</option>
                                <option value="MM">{{ translate('Myanmar') }}</option>
                                <option value="NP">{{ translate('Nepal') }}</option>
                                <option value="KP">{{ translate('North Korea') }}</option>
                                <option value="OM">{{ translate('Oman') }}</option>
                                <option value="PK">{{ translate('Pakistan') }}</option>
                                <option value="PS">{{ translate('Palestine') }}</option>
                                <option value="PH">{{ translate('Philippines') }}</option>
                                <option value="QA">{{ translate('Qatar') }}</option>
                                <option value="SA">{{ translate('Saudi Arabia') }}</option>
                                <option value="SG">{{ translate('Singapore') }}</option>
                                <option value="KR">{{ translate('South Korea') }}</option>
                                <option value="LK">{{ translate('Sri Lanka') }}</option>
                                <option value="SY">{{ translate('Syria') }}</option>
                                <option value="TW">{{ translate('Taiwan') }}</option>
                                <option value="TJ">{{ translate('Tajikistan') }}</option>
                                <option value="TH">{{ translate('Thailand') }}</option>
                                <option value="TL">{{ translate('Timor-Leste') }}</option>
                                <option value="TR">{{ translate('Turkey') }}</option>
                                <option value="TM">{{ translate('Turkmenistan') }}</option>
                                <option value="AE">{{ translate('United Arab Emirates') }}</option>
                                <option value="UZ">{{ translate('Uzbekistan') }}</option>
                                <option value="VN">{{ translate('Vietnam') }}</option>
                                <option value="YE">{{ translate('Yemen') }}</option>
                                
                                <!-- 欧洲 -->
                                <option value="AL">{{ translate('Albania') }}</option>
                                <option value="AD">{{ translate('Andorra') }}</option>
                                <option value="AT">{{ translate('Austria') }}</option>
                                <option value="BY">{{ translate('Belarus') }}</option>
                                <option value="BE">{{ translate('Belgium') }}</option>
                                <option value="BA">{{ translate('Bosnia and Herzegovina') }}</option>
                                <option value="BG">{{ translate('Bulgaria') }}</option>
                                <option value="HR">{{ translate('Croatia') }}</option>
                                <option value="CY">{{ translate('Cyprus') }}</option>
                                <option value="CZ">{{ translate('Czech Republic') }}</option>
                                <option value="DK">{{ translate('Denmark') }}</option>
                                <option value="EE">{{ translate('Estonia') }}</option>
                                <option value="FI">{{ translate('Finland') }}</option>
                                <option value="FR">{{ translate('France') }}</option>
                                <option value="DE">{{ translate('Germany') }}</option>
                                <option value="GR">{{ translate('Greece') }}</option>
                                <option value="HU">{{ translate('Hungary') }}</option>
                                <option value="IS">{{ translate('Iceland') }}</option>
                                <option value="IE">{{ translate('Ireland') }}</option>
                                <option value="IT">{{ translate('Italy') }}</option>
                                <option value="XK">{{ translate('Kosovo') }}</option>
                                <option value="LV">{{ translate('Latvia') }}</option>
                                <option value="LI">{{ translate('Liechtenstein') }}</option>
                                <option value="LT">{{ translate('Lithuania') }}</option>
                                <option value="LU">{{ translate('Luxembourg') }}</option>
                                <option value="MT">{{ translate('Malta') }}</option>
                                <option value="MD">{{ translate('Moldova') }}</option>
                                <option value="MC">{{ translate('Monaco') }}</option>
                                <option value="ME">{{ translate('Montenegro') }}</option>
                                <option value="NL">{{ translate('Netherlands') }}</option>
                                <option value="MK">{{ translate('North Macedonia') }}</option>
                                <option value="NO">{{ translate('Norway') }}</option>
                                <option value="PL">{{ translate('Poland') }}</option>
                                <option value="PT">{{ translate('Portugal') }}</option>
                                <option value="RO">{{ translate('Romania') }}</option>
                                <option value="RU">{{ translate('Russia') }}</option>
                                <option value="SM">{{ translate('San Marino') }}</option>
                                <option value="RS">{{ translate('Serbia') }}</option>
                                <option value="SK">{{ translate('Slovakia') }}</option>
                                <option value="SI">{{ translate('Slovenia') }}</option>
                                <option value="ES">{{ translate('Spain') }}</option>
                                <option value="SE">{{ translate('Sweden') }}</option>
                                <option value="CH">{{ translate('Switzerland') }}</option>
                                <option value="UA">{{ translate('Ukraine') }}</option>
                                <option value="GB">{{ translate('United Kingdom') }}</option>
                                <option value="VA">{{ translate('Vatican City') }}</option>
                                
                                <!-- 北美洲 -->
                                <option value="AG">{{ translate('Antigua and Barbuda') }}</option>
                                <option value="BS">{{ translate('Bahamas') }}</option>
                                <option value="BB">{{ translate('Barbados') }}</option>
                                <option value="BZ">{{ translate('Belize') }}</option>
                                <option value="CA">{{ translate('Canada') }}</option>
                                <option value="CR">{{ translate('Costa Rica') }}</option>
                                <option value="CU">{{ translate('Cuba') }}</option>
                                <option value="DM">{{ translate('Dominica') }}</option>
                                <option value="DO">{{ translate('Dominican Republic') }}</option>
                                <option value="SV">{{ translate('El Salvador') }}</option>
                                <option value="GD">{{ translate('Grenada') }}</option>
                                <option value="GT">{{ translate('Guatemala') }}</option>
                                <option value="HT">{{ translate('Haiti') }}</option>
                                <option value="HN">{{ translate('Honduras') }}</option>
                                <option value="JM">{{ translate('Jamaica') }}</option>
                                <option value="MX">{{ translate('Mexico') }}</option>
                                <option value="NI">{{ translate('Nicaragua') }}</option>
                                <option value="PA">{{ translate('Panama') }}</option>
                                <option value="KN">{{ translate('Saint Kitts and Nevis') }}</option>
                                <option value="LC">{{ translate('Saint Lucia') }}</option>
                                <option value="VC">{{ translate('Saint Vincent and the Grenadines') }}</option>
                                <option value="TT">{{ translate('Trinidad and Tobago') }}</option>
                                <option value="US">{{ translate('United States') }}</option>
                                
                                <!-- 南美洲 -->
                                <option value="AR">{{ translate('Argentina') }}</option>
                                <option value="BO">{{ translate('Bolivia') }}</option>
                                <option value="BR">{{ translate('Brazil') }}</option>
                                <option value="CL">{{ translate('Chile') }}</option>
                                <option value="CO">{{ translate('Colombia') }}</option>
                                <option value="EC">{{ translate('Ecuador') }}</option>
                                <option value="FK">{{ translate('Falkland Islands') }}</option>
                                <option value="GF">{{ translate('French Guiana') }}</option>
                                <option value="GY">{{ translate('Guyana') }}</option>
                                <option value="PY">{{ translate('Paraguay') }}</option>
                                <option value="PE">{{ translate('Peru') }}</option>
                                <option value="SR">{{ translate('Suriname') }}</option>
                                <option value="UY">{{ translate('Uruguay') }}</option>
                                <option value="VE">{{ translate('Venezuela') }}</option>
                                
                                <!-- 非洲 -->
                                <option value="DZ">{{ translate('Algeria') }}</option>
                                <option value="AO">{{ translate('Angola') }}</option>
                                <option value="BJ">{{ translate('Benin') }}</option>
                                <option value="BW">{{ translate('Botswana') }}</option>
                                <option value="BF">{{ translate('Burkina Faso') }}</option>
                                <option value="BI">{{ translate('Burundi') }}</option>
                                <option value="CV">{{ translate('Cape Verde') }}</option>
                                <option value="CM">{{ translate('Cameroon') }}</option>
                                <option value="CF">{{ translate('Central African Republic') }}</option>
                                <option value="TD">{{ translate('Chad') }}</option>
                                <option value="KM">{{ translate('Comoros') }}</option>
                                <option value="CG">{{ translate('Congo') }}</option>
                                <option value="CD">{{ translate('Congo (Democratic Republic)') }}</option>
                                <option value="CI">{{ translate('Cote d\'Ivoire') }}</option>
                                <option value="DJ">{{ translate('Djibouti') }}</option>
                                <option value="EG">{{ translate('Egypt') }}</option>
                                <option value="GQ">{{ translate('Equatorial Guinea') }}</option>
                                <option value="ER">{{ translate('Eritrea') }}</option>
                                <option value="SZ">{{ translate('Eswatini') }}</option>
                                <option value="ET">{{ translate('Ethiopia') }}</option>
                                <option value="GA">{{ translate('Gabon') }}</option>
                                <option value="GM">{{ translate('Gambia') }}</option>
                                <option value="GH">{{ translate('Ghana') }}</option>
                                <option value="GN">{{ translate('Guinea') }}</option>
                                <option value="GW">{{ translate('Guinea-Bissau') }}</option>
                                <option value="KE">{{ translate('Kenya') }}</option>
                                <option value="LS">{{ translate('Lesotho') }}</option>
                                <option value="LR">{{ translate('Liberia') }}</option>
                                <option value="LY">{{ translate('Libya') }}</option>
                                <option value="MG">{{ translate('Madagascar') }}</option>
                                <option value="MW">{{ translate('Malawi') }}</option>
                                <option value="ML">{{ translate('Mali') }}</option>
                                <option value="MR">{{ translate('Mauritania') }}</option>
                                <option value="MU">{{ translate('Mauritius') }}</option>
                                <option value="YT">{{ translate('Mayotte') }}</option>
                                <option value="MA">{{ translate('Morocco') }}</option>
                                <option value="MZ">{{ translate('Mozambique') }}</option>
                                <option value="NA">{{ translate('Namibia') }}</option>
                                <option value="NE">{{ translate('Niger') }}</option>
                                <option value="NG">{{ translate('Nigeria') }}</option>
                                <option value="RE">{{ translate('Reunion') }}</option>
                                <option value="RW">{{ translate('Rwanda') }}</option>
                                <option value="ST">{{ translate('Sao Tome and Principe') }}</option>
                                <option value="SN">{{ translate('Senegal') }}</option>
                                <option value="SC">{{ translate('Seychelles') }}</option>
                                <option value="SL">{{ translate('Sierra Leone') }}</option>
                                <option value="SO">{{ translate('Somalia') }}</option>
                                <option value="ZA">{{ translate('South Africa') }}</option>
                                <option value="SS">{{ translate('South Sudan') }}</option>
                                <option value="SD">{{ translate('Sudan') }}</option>
                                <option value="TZ">{{ translate('Tanzania') }}</option>
                                <option value="TG">{{ translate('Togo') }}</option>
                                <option value="TN">{{ translate('Tunisia') }}</option>
                                <option value="UG">{{ translate('Uganda') }}</option>
                                <option value="EH">{{ translate('Western Sahara') }}</option>
                                <option value="ZM">{{ translate('Zambia') }}</option>
                                <option value="ZW">{{ translate('Zimbabwe') }}</option>
                                
                                <!-- 大洋洲 -->
                                <option value="AS">{{ translate('American Samoa') }}</option>
                                <option value="AU">{{ translate('Australia') }}</option>
                                <option value="CK">{{ translate('Cook Islands') }}</option>
                                <option value="FJ">{{ translate('Fiji') }}</option>
                                <option value="PF">{{ translate('French Polynesia') }}</option>
                                <option value="GU">{{ translate('Guam') }}</option>
                                <option value="KI">{{ translate('Kiribati') }}</option>
                                <option value="MH">{{ translate('Marshall Islands') }}</option>
                                <option value="FM">{{ translate('Micronesia') }}</option>
                                <option value="NR">{{ translate('Nauru') }}</option>
                                <option value="NC">{{ translate('New Caledonia') }}</option>
                                <option value="NZ">{{ translate('New Zealand') }}</option>
                                <option value="NU">{{ translate('Niue') }}</option>
                                <option value="NF">{{ translate('Norfolk Island') }}</option>
                                <option value="MP">{{ translate('Northern Mariana Islands') }}</option>
                                <option value="PW">{{ translate('Palau') }}</option>
                                <option value="PG">{{ translate('Papua New Guinea') }}</option>
                                <option value="PN">{{ translate('Pitcairn Islands') }}</option>
                                <option value="WS">{{ translate('Samoa') }}</option>
                                <option value="SB">{{ translate('Solomon Islands') }}</option>
                                <option value="TK">{{ translate('Tokelau') }}</option>
                                <option value="TO">{{ translate('Tonga') }}</option>
                                <option value="TV">{{ translate('Tuvalu') }}</option>
                                <option value="VU">{{ translate('Vanuatu') }}</option>
                                <option value="WF">{{ translate('Wallis and Futuna') }}</option>
                                
                                <!-- 其他地区 -->
                                <option value="AQ">{{ translate('Antarctica') }}</option>
                                <option value="BV">{{ translate('Bouvet Island') }}</option>
                                <option value="IO">{{ translate('British Indian Ocean Territory') }}</option>
                                <option value="CC">{{ translate('Cocos Islands') }}</option>
                                <option value="CX">{{ translate('Christmas Island') }}</option>
                                <option value="FO">{{ translate('Faroe Islands') }}</option>
                                <option value="GI">{{ translate('Gibraltar') }}</option>
                                <option value="GL">{{ translate('Greenland') }}</option>
                                <option value="HM">{{ translate('Heard Island and McDonald Islands') }}</option>
                                <option value="JE">{{ translate('Jersey') }}</option>
                                <option value="GG">{{ translate('Guernsey') }}</option>
                                <option value="IM">{{ translate('Isle of Man') }}</option>
                                <option value="SJ">{{ translate('Svalbard and Jan Mayen') }}</option>
                                <option value="TF">{{ translate('French Southern Territories') }}</option>
                                <option value="UM">{{ translate('United States Minor Outlying Islands') }}</option>
                                <option value="VI">{{ translate('U.S. Virgin Islands') }}</option>
                                <option value="VG">{{ translate('British Virgin Islands') }}</option>
                                <option value="AI">{{ translate('Anguilla') }}</option>
                                <option value="AW">{{ translate('Aruba') }}</option>
                                <option value="BM">{{ translate('Bermuda') }}</option>
                                <option value="BQ">{{ translate('Caribbean Netherlands') }}</option>
                                <option value="KY">{{ translate('Cayman Islands') }}</option>
                                <option value="CW">{{ translate('Curacao') }}</option>
                                <option value="MS">{{ translate('Montserrat') }}</option>
                                <option value="PR">{{ translate('Puerto Rico') }}</option>
                                <option value="BL">{{ translate('Saint Barthelemy') }}</option>
                                <option value="SH">{{ translate('Saint Helena') }}</option>
                                <option value="MF">{{ translate('Saint Martin') }}</option>
                                <option value="PM">{{ translate('Saint Pierre and Miquelon') }}</option>
                                <option value="SX">{{ translate('Sint Maarten') }}</option>
                                <option value="GS">{{ translate('South Georgia and the South Sandwich Islands') }}</option>
                                <option value="TC">{{ translate('Turks and Caicos Islands') }}</option>
                            </select>
                            <div class="field-error" id="country-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="stateOrProvince">{{ translate('State/Province') }} <span class="required">*</span></label>
                            <input type="text" id="stateOrProvince" name="stateOrProvince" placeholder="{{ translate('Enter state or province') }}" required>
                            <div class="field-error" id="stateOrProvince-error"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="city">{{ translate('City') }} <span class="required">*</span></label>
                            <input type="text" id="city" name="city" placeholder="{{ translate('New York') }}" required>
                            <div class="field-error" id="city-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="zip">{{ translate('Zip Code') }} <span class="required">*</span></label>
                            <input type="text" id="zip" name="zip" placeholder="{{ translate('10001') }}" required>
                            <div class="field-error" id="zip-error"></div>
                        </div>
                    </div>
                    
                    <div class="form-row">
                        <div class="form-group">
                            <label for="houseNumberOrName">{{ translate('House Number/Name') }} <span class="required">*</span></label>
                            <input type="text" id="houseNumberOrName" name="houseNumberOrName" placeholder="{{ translate('123') }}" required>
                            <div class="field-error" id="houseNumberOrName-error"></div>
                        </div>
                        <div class="form-group">
                            <label for="street">{{ translate('Street') }} <span class="required">*</span></label>
                            <input type="text" id="street" name="street" placeholder="{{ translate('Main Street') }}" required>
                            <div class="field-error" id="street-error"></div>
                        </div>
                    </div>
                    
                    <div class="loading" id="loading">
                        <div class="spinner"></div>
                        <span>{{ translate('Processing payment...') }}</span>
                        <div class="progress-bar">
                            <div class="progress-bar-fill" id="progress-fill"></div>
                        </div>
                    </div>
                    
                    <button type="submit" class="submit-btn" id="submit-btn">
                        {{ translate('Complete Payment') }} (${{ $paymentData->payment_amount }})
                    </button>
                </form>

                <script>
                    const translations = {
                        'Please fill in all required fields.': @json(translate('Please fill in all required fields.')),
                        'Please enter a valid email address.': @json(translate('Please enter a valid email address.')),
                        'Please enter expiry date in MMYY format.': @json(translate('Please enter expiry date in MMYY format.')),
                        'Card number is required.': @json(translate('Card number is required.')),
                        'Card number must be 16 digits.': @json(translate('Card number must be 16 digits.')),
                        'Expiry date is required.': @json(translate('Expiry date is required.')),
                        'CVV is required.': @json(translate('CVV is required.')),
                        'CVV must be 3 digits.': @json(translate('CVV must be 3 digits.')),
                        'First name is required.': @json(translate('First name is required.')),
                        'Last name is required.': @json(translate('Last name is required.')),
                        'Email is required.': @json(translate('Email is required.')),
                        'Country code is required.': @json(translate('Country code is required.')),
                        'Phone number is required.': @json(translate('Phone number is required.')),
                        'Country is required.': @json(translate('Country is required.')),
                        'State/Province is required.': @json(translate('State/Province is required.')),
                        'City is required.': @json(translate('City is required.')),
                        'Zip code is required.': @json(translate('Zip code is required.')),
                        'House number/name is required.': @json(translate('House number/name is required.')),
                        'Street is required.': @json(translate('Street is required.')),
                        'An error occurred. Please try again.': @json(translate('An error occurred. Please try again.')),
                        'Processing payment...': @json(translate('Processing payment...')),
                        'Invalid card number format.': @json(translate('Invalid card number format.')),
                        'Invalid CVV format.': @json(translate('Invalid CVV format.')),
                        'Invalid expiry date.': @json(translate('Invalid expiry date.')),
                        'Phone number should only contain digits.': @json(translate('Phone number should only contain digits.'))
                    };

                    const csrfToken = document.querySelector('input[name="_token"]')?.value;

                    function translate(key) {
                        return translations[key] || key;
                    }

                    function showFieldError(fieldId, message) {
                        // 针对合并后的手机号
                        if (fieldId === 'fullPhone') {
                            document.getElementById('countryCode').classList.add('error');
                            document.getElementById('phoneNo').classList.add('error');
                            document.getElementById('fullPhone-error').textContent = translate(message);
                            document.getElementById('fullPhone-error').style.display = 'block';
                        } else {
                            const field = document.getElementById(fieldId);
                            const errorDiv = document.getElementById(fieldId + '-error');
                            
                            if (field && errorDiv) {
                                field.classList.add('error');
                                errorDiv.textContent = translate(message);
                                errorDiv.style.display = 'block';
                            }
                        }
                    }

                    function clearFieldError(fieldId) {
                        if (fieldId === 'fullPhone') {
                            document.getElementById('countryCode').classList.remove('error');
                            document.getElementById('phoneNo').classList.remove('error');
                            document.getElementById('fullPhone-error').style.display = 'none';
                        } else {
                            const field = document.getElementById(fieldId);
                            const errorDiv = document.getElementById(fieldId + '-error');
                            
                            if (field && errorDiv) {
                                field.classList.remove('error');
                                errorDiv.style.display = 'none';
                            }
                        }
                        
                    }

                    function clearAllErrors() {
                        const requiredFields = ['cardNo', 'expDate', 'cvv', 'firstName', 'lastName', 'email', 'countryCode', 'phoneNo', 'country', 'stateOrProvince', 'city', 'houseNumberOrName', 'street', 'zip'];
                        requiredFields.forEach(field => clearFieldError(field));
                        document.getElementById('error-message').style.display = 'none';
                    }

                    function validateForm() {
                        clearAllErrors();
                        
                        let isValid = true;
                        
                        // Card number validation
                        const cardNo = document.getElementById('cardNo').value.trim();
                        if (!cardNo) {
                            showFieldError('cardNo', 'Card number is required.');
                            isValid = false;
                        } else if (!/^\d{16}$/.test(cardNo)) {
                            showFieldError('cardNo', 'Card number must be 16 digits.');
                            isValid = false;
                        }
                        
                        // Expiry date validation
                        const expDate = document.getElementById('expDate').value.trim();
                        if (!expDate) {
                            showFieldError('expDate', 'Expiry date is required.');
                            isValid = false;
                        } else if (!/^\d{4}$/.test(expDate)) {
                            showFieldError('expDate', 'Please enter expiry date in MMYY format.');
                            isValid = false;
                        } else {
                            const month = parseInt(expDate.substring(0, 2));
                            const year = parseInt(expDate.substring(2, 4));
                            if (month < 1 || month > 12) {
                                showFieldError('expDate', 'Invalid expiry date.');
                                isValid = false;
                            }
                        }
                        
                        // CVV validation
                        const cvv = document.getElementById('cvv').value.trim();
                        if (!cvv) {
                            showFieldError('cvv', 'CVV is required.');
                            isValid = false;
                        } else if (!/^\d{3}$/.test(cvv)) {
                            showFieldError('cvv', 'CVV must be 3 digits.');
                            isValid = false;
                        }
                        
                        // First name validation
                        const firstName = document.getElementById('firstName').value.trim();
                        if (!firstName) {
                            showFieldError('firstName', 'First name is required.');
                            isValid = false;
                        }
                        
                        // Last name validation
                        const lastName = document.getElementById('lastName').value.trim();
                        if (!lastName) {
                            showFieldError('lastName', 'Last name is required.');
                            isValid = false;
                        }
                        
                        // Email validation
                        const email = document.getElementById('email').value.trim();
                        if (!email) {
                            showFieldError('email', 'Email is required.');
                            isValid = false;
                        } else {
                            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
                            if (!emailRegex.test(email)) {
                                showFieldError('email', 'Please enter a valid email address.');
                                isValid = false;
                            }
                        }
                        
                        // 合并手机号校验
                        const countryCode = document.getElementById('countryCode').value;
                        const phoneNo = document.getElementById('phoneNo').value.trim();
                        if (!countryCode || !phoneNo) {
                            showFieldError('fullPhone', 'Phone number is required.');
                            isValid = false;
                        } else if (!/^\d+$/.test(phoneNo)) {
                            showFieldError('fullPhone', 'Phone number should only contain digits.');
                            isValid = false;
                        }
                        
                        // Country validation
                        const country = document.getElementById('country').value;
                        if (!country) {
                            showFieldError('country', 'Country is required.');
                            isValid = false;
                        }
                        
                        // State/Province validation
                        const stateOrProvince = document.getElementById('stateOrProvince').value.trim();
                        if (!stateOrProvince) {
                            showFieldError('stateOrProvince', 'State/Province is required.');
                            isValid = false;
                        }
                        
                        // City validation
                        const city = document.getElementById('city').value.trim();
                        if (!city) {
                            showFieldError('city', 'City is required.');
                            isValid = false;
                        }
                        
                        // Zip code validation
                        const zip = document.getElementById('zip').value.trim();
                        if (!zip) {
                            showFieldError('zip', 'Zip code is required.');
                            isValid = false;
                        }
                        
                        // House number/name validation
                        const houseNumberOrName = document.getElementById('houseNumberOrName').value.trim();
                        if (!houseNumberOrName) {
                            showFieldError('houseNumberOrName', 'House number/name is required.');
                            isValid = false;
                        }
                        
                        // Street validation
                        const street = document.getElementById('street').value.trim();
                        if (!street) {
                            showFieldError('street', 'Street is required.');
                            isValid = false;
                        }
                        
                        return isValid;
                    }

                    function updateProgress(percentage) {
                        const progressFill = document.getElementById('progress-fill');
                        if (progressFill) {
                            progressFill.style.width = percentage + '%';
                        }
                    }

                    document.getElementById('payment-form').addEventListener('submit', function(e) {
                        e.preventDefault();
                        
                        if (!validateForm()) {
                            return;
                        }
                        
                        const form = e.target;
                        const formData = new FormData(form);
                        const submitBtn = document.getElementById('submit-btn');
                        const loading = document.getElementById('loading');
                        
                        submitBtn.disabled = true;
                        submitBtn.style.display = 'none';
                        loading.style.display = 'block';
                        
                        // Progress simulation
                        let progress = 0;
                        const progressInterval = setInterval(() => {
                            progress += Math.random() * 20;
                            if (progress > 90) progress = 90;
                            updateProgress(progress);
                        }, 200);
                        
                        fetch(window.location.href, {
                            method: 'POST',
                            body: formData,
                            headers: {
                                'X-CSRF-TOKEN': csrfToken,
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(response => response.text())
                        .then(html => {
                            clearInterval(progressInterval);
                            updateProgress(100);
                            
                            setTimeout(() => {
                                document.open();
                                document.write(html);
                                document.close();
                            }, 500);
                        })
                        .catch(error => {
                            console.error('Error:', error);
                            clearInterval(progressInterval);
                            
                            const errorMessage = document.getElementById('error-message');
                            errorMessage.textContent = translate('An error occurred. Please try again.');
                            errorMessage.style.display = 'block';
                            
                            submitBtn.disabled = false;
                            submitBtn.style.display = 'block';
                            loading.style.display = 'none';
                            updateProgress(0);
                        });
                    });
                    
                    // Input validation and formatting
                    ['cardNo', 'cvv', 'expDate'].forEach(id => {
                        document.getElementById(id).addEventListener('input', function(e) {
                            e.target.value = e.target.value.replace(/\D/g, '');
                            clearFieldError(id);
                        });
                    });

                    // Phone number formatting
                    document.getElementById('phoneNo').addEventListener('input', function(e) {
                        e.target.value = e.target.value.replace(/\D/g, '');
                        clearFieldError('phoneNo');
                    });

                    // Clear field errors on input for all fields
                    const allInputs = document.querySelectorAll('input, select');
                    allInputs.forEach(input => {
                        input.addEventListener('input', function() {
                            clearFieldError(this.id);
                        });
                        
                        input.addEventListener('change', function() {
                            clearFieldError(this.id);
                        });
                    });
                </script>
            </div>

        @elseif($type === 'processing')
            <div class="header">
                <h1>{{ translate('Processing Your Payment') }}</h1>
                <p>{{ translate('Please wait while we process your payment securely') }}</p>
            </div>
            
            <div class="content">
                <div class="loading" style="display: block;">
                    <div class="spinner"></div>
                    <span id="processing-text">{{ translate('Initializing secure payment processing...') }}</span>
                    <div class="progress-bar">
                        <div class="progress-bar-fill" id="processing-progress"></div>
                    </div>
                </div>
                
                <div id="payment-message" class="message processing">{{ translate('Initializing secure payment processing...') }}</div>
                <div id="payment-details" style="display: none;">
                    <p><strong>{{ translate('Order ID') }}:</strong> {{ $paymentData->id }}</p>
                    <p><strong>{{ translate('Amount') }}:</strong> ${{ $paymentData->payment_amount }}</p>
                </div>

                <script src="{{ $jsUrl }}/js/nova2pay-pay.min.js"></script>
                <script>
                    const paymentConfig = @json($paymentConfig);
                    const messageDiv = document.getElementById('payment-message');
                    const detailsDiv = document.getElementById('payment-details');
                    const processingText = document.getElementById('processing-text');
                    const processingProgress = document.getElementById('processing-progress');

                    // Progress animation
                    let progress = 0;
                    const progressInterval = setInterval(() => {
                        progress += Math.random() * 15;
                        if (progress > 80) progress = 80;
                        processingProgress.style.width = progress + '%';
                    }, 300);

                    setTimeout(function() {
                        processingText.textContent = '{{ translate("Submitting payment details securely...") }}';
                        messageDiv.textContent = '{{ translate("Submitting payment details securely...") }}';
                        detailsDiv.style.display = 'block';
                        
                        new Nova2pay(paymentConfig, function(res) {
                            clearInterval(progressInterval);
                            processingProgress.style.width = '100%';
                            console.log('Payment response:', res);
                            if (
                                res &&
                                res.status === 'SUCCESS' &&
                                res.successMsg.resultCode === '10000' &&
                                res.successMsg.redirectUrl
                            ) {
                                messageDiv.className = 'message success';
                                messageDiv.textContent = '{{ translate("Payment successful! Redirecting...") }}';
                                processingText.textContent = '{{ translate("Payment successful! Redirecting...") }}';

                                const redirectUrl = decodeURIComponent(res.successMsg.redirectUrl);
                                setTimeout(function() {
                                    window.location.href = redirectUrl;
                                }, 1000);
                            } else {
                                const errorMsg = res && res.errorMsg
                                    ? res.errorMsg + ' ({{ translate("Code") }}: ' + res.errorCode + ')'
                                    : '{{ translate("An unknown error occurred.") }}';

                                messageDiv.className = 'message error';
                                messageDiv.innerHTML = '<strong>{{ translate("Payment Failed") }}:</strong> ' + errorMsg +
                                    '<br><br><button onclick="history.back()" class="retry-btn">{{ translate("Go Back") }}</button>';

                                processingText.textContent = '{{ translate("Payment failed") }}';
                                processingProgress.style.backgroundColor = '#e53e3e';
                            }
                        });
                    }, 1500);
                </script>
            </div>

        @elseif($type === 'error')
            <div class="header">
                <div style="font-size: 48px; margin-bottom: 10px;">⚠️</div>
                <h1>{{ translate('Payment Error') }}</h1>
                <p>{{ translate('Something went wrong with your payment') }}</p>
            </div>
            
            <div class="content">
                <div class="error-message" style="display: block;">{{ $message }}</div>
                @if($showRetry ?? true)
                    <button onclick="history.back()" class="retry-btn">{{ translate('Go Back') }}</button>
                @endif
            </div>
        @endif
    </div>
</body>
</html>