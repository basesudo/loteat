import { useQuery } from "react-query";
import { agency_payment_thank_you_api } from "../../../ApiRoutes";
import MainApi from "../../../MainApi";
import { onErrorResponse } from "../../../api-error-response/ErrorResponses";

const getAgencyPaymentThankYou = async (agencyId) => {
  const { data } = await MainApi.get(agency_payment_thank_you_api, {
    params: {
      agency_id: agencyId
    }
  });
  return data;
};

export default function useGetAgencyPaymentThankYou(agencyId) {
  return useQuery(
    ["agency-payment-thank-you", agencyId],
    () => getAgencyPaymentThankYou(agencyId),
    {
      enabled: !!agencyId, // 只在 agencyId 存在时启用查询
      onError: onErrorResponse,
    }
  );
}