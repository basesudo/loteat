import i18n from "i18next";
import { initReactI18next } from "react-i18next";
import LanguageDetector from 'i18next-browser-languagedetector';

// 基础语言导入
import { english } from "./en";
import { bengali } from "./bn";
import { arabic } from "./ar";
import { spain } from "./es";
import { french } from "./fr";
import { german } from "./de";
import { hindi } from "./hi";
import { indonesian } from "./id";
import { japanese } from "./ja";
import { korean } from "./ko";
import { russian } from "./ru";
import { vietnam } from "./vi";
import { chinese } from "./zh";

// 英语地区变体
import { australianEnglish } from "./en-AU";
import { newZealandEnglish } from "./en-NZ";
import { canadianEnglish } from "./en-CA";

// 西班牙语地区变体
import { espanolMexicano } from "./es-MX";
import { latinAmericanSpanish } from "./es-419";

// 葡萄牙语地区变体
import { portuguese } from "./pt";
import { brazilianPortuguese } from "./pt-BR";

// 中文地区变体
import { traditionalChinese } from "./zh-TW";

// 其他欧洲语言
import { turkish } from "./tr";
import { italian } from "./it";
import { polish } from "./pl";
import { finnish } from "./fi";
import { greek } from "./el";
import { danish } from "./da";
import { serbian } from "./sr";
import { hungarian } from "./hu";
import { czech } from "./cs";

// 语言配置映射
const languageConfig = {
  // 英语及其变体
  en: { translation: english, name: "English" },
  "en-AU": { translation: australianEnglish, name: "English (Australia)" },
  "en-NZ": { translation: newZealandEnglish, name: "English (New Zealand)" },
  "en-CA": { translation: canadianEnglish, name: "English (Canada)" },
  
  // 中文及其变体
  zh: { translation: chinese, name: "简体中文" },
  "zh-TW": { translation: traditionalChinese, name: "繁體中文" },
  
  // 西班牙语及其变体
  es: { translation: spain, name: "Español" },
  "es-MX": { translation: espanolMexicano, name: "Español (México)" },
  "es-419": { translation: latinAmericanSpanish, name: "Español (Latinoamérica)" },
  
  // 葡萄牙语及其变体
  pt: { translation: portuguese, name: "Português" },
  "pt-BR": { translation: brazilianPortuguese, name: "Português (Brasil)" },
  
  // 亚洲语言
  bn: { translation: bengali, name: "বাংলা" },
  ar: { translation: arabic, name: "العربية" },
  hi: { translation: hindi, name: "हिन्दी" },
  id: { translation: indonesian, name: "Bahasa Indonesia" },
  ja: { translation: japanese, name: "日本語" },
  ko: { translation: korean, name: "한국어" },
  vi: { translation: vietnam, name: "Tiếng Việt" },
  
  // 欧洲语言
  fr: { translation: french, name: "Français" },
  de: { translation: german, name: "Deutsch" },
  it: { translation: italian, name: "Italiano" },
  tr: { translation: turkish, name: "Türkçe" },
  pl: { translation: polish, name: "Polski" },
  fi: { translation: finnish, name: "Suomi" },
  el: { translation: greek, name: "Ελληνικά" },
  da: { translation: danish, name: "Dansk" },
  sr: { translation: serbian, name: "Српски" },
  hu: { translation: hungarian, name: "Magyar" },
  cs: { translation: czech, name: "Čeština" },
  ru: { translation: russian, name: "Русский" },
};

// 导出语言列表（按字母顺序排列）
export const languageList = Object.entries(languageConfig)
  .map(([code, config]) => ({ code, name: config.name }))
  .sort((a, b) => a.name.localeCompare(b.name, 'en', { sensitivity: 'base' }));

// 构建i18n资源
const resources = Object.entries(languageConfig).reduce((acc, [code, config]) => {
  acc[code] = { translation: config.translation };
  return acc;
}, {});

// 配置i18n
i18n
  .use(LanguageDetector) // 添加语言检测插件
  .use(initReactI18next)
  .init({
    resources,
    lng: undefined, // 让检测器自动确定语言
    fallbackLng: "en",
    interpolation: {
      escapeValue: false, // React已经防止XSS攻击
    },
    // 添加调试模式（开发环境）
    debug: process.env.NODE_ENV === 'development',
    // 配置语言检测选项
    detection: {
      order: ['localStorage', 'navigator', 'htmlTag', 'path', 'subdomain'],
      lookupLocalStorage: 'i18nextLng',
      caches: ['localStorage'],
      // 检查路径和子域名
      lookupFromPathIndex: 0,
      lookupFromSubdomainIndex: 0,
    }
  });


export default i18n;