import { useQuery } from "react-query";
import MainApi from "../../../MainApi";
import { onErrorResponse } from "../../../api-error-response/ErrorResponses";

/**
 * 获取钱包代理商支付信息的钩子
 * @param {string|null} agencyId - 代理商ID
 * @param {Object} options - 额外查询参数
 * @returns {Object} - 查询结果对象
 */
const useGetWalletAgencyPaymentInfo = (agencyId, options = {}) => {
  return useQuery(
    ['wallet-agency-payment-info', agencyId, options],
    async () => {
      if (!agencyId) return null;
      
      // 构建查询参数
      const params = { agency_id: agencyId, ...options };
      
      const { data } = await MainApi.get(`api/v1/wallet-agenc-payment-info`, { params });
      return data;
    },
    {
      enabled: !!agencyId,
      retry: 1,
      refetchOnWindowFocus: false,
      onError: onErrorResponse,
    }
  );
};

export default useGetWalletAgencyPaymentInfo;