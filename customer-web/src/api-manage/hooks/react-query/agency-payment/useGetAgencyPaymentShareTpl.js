import { useQuery } from "react-query";
import { agency_payment_share_api } from "../../../ApiRoutes";
import MainApi from "../../../MainApi";
import { onErrorResponse } from "../../../api-error-response/ErrorResponses";

const getAgencyPaymentShare = async (agencyId) => {
  const { data } = await MainApi.get(agency_payment_share_api, {
    params: {
      agency_id: agencyId
    }
  });
  return data;
};

export default function useGetAgencyPaymentShare(agencyId) {
  return useQuery(
    ["agency-payment-share", agencyId], 
    () => getAgencyPaymentShare(agencyId), 
    {
      enabled: !!agencyId, // 只在 agencyId 存在时启用查询
      onError: onErrorResponse,
    }
  );
}