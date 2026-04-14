import React, { useState } from "react";
import { Checkbox, FormControlLabel, FormGroup } from "@mui/material";
import { useTranslation } from "react-i18next";
import { CustomStackFullWidth } from "../../../styled-components/CustomStyles.style";
// import { CustomTypographyGray } from '../../error/Errors.style'
// import { CustomTypography } from '../../custom-tables/Tables.style'
import LoadingButton from "@mui/lab/LoadingButton";
import { CustomTypography } from "../../landing-page/hero-section/HeroSection.style";
import Link from "next/link";
import { useTheme } from "@emotion/react";
import { useDispatch, useSelector } from "react-redux";
import { setOfflineInfoStep } from "../../../redux/slices/offlinePaymentData";
import { useRouter } from "next/router";

const PlaceOrder = (props) => {
  const {
    placeOrder,
    orderLoading,
    zoneData,
    isStoreOpenOrNot,
    storeData,
    isSchedules,
    page,
    storeCloseToast,
    paymentMethod, // 支付方式
    nova2payFormValid, // nova2pay 表单验证状态
  } = props;

  const { offlineInfoStep } = useSelector((state) => state.offlinePayment);
  const { t } = useTranslation();
  const theme = useTheme();
  const router = useRouter();
  const dispatch = useDispatch();
  const [checked, setChecked] = useState(false);

  const handleChange = (e) => {
    setChecked(e.target.checked);
  };

  const handleOffline = (e) => {
    if (storeData?.active) {
      //checking restaurant or shop open or not
      if (isSchedules()) {
        setChecked(e.target.checked);
        dispatch(setOfflineInfoStep(2));
        router.push(
          { pathname: "/checkout", query: { page: page, method: "offline" } },
          undefined,
          { shallow: true }
        );
      } else {
        storeCloseToast();
      }
    } else {
      storeCloseToast();
    }
  };

  // 检查是否可以启用按钮
  const isButtonDisabled = () => {
    // 首先检查条款是否同意
    if (!checked) return true;
    
    // 如果选择了 nova2pay 支付方式，必须通过表单验证
    if (paymentMethod === 'nova2pay' && !nova2payFormValid) {
      return true;
    }
    
    // 如果选择了 agency_payment 支付方式，也需要表单验证（如果有的话）
    if (paymentMethod === 'agency_payment' && !nova2payFormValid) {
      return true;
    }
    
    return false;
  };

  const primaryColor = theme.palette.primary.main;
  return (
    <CustomStackFullWidth alignItems="center" spacing={2} mt=".5rem">
      <FormGroup>
        <FormControlLabel
          control={<Checkbox checked={checked} onChange={handleChange} />}
          label={
            <CustomTypography fontSize="12px">
              {t(`I agree that placing the order places me under`)}{" "}
              <Link
                href="/terms-and-conditions"
                style={{ color: primaryColor }}
              >
                {t("Terms and Conditions")}
              </Link>{" "}
              {t("&")}
              <Link href="/privacy-policy" style={{ color: primaryColor }}>
                {" "}
                {t("Privacy Policy")}
              </Link>
            </CustomTypography>
          }
        />
      </FormGroup>
      {offlineInfoStep === 0 ? (
        <LoadingButton
          type="submit"
          fullWidth
          variant="contained"
          onClick={placeOrder}
          loading={orderLoading}
          disabled={isButtonDisabled()}
          sx={{
            opacity: isButtonDisabled() ? 0.6 : 1,
            cursor: isButtonDisabled() ? 'not-allowed' : 'pointer',
          }}
        >
          {t("Place Order")}
        </LoadingButton>
      ) : (
        <LoadingButton
          // type="submit"
          fullWidth
          variant="contained"
          onClick={handleOffline}
          loading={orderLoading}
          disabled={isButtonDisabled()}
          sx={{
            opacity: isButtonDisabled() ? 0.6 : 1,
            cursor: isButtonDisabled() ? 'not-allowed' : 'pointer',
          }}
        >
          {t("Confirm Order")}
        </LoadingButton>
      )}
    </CustomStackFullWidth>
  );
};

PlaceOrder.propTypes = {};

export default PlaceOrder;