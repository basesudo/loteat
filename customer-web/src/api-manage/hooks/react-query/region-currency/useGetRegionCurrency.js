import { useQuery } from "react-query";
import { onSingleErrorResponse } from "../../../api-error-response/ErrorResponses";
import MainApi from "../../../MainApi";
import { region_currency_list } from "../../../ApiRoutes";

const getRegionCurrency = async () => {
  const { data } = await MainApi.get(region_currency_list);
  return data;
};

export default function useGetRegionCurrency() {
  return useQuery("region-currency", getRegionCurrency, {
    onError: onSingleErrorResponse,
    staleTime: 5 * 60 * 1000, // 5分钟内不重新请求
    cacheTime: 10 * 60 * 1000, // 缓存10分钟
  });
}