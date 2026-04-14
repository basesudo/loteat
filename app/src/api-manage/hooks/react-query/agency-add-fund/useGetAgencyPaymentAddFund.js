import { useQuery } from "react-query";
import MainApi from "../../../MainApi";
import { onErrorResponse } from "../../../api-error-response/ErrorResponses";

/**
 * 获取代理商支付添加资金信息的钩子
 * @param {string|null} agencyId - 代理商ID
 * @returns {Object} - 查询结果对象
 */
const useGetAgencyPaymentAddFund = (agencyId) => {
  return useQuery(
    ['agency-payment-add-fund', agencyId],
    async () => {
      if (!agencyId) return null;
      
      // 构建查询参数
      const params = { 
        agency_id: agencyId
      };
      
      const { data } = await MainApi.get(`/agency-payment-add-fund`, { params });
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

export default useGetAgencyPaymentAddFund;