export const setDefaultLanguage = () => {
  // 尝试从 localStorage 获取 i18nextLng 设置
  const storedLang = localStorage.getItem("i18nextLng");
  
  let lan = "en";
  let country = "US";
  
  if (storedLang) {
    // 如果已有 i18nextLng 设置，使用该设置
    lan = storedLang;
    
    // 根据语言代码猜测国家或地区码
    country = getCountryFromLanguage(lan);
  }
  
  localStorage.setItem("language-setting", JSON.stringify(lan));
  localStorage.setItem("country", JSON.stringify(country));
};

// 根据语言代码猜测国家或地区码的辅助函数
const getCountryFromLanguage = (languageCode) => {
  const languageToCountryMap = {
    // 英语及其变体
    "en": "US",
    "en-AU": "AU",
    "en-NZ": "NZ", 
    "en-CA": "CA",
    
    // 中文及其变体
    "zh": "CN",
    "zh-TW": "TW",
    
    // 西班牙语及其变体
    "es": "ES",
    "es-MX": "MX",
    "es-419": "AR", // 拉丁美洲西班牙语默认为阿根廷
    
    // 葡萄牙语及其变体
    "pt": "PT",
    "pt-BR": "BR",
    
    // 亚洲语言
    "bn": "BD", // 孟加拉语
    "ar": "SA", // 阿拉伯语默认为沙特阿拉伯
    "hi": "IN", // 印地语
    "id": "ID", // 印尼语
    "ja": "JP", // 日语
    "ko": "KR", // 韩语
    "vi": "VN", // 越南语
    
    // 欧洲语言
    "fr": "FR", // 法语
    "de": "DE", // 德语
    "it": "IT", // 意大利语
    "tr": "TR", // 土耳其语
    "pl": "PL", // 波兰语
    "fi": "FI", // 芬兰语
    "el": "GR", // 希腊语
    "da": "DK", // 丹麦语
    "sr": "RS", // 塞尔维亚语
    "hu": "HU", // 匈牙利语
    "cs": "CZ", // 捷克语
    "ru": "RU", // 俄语
  };
  
  return languageToCountryMap[languageCode] || "US";
};