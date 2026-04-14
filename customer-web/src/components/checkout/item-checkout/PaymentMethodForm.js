import React, { useState, useEffect } from 'react';
import { Stack, Typography, TextField, FormControl, InputLabel, Select, MenuItem } from '@mui/material';
import { useTranslation } from 'react-i18next';
import { CustomPaperBigCard } from '../../../styled-components/CustomStyles.style';

// 全球国家/地区列表（两位字母代码）
const countries = [
  { code: 'AD', name: 'Andorra' },
  { code: 'AE', name: 'United Arab Emirates' },
  { code: 'AF', name: 'Afghanistan' },
  { code: 'AG', name: 'Antigua and Barbuda' },
  { code: 'AI', name: 'Anguilla' },
  { code: 'AL', name: 'Albania' },
  { code: 'AM', name: 'Armenia' },
  { code: 'AO', name: 'Angola' },
  { code: 'AQ', name: 'Antarctica' },
  { code: 'AR', name: 'Argentina' },
  { code: 'AS', name: 'American Samoa' },
  { code: 'AT', name: 'Austria' },
  { code: 'AU', name: 'Australia' },
  { code: 'AW', name: 'Aruba' },
  { code: 'AX', name: 'Åland Islands' },
  { code: 'AZ', name: 'Azerbaijan' },
  { code: 'BA', name: 'Bosnia and Herzegovina' },
  { code: 'BB', name: 'Barbados' },
  { code: 'BD', name: 'Bangladesh' },
  { code: 'BE', name: 'Belgium' },
  { code: 'BF', name: 'Burkina Faso' },
  { code: 'BG', name: 'Bulgaria' },
  { code: 'BH', name: 'Bahrain' },
  { code: 'BI', name: 'Burundi' },
  { code: 'BJ', name: 'Benin' },
  { code: 'BL', name: 'Saint Barthélemy' },
  { code: 'BM', name: 'Bermuda' },
  { code: 'BN', name: 'Brunei Darussalam' },
  { code: 'BO', name: 'Bolivia' },
  { code: 'BQ', name: 'Bonaire, Sint Eustatius and Saba' },
  { code: 'BR', name: 'Brazil' },
  { code: 'BS', name: 'Bahamas' },
  { code: 'BT', name: 'Bhutan' },
  { code: 'BV', name: 'Bouvet Island' },
  { code: 'BW', name: 'Botswana' },
  { code: 'BY', name: 'Belarus' },
  { code: 'BZ', name: 'Belize' },
  { code: 'CA', name: 'Canada' },
  { code: 'CC', name: 'Cocos (Keeling) Islands' },
  { code: 'CD', name: 'Congo, Democratic Republic of the' },
  { code: 'CF', name: 'Central African Republic' },
  { code: 'CG', name: 'Congo' },
  { code: 'CH', name: 'Switzerland' },
  { code: 'CI', name: 'Côte d\'Ivoire' },
  { code: 'CK', name: 'Cook Islands' },
  { code: 'CL', name: 'Chile' },
  { code: 'CM', name: 'Cameroon' },
  { code: 'CN', name: 'China' },
  { code: 'CO', name: 'Colombia' },
  { code: 'CR', name: 'Costa Rica' },
  { code: 'CU', name: 'Cuba' },
  { code: 'CV', name: 'Cabo Verde' },
  { code: 'CW', name: 'Curaçao' },
  { code: 'CX', name: 'Christmas Island' },
  { code: 'CY', name: 'Cyprus' },
  { code: 'CZ', name: 'Czech Republic' },
  { code: 'DE', name: 'Germany' },
  { code: 'DJ', name: 'Djibouti' },
  { code: 'DK', name: 'Denmark' },
  { code: 'DM', name: 'Dominica' },
  { code: 'DO', name: 'Dominican Republic' },
  { code: 'DZ', name: 'Algeria' },
  { code: 'EC', name: 'Ecuador' },
  { code: 'EE', name: 'Estonia' },
  { code: 'EG', name: 'Egypt' },
  { code: 'EH', name: 'Western Sahara' },
  { code: 'ER', name: 'Eritrea' },
  { code: 'ES', name: 'Spain' },
  { code: 'ET', name: 'Ethiopia' },
  { code: 'FI', name: 'Finland' },
  { code: 'FJ', name: 'Fiji' },
  { code: 'FK', name: 'Falkland Islands (Malvinas)' },
  { code: 'FM', name: 'Micronesia' },
  { code: 'FO', name: 'Faroe Islands' },
  { code: 'FR', name: 'France' },
  { code: 'GA', name: 'Gabon' },
  { code: 'GB', name: 'United Kingdom' },
  { code: 'GD', name: 'Grenada' },
  { code: 'GE', name: 'Georgia' },
  { code: 'GF', name: 'French Guiana' },
  { code: 'GG', name: 'Guernsey' },
  { code: 'GH', name: 'Ghana' },
  { code: 'GI', name: 'Gibraltar' },
  { code: 'GL', name: 'Greenland' },
  { code: 'GM', name: 'Gambia' },
  { code: 'GN', name: 'Guinea' },
  { code: 'GP', name: 'Guadeloupe' },
  { code: 'GQ', name: 'Equatorial Guinea' },
  { code: 'GR', name: 'Greece' },
  { code: 'GS', name: 'South Georgia and the South Sandwich Islands' },
  { code: 'GT', name: 'Guatemala' },
  { code: 'GU', name: 'Guam' },
  { code: 'GW', name: 'Guinea-Bissau' },
  { code: 'GY', name: 'Guyana' },
  { code: 'HK', name: 'Hong Kong' },
  { code: 'HM', name: 'Heard Island and McDonald Islands' },
  { code: 'HN', name: 'Honduras' },
  { code: 'HR', name: 'Croatia' },
  { code: 'HT', name: 'Haiti' },
  { code: 'HU', name: 'Hungary' },
  { code: 'ID', name: 'Indonesia' },
  { code: 'IE', name: 'Ireland' },
  { code: 'IL', name: 'Israel' },
  { code: 'IM', name: 'Isle of Man' },
  { code: 'IN', name: 'India' },
  { code: 'IO', name: 'British Indian Ocean Territory' },
  { code: 'IQ', name: 'Iraq' },
  { code: 'IR', name: 'Iran' },
  { code: 'IS', name: 'Iceland' },
  { code: 'IT', name: 'Italy' },
  { code: 'JE', name: 'Jersey' },
  { code: 'JM', name: 'Jamaica' },
  { code: 'JO', name: 'Jordan' },
  { code: 'JP', name: 'Japan' },
  { code: 'KE', name: 'Kenya' },
  { code: 'KG', name: 'Kyrgyzstan' },
  { code: 'KH', name: 'Cambodia' },
  { code: 'KI', name: 'Kiribati' },
  { code: 'KM', name: 'Comoros' },
  { code: 'KN', name: 'Saint Kitts and Nevis' },
  { code: 'KP', name: 'Korea, Democratic People\'s Republic of' },
  { code: 'KR', name: 'Korea, Republic of' },
  { code: 'KW', name: 'Kuwait' },
  { code: 'KY', name: 'Cayman Islands' },
  { code: 'KZ', name: 'Kazakhstan' },
  { code: 'LA', name: 'Lao People\'s Democratic Republic' },
  { code: 'LB', name: 'Lebanon' },
  { code: 'LC', name: 'Saint Lucia' },
  { code: 'LI', name: 'Liechtenstein' },
  { code: 'LK', name: 'Sri Lanka' },
  { code: 'LR', name: 'Liberia' },
  { code: 'LS', name: 'Lesotho' },
  { code: 'LT', name: 'Lithuania' },
  { code: 'LU', name: 'Luxembourg' },
  { code: 'LV', name: 'Latvia' },
  { code: 'LY', name: 'Libya' },
  { code: 'MA', name: 'Morocco' },
  { code: 'MC', name: 'Monaco' },
  { code: 'MD', name: 'Moldova' },
  { code: 'ME', name: 'Montenegro' },
  { code: 'MF', name: 'Saint Martin (French part)' },
  { code: 'MG', name: 'Madagascar' },
  { code: 'MH', name: 'Marshall Islands' },
  { code: 'MK', name: 'North Macedonia' },
  { code: 'ML', name: 'Mali' },
  { code: 'MM', name: 'Myanmar' },
  { code: 'MN', name: 'Mongolia' },
  { code: 'MO', name: 'Macao' },
  { code: 'MP', name: 'Northern Mariana Islands' },
  { code: 'MQ', name: 'Martinique' },
  { code: 'MR', name: 'Mauritania' },
  { code: 'MS', name: 'Montserrat' },
  { code: 'MT', name: 'Malta' },
  { code: 'MU', name: 'Mauritius' },
  { code: 'MV', name: 'Maldives' },
  { code: 'MW', name: 'Malawi' },
  { code: 'MX', name: 'Mexico' },
  { code: 'MY', name: 'Malaysia' },
  { code: 'MZ', name: 'Mozambique' },
  { code: 'NA', name: 'Namibia' },
  { code: 'NC', name: 'New Caledonia' },
  { code: 'NE', name: 'Niger' },
  { code: 'NF', name: 'Norfolk Island' },
  { code: 'NG', name: 'Nigeria' },
  { code: 'NI', name: 'Nicaragua' },
  { code: 'NL', name: 'Netherlands' },
  { code: 'NO', name: 'Norway' },
  { code: 'NP', name: 'Nepal' },
  { code: 'NR', name: 'Nauru' },
  { code: 'NU', name: 'Niue' },
  { code: 'NZ', name: 'New Zealand' },
  { code: 'OM', name: 'Oman' },
  { code: 'PA', name: 'Panama' },
  { code: 'PE', name: 'Peru' },
  { code: 'PF', name: 'French Polynesia' },
  { code: 'PG', name: 'Papua New Guinea' },
  { code: 'PH', name: 'Philippines' },
  { code: 'PK', name: 'Pakistan' },
  { code: 'PL', name: 'Poland' },
  { code: 'PM', name: 'Saint Pierre and Miquelon' },
  { code: 'PN', name: 'Pitcairn' },
  { code: 'PR', name: 'Puerto Rico' },
  { code: 'PS', name: 'Palestine, State of' },
  { code: 'PT', name: 'Portugal' },
  { code: 'PW', name: 'Palau' },
  { code: 'PY', name: 'Paraguay' },
  { code: 'QA', name: 'Qatar' },
  { code: 'RE', name: 'Réunion' },
  { code: 'RO', name: 'Romania' },
  { code: 'RS', name: 'Serbia' },
  { code: 'RU', name: 'Russian Federation' },
  { code: 'RW', name: 'Rwanda' },
  { code: 'SA', name: 'Saudi Arabia' },
  { code: 'SB', name: 'Solomon Islands' },
  { code: 'SC', name: 'Seychelles' },
  { code: 'SD', name: 'Sudan' },
  { code: 'SE', name: 'Sweden' },
  { code: 'SG', name: 'Singapore' },
  { code: 'SH', name: 'Saint Helena, Ascension and Tristan da Cunha' },
  { code: 'SI', name: 'Slovenia' },
  { code: 'SJ', name: 'Svalbard and Jan Mayen' },
  { code: 'SK', name: 'Slovakia' },
  { code: 'SL', name: 'Sierra Leone' },
  { code: 'SM', name: 'San Marino' },
  { code: 'SN', name: 'Senegal' },
  { code: 'SO', name: 'Somalia' },
  { code: 'SR', name: 'Suriname' },
  { code: 'SS', name: 'South Sudan' },
  { code: 'ST', name: 'Sao Tome and Principe' },
  { code: 'SV', name: 'El Salvador' },
  { code: 'SX', name: 'Sint Maarten (Dutch part)' },
  { code: 'SY', name: 'Syrian Arab Republic' },
  { code: 'SZ', name: 'Eswatini' },
  { code: 'TC', name: 'Turks and Caicos Islands' },
  { code: 'TD', name: 'Chad' },
  { code: 'TF', name: 'French Southern Territories' },
  { code: 'TG', name: 'Togo' },
  { code: 'TH', name: 'Thailand' },
  { code: 'TJ', name: 'Tajikistan' },
  { code: 'TK', name: 'Tokelau' },
  { code: 'TL', name: 'Timor-Leste' },
  { code: 'TM', name: 'Turkmenistan' },
  { code: 'TN', name: 'Tunisia' },
  { code: 'TO', name: 'Tonga' },
  { code: 'TR', name: 'Turkey' },
  { code: 'TT', name: 'Trinidad and Tobago' },
  { code: 'TV', name: 'Tuvalu' },
  { code: 'TW', name: 'Taiwan' },
  { code: 'TZ', name: 'Tanzania' },
  { code: 'UA', name: 'Ukraine' },
  { code: 'UG', name: 'Uganda' },
  { code: 'UM', name: 'United States Minor Outlying Islands' },
  { code: 'US', name: 'United States of America' },
  { code: 'UY', name: 'Uruguay' },
  { code: 'UZ', name: 'Uzbekistan' },
  { code: 'VA', name: 'Holy See' },
  { code: 'VC', name: 'Saint Vincent and the Grenadines' },
  { code: 'VE', name: 'Venezuela' },
  { code: 'VG', name: 'Virgin Islands (British)' },
  { code: 'VI', name: 'Virgin Islands (U.S.)' },
  { code: 'VN', name: 'Viet Nam' },
  { code: 'VU', name: 'Vanuatu' },
  { code: 'WF', name: 'Wallis and Futuna' },
  { code: 'WS', name: 'Samoa' },
  { code: 'YE', name: 'Yemen' },
  { code: 'YT', name: 'Mayotte' },
  { code: 'ZA', name: 'South Africa' },
  { code: 'ZM', name: 'Zambia' },
  { code: 'ZW', name: 'Zimbabwe' }
];

// 全球电话区号列表
const countryCodes = [
  { code: '+1', country: 'US/CA', name: 'United States/Canada' },
  { code: '+7', country: 'RU/KZ', name: 'Russia/Kazakhstan' },
  { code: '+20', country: 'EG', name: 'Egypt' },
  { code: '+27', country: 'ZA', name: 'South Africa' },
  { code: '+30', country: 'GR', name: 'Greece' },
  { code: '+31', country: 'NL', name: 'Netherlands' },
  { code: '+32', country: 'BE', name: 'Belgium' },
  { code: '+33', country: 'FR', name: 'France' },
  { code: '+34', country: 'ES', name: 'Spain' },
  { code: '+36', country: 'HU', name: 'Hungary' },
  { code: '+39', country: 'IT', name: 'Italy' },
  { code: '+40', country: 'RO', name: 'Romania' },
  { code: '+41', country: 'CH', name: 'Switzerland' },
  { code: '+43', country: 'AT', name: 'Austria' },
  { code: '+44', country: 'GB', name: 'United Kingdom' },
  { code: '+45', country: 'DK', name: 'Denmark' },
  { code: '+46', country: 'SE', name: 'Sweden' },
  { code: '+47', country: 'NO', name: 'Norway' },
  { code: '+48', country: 'PL', name: 'Poland' },
  { code: '+49', country: 'DE', name: 'Germany' },
  { code: '+51', country: 'PE', name: 'Peru' },
  { code: '+52', country: 'MX', name: 'Mexico' },
  { code: '+53', country: 'CU', name: 'Cuba' },
  { code: '+54', country: 'AR', name: 'Argentina' },
  { code: '+55', country: 'BR', name: 'Brazil' },
  { code: '+56', country: 'CL', name: 'Chile' },
  { code: '+57', country: 'CO', name: 'Colombia' },
  { code: '+58', country: 'VE', name: 'Venezuela' },
  { code: '+60', country: 'MY', name: 'Malaysia' },
  { code: '+61', country: 'AU', name: 'Australia' },
  { code: '+62', country: 'ID', name: 'Indonesia' },
  { code: '+63', country: 'PH', name: 'Philippines' },
  { code: '+64', country: 'NZ', name: 'New Zealand' },
  { code: '+65', country: 'SG', name: 'Singapore' },
  { code: '+66', country: 'TH', name: 'Thailand' },
  { code: '+81', country: 'JP', name: 'Japan' },
  { code: '+82', country: 'KR', name: 'South Korea' },
  { code: '+84', country: 'VN', name: 'Vietnam' },
  { code: '+86', country: 'CN', name: 'China' },
  { code: '+90', country: 'TR', name: 'Turkey' },
  { code: '+91', country: 'IN', name: 'India' },
  { code: '+92', country: 'PK', name: 'Pakistan' },
  { code: '+93', country: 'AF', name: 'Afghanistan' },
  { code: '+94', country: 'LK', name: 'Sri Lanka' },
  { code: '+95', country: 'MM', name: 'Myanmar' },
  { code: '+98', country: 'IR', name: 'Iran' },
];

const PaymentMethodForm = ({ 
  paymentMethod, 
  totalAmount,
  offlinePaymentOptions,
  offlinePaymentLoading,
  usePartialPayment,
  placeOrder,
  onFormDataChange,
  onValidationChange
}) => {
  const { t } = useTranslation();
  const [formData, setFormData] = useState({
    cardNo: '',
    expDate: '', // yyMM 格式存储
    cvv: '',
    firstName: '',
    lastName: '',
    email: '',
    countryCode: '+1',
    phoneNo: '',
    country: '',
    city: '',
    houseNumberOrName: '',
    street: '',
    zip: ''
  });

  // 用于显示的日期格式（MM/YY）
  const [displayExpDate, setDisplayExpDate] = useState('');

  // 验证表单数据
  const validateForm = () => {
    if (paymentMethod !== 'nova2pay') {
      return true; // 非nova2pay支付方式不需要验证
    }

    const requiredFields = [
      'cardNo', 'expDate', 'cvv', 'firstName', 'lastName', 
      'email', 'countryCode', 'phoneNo', 'country', 
      'city', 'houseNumberOrName', 'street', 'zip'
    ];

    // 检查所有必填字段是否已填写
    const isValid = requiredFields.every(field => {
      const value = formData[field];
      return value && value.toString().trim() !== '';
    });

    // 检查email格式
    const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    const isEmailValid = emailRegex.test(formData.email);

    // 检查过期日期格式
    const isExpDateValid = formData.expDate.length === 4 && /^\d{4}$/.test(formData.expDate);

    return isValid && isEmailValid && isExpDateValid;
  };

  // 当表单数据或支付方式改变时更新验证状态
  useEffect(() => {
    const isValid = validateForm();
    if (onValidationChange) {
      // 修复这里：只传递 isValid 参数
      onValidationChange(isValid);
    }
  }, [formData, paymentMethod, onValidationChange]);

  const handleInputChange = (field, value) => {
    const newFormData = {
      ...formData,
      [field]: value
    };
    setFormData(newFormData);
    
    // 将表单数据传递给父组件
    if (onFormDataChange) {
      onFormDataChange(paymentMethod, newFormData);
    }
  };

  // 处理过期日期输入
  const handleExpDateChange = (e) => {
    let value = e.target.value.replace(/\D/g, ''); // 只保留数字
    
    if (value.length >= 2) {
      // 添加斜杠分隔符
      value = value.substring(0, 2) + '/' + value.substring(2, 4);
    }
    
    setDisplayExpDate(value);
    
    // 转换为yyMM格式存储
    if (value.length === 5) {
      const parts = value.split('/');
      const month = parts[0];
      const year = parts[1];
      const yyMMFormat = year + month;
      
      handleInputChange('expDate', yyMMFormat);
    } else {
      handleInputChange('expDate', '');
    }
  };

  const renderFormContent = () => {
    switch (paymentMethod) {
      case "nova2pay":
        return (
          <CustomPaperBigCard padding="20px">
            <Stack spacing={3}>
              <Typography 
                variant="h6" 
                sx={{ 
                  fontWeight: 600,
                  color: 'text.primary',
                  marginBottom: 1
                }}
              >
                {t("Payment Details")}
              </Typography>
              
              <TextField
                label={t("Card Number")}
                fullWidth
                variant="outlined"
                value={formData.cardNo}
                onChange={(e) => handleInputChange('cardNo', e.target.value)}
                placeholder="1234567890123456"
                required
                sx={{
                  '& .MuiOutlinedInput-root': {
                    borderRadius: '8px',
                  }
                }}
              />
              
              <Stack direction="row" spacing={2}>
                <TextField
                  label={t("Expiry Date (MM/YY)")}
                  fullWidth
                  variant="outlined"
                  value={displayExpDate}
                  onChange={handleExpDateChange}
                  placeholder="12/24"
                  inputProps={{ 
                    maxLength: 5,
                    pattern: "[0-9]{2}/[0-9]{2}"
                  }}
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
                
                <TextField
                  label={t("CVV")}
                  fullWidth
                  variant="outlined"
                  value={formData.cvv}
                  onChange={(e) => handleInputChange('cvv', e.target.value)}
                  placeholder="123"
                  inputProps={{ maxLength: 3 }}
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
              </Stack>
              
              <Typography 
                variant="h6" 
                sx={{ 
                  fontWeight: 600,
                  color: 'text.primary',
                  marginTop: 3,
                  marginBottom: 1
                }}
              >
                {t("Customer Information")}
              </Typography>
              
              <Stack direction="row" spacing={2}>
                <TextField
                  label={t("First Name")}
                  fullWidth
                  variant="outlined"
                  value={formData.firstName}
                  onChange={(e) => handleInputChange('firstName', e.target.value)}
                  placeholder={t("Enter first name")}
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
                
                <TextField
                  label={t("Last Name")}
                  fullWidth
                  variant="outlined"
                  value={formData.lastName}
                  onChange={(e) => handleInputChange('lastName', e.target.value)}
                  placeholder={t("Enter last name")}
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
              </Stack>
              
              <TextField
                label={t("Email")}
                fullWidth
                variant="outlined"
                type="email"
                value={formData.email}
                onChange={(e) => handleInputChange('email', e.target.value)}
                placeholder="example@email.com"
                required
                sx={{
                  '& .MuiOutlinedInput-root': {
                    borderRadius: '8px',
                  }
                }}
              />
              
              <Stack direction="row" spacing={2}>
                <FormControl 
                  sx={{ 
                    minWidth: 120,
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                >
                  <InputLabel>{t("Country Code")}</InputLabel>
                  <Select
                    value={formData.countryCode}
                    label={t("Country Code")}
                    onChange={(e) => handleInputChange('countryCode', e.target.value)}
                    required
                  >
                    {countryCodes.map((item) => (
                      <MenuItem key={item.code} value={item.code}>
                        {item.code} ({item.country})
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
                
                <TextField
                  label={t("Phone Number")}
                  fullWidth
                  variant="outlined"
                  value={formData.phoneNo}
                  onChange={(e) => handleInputChange('phoneNo', e.target.value)}
                  placeholder="1234567890"
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
              </Stack>
              
              <Stack direction="row" spacing={2}>
                <FormControl 
                  fullWidth
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                >
                  <InputLabel>{t("Country/Region")}</InputLabel>
                  <Select
                    value={formData.country}
                    label={t("Country/Region")}
                    onChange={(e) => handleInputChange('country', e.target.value)}
                    required
                  >
                    {countries.map((country) => (
                      <MenuItem key={country.code} value={country.code}>
                        {country.name} ({country.code})
                      </MenuItem>
                    ))}
                  </Select>
                </FormControl>
                
                <TextField
                  label={t("City")}
                  fullWidth
                  variant="outlined"
                  value={formData.city}
                  onChange={(e) => handleInputChange('city', e.target.value)}
                  placeholder={t("Enter city")}
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
              </Stack>
              
              <Stack direction="row" spacing={2}>
                <TextField
                  label={t("House Number or Name")}
                  fullWidth
                  variant="outlined"
                  value={formData.houseNumberOrName}
                  onChange={(e) => handleInputChange('houseNumberOrName', e.target.value)}
                  placeholder={t("Enter house number or name")}
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
                
                <TextField
                  label={t("Street")}
                  fullWidth
                  variant="outlined"
                  value={formData.street}
                  onChange={(e) => handleInputChange('street', e.target.value)}
                  placeholder={t("Enter street name")}
                  required
                  sx={{
                    '& .MuiOutlinedInput-root': {
                      borderRadius: '8px',
                    }
                  }}
                />
              </Stack>
              
              <TextField
                label={t("Zip Code")}
                fullWidth
                variant="outlined"
                value={formData.zip}
                onChange={(e) => handleInputChange('zip', e.target.value)}
                placeholder={t("Enter zip code")}
                required
                sx={{
                  '& .MuiOutlinedInput-root': {
                    borderRadius: '8px',
                  }
                }}
              />
            </Stack>
          </CustomPaperBigCard>
        );
      default:
        return null;
    }
  };

  return renderFormContent();
};

export default PaymentMethodForm;