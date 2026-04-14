import React, { useEffect, useReducer, useState } from "react";
import { alpha, Grid, Typography, useMediaQuery, Button } from "@mui/material";
import { Stack } from "@mui/system";
import {
  CustomPaperBigCard,
  CustomStackFullWidth,
} from "../../../styled-components/CustomStyles.style";
// 在文件顶部的其他导入语句下添加
import {
  getAmountWithSign,
  getDiscountedAmount,
} from "../../../helper-functions/CardHelpers";
import DeliveryDetails from "./DeliveryDetails";
import useGetStoreDetails from "../../../api-manage/hooks/react-query/store/useGetStoreDetails";
import { useDispatch, useSelector } from "react-redux";
import { useTranslation } from "react-i18next";
import {
  getDayNumber,
  getFinalTotalPrice,
  getInfoFromZoneData,
  getProductDiscount,
  getTaxableTotalPrice,
  getVariation,
  handleDistance,
  isAvailable,
  isFoodAvailableBySchedule,
} from "../../../utils/CustomFunctions";
import { today, tomorrow } from "../../../utils/formatedDays";
import { GoogleApi } from "../../../api-manage/hooks/react-query/googleApi";
import { useMutation, useQuery } from "react-query";
import {
  onErrorResponse,
  onSingleErrorResponse,
} from "../../../api-manage/api-error-response/ErrorResponses";
import { toast } from "react-hot-toast";
import Router from "next/router";
import { OrderApi } from "../../../api-manage/another-formated-api/orderApi";
import { ProfileApi } from "../../../api-manage/another-formated-api/profileApi";
import HaveCoupon from "./HaveCoupon";
import DeliveryManTip from "../DeliveryManTip";
import { CouponTitle } from "../CheckOut.style";
import SimpleBar from "simplebar-react";
import "simplebar-react/dist/simplebar.min.css";
import OrderSummaryDetails from "./OrderSummaryDetails";
import OrderCalculationShimmer from "./OrderCalculationShimmer";
import OrderCalculation from "./OrderCalculation";
import PlaceOrder from "./PlaceOrder";
import { baseUrl } from "../../../api-manage/MainApi";
import { cod_exceeds_message } from "../../../utils/toasterMessages";
import {
  setClearCart,
  setRemoveItemFromCart,
} from "../../../redux/slices/cart";
import { getCurrentModuleType } from "../../../helper-functions/getCurrentModuleType";
import SinglePrescriptionUpload from "../Prescription/SinglePrescriptionUpload";
import useGetVehicleCharge from "../../../api-manage/hooks/react-query/order-place/useGetVehicleCharge";
import { getStoresOrRestaurants } from "../../../helper-functions/getStoresOrRestaurants";
import moment from "moment/moment";
import CheckoutStepper from "./CheckoutStepper";
import { INITIAL_STATE, scheduleReducer } from "./ScheduleReducer";
import { useTheme } from "@emotion/react";
import useGetMostTrips from "../../../api-manage/hooks/react-query/useGetMostTrips";
import PartialPayment from "./PartialPayment";
import CustomModal from "../../modal";
import PartialPaymentModal from "./PartialPaymentModal";
import AddPaymentMethod from "./AddPaymentMethod";
import Cutlery from "./Cutlery";
import ItemSelectWithChip from "../../ItemSelectWithChip";
import { deliveryInstructions, productUnavailableData } from "./demoData";
import { handleValuesFromCartItems } from "../../product-details/product-details-section/helperFunction";
import useGetOfflinePaymentOptions from "../../../api-manage/hooks/react-query/offlinePayment/useGetOfflinePaymentOptions";
import { getGuestId, getToken } from "../../../helper-functions/getToken";
import OfflineForm from "./offline-payment/OfflineForm";
import { useOfflinePayment } from "../../../api-manage/hooks/react-query/offlinePayment/useOfflinePayment";
import {
  setOfflineInfoStep,
  setOfflineMethod,
  setOrderDetailsModal,
} from "../../../redux/slices/offlinePaymentData";

import CustomImageContainer from "../../CustomImageContainer";
import thunderstorm from "../assets/thunderstorm.svg";
import { setGuestUserOrderId } from "../../../redux/slices/guestUserInfo";
import {
  setOrderDetailsModalOpen,
  setOrderInformation,
} from "../../../redux/slices/utils";

import ContentCopyIcon from '@mui/icons-material/ContentCopy';
import { IconButton, Tooltip } from '@mui/material';
import { copyToClipboard } from "../../../utils/copyToClipboard";
import PaymentMethodForm from './PaymentMethodForm';

const ItemCheckout = (props) => {
  const { configData, router, page, cartList, campaignItemList, totalAmount } =
    props;
  const theme = useTheme();
  const isSmall = useMediaQuery(theme.breakpoints.down("md"));
  const [orderType, setOrderType] = useState("delivery");
  const [payableAmount, setPayableAmount] = useState(null);
  const [address, setAddress] = useState(undefined);
  const { couponInfo } = useSelector((state) => state.profileInfo);
  const [paymentMethod, setPaymentMethod] = useState("");
  const [numberOfDay, setDayNumber] = useState(getDayNumber(today));
  const [couponDiscount, setCouponDiscount] = useState(null);
  const [offlinePayments, setOfflinePayments] = useState("");
  const [scheduleAt, setScheduleAt] = useState("now");
  const [orderSuccess, setOrderSuccess] = useState(false);
  const [taxAmount, setTaxAmount] = useState(0);
  const [total_order_amount, setTotalOrderAmount] = useState(0);
  const [enabled, setEnabled] = useState(cartList?.length ? true : false);
  const [deliveryTip, setDeliveryTip] = useState(0);
  const [deliveryFee, setDeliveryFee] = useState(0);
  const [isImageSelected, setIsImageSelected] = useState([]);
  const [cutlery, setCutlery] = useState(0);
  const [unavailable_item_note, setUnavailable_item_note] = useState(null);
  const [delivery_instruction, setDelivery_instruction] = useState(null);
  const [usePartialPayment, setUsePartialPayment] = useState(false);
  const [switchToWallet, setSwitchToWallet] = useState(false);
  const [openModal, setOpenModal] = useState(false);
  const [openPartialModel, setOpenPartialModel] = useState(false);
  const [orderId, setOrderId] = useState("");
  const [offlineCheck, setOfflineCheck] = useState(false);
  // 添加以下新状态
  const [showPaymentForm, setShowPaymentForm] = useState(false);
  const [selectedPaymentMethod, setSelectedPaymentMethod] = useState("");
  const [isPaymentFormValid, setIsPaymentFormValid] = useState(false);
  const [isStoreOpenOrNot, setIsStoreOpenOrNot] = useState(true);
  const [state, customDispatch] = useReducer(scheduleReducer, INITIAL_STATE);
  const { profileInfo } = useSelector((state) => state.profileInfo);
  const { guestUserInfo } = useSelector((state) => state.guestUserInfo);
  const { offlinePaymentInfo } = useSelector((state) => state.offlinePayment);
  const token = getToken();
  const dispatch = useDispatch();
  const { t } = useTranslation();
  const guest_id = getGuestId();
  const { method } = router.query;
  const currentModuleType = getCurrentModuleType();
  const storeId =
    page === "campaign"
      ? campaignItemList?.[0]?.store_id
      : cartList?.[0]?.store_id;
  const { data: storeData, refetch } = useGetStoreDetails(storeId);
  const { data: tripsData } = useGetMostTrips();
  const { mutate: offlineMutate, isLoading: offlinePaymentLoading } =
    useOfflinePayment();
  const {
    data: offlinePaymentOptions,
    refetch: refetchOfflinePaymentOptions,
    isLoading: offlineIsLoading,
  } = useGetOfflinePaymentOptions();
  useEffect(() => {
    refetchOfflinePaymentOptions();
  }, []);
  useEffect(() => {
    if (storeId) {
      refetch();
    }
  }, [storeId]);
  const [readyToNavigate, setReadyToNavigate] = useState(false);
  useEffect(() => {
    if (orderSuccess && readyToNavigate) {
      handleOrderSuccess();
    }
  }, [orderSuccess, readyToNavigate]);

  useEffect(() => {
    const currentLatLng = JSON.parse(localStorage.getItem("currentLatLng"));
    const location = localStorage.getItem("location");
    setAddress({
      ...currentLatLng,
      latitude: currentLatLng?.lat,
      longitude: currentLatLng?.lng,
      address: location,
      address_type: "Selected Address",
    });
    refetch();
  }, []);

  // 在现有的 useEffect 后添加
  useEffect(() => {
    // 当选择特定支付方式时显示对应表单
    if (paymentMethod === "nobody" || paymentMethod === "agency_payment" || paymentMethod === "offline_payment") {
      setShowPaymentForm(true);
      setSelectedPaymentMethod(paymentMethod);
      setIsPaymentFormValid(false); // 重置表单验证状态
    } else {
      setShowPaymentForm(false);
      setSelectedPaymentMethod("");
      setIsPaymentFormValid(true); // 其他支付方式不需要额外验证
    }
  }, [paymentMethod]);

  const currentLatLng = JSON.parse(
    window.localStorage.getItem("currentLatLng")
  );
  const { data: zoneData } = useQuery(
    ["zoneId", location],
    async () => GoogleApi.getZoneId(currentLatLng),
    {
      retry: 1,
    }
  );

  // 在组件的状态定义部分添加
  const [agencyPaymentModalOpen, setAgencyPaymentModalOpen] = useState(false);
  const [agencyPaymentDetails, setAgencyPaymentDetails] = useState(null);
  const { data: distanceData, refetch: refetchDistance } = useQuery(
    ["get-distance", storeData, address],
    () => GoogleApi.distanceApi(storeData, address),
    {
      enabled: false,
      onError: onErrorResponse,
    }
  );
  const tempDistance = handleDistance(
    distanceData?.data?.rows?.[0]?.elements,
    { latitude: storeData?.latitude, longitude: storeData?.longitude },
    address
  );

  const {
    data: extraCharge,
    isLoading: extraChargeLoading,
    refetch: extraChargeRefetch,
  } = useGetVehicleCharge({ tempDistance });
  useEffect(() => {
    if (distanceData) {
      extraChargeRefetch();
    }
  }, [distanceData]);
  const handleChange = (event) => {
    setDayNumber(event.target.value);
  };
  //order post api
  const { mutate: orderMutation, isLoading: orderLoading } = useMutation(
    "order-place",
    OrderApi.placeOrder
  );
  const userOnSuccessHandler = (res) => {
    // dispatch(setUser(res.data))
    // dispatch(setWalletAmount(res?.data?.wallet_balance))
  };
  const { isLoading: customerLoading, data: customerData } = useQuery(
    ["profile-info"],
    ProfileApi.profileInfo,
    {
      onSuccess: userOnSuccessHandler,
      onError: onSingleErrorResponse,
    }
  );
  useEffect(() => { }, [customerData]);

  useEffect(() => {
    const currentLatLng = JSON.parse(localStorage.getItem("currentLatLng"));
    const location = localStorage.getItem("location");
    setAddress({
      ...currentLatLng,
      latitude: currentLatLng?.lat,
      longitude: currentLatLng?.lng,
      address: location,
      address_type: "Selected Address",
    });
    refetch();
  }, []);
  useEffect(() => {
    storeData && address && refetchDistance();
  }, [storeData, address]);

  useEffect(() => {
    const taxAmount = getTaxableTotalPrice(
      cartList,
      couponDiscount,
      storeData?.tax,
      storeData
    );
    setTaxAmount(taxAmount);
  }, [cartList, couponDiscount, storeData]);
  useEffect(() => {
    const total_order_amount = getFinalTotalPrice(
      cartList,
      couponDiscount,
      taxAmount,
      storeData
    );
    setTotalOrderAmount(total_order_amount);
  }, [cartList, couponDiscount, taxAmount]);

  const handleOffineOrder = () => {
    const offlinePaymentData = {
      ...offlinePaymentInfo,
      order_id: orderId,
      guest_id: guest_id,
    };
    dispatch(setOfflineInfoStep(3));
    dispatch(setOrderDetailsModal(true));
    offlineMutate(offlinePaymentData);
  };

  //orderId
  //offlinePaymentInfo
  useEffect(() => {
    if (offlineCheck) {
      handleOffineOrder();
    }
  }, [orderId]);

  const handleProductList = (productList, totalQty) => {
    return productList?.map((cart) => {
      return {
        add_on_ids:
          cart?.selectedAddons?.length > 0
            ? cart?.selectedAddons?.map((add) => {
              return add.id;
            })
            : [],
        add_on_qtys:
          cart?.selectedAddons?.length > 0
            ? cart?.selectedAddons?.map((add) => {
              totalQty += add.quantity;
              return totalQty;
            })
            : [],
        add_ons:
          cart?.selectedAddons?.length > 0
            ? cart?.selectedAddons?.map((add) => {
              return {
                id: add.id,
                name: add.name,
                price: add.price,
              };
            })
            : [],
        item_id: cart?.id,
        item_campaign_id: cart?.available_date_starts ? cart?.id : null,
        item_type: cart?.available_date_starts
          ? "AppModelsItemCampaign"
          : "AppModelsItem",
        price: cart?.price,
        quantity: cart?.quantity,
        variant:
          cart?.module_type === "food" ? getVariation(cart?.variation) : [],
        //new variation form needs to added here
        variation:
          cart?.module_type === "food"
            ? cart?.food_variations?.length > 0
              ? cart?.food_variations?.map((variation) => {
                return {
                  name: variation.name,
                  values: {
                    label: handleValuesFromCartItems(variation.values),
                  },
                };
              })
              : []
            : cart?.selectedOption?.length > 0
              ? cart?.selectedOption
              : [],
      };
    });
  };

  const handleOrderMutationObject = (carts, productList) => {
    const guestId = getToken() ? "" : guest_id;
    const isDigital =
      paymentMethod !== "cash_on_delivery" &&
        paymentMethod !== "wallet" &&
        paymentMethod !== "offline_payment" &&
        paymentMethod !== "agency_payment" &&
        paymentMethod !== ""
        ? "digital_payment"
        : paymentMethod;

    const originData = {
      latitude: storeData?.latitude,
      longitude: storeData?.longitude,
    };

    // 基础订单数据
    const baseOrderData = {
      cart: JSON.stringify(carts),
      ...address,
      partial_payment: usePartialPayment,
      schedule_at: scheduleAt === "now" ? null : scheduleAt,
      payment_method: isDigital,
      order_type: orderType === "schedule_order" ? "delivery" : orderType,
      store_id: storeData?.id,
      coupon_code: couponDiscount?.code,
      coupon_discount_amount: couponDiscount?.discount,
      coupon_discount_title: couponDiscount?.title,
      discount_amount: getProductDiscount(productList),
      distance: handleDistance(
        distanceData?.data?.rows?.[0]?.elements,
        originData,
        address
      ),
      order_amount: totalAmount,
      dm_tips: deliveryTip,
      cutlery: cutlery,
      unavailable_item_note: unavailable_item_note,
      delivery_instruction: delivery_instruction,
      guest_id: guestId,
      contact_person_name: guestUserInfo?.contact_person_name,
      contact_person_number: guestUserInfo?.contact_person_number,
      contact_person_email: guestUserInfo?.contact_person_email,
      is_buy_now: page === "buy_now" || page === "campaign" ? 1 : 0,
      house: address?.house,
      floor: address?.floor,
    };

    // 根据支付方式添加特定的表单数据
    if (paymentMethod === "nova2pay" && paymentFormData.nova2pay) {
      return {
        ...baseOrderData,
        payment_details: {
          cardNo: paymentFormData.nova2pay.cardNo,
          expDate: paymentFormData.nova2pay.expDate,
          cvv: paymentFormData.nova2pay.cvv,
          firstName: paymentFormData.nova2pay.firstName,
          lastName: paymentFormData.nova2pay.lastName,
          email: paymentFormData.nova2pay.email,
          countryCode: paymentFormData.nova2pay.countryCode,
          phoneNo: paymentFormData.nova2pay.phoneNo,
          country: paymentFormData.nova2pay.country,
          city: paymentFormData.nova2pay.city,
          houseNumberOrName: paymentFormData.nova2pay.houseNumberOrName,
          street: paymentFormData.nova2pay.street,
          zip: paymentFormData.nova2pay.zip,
        }
      };
    } else if (paymentMethod === "agency_payment" && paymentFormData.agency_payment) {
      return {
        ...baseOrderData,
        agency_details: {
          agencyId: paymentFormData.agency_payment.agencyId,
          reference: paymentFormData.agency_payment.reference || "",
        }
      };
    }

    return baseOrderData;
  };
  const handlePlaceOrder = () => {
    const itemsList = page === "campaign" ? campaignItemList : cartList;
    const isAvailable = storeData?.schedule_order
      ? isFoodAvailableBySchedule(itemsList, scheduleAt)
      : true;
    if (isAvailable) {
      const walletAmount = customerData?.data?.wallet_balance;
      let productList = page === "campaign" ? campaignItemList : cartList;
      if (paymentMethod === "wallet") {
        if (Number(walletAmount) < Number(totalAmount)) {
          toast.error(t("Wallet balance is below total amount."), {
            id: "wallet",
            position: "bottom-right",
          });
        } else {
          let totalQty = 0;
          let carts = handleProductList(productList, totalQty);
          const handleSuccessSecond = (response) => {
            if (response?.data) {
              if (token) {
                dispatch(setOrderDetailsModal(true));
              }
              if (paymentMethod === "digital_payment") {
                toast.success(response?.data?.message);
                const newBaseUrl = baseUrl;
                const page = "my-orders";
                const callBackUrl = token
                  ? `${window.location.origin}/profile?page=${page}`
                  : `${window.location.origin}/order?order_id=${response?.data?.order_id}&total=${response?.data?.total_ammount}`;
                const url = `${newBaseUrl}/payment-mobile?order_id=${response?.data?.order_id
                  }&customer_id=${customerData?.data?.id ?? guest_id
                  }&callback=${callBackUrl},`;
                localStorage.setItem("totalAmount", totalAmount);
                dispatch(setClearCart());
                Router.push(url);
              } else if (paymentMethod === "wallet") {
                toast.success(response?.data?.message);
                setOrderId(response?.data?.order_id);
                setOrderSuccess(true);
              } else {
                if (response.status === 203) {
                  toast.error(response.data.errors[0].message);
                }
                //setOrderSuccess(true)
              }
            }
          };
          if (carts?.length > 0) {
            let order = handleOrderMutationObject(carts, productList);
            orderMutation(order, {
              onSuccess: handleSuccessSecond,
              onError: (error) => {
                error?.response?.data?.errors?.forEach((item) =>
                  toast.error(item.message, {
                    position: "bottom-right",
                  })
                );
              },
            });
          }
        }
      } else {
        let totalQty = 0;
        let carts = handleProductList(productList, totalQty);
        const handleSuccess = (response) => {
          if (response?.data) {
            if (
              paymentMethod === "cash_on_delivery" ||
              paymentMethod === "offline_payment" ||
              paymentMethod === "wallet" ||
              paymentMethod === "agency_payment"
            ) {
              toast.success(response?.data?.message, {
                id: paymentMethod,
              });
            }
            if (
              paymentMethod !== "cash_on_delivery" &&
              paymentMethod !== "offline_payment" &&
              paymentMethod !== "agency_payment"
            ) {
              const payment_platform = "web";
              const page = "my-orders";
              const callBackUrl = token
                ? `${window.location.origin}/profile?page=${page}`
                : `${window.location.origin}/home`;
              const url = `${baseUrl}/payment-mobile?order_id=${response?.data?.order_id
                }&customer_id=${customerData?.data?.id ?? guest_id
                }&payment_platform=${payment_platform}&callback=${callBackUrl}&payment_method=${paymentMethod}`;
              localStorage.setItem("totalAmount", totalAmount);
              dispatch(setClearCart());
              Router.push(url, undefined, { shallow: true });
            } else if (paymentMethod === "offline_payment") {
              setOrderId(response?.data?.order_id);
              dispatch(setOrderInformation(response?.data));
              setOrderSuccess(true);
              setOfflineCheck(true);
            } else if (paymentMethod === "agency_payment") {
              setOrderId(response?.data?.order_id);
              dispatch(setOrderInformation(response?.data));

              // 创建显示详细信息的数据对象
              const agencyOrderDetails = {
                message: response?.data?.message || "Order placed successfully",
                order_id: response?.data?.order_id,
                total_ammount: response?.data?.total_ammount || totalAmount,
                status: response?.data?.status || "pending",
                created_at: response?.data?.created_at || new Date().toISOString(),
                user_id: response?.data?.user_id || customerData?.data?.id,
                agency_id: response?.data?.agency_id
              };

              // 保存数据到状态中供模态框使用
              setAgencyPaymentDetails(agencyOrderDetails);

              // 打开模态框
              setAgencyPaymentModalOpen(true);

              // 设置订单成功状态（但等待用户确认模态框后再跳转）
              setOrderSuccess(true);
            } else {
              setOrderId(response?.data?.order_id);
              dispatch(setOrderInformation(response?.data));
              setOrderSuccess(true);
            }
          }
        };
        if (carts?.length > 0) {
          let order = handleOrderMutationObject(carts, productList);
          orderMutation(order, {
            onSuccess: handleSuccess,
            onError: (error) => {
              error?.response?.data?.errors?.forEach((item) =>
                toast.error(item.message, {
                  position: "bottom-right",
                })
              );
            },
          });
        }
      }
    } else {
      toast.error(
        t(
          "One or more item is not available for the chosen preferable schedule time."
        )
      );
    }
  };

  const isStoreOpen = () => {
    // storeData?.schedule_order
  };
  const storeCloseToast = () =>
    toast.error(
      t(`${getStoresOrRestaurants().slice(0, -1)} is closed. Try again later.`)
    );
  //totalAmount
  const handlePlaceOrderBasedOnAvailability = () => {
    //cod -> cash on delivery
    const codLimit =
      getInfoFromZoneData(zoneData)?.pivot?.maximum_cod_order_amount;
    if (orderType === "take_away") {
      handlePlaceOrder();
    } else {
      if (codLimit) {
        if (totalAmount <= codLimit) {
          handlePlaceOrder();
        } else {
          toast.error(t(cod_exceeds_message), {
            duration: 5000,
          });
        }
      } else {
        handlePlaceOrder();
      }
    }
  };

  // 在现有状态后添加支付表单数据状态
  const [paymentFormData, setPaymentFormData] = useState({});

  // 添加处理支付表单数据变化的函数
  const handlePaymentFormDataChange = (paymentMethod, formData) => {
    setPaymentFormData({
      ...paymentFormData,
      [paymentMethod]: formData
    });
  };

  // 添加处理表单验证状态的函数
  const handlePaymentFormValidation = (isValid) => {
    setIsPaymentFormValid(isValid);
  };

  const isSchedules = () => {
    if (storeData?.schedules.length > 0) {
      const todayInNumber = moment().weekday();
      let isOpen = false;
      let filteredSchedules = storeData?.schedules.filter(
        (item) => item.day === todayInNumber
      );
      let isAvailableNow = [];

      filteredSchedules.forEach((item) => {
        if (isAvailable(item?.opening_time, item?.closing_time)) {
          isAvailableNow.push(item);
        }
      });

      if (isAvailableNow.length > 0) {
        isOpen = true;
      } else {
        isOpen = false;
      }

      return isOpen; // Add this line to return true or false based on whether the store is open.
    }
  };
  const placeOrder = () => {
    // 检查 nova2pay 表单验证
    if (paymentMethod === "nova2pay" && !isPaymentFormValid) {
      toast.error(t("Please fill in all required payment details"));
      return;
    }

    if (storeData?.active) {
      //checking restaurant or shop open or not
      if (isSchedules()) {
        handlePlaceOrderBasedOnAvailability();
      } else {
        storeCloseToast();
      }
    } else {
      storeCloseToast();
    }
  };

  const couponRemove = () => { };
  const handleOrderSuccess = () => {
    if (page === "buysetScheduleAt_now") {
      dispatch(setRemoveItemFromCart(cartList?.[0]));
    }
    localStorage.setItem("totalAmount", totalAmount);
    if (!token) {
      Router.push("/home");
    } else {
      Router.push(
        {
          pathname: "/profile",
          query: { orderId: orderId, page: "my-orders", from: "checkout" },
        },
        undefined,
        { shallow: false }
      );
    }
  };
  const handleImageUpload = (value) => {
    setIsImageSelected([value]);
  };
  const handlePartialPayment = () => {
    if (payableAmount > customerData?.data?.wallet_balance) {
      setUsePartialPayment(true);
      setPaymentMethod("");
      dispatch(setOfflineMethod(""));
    } else {
      setPaymentMethod("wallet");
      setSwitchToWallet(true);
      dispatch(setOfflineMethod(""));
    }
  };
  const removePartialPayment = () => {
    if (payableAmount > customerData?.data?.wallet_balance) {
      setUsePartialPayment(false);
      setPaymentMethod("");
      dispatch(setOfflineMethod(""));
    } else {
      setPaymentMethod("");
      setSwitchToWallet(false);
      dispatch(setOfflineMethod(""));
    }
  };
  const handlePartialPaymentCheck = () => {
    if (configData?.partial_payment_status === 1) {
      if (couponDiscount && usePartialPayment) {
        if (
          payableAmount > customerData?.data?.wallet_balance &&
          !usePartialPayment
        ) {
          setOpenPartialModel(true);
        } else {
          if (
            usePartialPayment &&
            customerData?.data?.wallet_balance > payableAmount
          ) {
            setOpenModal(true);
          }
        }
      } else if ((deliveryTip > 0 && usePartialPayment) || switchToWallet) {
        if (payableAmount > customerData?.data?.wallet_balance) {
          setOpenPartialModel(true);
        } else {
          if (
            usePartialPayment &&
            customerData?.data?.wallet_balance > payableAmount
          ) {
            setOpenModal(true);
          }
        }
      } else if (orderType && usePartialPayment) {
        if (
          payableAmount > customerData?.data?.wallet_balance &&
          !usePartialPayment
        ) {
          setOpenPartialModel(true);
        } else {
          if (
            usePartialPayment &&
            customerData?.data?.wallet_balance > payableAmount
          ) {
            setOpenModal(true);
          }
          //setOpenModal(true);
        }
      }
    }
  };
  useEffect(() => {
    handlePartialPaymentCheck();
  }, [payableAmount]);

  const agreeToPartial = () => {
    setPaymentMethod("");
    setUsePartialPayment(true);
    setOpenPartialModel(false);
    setSwitchToWallet(false);
  };
  const notAgreeToPartial = () => {
    setUsePartialPayment(false);
    setOpenPartialModel(false);
    setSwitchToWallet(false);
  };
  const agreeToWallet = () => {
    setPaymentMethod("wallet");
    setSwitchToWallet(true);
    setUsePartialPayment(false);
    setOpenModal(false);
  };
  const notAgreeToWallet = () => {
    setPaymentMethod("");
    setSwitchToWallet(false);
    setUsePartialPayment(false);
    setOpenModal(false);
  };
  const handleCutlery = (status) => {
    if (status) {
      setCutlery(1);
    } else {
      setCutlery(1);
    }
  };
  const handleItemUnavailableNote = (value) => {
    setUnavailable_item_note(value);
  };
  const handleDeliveryInstructionNote = (value) => {
    setDelivery_instruction(value);
  };
  useEffect(() => {
    if (paymentMethod !== "wallet") {
      setSwitchToWallet(false);
    }
  }, [paymentMethod]);
  const handleBadWeatherUi = (zoneWiseData) => {
    const currentZoneInfo = zoneWiseData?.find(
      (item) => item.id === storeData?.zone_id
    );

    if (currentZoneInfo) {
      if (currentZoneInfo?.increased_delivery_fee_status === 1) {
        return (
          <>
            {currentZoneInfo?.increase_delivery_charge_message && (
              <CustomStackFullWidth
                alignItems="center"
                justifyContent="flex-start"
                gap="10px"
                direction="row"
                mt="10px"
                sx={{
                  backgroundColor: (theme) =>
                    alpha(theme.palette.primary.main, 0.3),
                  borderRadius: "4px",
                  padding: "5px 10px",
                }}
              >
                <CustomImageContainer
                  height="40px"
                  width="40px"
                  src={thunderstorm.src}
                  objectFit="contained"
                />

                <Typography>
                  {currentZoneInfo?.increase_delivery_charge_message}
                </Typography>
              </CustomStackFullWidth>
            )}
          </>
        );
      }
    }
  };
  return (
    <>
      {method === "offline" ? (
        <Grid container mb="2rem" paddingTop={{ xs: "1.5rem", md: "2.5rem" }}>
          <Grid item xs={12} md={12}>
            <CheckoutStepper />
            <CustomStackFullWidth
              marginTop={{ xs: "1.5rem", md: "2.5rem" }}
              alignItems="center"
            >
              <CustomPaperBigCard
                sx={{ width: { xs: "100%", sm: "90%", md: "80%" } }}
              >
                <OfflineForm
                  offlinePaymentOptions={offlinePaymentOptions}
                  total_order_amount={payableAmount}
                  placeOrder={placeOrder}
                  offlinePaymentLoading={offlinePaymentLoading || orderLoading}
                  usePartialPayment={usePartialPayment}
                />
              </CustomPaperBigCard>
            </CustomStackFullWidth>
          </Grid>
        </Grid>
      ) : (
        <Grid
          container
          spacing={3}
          mb="2rem"
          paddingTop={{ xs: "1.5rem", md: "2.5rem" }}
        >
          <Grid item xs={12} md={7}>
            <Stack
              spacing={{ xs: 2, sm: 2, md: 3 }}
              pb={{ xs: "1rem", sm: "2rem", md: "4rem" }}
            >
              <CheckoutStepper />
              {zoneData && (
                <AddPaymentMethod
                  setPaymentMethod={setPaymentMethod}
                  paymentMethod={paymentMethod}
                  zoneData={zoneData}
                  configData={configData}
                  orderType={orderType}
                  usePartialPayment={usePartialPayment}
                  offlinePaymentOptions={offlinePaymentOptions}
                  setSwitchToWallet={setSwitchToWallet}
                  onPaymentMethodChange={(method) => {
                    setPaymentMethod(method);
                    // 如果切换到非nova2pay方式，重置表单验证状态
                    if (method !== 'nova2pay' && method !== 'agency_payment') {
                      setIsPaymentFormValid(true);
                    } else {
                      setIsPaymentFormValid(false);
                    }
                  }}
                />
              )}

              {/* 添加支付方式表单 */}
              {showPaymentForm && (
                <PaymentMethodForm
                  paymentMethod={selectedPaymentMethod}
                  totalAmount={payableAmount}
                  offlinePaymentOptions={offlinePaymentOptions}
                  offlinePaymentLoading={offlinePaymentLoading}
                  usePartialPayment={usePartialPayment}
                  placeOrder={placeOrder}
                  onFormDataChange={handlePaymentFormDataChange}
                  onValidationChange={handlePaymentFormValidation}
                />
              )}

              <DeliveryDetails
                storeData={storeData}
                setOrderType={setOrderType}
                orderType={orderType}
                setAddress={setAddress}
                address={address}
                customDispatch={customDispatch}
                scheduleTime={state.scheduleTime}
                setDayNumber={setDayNumber}
                setDeliveryTip={setDeliveryTip}
                handleChange={handleChange}
                today={today}
                tomorrow={tomorrow}
                numberOfDay={numberOfDay}
                configData={configData}
                setScheduleAt={setScheduleAt}
              />

              {Number.parseInt(configData?.dm_tips_status) === 1 &&
                orderType !== "take_away" && (
                  <DeliveryManTip
                    orderType={orderType}
                    deliveryTip={deliveryTip}
                    setDeliveryTip={setDeliveryTip}
                    isSmall={isSmall}
                    tripsData={tripsData}
                    setUsePartialPayment={setUsePartialPayment}
                  />
                )}

              <Grid item md={12} xs={12}></Grid>
            </Stack>
          </Grid>
          <Grid item xs={12} md={5} height="auto">
            <CustomStackFullWidth>
              {currentModuleType === "pharmacy" && (
                <CustomPaperBigCard
                  sx={{ marginBottom: "1rem" }}
                  padding={isSmall ? "0px" : "1.25rem"}
                  noboxshadow={isSmall && "true"}
                  backgroundcolor={isSmall && theme.palette.background.default}
                >
                  <SinglePrescriptionUpload
                    t={t}
                    handleImageUpload={handleImageUpload}
                    borderRadius="10px"
                  />
                </CustomPaperBigCard>
              )}
              <CustomPaperBigCard
                height="auto"
                padding={isSmall ? "0px" : "1.25rem"}
                noboxshadow={isSmall && "true"}
                backgroundcolor={isSmall && theme.palette.background.default}
              >
                <Stack justifyContent="space-between">
                  <CouponTitle textAlign="left">
                    {t("Order Summary")}
                  </CouponTitle>
                  {zoneData && handleBadWeatherUi(zoneData?.data?.zone_data)}
                  <SimpleBar style={{ maxHeight: "500px", width: "100%" }}>
                    <OrderSummaryDetails
                      page={page}
                      configData={configData}
                      cartList={cartList}
                      t={t}
                      campaignItemList={campaignItemList}
                      isSmall={isSmall}
                    />
                  </SimpleBar>
                  {storeData && token && (
                    <HaveCoupon
                      store_id={storeData?.id}
                      setCouponDiscount={setCouponDiscount}
                      counponRemove={couponRemove}
                      couponDiscount={couponDiscount}
                      totalAmount={totalAmount}
                      deliveryFee={deliveryFee}
                      deliveryTip={deliveryTip}
                      setSwitchToWallet={setSwitchToWallet}
                      walletBalance={customerData?.data?.wallet_balance}
                      payableAmount={payableAmount}
                    />
                  )}
                  {configData?.customer_wallet_status === 1 &&
                    customerData?.data?.wallet_balance > 0 &&
                    configData?.partial_payment_status === 1 && (
                      <Grid item md={12} xs={12}>
                        <PartialPayment
                          remainingBalance={
                            customerData?.data?.wallet_balance - payableAmount
                          }
                          handlePartialPayment={handlePartialPayment}
                          usePartialPayment={usePartialPayment}
                          walletBalance={customerData?.data?.wallet_balance}
                          paymentMethod={paymentMethod}
                          switchToWallet={switchToWallet}
                          removePartialPayment={removePartialPayment}
                          payableAmount={payableAmount}
                        />
                      </Grid>
                    )}
                  {getCurrentModuleType() === "food" && storeData?.cutlery && (
                    <Cutlery isChecked={cutlery} handleChange={handleCutlery} />
                  )}
                  <ItemSelectWithChip
                    title="If Any Product is not available"
                    data={productUnavailableData}
                    handleChange={handleItemUnavailableNote}
                  />
                  <ItemSelectWithChip
                    title="Add More Delivery Instruction"
                    data={deliveryInstructions}
                    handleChange={handleDeliveryInstructionNote}
                  />
                  {distanceData && storeData ? (
                    <OrderCalculation
                      usePartialPayment={usePartialPayment}
                      cartList={
                        page === "campaign" ? campaignItemList : cartList
                      }
                      storeData={storeData}
                      couponDiscount={couponDiscount}
                      taxAmount={taxAmount}
                      distanceData={distanceData}
                      total_order_amount={total_order_amount}
                      configData={configData}
                      couponInfo={couponInfo}
                      orderType={orderType}
                      deliveryTip={deliveryTip}
                      origin={{
                        latitude: storeData?.latitude,
                        longitude: storeData?.longitude,
                      }}
                      destination={address}
                      zoneData={zoneData}
                      extraCharge={extraCharge && extraCharge}
                      setDeliveryFee={setDeliveryFee}
                      extraChargeLoading={extraChargeLoading}
                      walletBalance={customerData?.data?.wallet_balance}
                      setPayableAmount={setPayableAmount}
                      additionalCharge={
                        configData?.additional_charge_status === 1 &&
                        configData?.additional_charge
                      }
                      payableAmount={payableAmount}
                    />
                  ) : (
                    extraChargeLoading && <OrderCalculationShimmer />
                  )}
                  <PlaceOrder
                    placeOrder={placeOrder}
                    orderLoading={orderLoading}
                    zoneData={zoneData}
                    isStoreOpenOrNot={isStoreOpenOrNot}
                    storeData={storeData}
                    isSchedules={isSchedules}
                    page={page}
                    storeCloseToast={storeCloseToast}
                    paymentMethod={paymentMethod}
                    nova2payFormValid={isPaymentFormValid} // 传递验证状态
                  />
                </Stack>
              </CustomPaperBigCard>
            </CustomStackFullWidth>
          </Grid>
          {openModal && (
            <CustomModal
              openModal={openModal}
            //handleClose={() => setOpenModal(false)}
            >
              <PartialPaymentModal
                payableAmount={payableAmount}
                agree={agreeToWallet}
                reject={notAgreeToWallet}
                colorTitle=" Want to pay via your wallet ? "
                title="You can pay the full amount with your wallet."
                remainingBalance={
                  customerData?.data?.wallet_balance - payableAmount
                }
              />
            </CustomModal>
          )}
          {openPartialModel && (
            <CustomModal
              openModal={openPartialModel}
            //handleClose={() => setOpenPartialModel(false)}
            >
              <PartialPaymentModal
                payableAmount={payableAmount}
                agree={agreeToPartial}
                reject={notAgreeToPartial}
                colorTitle=" Want to pay partially with wallet ? "
                title="You do not have sufficient balance to pay full amount via wallet."
              />
            </CustomModal>
          )}
          {agencyPaymentModalOpen && agencyPaymentDetails && (
            <CustomModal
              openModal={agencyPaymentModalOpen}
              handleClose={() => setAgencyPaymentModalOpen(false)}
            >
              <CustomStackFullWidth
                alignItems="center"
                spacing={3}
                padding="20px"
                maxWidth="600px"
                minWidth="400px"
              >
                <Typography variant="h6" textAlign="center" color="primary.main">
                  {t(agencyPaymentDetails.message)}
                </Typography>

                <CustomStackFullWidth spacing={1.5}>
                  <Stack direction="row" justifyContent="space-between">
                    <Typography fontWeight="500">{t("Order ID")}</Typography>
                    <Typography>{agencyPaymentDetails.order_id}</Typography>
                  </Stack>

                  <Stack direction="row" justifyContent="space-between">
                    <Typography fontWeight="500">{t("Status")}</Typography>
                    <Typography
                      sx={{
                        textTransform: "capitalize",
                        color: theme.palette.primary.main,
                      }}
                    >
                      {agencyPaymentDetails.status}
                    </Typography>
                  </Stack>

                  <Stack direction="row" justifyContent="space-between">
                    <Typography fontWeight="500">{t("Total Amount")}</Typography>
                    <Typography>
                      {getAmountWithSign(agencyPaymentDetails.total_ammount)}
                    </Typography>
                  </Stack>

                  <Stack direction="row" justifyContent="space-between">
                    <Typography fontWeight="500">{t("Payment Link")}</Typography>
                    <Stack direction="row" alignItems="center" spacing={1}>
                      <Typography fontWeight="500">{t("Shere Link")}</Typography>
                      <Tooltip title={t("Copy Link")}>
                        <IconButton
                          size="small"
                          onClick={() => {
                            copyToClipboard(
                              `${window.location.origin}/agency-payment?agency_id=${agencyPaymentDetails.agency_id}`
                            ).then(success => {
                              if (success) {
                                toast.success(t("Link copied to clipboard"));
                              } else {
                                toast.error(t("Failed to copy link"));
                              }
                            });
                          }}
                          sx={{
                            color: theme.palette.primary.main,
                            padding: "2px",
                          }}
                        >
                          <ContentCopyIcon fontSize="small" />
                        </IconButton>
                      </Tooltip>
                    </Stack>
                  </Stack>
                </CustomStackFullWidth>

                <Button
                  variant="contained"
                  color="primary"
                  fullWidth
                  onClick={() => {
                    setAgencyPaymentModalOpen(false);
                    setReadyToNavigate(true); // 用户确认后，设置准备跳转状态
                  }}
                >
                  {t("Ok")}
                </Button>
              </CustomStackFullWidth>
            </CustomModal>
          )}
        </Grid>
      )}
    </>
  );
};

ItemCheckout.propTypes = {};

export default ItemCheckout;