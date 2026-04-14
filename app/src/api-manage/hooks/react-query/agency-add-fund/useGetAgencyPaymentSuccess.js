import { useQuery } from "react-query";
import MainApi from "../../../MainApi";
import { onErrorResponse } from "../../../api-error-response/ErrorResponses";

/**
 * 获取代理商支付成功信息的钩子
 * @param {string|null} agencyId - 代理商ID
 * @param {number|null} amount - 金额
 * @param {Object} options - 额外查询参数
 * @returns {Object} - 查询结果对象
 */
const useGetAgencyPaymentSuccess = (agencyId) => {
  return useQuery(
    ['agency-payment-success', agencyId],
    async () => {
      if (!agencyId) return null;
      
      // 构建查询参数
      const params = { 
        agency_id: agencyId,
      };
      
      const { data } = await MainApi.get(`/agency-payment-success`, { params });
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

export default useGetAgencyPaymentSuccess;