import { useQuery } from "react-query";
import MainApi from "../../../MainApi";

/**
 * 获取代理商支付订单信息的钩子
 * @param {string|null} agencyId - 代理商ID
 * @returns {Object} - 查询结果对象
 */
const useGetAgencyPaymentInfo = (agencyId) => {
  return useQuery(
    ['agency-payment-info', agencyId],
    async () => {
      if (!agencyId) return null;
      
      const { data } = await MainApi.get(`/agency-payment-info?agency_id=${agencyId}`);
      return data;
    },
    {
      enabled: !!agencyId,
      retry: 1,
      refetchOnWindowFocus: false,
    }
  );
};

export default useGetAgencyPaymentInfo;